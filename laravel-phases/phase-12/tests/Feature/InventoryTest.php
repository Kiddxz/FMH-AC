<?php

namespace Tests\Feature;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Phase 12: inventory items, batches with expiry, stock in / usage / corrections, alerts, usage log, suppliers.
 */
class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $vet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('email', 'assistant@fmhanimalclinic.com')->first();
        $this->vet = User::where('email', 'admin@fmhanimalclinic.com')->first();
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

    private function item(string $sku): InventoryItem
    {
        return InventoryItem::where('sku', $sku)->first();
    }

    public function test_list_shows_real_stock_numbers_and_alerts(): void
    {
        // Demo data: low = Rabies (8/10), Gloves (7/10), Deworming (25/30); out = Cotton Balls; 5-in-1 has a batch expiring in 20 days
        $page = $this->actingAs($this->staff)->get('/staff/inventory')->assertOk();
        $this->assertSeeWords($page, 'Total Items', 'Inventory Alerts (5)', 'Low / Out of Stock (4)', 'Expiring Soon (1)', 'Cotton Balls', 'Out of Stock', '+ Add Item');
        $page->assertSeeInOrder(['Total Items', '10', 'Available Items', '6', 'Low Stock', '3', 'Out of Stock', '1']);

        // the filters (checked on the table rows; the alerts box above the table lists items too)
        $rows = fn (string $query) => $this->actingAs($this->staff)->get('/staff/inventory?' . $query)->viewData('items')->pluck('name')->all();
        $this->assertSame(['Deworming Tablet', 'Rabies Vaccine', 'Surgical Gloves'], $rows('status=low'));
        $this->assertSame(['Cotton Balls'], $rows('status=out'));
        $this->assertSame(['Dog Food 1kg', 'Pet Shampoo'], $rows('category=product'));
        $this->assertSame(['Amoxicillin 250mg'], $rows('search=amox'));
        $this->assertSame(['5-in-1 Vaccine (DHPPL)'], $rows('status=expiring'));
    }

    public function test_bell_and_dashboards_show_the_alerts(): void
    {
        $this->assertSeeWords($this->actingAs($this->staff)->get('/staff'), 'Inventory alerts: 5', 'Inventory Alerts (5)');
        $this->assertSeeWords($this->actingAs($this->vet)->get('/admin'), 'Inventory alerts: 5', 'Inventory Alerts (5)');
        // the customer area has no bell
        $this->actingAs(User::where('email', 'owner@fmhanimalclinic.com')->first())->get('/portal')->assertDontSee('Inventory alerts');
    }

    public function test_staff_adds_and_edits_an_item(): void
    {
        $this->actingAs($this->staff)->post('/staff/inventory', [
            'sku' => 'med-cef', 'name' => 'Cephalexin 250mg', 'category' => 'medicine', 'unit' => 'capsules',
            'reorder_level' => 20, 'selling_price' => '18', 'supplier_id' => Supplier::first()->id, 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $item = $this->item('MED-CEF');   // the code is saved in capital letters
        $this->assertSame('Cephalexin 250mg', $item->name);
        $this->assertSame(0, $item->stock);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Inventory', 'action' => 'created']);

        // duplicate code / bad values
        $this->actingAs($this->staff)->post('/staff/inventory', [
            'sku' => 'MED-CEF', 'name' => '', 'category' => 'food', 'unit' => '', 'reorder_level' => -1,
        ])->assertSessionHasErrors(['sku', 'name', 'category', 'unit', 'reorder_level']);

        // deactivate: hidden from the normal list, shown under "Inactive Items"
        $this->actingAs($this->staff)->put("/staff/inventory/{$item->id}", [
            'sku' => 'MED-CEF', 'name' => 'Cephalexin 250mg', 'category' => 'medicine', 'unit' => 'capsules',
            'reorder_level' => 20, 'is_active' => '0',
        ])->assertRedirect("/staff/inventory/{$item->id}");
        $this->assertFalse($item->fresh()->is_active);
        $this->actingAs($this->staff)->get('/staff/inventory')->assertDontSee('Cephalexin 250mg');
        $this->actingAs($this->staff)->get('/staff/inventory?status=inactive')->assertSee('Cephalexin 250mg');
    }

    public function test_stock_in_creates_a_batch_and_a_log_row(): void
    {
        $cotton = $this->item('SUP-COT');

        $this->actingAs($this->staff)->post("/staff/inventory/{$cotton->id}/stock-in", [
            'quantity' => 30, 'batch_number' => 'COT-2026', 'expiration_date' => '', 'received_date' => today()->format('Y-m-d'),
            'unit_cost' => '25.50', 'supplier_id' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame(30, $cotton->stock);
        $this->assertDatabaseHas('inventory_movements', ['inventory_item_id' => $cotton->id, 'type' => 'stock_in', 'quantity' => 30, 'user_id' => $this->staff->id]);

        // expired or future-dated deliveries are refused
        $this->actingAs($this->staff)->post("/staff/inventory/{$cotton->id}/stock-in", [
            'quantity' => 0, 'expiration_date' => today()->subDay()->format('Y-m-d'), 'received_date' => today()->addDay()->format('Y-m-d'),
        ])->assertSessionHasErrors(['quantity', 'expiration_date', 'received_date']);
    }

    public function test_usage_takes_from_the_batch_that_expires_first(): void
    {
        $vaccine = $this->item('VAC-5IN1');   // batches: 15 (expires in 20 days) and 10 (in 200 days)
        $soon = $vaccine->batches()->orderBy('expiration_date')->first();
        $later = $vaccine->batches()->orderByDesc('expiration_date')->first();

        $this->actingAs($this->staff)->post("/staff/inventory/{$vaccine->id}/usage", ['quantity' => 20, 'remarks' => 'Vaccination day'])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $soon->fresh()->quantity);
        $this->assertSame(5, $later->fresh()->quantity);
        $this->assertSame(2, InventoryMovement::where('inventory_item_id', $vaccine->id)->where('type', 'usage')->count());
        $this->assertSame(-20, (int) InventoryMovement::where('inventory_item_id', $vaccine->id)->where('type', 'usage')->sum('quantity'));

        // cannot use more than what is left; a reason is required
        $this->actingAs($this->staff)->post("/staff/inventory/{$vaccine->id}/usage", ['quantity' => 6, 'remarks' => 'x'])
            ->assertSessionHasErrors(['quantity' => 'Only 5 doses of 5-in-1 Vaccine (DHPPL) can be used (expired stock is not counted).']);
        $this->actingAs($this->staff)->post("/staff/inventory/{$vaccine->id}/usage", ['quantity' => 1, 'remarks' => ''])
            ->assertSessionHasErrors('remarks');
        $this->assertSame(5, $vaccine->stock);
    }

    public function test_vet_records_usage_but_cannot_manage_stock(): void
    {
        $amox = $this->item('MED-AMOX');

        $page = $this->actingAs($this->vet)->get("/admin/inventory/{$amox->id}")->assertOk();
        $this->assertSeeWords($page, 'Record Usage');
        $page->assertDontSee('Stock In (delivery)')->assertDontSee('Correct Count')->assertDontSee('Edit Item');

        $this->actingAs($this->vet)->post("/admin/inventory/{$amox->id}/usage", ['quantity' => 14, 'remarks' => 'Luna - 7 days'])
            ->assertSessionHasNoErrors();
        $this->assertSame(186, $amox->stock);

        // staff-only actions
        $batch = $amox->batches()->first();
        $this->actingAs($this->vet)->post("/staff/inventory/{$amox->id}/stock-in", ['quantity' => 5, 'received_date' => today()->format('Y-m-d')])->assertForbidden();
        $this->actingAs($this->vet)->patch("/staff/inventory/batches/{$batch->id}/count", ['counted' => 1, 'reason' => 'x'])->assertForbidden();
        $this->actingAs($this->vet)->get('/staff/inventory/create')->assertForbidden();
    }

    public function test_expired_stock_is_not_used_and_can_be_disposed(): void
    {
        $dewormer = $this->item('MED-DEW');   // 25 tablets, not expired
        $old = new InventoryBatch([
            'inventory_item_id' => $dewormer->id, 'batch_number' => 'OLD-1',
            'expiration_date' => today()->subDays(3)->toDateString(), 'received_date' => today()->subYear()->toDateString(),
        ]);
        $old->quantity = 12;
        $old->save();

        $page = $this->actingAs($this->staff)->get("/staff/inventory/{$dewormer->id}");
        $this->assertSeeWords($page, '25 tablets available', '+ 12 expired, to dispose', 'Dispose Expired');
        $this->assertSeeWords($this->actingAs($this->staff)->get('/staff/inventory'), 'Expired (1)');

        // usage ignores the expired batch
        $this->actingAs($this->staff)->post("/staff/inventory/{$dewormer->id}/usage", ['quantity' => 26, 'remarks' => 'x'])->assertSessionHasErrors('quantity');
        $this->actingAs($this->staff)->post("/staff/inventory/{$dewormer->id}/usage", ['quantity' => 5, 'remarks' => 'Rocky'])->assertSessionHasNoErrors();
        $this->assertSame(12, $old->fresh()->quantity);

        $this->actingAs($this->staff)->patch("/staff/inventory/batches/{$old->id}/dispose")->assertSessionHasNoErrors();
        $this->assertSame(0, $old->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', ['inventory_batch_id' => $old->id, 'type' => 'expired', 'quantity' => -12]);

        // a good batch cannot be disposed
        $good = $dewormer->batches()->where('id', '!=', $old->id)->first();
        $this->actingAs($this->staff)->patch("/staff/inventory/batches/{$good->id}/dispose")->assertSessionHasErrors('batch');
    }

    public function test_count_correction_logs_the_difference(): void
    {
        $gloves = $this->item('SUP-GLV');   // 7 boxes
        $batch = $gloves->batches()->first();

        $this->actingAs($this->staff)->patch("/staff/inventory/batches/{$batch->id}/count", ['counted' => 5, 'reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($this->staff)->patch("/staff/inventory/batches/{$batch->id}/count", ['counted' => 5, 'reason' => 'Monthly count'])
            ->assertSessionHas('status', 'Count corrected (-2 boxes).');
        $this->assertSame(5, $batch->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', ['inventory_batch_id' => $batch->id, 'type' => 'adjustment', 'quantity' => -2, 'remarks' => 'Monthly count']);

        $this->actingAs($this->staff)->patch("/staff/inventory/batches/{$batch->id}/count", ['counted' => 5, 'reason' => 'Again'])
            ->assertSessionHas('status', 'The count matches the system. Nothing was changed.');
    }

    public function test_usage_log_page_and_filters(): void
    {
        $this->actingAs($this->staff)->post('/staff/inventory/' . $this->item('MED-AMOX')->id . '/usage', ['quantity' => 3, 'remarks' => 'Buddy - wound']);

        $this->assertSeeWords($this->actingAs($this->staff)->get('/staff/inventory/log'), 'Inventory Usage Log', 'Opening stock', 'Buddy - wound');
        $this->actingAs($this->staff)->get('/staff/inventory/log?type=usage')->assertSee('Buddy - wound')->assertDontSee('Opening stock');
        $this->actingAs($this->staff)->get('/staff/inventory/log?search=shampoo')->assertSee('Pet Shampoo')->assertDontSee('Buddy - wound');
        $this->actingAs($this->vet)->get('/admin/inventory/log')->assertOk()->assertSee('Buddy - wound');
    }

    public function test_suppliers_are_managed_by_staff(): void
    {
        $this->actingAs($this->staff)->get('/staff/suppliers')->assertOk()->assertSee('VetSupply Philippines');

        $this->actingAs($this->staff)->post('/staff/suppliers', [
            'name' => 'Animal Health Trading', 'contact_person' => 'Ben Lim', 'contact_number' => '09171112222',
            'email' => 'ben@aht.example', 'address' => 'Pasay City', 'is_active' => '1',
        ])->assertRedirect('/staff/suppliers');
        $supplier = Supplier::where('name', 'Animal Health Trading')->first();
        $this->assertTrue($supplier->is_active);

        $this->actingAs($this->staff)->post('/staff/suppliers', ['name' => 'VetSupply Philippines', 'contact_number' => '123'])
            ->assertSessionHasErrors(['name', 'contact_number']);

        $this->actingAs($this->staff)->patch("/staff/suppliers/{$supplier->id}/toggle")->assertSessionHasNoErrors();
        $this->assertFalse($supplier->fresh()->is_active);

        // the vet and the super admin do not manage suppliers
        $this->actingAs($this->vet)->get('/staff/suppliers')->assertForbidden();
        $this->actingAs(User::where('email', 'superadmin@fmhanimalclinic.com')->first())->get('/staff/suppliers')->assertForbidden();
    }

    public function test_access_rules(): void
    {
        $owner = User::where('email', 'owner@fmhanimalclinic.com')->first();
        $super = User::where('email', 'superadmin@fmhanimalclinic.com')->first();
        $item = $this->item('MED-AMOX');

        foreach ([$owner, $super] as $user) {
            $this->actingAs($user)->get('/staff/inventory')->assertForbidden();
            $this->actingAs($user)->get('/admin/inventory')->assertForbidden();
            $this->actingAs($user)->post("/staff/inventory/{$item->id}/usage", ['quantity' => 1, 'remarks' => 'x'])->assertForbidden();
        }
        $this->actingAs($this->staff)->get('/admin/inventory')->assertForbidden();

        // the super admin keeps the read-only overview from Phase 8
        $this->actingAs($super)->get('/superadmin/inventory')->assertOk()->assertSee('Amoxicillin 250mg');
        $this->assertSame(200, $item->stock);
    }
}
