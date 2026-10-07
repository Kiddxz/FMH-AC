<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * A booking for one pet. It can have one or more services (e.g. Consultation + Vaccination):
 *  - services()   every chosen service (table appointment_service)
 *  - service()    the first (main) one, kept in appointments.service_id for the patient flow and filters
 * Pages show $appointment->service_names ("Consultation, Vaccination") and $appointment->total_price.
 */
class Appointment extends Model
{
    // The most services one booking may have
    public const MAX_SERVICES = 5;

    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

    protected $fillable = [
        'customer_id',
        'pet_id',
        'service_id',
        'veterinarian_id',
        'appointment_date',
        'appointment_time',
        'reason',
        'notes',
    ];

    // New appointments start as Pending
    protected $attributes = [
        'status' => 'pending',
    ];

    // The services are always loaded with the appointment (one query for a whole list)
    protected $with = ['services'];

    protected static function booted(): void
    {
        // A new appointment always has its main service in appointment_service too
        static::created(function (Appointment $appointment) {
            if ($appointment->service_id) {
                $appointment->services()->syncWithoutDetaching([$appointment->service_id]);
                $appointment->unsetRelation('services');
            }
        });
    }

    protected function casts(): array
    {
        return ['appointment_date' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    // Every service of the appointment, in the order they were chosen
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withTimestamps()->orderBy('appointment_service.id');
    }

    // The chosen services (older records without rows in appointment_service show their main service)
    public function serviceList(): Collection
    {
        $list = $this->relationLoaded('services') ? $this->services : $this->services()->get();

        return $list->isEmpty() && $this->service ? collect([$this->service]) : $list;
    }

    // "Consultation, Vaccination"
    protected function serviceNames(): Attribute
    {
        return Attribute::get(fn () => $this->serviceList()->pluck('name')->join(', '));
    }

    // The total price of all the chosen services (paid at the clinic)
    protected function totalPrice(): Attribute
    {
        return Attribute::get(fn () => (float) $this->serviceList()->sum('price'));
    }

    // Saves the chosen services. The first one becomes the main service (service_id).
    public function saveServices(array $serviceIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $serviceIds)));
        if ($ids === []) {
            return;
        }
        if ((int) $this->service_id !== $ids[0]) {
            $this->service_id = $ids[0];
            $this->save();
        }
        $this->services()->sync($ids);
        $this->unsetRelation('services');
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinarian_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // The patient-flow visit created when the pet checks in at the clinic
    public function visit(): HasOne
    {
        return $this->hasOne(PatientVisit::class);
    }
}
