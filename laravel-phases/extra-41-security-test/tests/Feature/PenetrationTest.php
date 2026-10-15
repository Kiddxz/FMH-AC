<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CareInstruction;
use App\Models\Pet;
use App\Models\Role;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Waiver;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Security test: we try to break in like an attacker would, and every attempt must fail.
 */
class PenetrationTest extends TestCase
{
    use RefreshDatabase;

    private User $mark;    // customer
    private User $staff;
    private User $vet;
    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 09:30:00');
        $this->seed(DatabaseSeeder::class);
        Mail::fake();
        $this->mark = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
        $this->super = User::where('email', 'superadmin@fmhanimalclinic.com')->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // 1. A customer opens ANOTHER customer's records by changing the number in the address (IDOR)
    public function test_a_customer_cannot_open_other_peoples_records(): void
    {
        $markCustomer = $this->mark->customer->id;
        $otherPet = Pet::where('customer_id', '!=', $markCustomer)->first();
        $otherAppointment = Appointment::where('customer_id', '!=', $markCustomer)->first();
        $this->assertNotNull($otherPet);
        $this->assertNotNull($otherAppointment);

        $this->actingAs($this->mark);
        $this->assertContains($this->get("/portal/pets/{$otherPet->id}")->status(), [403, 404]);
        $this->assertContains($this->get("/portal/pets/{$otherPet->id}/edit")->status(), [403, 404]);
        $this->assertContains($this->put("/portal/pets/{$otherPet->id}", ['petname' => 'Hacked', 'species' => 'dog', 'breed' => 'x', 'sex' => 'male', 'age' => 1])->status(), [403, 404]);
        $this->assertNotSame('Hacked', $otherPet->fresh()->name);

        $this->assertContains($this->get("/portal/appointments/{$otherAppointment->id}")->status(), [403, 404]);
        $before = $otherAppointment->status;
        $this->assertContains($this->patch("/portal/appointments/{$otherAppointment->id}/cancel", ['cancel_reason' => 'x'])->status(), [403, 404]);
        $this->assertSame($before, $otherAppointment->fresh()->status);   // not cancelled by a stranger

        $otherWaiver = Waiver::whereHas('pet', fn ($q) => $q->where('customer_id', '!=', $markCustomer))->first();
        if ($otherWaiver) {
            $this->assertContains($this->get("/portal/waivers/{$otherWaiver->id}")->status(), [403, 404]);
            $this->assertContains($this->post("/portal/waivers/{$otherWaiver->id}/sign", ['signer_name' => 'Mark Santos', 'agree' => '1'])->status(), [403, 404]);
        }
        $otherCare = CareInstruction::whereHas('pet', fn ($q) => $q->where('customer_id', '!=', $markCustomer))->first();
        if ($otherCare) {
            $this->assertContains($this->get("/portal/care-instructions/{$otherCare->id}/download")->status(), [403, 404]);
        }
    }

    // 2. Opening pages of a higher role (privilege escalation)
    public function test_no_one_can_open_the_pages_of_another_role(): void
    {
        foreach (['/staff', '/staff/pos', '/admin', '/admin/pet-records', '/superadmin', '/superadmin/users', '/superadmin/backups'] as $url) {
            $this->actingAs($this->mark)->get($url)->assertForbidden();
        }
        foreach (['/admin/pet-records', '/superadmin/users', '/superadmin/backups', '/superadmin/settings'] as $url) {
            $this->actingAs($this->staff)->get($url)->assertForbidden();
        }
        foreach (['/staff/pos', '/superadmin/users', '/superadmin/roles'] as $url) {
            $this->actingAs($this->vet)->get($url)->assertForbidden();
        }
        // a staff member cannot make themself Super Admin or download the database
        $this->actingAs($this->staff)->post('/superadmin/users', [
            'firstname' => 'Evil', 'lastname' => 'Admin', 'email' => 'evil@example.com', 'role_id' => Role::where('slug', Role::SUPER_ADMIN)->value('id'),
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertForbidden();
        $this->assertNull(User::where('email', 'evil@example.com')->first());
        $this->actingAs($this->staff)->post('/superadmin/backups')->assertForbidden();
    }

    // 3. Registering with a hidden "role_id" field to become Super Admin (mass assignment)
    public function test_register_cannot_choose_its_own_role(): void
    {
        $this->post('/register', [
            'firstname' => 'Juan', 'lastname' => 'Dela Cruz', 'email' => 'juan@example.com', 'mobile' => '09171112233',
            'password' => 'Password123', 'password_confirmation' => 'Password123', 'tnc' => '1',
            'role_id' => Role::where('slug', Role::SUPER_ADMIN)->value('id'), 'status' => 'active', 'email_verified_at' => now(),
        ]);
        $juan = User::where('email', 'juan@example.com')->first();
        if ($juan) {
            $this->assertSame(Role::CUSTOMER, $juan->role->slug);
            $this->assertNull($juan->email_verified_at);   // still has to verify the email
        }
        $this->assertTrue(true);
    }

    // 4. Script in a text box (stored XSS): it is shown as plain text, never run
    public function test_scripts_typed_in_text_boxes_are_not_run(): void
    {
        $evil = '<script>alert("hacked")</script><img src=x onerror=alert(1)>';
        $this->actingAs($this->mark)->post('/portal/appointments', [
            'pet_id' => $this->mark->customer->pets()->value('id'),
            'service_ids' => [Service::where('name', 'Consultation')->value('id')],
            'appointment_date' => '2026-10-06', 'appointment_time' => '09:00', 'reason' => $evil,
        ]);
        $appointment = Appointment::where('reason', $evil)->first();
        $this->assertNotNull($appointment);

        foreach ([[$this->mark, "/portal/appointments/{$appointment->id}"], [$this->staff, "/staff/appointments/{$appointment->id}"], [$this->vet, "/admin/appointments/{$appointment->id}"]] as [$user, $url]) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('<script>alert("hacked")</script>', $html, $url);
            $this->assertStringNotContainsString('<img src=x onerror', $html, $url);
            $this->assertStringContainsString('&lt;script&gt;', $html, $url);
        }
    }

    // 5. SQL injection in the search boxes
    public function test_sql_injection_in_search_does_nothing(): void
    {
        $total = \App\Models\Customer::count();
        foreach (["' OR '1'='1", "'; DROP TABLE users; --", '" OR 1=1 --', "%' UNION SELECT password FROM users --"] as $attack) {
            $this->actingAs($this->staff)->get('/staff/customers?search=' . urlencode($attack))->assertOk()->assertDontSee('$2y$', false);
            $this->actingAs($this->staff)->get('/staff/pets?search=' . urlencode($attack))->assertOk();
        }
        $this->assertSame($total, \App\Models\Customer::count());
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('users'));
    }

