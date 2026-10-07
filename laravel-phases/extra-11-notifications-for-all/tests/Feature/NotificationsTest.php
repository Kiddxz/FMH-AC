<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\User;
use App\Models\Waiver;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The notification bell: each role sees the things it should act on.
 */
class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->first();
    }

    public function test_staff_see_new_bookings_bills_and_inventory(): void
    {
        $staff = $this->user('assistant@fmhanimalclinic.com');

        // the bell shows the number; the list is loaded when it is clicked
        $page = $this->actingAs($staff)->get('/staff')->assertOk();
        $page->assertSee('id="notif-button"', false)->assertSee('/notifications');

        $list = $this->get('/notifications')->assertOk();
        $list->assertSee('New booking to confirm: Coco')     // demo booking that is still Pending
            ->assertSee('1 bill still has a balance')        // demo counter sale with a ₱390 balance
            ->assertSee('Cotton Balls is out of stock');     // inventory alert
    }

    public function test_a_notification_goes_away_once_it_is_done(): void
    {
        $staff = $this->user('assistant@fmhanimalclinic.com');
        $coco = \App\Models\Appointment::whereHas('pet', fn ($q) => $q->where('name', 'Coco'))->where('status', 'pending')->first();

        $this->actingAs($staff)->get('/notifications')->assertSee('New booking to confirm: Coco');
        $this->patch('/staff/appointments/' . $coco->id . '/confirm');
        $this->get('/notifications')->assertDontSee('New booking to confirm: Coco');
    }

    public function test_the_vet_sees_waivers_to_review_and_todays_appointments(): void
    {
        $waiver = Waiver::where('status', 'pending')->first();
        $waiver->forceFill(['status' => 'signed', 'signer_name' => 'Mark Santos', 'signed_at' => now(), 'signed_via' => 'clinic'])->save();

        $this->actingAs($this->user('admin@fmhanimalclinic.com'))->get('/notifications')->assertOk()
            ->assertSee('Waiver to review: ' . $waiver->reference)
            ->assertSee('Cotton Balls is out of stock');
    }

    public function test_the_super_admin_sees_failed_logins_backups_and_new_customers(): void
    {
        ActivityLog::record('login_failed', 'Authentication', 'Failed login for someone@example.com (wrong password).');
        Backup::query()->delete();   // start with no backup at all

        $super = $this->user('superadmin@fmhanimalclinic.com');
        $this->actingAs($super)->get('/superadmin')->assertOk()->assertSee('id="notif-button"', false);
        $this->get('/notifications')->assertOk()
            ->assertSee('1 failed login in the last 24 hours')
            ->assertSee('No backup yet')
            ->assertSee('inventory alerts');

        // after a backup, that notification is gone
        $this->post('/superadmin/backups');
        $this->get('/notifications')->assertDontSee('No backup yet');
    }

    public function test_customers_and_guests_have_no_bell(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
        $this->actingAs($this->user('owner@fmhanimalclinic.com'))->get('/notifications')->assertForbidden();
        $this->get('/portal')->assertDontSee('id="notif-button"', false);
    }
}
