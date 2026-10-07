<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use App\Services\DatabaseBackup;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * One appointment, more than one service (e.g. Consultation + Vaccination).
 */
class MultiServiceAppointmentTest extends TestCase
{
    use RefreshDatabase;

    private User $mark;
    private User $staff;
    private Pet $max;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 07:00:00');   // a Monday, before opening time
        $this->seed(DatabaseSeeder::class);
        $this->mark = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->max = Pet::where('name', 'Max')->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function service(string $name): Service
    {
        return Service::where('name', $name)->first();
    }

    private function bookTwo(): Appointment
    {
        $this->actingAs($this->mark)->post('/portal/appointments', [
            'pet_id' => $this->max->id,
            'service_ids' => [$this->service('Consultation')->id, $this->service('Vaccination')->id],
            'appointment_date' => '2026-10-06', 'appointment_time' => '09:00', 'reason' => 'Check-up and shots',
        ])->assertSessionHasNoErrors();

        return Appointment::latest('id')->first();
    }

    public function test_customer_books_two_services_in_one_appointment(): void
    {
        $this->actingAs($this->mark)->get('/portal/appointments/create')->assertOk()
            ->assertSee('name="service_ids[]"', false)->assertSee('Estimated total');

        $appointment = $this->bookTwo();

        $this->assertSame(['Consultation', 'Vaccination'], $appointment->services->pluck('name')->all());
        $this->assertSame($this->service('Consultation')->id, $appointment->service_id);   // the first one is the main service
        $this->assertSame('Consultation, Vaccination', $appointment->service_names);
        $this->assertSame(1300.0, $appointment->total_price);                                // ₱500 + ₱800

        $this->get('/portal/appointments/' . $appointment->id)->assertOk()->assertSee('Consultation, Vaccination')->assertSee('₱1,300.00');
        $this->get('/portal/appointments')->assertSee('Consultation, Vaccination');
    }

    public function test_service_choices_are_checked(): void
    {
        $base = ['pet_id' => $this->max->id, 'appointment_date' => '2026-10-06', 'appointment_time' => '09:00'];
        $this->actingAs($this->mark);

        $this->post('/portal/appointments', $base)->assertSessionHasErrors(['service_ids' => 'Please choose at least one service.']);
        $this->post('/portal/appointments', $base + ['service_ids' => [$this->service('Nail Trimming')->id]])   // inactive
            ->assertSessionHasErrors('service_ids.0');
        $consult = $this->service('Consultation')->id;
        $this->post('/portal/appointments', $base + ['service_ids' => [$consult, $consult]])->assertSessionHasErrors('service_ids.0');
        $this->post('/portal/appointments', $base + ['service_ids' => Service::pluck('id')->all()])   // 6 services
            ->assertSessionHasErrors(['service_ids' => 'You can choose up to 5 services in one booking.']);
        $this->assertSame(0, Appointment::where('reason', null)->whereDate('appointment_date', '2026-10-06')->count());
    }

    public function test_the_clinic_sees_and_edits_all_services(): void
    {
        $appointment = $this->bookTwo();
        $this->actingAs($this->staff);

        $this->get('/staff/appointments/' . $appointment->id)->assertSee('Consultation, Vaccination')->assertSee('₱1,300.00');
        // the list filter finds it by its SECOND service too
        $this->get('/staff/appointments?service=' . $this->service('Vaccination')->id)->assertSee($appointment->reference);
        $this->get('/staff/appointments/' . $appointment->id . '/edit')->assertOk()->assertSee('name="service_ids[]"', false);

        // staff change it to Deworming + Grooming
        $this->put('/staff/appointments/' . $appointment->id, [
            'service_ids' => [$this->service('Deworming')->id, $this->service('Grooming')->id],
            'appointment_date' => '2026-10-06', 'appointment_time' => '09:00',
        ])->assertSessionHasNoErrors();
        $appointment->refresh();
        $this->assertSame('Deworming, Grooming', $appointment->service_names);
        $this->assertSame($this->service('Deworming')->id, $appointment->service_id);
        $this->assertSame(2, DB::table('appointment_service')->where('appointment_id', $appointment->id)->count());

        // the POS bill starts with every service of the appointment
        $this->get('/staff/pos?appointment=' . $appointment->id)->assertOk()
            ->assertViewHas('firstLines', [
                ['item' => 'service:' . $this->service('Deworming')->id, 'quantity' => 1],
                ['item' => 'service:' . $this->service('Grooming')->id, 'quantity' => 1],
            ]);

        // the appointment report counts the appointment under each of its services
        $report = $this->get('/staff/reports?type=appointments&from=2026-10-01&to=2026-10-31')->assertOk();
        $report->assertSee('Deworming')->assertSee('Grooming');
    }

    public function test_one_service_still_works_and_old_records_show_their_service(): void
    {
        // older forms send one "service_id"
        $this->actingAs($this->mark)->post('/portal/appointments', [
            'pet_id' => $this->max->id, 'service_id' => $this->service('Consultation')->id,
            'appointment_date' => '2026-10-06', 'appointment_time' => '10:00',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Consultation', Appointment::latest('id')->first()->service_names);

        // a record with no rows in appointment_service shows its main service
        $old = Appointment::latest('id')->first();
        DB::table('appointment_service')->where('appointment_id', $old->id)->delete();
        $this->assertSame('Consultation', $old->fresh()->service_names);
    }

    public function test_backups_keep_the_services_and_old_backup_files_still_restore(): void
    {
        $appointment = $this->bookTwo();
        $super = User::where('email', 'superadmin@fmhanimalclinic.com')->first();
        $backups = app(DatabaseBackup::class);

        $backup = $backups->create($super->id);
        $file = 'backups/' . $backup->filename;
        $this->assertArrayHasKey('appointment_service', json_decode(Storage::disk('local')->get($file), true)['tables']);

        DB::table('appointment_service')->delete();
        $backups->restore($backup, $super->id);
        $this->assertSame('Consultation, Vaccination', $appointment->fresh()->service_names);

        // a backup made before this change has no appointment_service table: it still restores,
        // and each appointment shows its main service
        $data = json_decode(Storage::disk('local')->get($file), true);
        unset($data['tables']['appointment_service']);
        Storage::disk('local')->put($file, json_encode($data));
        $backups->restore($backup, $super->id);
        $this->assertSame('Consultation', $appointment->fresh()->service_names);
    }
}