    // 6. Guessing passwords (brute force): blocked after a few tries
    public function test_guessing_passwords_is_blocked(): void
    {
        $statuses = [];
        for ($i = 0; $i < 15; $i++) {
            $statuses[] = $this->post('/login', ['email' => 'owner@fmhanimalclinic.com', 'password' => 'wrong' . $i])->status();
        }
        $this->assertContains(429, $statuses, 'Too many tries should be refused (429).');
        // even the right password is refused while blocked
        $this->post('/login', ['email' => 'owner@fmhanimalclinic.com', 'password' => 'owner123'])->assertStatus(429);
        $this->assertGuest();
    }

    // 7. Guessing booking references / codes / pay links
    public function test_guessing_references_codes_and_pay_links_fails(): void
    {
        $statuses = [];
        for ($i = 1; $i <= 15; $i++) {
            $statuses[] = $this->post('/book/check', ['reference' => 'APP-' . str_pad((string) $i, 6, '0', STR_PAD_LEFT), 'contact_number' => '09170000000'])->status();
        }
        $this->assertContains(429, $statuses, 'Guessing booking references must be limited.');

        $this->get('/pay/' . str_repeat('a', 40))->assertNotFound();
        $this->get('/pay/1')->assertNotFound();
        $bill = Transaction::first();
        if ($bill) {
            $this->get('/pay/' . $bill->id)->assertNotFound();   // the bill number is not the pay link
        }
    }

    // 8. Passwords are never saved as plain text
    public function test_passwords_are_hashed(): void
    {
        $raw = \Illuminate\Support\Facades\DB::table('users')->where('email', 'owner@fmhanimalclinic.com')->value('password');
        $this->assertNotSame('owner123', $raw);
        $this->assertTrue(Hash::check('owner123', $raw));
        $this->assertStringStartsWith('$2y$', $raw);
    }

    // 9. A deactivated account cannot log in
    public function test_a_deactivated_account_cannot_log_in(): void
    {
        $this->mark->forceFill(['status' => 'inactive'])->save();
        $this->post('/login', ['email' => 'owner@fmhanimalclinic.com', 'password' => 'owner123']);
        $this->assertGuest();
    }

    // 10. Logging in gives a NEW session id (stops session fixation)
    public function test_login_changes_the_session_id(): void
    {
        $this->get('/login');
        $before = session()->getId();
        $this->post('/login', ['email' => 'owner@fmhanimalclinic.com', 'password' => 'owner123'])->assertRedirect();
        $this->assertNotSame($before, session()->getId());
    }

    // 11. Uploading a fake "backup" that is really a PHP file
    public function test_a_php_file_cannot_be_uploaded_as_a_backup(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]); ?>');
        $this->actingAs($this->super)->post('/superadmin/backups/upload', ['backup_file' => $file])->assertSessionHasErrors('backup_file');
    }

    // 12. Other people's notifications cannot be marked as read
    public function test_customers_have_no_notification_bell(): void
    {
        $this->actingAs($this->mark)->get('/notifications')->assertForbidden();
        $this->actingAs($this->mark)->postJson('/notifications/read-all')->assertForbidden();
    }
}
