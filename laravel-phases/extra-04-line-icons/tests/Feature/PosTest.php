<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\PatientVisit;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PatientFlow;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Phase 14: POS and transactions.
 * Demo data: OR-000001 (Max's vaccination, ₱800, paid in cash 30 days ago)
 *            OR-000002 (Anna Reyes, 2 Dog Food + 1 Pet Shampoo = ₱890, ₱500 GCash today, balance ₱390)
 * Dog Food stock after the demo sale: 38.
 */
class PosTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $vet;
    private User $owner;
    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
        $this->owner = User::where('email', 'owner@fmhanimalclinic.com')->first();
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

    private function product(string $sku): InventoryItem
    {
        return InventoryItem::where('sku', $sku)->first();
    }

    private function service(string $name): Service
    {
        return Service::where('name', $name)->first();
    }

    // A completed walk-in visit for Buddy on today's board
    private function completedVisit(): PatientVisit
    {
        $buddy = Pet::where('name', 'Buddy')->first();
        $flow = app(PatientFlow::class);
        $visit = $flow->checkIn(['customer_id' => $buddy->customer_id, 'pet_id' => $buddy->id, 'service_id' => $this->service('Consultation')->id, 'visit_type' => 'walk_in'], $this->staff->id);
        $flow->changeStatus($visit, 'ongoing');
        $flow->changeStatus($visit, 'completed');

        return $visit->fresh();
    }

    public function test_pos_page_lists_services_and_products(): void
    {
        $page = $this->actingAs($this->staff)->get('/staff/pos')->assertOk();
        $this->assertSeeWords($page, 'Point of Sale', 'Consultation · ₱500.00', 'Dog Food 1kg · ₱320.00 (38 bags left)', 'Walk-in buyer (no record)', 'Save Bill');
        // items that are not for sale (no selling price) are not listed
        $page->assertDontSee('Disposable Syringes');
    }

    public function test_sale_saves_bill_payment_and_deducts_stock(): void
    {
        $dogFood = $this->product('PRD-DOGF');
        $consultation = $this->service('Consultation');
        $mark = $this->owner->customer;

        $this->actingAs($this->staff)->post('/staff/pos', [
            'customer_id' => $mark->id,
            'pet_id' => Pet::where('name', 'Max')->value('id'),
            'items' => [
                ['item' => 'service:' . $consultation->id, 'quantity' => 1],
                ['item' => 'product:' . $dogFood->id, 'quantity' => 1],
            ],
            'discount' => '20',
            'amount' => '800',
            'method' => 'cash',
            'amount_tendered' => '1000',
        ])->assertRedirect('/staff/transactions/3')->assertSessionHasNoErrors();

        $bill = Transaction::find(3);
        $this->assertSame('OR-000003', $bill->receipt_number);
        $this->assertSame('counter', $bill->transaction_type);
        $this->assertSame('820.00', $bill->subtotal);          // 500 + 320, from the database prices
        $this->assertSame('800.00', $bill->total);              // minus the ₱20 discount
        $this->assertSame('800.00', $bill->amount_paid);
        $this->assertSame('0.00', $bill->balance);
        $this->assertSame('paid', $bill->status);
        $this->assertSame($this->staff->id, $bill->cashier_id);
        $this->assertCount(2, $bill->items);
        $this->assertSame('200.00', $bill->payments->first()->change_given);

        // stock: 38 -> 37, and the usage log points to the bill
        $this->assertSame(37, $dogFood->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', ['inventory_item_id' => $dogFood->id, 'type' => 'sale', 'quantity' => -1, 'reference_type' => $bill->getMorphClass(), 'reference_id' => $bill->id]);
        $this->assertDatabaseHas('activity_logs', ['module' => 'POS', 'action' => 'created']);

        $page = $this->actingAs($this->staff)->get('/staff/transactions/3')->assertOk();
        $this->assertSeeWords($page, 'OR-000003', 'Consultation', 'Dog Food 1kg', '₱820.00', '− ₱20.00', 'Counter sale');
        $page->assertDontSee('Collect Payment');   // nothing left to pay

        $receipt = $this->actingAs($this->staff)->get('/staff/transactions/3/receipt')->assertOk();
        $this->assertSeeWords($receipt, 'RECEIPT', 'OR-000003', 'TOTAL', '₱800.00', 'Cash received ₱1,000.00', 'Change', '₱200.00', 'BALANCE', '₱0.00');
    }

    public function test_not_enough_stock_saves_nothing(): void
    {
        $dogFood = $this->product('PRD-DOGF');
        $count = Transaction::count();

        $this->actingAs($this->staff)->post('/staff/pos', [
            'items' => [
                ['item' => 'service:' . $this->service('Grooming')->id, 'quantity' => 1],
                ['item' => 'product:' . $dogFood->id, 'quantity' => 50],
            ],
            'amount' => '0',
        ])->assertSessionHasErrors('quantity');

        // all-or-nothing: no bill, no lines, and the stock did not move
        $this->assertSame($count, Transaction::count());
        $this->assertDatabaseCount('transaction_items', 3);   // only the demo bills' lines
        $this->assertSame(38, $dogFood->fresh()->stock);
    }

    public function test_bad_input_is_refused(): void
    {
        $consultation = 'service:' . $this->service('Consultation')->id;

        $this->actingAs($this->staff)->post('/staff/pos', [])->assertSessionHasErrors('items');
        $this->actingAs($this->staff)->post('/staff/pos', ['items' => [['item' => '', 'quantity' => 0]]])
            ->assertSessionHasErrors(['items.0.item', 'items.0.quantity']);
        $this->actingAs($this->staff)->post('/staff/pos', ['items' => [['item' => $consultation, 'quantity' => 1]], 'discount' => '600'])
            ->assertSessionHasErrors('discount');
        $this->actingAs($this->staff)->post('/staff/pos', ['items' => [['item' => $consultation, 'quantity' => 1]], 'amount' => '600', 'method' => 'cash'])
            ->assertSessionHasErrors('amount');   // more than the total
        $this->actingAs($this->staff)->post('/staff/pos', ['items' => [['item' => $consultation, 'quantity' => 1]], 'amount' => '500', 'method' => 'gcash'])
            ->assertSessionHasErrors('reference_number');
        $this->actingAs($this->staff)->post('/staff/pos', ['items' => [['item' => $consultation, 'quantity' => 1]], 'amount' => '500', 'method' => 'cash', 'amount_tendered' => '100'])
            ->assertSessionHasErrors('amount_tendered');
        // a pet that is not the chosen customer's
        $this->actingAs($this->staff)->post('/staff/pos', ['items' => [['item' => $consultation, 'quantity' => 1]], 'customer_id' => $this->owner->customer->id, 'pet_id' => Pet::where('name', 'Buddy')->value('id')])
            ->assertSessionHasErrors('pet_id');

        $this->assertSame(2, Transaction::count());
    }

    public function test_partial_payment_then_collect_the_balance(): void
    {
        $bill = Transaction::where('receipt_number', 'OR-000002')->first();
        $page = $this->actingAs($this->staff)->get("/staff/transactions/{$bill->id}");
        $this->assertSeeWords($page, 'Collect Payment', 'Balance: <strong>₱390.00</strong>', '1009 284 5531');

        $this->actingAs($this->staff)->post("/staff/transactions/{$bill->id}/payments", ['amount' => '400', 'method' => 'cash'])
            ->assertSessionHasErrors('amount');   // more than the balance
        $this->actingAs($this->staff)->post("/staff/transactions/{$bill->id}/payments", ['amount' => '190', 'method' => 'maya'])
            ->assertSessionHasErrors('reference_number');

        $this->actingAs($this->staff)->post("/staff/transactions/{$bill->id}/payments", ['amount' => '190', 'method' => 'maya', 'reference_number' => 'MY-777'])
            ->assertSessionHasNoErrors();
        $this->assertSame('partial', $bill->fresh()->status);
        $this->assertSame('200.00', $bill->fresh()->balance);

        $this->actingAs($this->staff)->post("/staff/transactions/{$bill->id}/payments", ['amount' => '200', 'method' => 'cash', 'amount_tendered' => '500'])
            ->assertSessionHasNoErrors();
        $bill->refresh();
        $this->assertSame('paid', $bill->status);
        $this->assertSame('890.00', $bill->amount_paid);
        $this->assertSame('0.00', $bill->balance);
        $this->assertCount(3, $bill->payments);
        $this->assertDatabaseHas('activity_logs', ['module' => 'POS', 'action' => 'payment']);

        // fully paid: no more payments
        $this->actingAs($this->staff)->post("/staff/transactions/{$bill->id}/payments", ['amount' => '1', 'method' => 'cash'])->assertForbidden();
    }

    public function test_bill_a_walk_in_visit_only_once(): void
    {
        $visit = $this->completedVisit();

        $board = $this->actingAs($this->staff)->get('/staff/patient-flow');
        $this->assertSeeWords($board, 'Bill');

        $this->assertSeeWords($this->actingAs($this->staff)->get('/staff/pos?visit=' . $visit->id), 'Walk-in · Queue #' . $visit->queue_number, 'John Cruz');
        $this->actingAs($this->staff)->post('/staff/pos', [
            'patient_visit_id' => $visit->id,
            'customer_id' => $this->owner->customer->id,   // ignored: the visit decides the owner
            'items' => [['item' => 'service:' . $visit->service_id, 'quantity' => 1]],
            'amount' => '500', 'method' => 'cash',
        ])->assertSessionHasNoErrors();

        $bill = Transaction::latest('id')->first();
        $this->assertSame('walk_in', $bill->transaction_type);
        $this->assertSame($visit->id, $bill->patient_visit_id);
        $this->assertSame($visit->customer_id, $bill->customer_id);
        $this->assertSame('Buddy', $bill->pet->name);

        // the board now shows the receipt number, and a second bill is refused
        $this->assertSeeWords($this->actingAs($this->staff)->get('/staff/patient-flow'), $bill->receipt_number . ' (Paid)');
        $this->assertSeeWords($this->actingAs($this->staff)->get('/staff/pos?visit=' . $visit->id), 'Already billed');
        $this->actingAs($this->staff)->post('/staff/pos', ['patient_visit_id' => $visit->id, 'items' => [['item' => 'service:' . $visit->service_id, 'quantity' => 1]]])
            ->assertSessionHasErrors('items');
    }

    public function test_bill_an_appointment(): void
    {
        $appointment = Appointment::where('status', 'confirmed')->whereDate('appointment_date', today())->first();   // Buddy 11:30
        $this->actingAs($this->staff)->patch("/staff/appointments/{$appointment->id}/complete");

        $this->assertSeeWords($this->actingAs($this->staff)->get("/staff/appointments/{$appointment->id}"), 'Bill This Appointment');
        $this->assertSeeWords($this->actingAs($this->staff)->get('/staff/pos?appointment=' . $appointment->id), 'Appointment ' . $appointment->reference);

        $this->actingAs($this->staff)->post('/staff/pos', [
            'appointment_id' => $appointment->id,
            'items' => [['item' => 'service:' . $appointment->service_id, 'quantity' => 1]],
            'amount' => '0',
        ])->assertSessionHasNoErrors();

        $bill = Transaction::latest('id')->first();
        $this->assertSame('appointment', $bill->transaction_type);
        $this->assertSame($appointment->id, $bill->appointment_id);
        $this->assertSame('unpaid', $bill->status);
        $this->assertSame($bill->total, $bill->balance);
        $this->assertSeeWords($this->actingAs($this->staff)->get("/staff/appointments/{$appointment->id}"), $bill->receipt_number . ' (Unpaid)');
    }

    public function test_vet_voids_a_bill_and_the_stock_comes_back(): void
    {
        $bill = Transaction::where('receipt_number', 'OR-000002')->first();
        $this->assertSame(38, $this->product('PRD-DOGF')->stock);

        // staff cannot void
        $this->actingAs($this->staff)->patch("/admin/transactions/{$bill->id}/void", ['void_reason' => 'Mistake'])->assertForbidden();

        $this->assertSeeWords($this->actingAs($this->vet)->get("/admin/transactions/{$bill->id}"), 'Void This Bill');
        $this->actingAs($this->vet)->patch("/admin/transactions/{$bill->id}/void", ['void_reason' => ''])->assertSessionHasErrors('void_reason');
        $this->actingAs($this->vet)->patch("/admin/transactions/{$bill->id}/void", ['void_reason' => 'Customer returned the items.'])->assertSessionHasNoErrors();

        $bill->refresh();
        $this->assertSame('void', $bill->status);
        $this->assertSame($this->vet->id, $bill->voided_by);
        $this->assertSame(40, $this->product('PRD-DOGF')->stock);
        $this->assertSame(25, $this->product('PRD-SHMP')->stock);
        $this->assertDatabaseHas('activity_logs', ['module' => 'POS', 'action' => 'voided']);

        // a void bill cannot be paid or voided again, and the receipt says VOID
        $this->actingAs($this->staff)->post("/staff/transactions/{$bill->id}/payments", ['amount' => '10', 'method' => 'cash'])->assertForbidden();
        $this->actingAs($this->vet)->patch("/admin/transactions/{$bill->id}/void", ['void_reason' => 'Again please'])->assertForbidden();
        $this->assertSeeWords($this->actingAs($this->vet)->get("/admin/transactions/{$bill->id}/receipt"), 'VOID', 'Customer returned the items.');
    }

    public function test_lists_filters_and_totals(): void
    {
        $page = $this->actingAs($this->staff)->get('/staff/transactions')->assertOk();
        $this->assertSeeWords($page, 'OR-000001', 'OR-000002', 'Collected Today (1)', '₱500.00', 'Unpaid Balances (1)', '₱390.00', '+ New Bill');

        $this->actingAs($this->staff)->get('/staff/transactions?status=partial')->assertSee('OR-000002')->assertDontSee('OR-000001');
        $this->actingAs($this->staff)->get('/staff/transactions?method=cash')->assertSee('OR-000001')->assertDontSee('OR-000002');
        $this->actingAs($this->staff)->get('/staff/transactions?search=anna')->assertSee('OR-000002')->assertDontSee('OR-000001');
        $this->actingAs($this->staff)->get('/staff/transactions?from=' . today()->toDateString())->assertSee('OR-000002')->assertDontSee('OR-000001');
        $this->actingAs($this->staff)->from('/staff/transactions')
            ->get('/staff/transactions?from=' . today()->toDateString() . '&to=' . today()->subDay()->toDateString())
            ->assertSessionHasErrors('to');

        $this->assertSeeWords($this->actingAs($this->vet)->get('/admin/transactions')->assertOk(), 'Payments', 'OR-000002');
    }

    public function test_super_admin_views_only(): void
    {
        $bill = Transaction::where('receipt_number', 'OR-000002')->first();

        $list = $this->actingAs($this->super)->get('/superadmin/transactions')->assertOk();
        $this->assertSeeWords($list, 'Sales &amp; Transaction Records', 'OR-000001', 'OR-000002', 'view only');
        $list->assertDontSee('+ New Bill');

        $page = $this->actingAs($this->super)->get("/superadmin/transactions/{$bill->id}")->assertOk();
        $page->assertDontSee('Collect Payment')->assertDontSee('Void This Bill');
        $this->actingAs($this->super)->get("/superadmin/transactions/{$bill->id}/receipt")->assertOk()->assertSee('OR-000002');

        $this->actingAs($this->super)->get('/staff/pos')->assertForbidden();
        $this->actingAs($this->super)->patch("/admin/transactions/{$bill->id}/void", ['void_reason' => 'Not allowed'])->assertForbidden();
    }

    public function test_access_rules(): void
    {
        $this->actingAs($this->owner)->get('/staff/pos')->assertForbidden();
        $this->actingAs($this->owner)->get('/admin/transactions')->assertForbidden();
        $this->actingAs($this->vet)->get('/staff/pos')->assertForbidden();          // the vet does not run the POS (decision P2)
        $this->actingAs($this->vet)->post('/staff/pos', [])->assertForbidden();
        $this->actingAs($this->staff)->get('/admin/transactions')->assertForbidden();
        $this->actingAs($this->staff)->get('/superadmin/transactions')->assertForbidden();

        // the "POS" permission can be switched off for Staff (Phase 8 roles page)
        $this->staff->role->permissions()->detach(\App\Models\Permission::where('slug', 'pos.manage')->value('id'));
        $this->actingAs($this->staff->fresh())->get('/staff/pos')->assertForbidden();
        $this->actingAs($this->staff->fresh())->get('/staff/transactions')->assertOk()->assertDontSee('+ New Bill');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/staff/pos')->assertRedirect('/login');
        $this->get('/admin/transactions')->assertRedirect('/login');
        $this->get('/superadmin/transactions/1/receipt')->assertRedirect('/login');
    }
}
