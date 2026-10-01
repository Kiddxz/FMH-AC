<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
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
