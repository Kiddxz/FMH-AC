<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Permission;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use LogicException;
use Tests\TestCase;

/**
 * Phase 16: activity logs (NFR-REQ023, NFR-REQ024, SCOPE-12).
 * Logins (also failed ones), appointments, transactions and inventory updates are logged automatically,
 * the log cannot be changed, and only the Super Admin can read it.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->super = User::where('email', 'superadmin@fmhanimalclinic.com')->first();
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

    public function test_logins_and_failed_logins_are_logged(): void
    {
        // wrong password for a real account: linked to that account, the password is never written
        $this->post('/login', ['email' => 'assistant@fmhanimalclinic.com', 'password' => 'secret-guess-1'])->assertSessionHasErrors('email');
        $failed = ActivityLog::where('action', 'login_failed')->latest('id')->first();
        $this->assertSame($this->staff->id, $failed->user_id);
        $this->assertStringContainsString('wrong password', $failed->description);
        $this->assertStringNotContainsString('secret-guess-1', $failed->description);
        $this->assertNotNull($failed->ip_address);

        // unknown email: logged without a user
        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'whatever1']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_failed', 'user_id' => null, 'description' => 'Failed login for nobody@example.com (no account with this email).']);

        // correct password: a normal login entry
        $this->post('/login', ['email' => 'assistant@fmhanimalclinic.com', 'password' => 'assistant123'])->assertRedirect('/staff');
        $this->assertDatabaseHas('activity_logs', ['action' => 'login', 'user_id' => $this->staff->id, 'module' => 'Authentication']);
    }

    public function test_inactive_accounts_and_too_many_tries_are_logged(): void
    {
        $this->staff->forceFill(['status' => 'inactive'])->save();
        $this->post('/login', ['email' => 'assistant@fmhanimalclinic.com', 'password' => 'assistant123'])->assertSessionHasErrors('email');
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_blocked', 'user_id' => $this->staff->id]);

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => 'owner@fmhanimalclinic.com', 'password' => 'wrong-pass' . $i]);
        }
        $this->assertSame(5, ActivityLog::where('action', 'login_failed')->where('description', 'like', '%owner@%')->count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_throttled']);
    }

    public function test_clinic_work_is_logged_with_the_user_and_ip(): void
    {
        $appointment = Appointment::where('status', 'pending')->first();
        $this->actingAs($this->staff)->patch("/staff/appointments/{$appointment->id}/confirm");
        $this->actingAs($this->staff)->post('/staff/inventory/' . InventoryItem::where('sku', 'SUP-COT')->value('id') . '/stock-in', ['quantity' => 10, 'received_date' => today()->toDateString()]);
        $this->actingAs($this->staff)->post('/staff/pos', [
            'items' => [['item' => 'service:' . Service::where('name', 'Grooming')->value('id'), 'quantity' => 1]],
            'amount' => '0',
        ]);

        foreach (['Appointments' => 'confirmed', 'Inventory' => 'stock_in', 'POS' => 'created'] as $module => $action) {
            $log = ActivityLog::where('module', $module)->where('action', $action)->latest('id')->first();
            $this->assertNotNull($log, "No {$module} / {$action} log entry.");
            $this->assertSame($this->staff->id, $log->user_id);
            $this->assertSame('127.0.0.1', $log->ip_address);
        }
    }

    public function test_log_entries_cannot_be_changed_or_deleted(): void
    {
        $log = ActivityLog::record('login', 'Authentication', 'Test entry.', null, $this->staff->id);

        try {
            $log->update(['description' => 'Changed!']);
            $this->fail('An activity log entry was changed.');
        } catch (LogicException) {
        }
        try {
            $log->delete();
            $this->fail('An activity log entry was deleted.');
        } catch (LogicException) {
        }
        $this->assertDatabaseHas('activity_logs', ['id' => $log->id, 'description' => 'Test entry.']);
    }

    public function test_viewer_filters(): void
    {
        $this->post('/login', ['email' => 'assistant@fmhanimalclinic.com', 'password' => 'bad-password1']);
        ActivityLog::record('created', 'Pets', 'A pet entry made by staff.', null, $this->staff->id);

        $page = $this->actingAs($this->super)->get('/superadmin/activity-logs')->assertOk();
        $this->assertSeeWords($page, 'System Activity Logs', 'Failed Logins (24 hours)', 'Failed login for assistant@fmhanimalclinic.com', 'Export CSV');
        $page->assertViewHas('stats', fn ($s) => $s['failed'] === 1);

        $this->actingAs($this->super)->get('/superadmin/activity-logs?action=login_failed')
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->action === 'login_failed');
        $this->actingAs($this->super)->get('/superadmin/activity-logs?module=Pets&user=' . $this->staff->id)
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
        $this->actingAs($this->super)->get('/superadmin/activity-logs?search=pet+entry')
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
        $this->actingAs($this->super)->get('/superadmin/activity-logs?from=' . today()->addDay()->toDateString())
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 0);
        $this->actingAs($this->super)->from('/superadmin/activity-logs')
            ->get('/superadmin/activity-logs?from=' . today()->toDateString() . '&to=' . today()->subDay()->toDateString())
            ->assertSessionHasErrors('to');
    }

    public function test_csv_export_is_logged_too(): void
    {
        ActivityLog::record('created', 'Pets', 'A pet entry made by staff.', null, $this->staff->id);

        $csv = $this->actingAs($this->super)->get('/superadmin/activity-logs?module=Pets&format=csv')->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('Content-Type'));
        $content = $csv->streamedContent();
        $this->assertStringContainsString('Date,Time,User,Role,Module,Action,Activity,"IP Address"', $content);
        $this->assertStringContainsString('A pet entry made by staff.', $content);
        $this->assertStringContainsString('Staff/Receptionist', $content);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Activity Logs', 'action' => 'exported', 'user_id' => $this->super->id]);
    }

    public function test_only_the_super_admin_reads_the_log(): void
    {
        $this->actingAs($this->staff)->get('/superadmin/activity-logs')->assertForbidden();
        $this->actingAs(User::where('email', 'admin@fmhanimalclinic.com')->first())->get('/superadmin/activity-logs?format=csv')->assertForbidden();

        $this->super->role->permissions()->detach(Permission::where('slug', 'activity_logs.view')->value('id'));
        $this->actingAs($this->super->fresh())->get('/superadmin/activity-logs')->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/superadmin/activity-logs')->assertRedirect('/login');
    }
}
