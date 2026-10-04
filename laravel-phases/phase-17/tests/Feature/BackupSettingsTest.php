<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\ClinicSchedule;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DatabaseBackup;
use App\Services\InventoryAlerts;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Phase 17: backup & recovery (SCOPE-13, NFR-REQ020) and system settings / maintenance mode (SCOPE-14).
 * The demo data includes one backup ("initial"). Files go to a fake disk (see tests/TestCase.php).
 */
class BackupSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $super;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->super = User::where('email', 'superadmin@fmhanimalclinic.com')->first();
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
    }

    // Like assertSee, but spaces and line breaks do not matter (editors may re-wrap long lines)
    private function assertSeeWords(TestResponse $response, string ...$texts): TestResponse
    {
        $html = preg_replace('/\s+/', ' ', $response->getContent());
        foreach ($texts as $text) {
            $this->assertStringContainsString($text, $html, "The page does not show: {$text}");
        }

        return $response;
    }

    // The settings form as it is now, with some values changed
    private function settingsForm(array $changes = []): array
    {
        $form = collect(Setting::DEFAULTS)->map(fn ($v, $key) => Setting::get($key))->all();
        $form['hours'] = ClinicSchedule::orderBy('day_of_week')->get()->map(fn ($s) => [
            'is_open' => $s->is_open ? '1' : '0',
            'opens_at' => $s->opens_at ? substr($s->opens_at, 0, 5) : '08:00',
            'closes_at' => $s->closes_at ? substr($s->closes_at, 0, 5) : '17:00',
            'slot_minutes' => $s->slot_minutes,
            'max_per_slot' => $s->max_per_slot,
        ])->all();

        return array_replace_recursive($form, $changes);
    }

    public function test_create_and_download_a_backup(): void
    {
        $page = $this->actingAs($this->super)->get('/superadmin/backups')->assertOk();
        $this->assertSeeWords($page, 'Database Backup', 'Create Backup', 'Upload Backup File', '_initial.json');

        $this->actingAs($this->super)->post('/superadmin/backups')->assertSessionHasNoErrors();
        $backup = Backup::latest('id')->first();
        $this->assertSame($this->super->id, $backup->created_by);
        Storage::disk('local')->assertExists('backups/' . $backup->filename);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Backups', 'action' => 'created']);

        $file = json_decode(Storage::disk('local')->get('backups/' . $backup->filename), true);
        $this->assertSame(DatabaseBackup::FORMAT, $file['format']);
        $this->assertCount(7, $file['tables']['pets']);
        $this->assertCount(2, $file['tables']['transactions']);
        $this->assertArrayNotHasKey('activity_logs', $file['tables']);   // the log is never replaced

        $download = $this->actingAs($this->super)->get("/superadmin/backups/{$backup->id}/download")->assertOk();
        $this->assertStringContainsString($backup->filename, $download->headers->get('Content-Disposition'));
    }

    public function test_restore_brings_the_data_back_and_keeps_the_log(): void
    {
        $this->actingAs($this->super)->post('/superadmin/backups');
        $backup = Backup::latest('id')->first();
        $logsBefore = ActivityLog::count();

        // things change after the backup
        Pet::where('name', 'Rocky')->update(['name' => 'Rocky Changed']);
        Customer::create(['first_name' => 'New', 'last_name' => 'Person', 'contact_number' => '09170000077']);
        Setting::set('clinic_name', 'Changed Clinic');

        // confirmation is required
        $this->actingAs($this->super)->post("/superadmin/backups/{$backup->id}/restore", ['confirm_text' => 'restore', 'password' => 'wrong'])
            ->assertSessionHasErrors(['confirm_text', 'password']);
        $this->assertTrue(Pet::where('name', 'Rocky Changed')->exists());

        $this->actingAs($this->super)->post("/superadmin/backups/{$backup->id}/restore", ['confirm_text' => 'RESTORE', 'password' => 'superadmin123'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Pet::where('name', 'Rocky')->exists());
        $this->assertFalse(Customer::where('first_name', 'New')->exists());
        $this->assertSame('FMH Animal Clinic', Setting::query()->where('key', 'clinic_name')->value('value'));
        $this->assertSame(2, Transaction::count());

        // a safety backup of the data before the restore, and the log keeps every old entry
        $this->assertTrue(Backup::where('filename', 'like', '%before-restore%')->exists());
        $this->assertGreaterThan($logsBefore, ActivityLog::count());
        $this->assertDatabaseHas('activity_logs', ['module' => 'Backups', 'action' => 'restored']);

        // the Super Admin is still logged in and can work
        $this->actingAs($this->super->fresh())->get('/superadmin')->assertOk();
    }

    public function test_upload_checks_the_file(): void
    {
        $good = Storage::disk('local')->get('backups/' . Backup::first()->filename);

        $this->actingAs($this->super)->post('/superadmin/backups/upload', ['backup_file' => UploadedFile::fake()->createWithContent('copy-from-usb.json', $good)])
            ->assertSessionHasNoErrors();
        $uploaded = Backup::latest('id')->first();
        $this->assertStringStartsWith('uploaded_', $uploaded->filename);
        Storage::disk('local')->assertExists('backups/' . $uploaded->filename);

        $this->actingAs($this->super)->post('/superadmin/backups/upload', ['backup_file' => UploadedFile::fake()->createWithContent('notes.json', '{"hello": "world"}')])
            ->assertSessionHasErrors('backup_file');
        $this->actingAs($this->super)->post('/superadmin/backups/upload', ['backup_file' => UploadedFile::fake()->createWithContent('photo.png', 'x')])
            ->assertSessionHasErrors('backup_file');
        $this->assertSame(2, Backup::count());
    }

    public function test_delete_a_backup(): void
    {
        $backup = Backup::first();
        $this->actingAs($this->super)->delete("/superadmin/backups/{$backup->id}")->assertSessionHasNoErrors();
        $this->assertNull($backup->fresh());
        Storage::disk('local')->assertMissing('backups/' . $backup->filename);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Backups', 'action' => 'deleted']);
    }

    public function test_clinic_information_is_printed_on_receipts(): void
    {
        $this->assertSeeWords($this->actingAs($this->super)->get('/superadmin/settings')->assertOk(), 'Clinic Information', 'Clinic Hours', 'Maintenance');

        $this->actingAs($this->super)->put('/superadmin/settings', $this->settingsForm([
            'clinic_name' => 'FMH Animal Clinic Pamplona', 'clinic_contact' => '(02) 8800-1234',
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activity_logs', ['module' => 'Settings', 'action' => 'updated']);

        $receipt = $this->actingAs($this->staff)->get('/staff/transactions/' . Transaction::first()->id . '/receipt');
        $this->assertSeeWords($receipt, 'FMH Animal Clinic Pamplona', '(02) 8800-1234', 'Thank you for trusting FMH Animal Clinic Pamplona!');
    }

    public function test_expiry_alert_days_change_the_alerts(): void
    {
        // the demo 5-in-1 vaccine batch expires in 20 days: "expiring" with 30 days, not with 10
        $this->assertSame(30, InventoryAlerts::expiringDays());
        $this->assertCount(1, InventoryAlerts::summary()['expiring']);

        $this->actingAs($this->super)->put('/superadmin/settings', $this->settingsForm(['expiry_alert_days' => '10']))->assertSessionHasNoErrors();

        $page = $this->actingAs($this->staff)->get('/staff/inventory?status=expiring');
        $page->assertViewHas('items', fn ($items) => $items->isEmpty());
        $this->assertSeeWords($this->actingAs($this->staff)->get('/staff'), 'stock expiring within 10 days');
    }

    public function test_clinic_hours_decide_the_time_slots(): void
    {
        $saturday = today()->next('Saturday')->toDateString();
        $this->actingAs($this->staff)->getJson('/appointments/slots?date=' . $saturday)->assertJson(['open' => true]);

        $this->actingAs($this->super)->put('/superadmin/settings', $this->settingsForm(['hours' => [6 => ['is_open' => '0']]]))->assertSessionHasNoErrors();
        $this->assertFalse(ClinicSchedule::where('day_of_week', 6)->value('is_open'));
        $this->actingAs($this->staff)->getJson('/appointments/slots?date=' . $saturday)->assertJson(['open' => false]);

        // wrong hours are refused
        $this->actingAs($this->super)->put('/superadmin/settings', $this->settingsForm(['hours' => [1 => ['opens_at' => '17:00', 'closes_at' => '08:00']]]))
            ->assertSessionHasErrors('hours.1.closes_at');
        $closedAllWeek = $this->settingsForm();
        foreach ($closedAllWeek['hours'] as $day => $row) {
            $closedAllWeek['hours'][$day]['is_open'] = '0';
        }
        $this->actingAs($this->super)->put('/superadmin/settings', $closedAllWeek)->assertSessionHasErrors('hours');
        $this->actingAs($this->super)->put('/superadmin/settings', $this->settingsForm(['expiry_alert_days' => '0', 'clinic_name' => '']))
            ->assertSessionHasErrors(['expiry_alert_days', 'clinic_name']);
    }

    public function test_maintenance_mode(): void
    {
        $owner = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $this->actingAs($this->super)->put('/superadmin/settings', $this->settingsForm(['system_status' => 'maintenance', 'maintenance_message' => 'Back at 3 PM.']))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('activity_logs', ['action' => 'maintenance_on']);

        $this->actingAs($this->staff)->get('/staff')->assertStatus(503)->assertSee('Under Maintenance')->assertSee('Back at 3 PM.');
        $this->actingAs($owner)->get('/portal/pets')->assertStatus(503);
        $this->actingAs($this->super)->get('/superadmin')->assertOk();                 // the Super Admin can still work
        $this->actingAs($this->super)->get('/superadmin/settings')->assertOk()->assertSee('Maintenance mode is ON');

        $this->actingAs($this->super)->put('/superadmin/settings', $this->settingsForm(['system_status' => 'active']));
        $this->actingAs($this->staff)->get('/staff')->assertOk();
    }

    public function test_login_page_works_during_maintenance(): void
    {
        Setting::set('system_status', 'maintenance');
        $this->get('/login')->assertOk();
        $this->get('/register')->assertStatus(503);
        $this->post('/login', ['email' => 'superadmin@fmhanimalclinic.com', 'password' => 'superadmin123'])->assertRedirect('/superadmin');
    }

    public function test_only_the_super_admin_manages_backups_and_settings(): void
    {
        $vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
        $backup = Backup::first();
        $this->actingAs($this->staff)->get('/superadmin/backups')->assertForbidden();
        $this->actingAs($vet)->get("/superadmin/backups/{$backup->id}/download")->assertForbidden();
        $this->actingAs($vet)->post("/superadmin/backups/{$backup->id}/restore", ['confirm_text' => 'RESTORE', 'password' => 'admin123'])->assertForbidden();
        $this->actingAs($this->staff)->put('/superadmin/settings', $this->settingsForm())->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/superadmin/backups')->assertRedirect('/login');
        $this->get('/superadmin/settings')->assertRedirect('/login');
    }
}
