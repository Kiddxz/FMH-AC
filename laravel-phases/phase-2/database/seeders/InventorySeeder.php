<?php

namespace Database\Seeders;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Suppliers, inventory items (medicines, vaccines, supplies, products),
 * their stock batches with expiration dates, and the matching usage-log rows.
 */
class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $staffId = User::where('email', 'assistant@fmhanimalclinic.com')->value('id');

        $vetSupply = Supplier::updateOrCreate(['name' => 'VetSupply Philippines'], [
            'contact_person' => 'Carlo Mendoza',
            'contact_number' => '09175550101',
            'email' => 'orders@vetsupply.example',
            'address' => 'Makati City',
        ]);

        $petMart = Supplier::updateOrCreate(['name' => 'PetMart Wholesale'], [
            'contact_person' => 'Liza Garcia',
            'contact_number' => '09175550202',
            'email' => 'sales@petmart.example',
            'address' => 'Parañaque City',
        ]);

        // sku, name, category, unit, reorder level, selling price, supplier, [ [quantity, expires in N days or null], ... ]
        $items = [
            ['VAC-RAB', 'Rabies Vaccine', 'vaccine', 'doses', 10, 800, $vetSupply, [[8, 75]]],
            ['VAC-5IN1', '5-in-1 Vaccine (DHPPL)', 'vaccine', 'doses', 10, 900, $vetSupply, [[15, 20], [10, 200]]],
            ['MED-AMOX', 'Amoxicillin 250mg', 'medicine', 'capsules', 50, 15, $vetSupply, [[200, 300]]],
            ['MED-DEW', 'Deworming Tablet', 'medicine', 'tablets', 30, 60, $vetSupply, [[25, 150]]],
            ['SUP-SYR', 'Disposable Syringes', 'supply', 'pieces', 50, null, $vetSupply, [[120, null]]],
            ['SUP-GLV', 'Surgical Gloves', 'supply', 'boxes', 10, null, $vetSupply, [[7, null]]],
            ['SUP-COT', 'Cotton Balls', 'supply', 'packs', 15, null, $vetSupply, []],
            ['SUP-DIS', 'Disinfectant Solution', 'supply', 'bottles', 8, null, $petMart, [[18, 400]]],
            ['PRD-SHMP', 'Pet Shampoo', 'product', 'bottles', 10, 250, $petMart, [[25, 500]]],
            ['PRD-DOGF', 'Dog Food 1kg', 'product', 'bags', 10, 320, $petMart, [[40, 240]]],
        ];

        foreach ($items as [$sku, $name, $category, $unit, $reorder, $price, $supplier, $batches]) {
            $item = InventoryItem::updateOrCreate(['sku' => $sku], [
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'reorder_level' => $reorder,
                'selling_price' => $price,
                'supplier_id' => $supplier->id,
                'is_active' => true,
            ]);

            // Add the opening stock only once (running the seeder again will not double the stock)
            if ($item->batches()->exists()) {
                continue;
            }

            foreach ($batches as $index => [$quantity, $expiresInDays]) {
                $batch = new InventoryBatch([
                    'inventory_item_id' => $item->id,
                    'supplier_id' => $supplier->id,
                    'batch_number' => $sku . '-B' . ($index + 1),
                    'expiration_date' => $expiresInDays ? now()->addDays($expiresInDays)->toDateString() : null,
                    'received_date' => now()->subDays(10)->toDateString(),
                ]);
                $batch->quantity = $quantity;
                $batch->save();

                // Every stock change is written to the usage log
                InventoryMovement::create([
                    'inventory_item_id' => $item->id,
                    'inventory_batch_id' => $batch->id,
                    'type' => 'stock_in',
                    'quantity' => $quantity,
                    'remarks' => 'Opening stock',
                    'user_id' => $staffId,
                ]);
            }
        }
    }
}
