<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Inventory Monitoring and Suppliers (capstone REQ014 - REQ017)
|--------------------------------------------------------------------------
| suppliers            : supplier records (managed by Staff)
| inventory_items      : medicines, vaccines, clinic supplies, products (e.g. dog food)
| inventory_batches    : each delivery with its own quantity and expiration date
| inventory_movements  : the USAGE LOG - every stock change (who, what, how many, when)
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('contact_person')->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);   // deactivate instead of delete
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();                // item code
            $table->string('name');
            $table->enum('category', ['medicine', 'vaccine', 'supply', 'product']);
            $table->string('unit');                         // pieces, doses, bottles, bags...
            $table->unsignedInteger('reorder_level')->default(0); // low-stock alert level
            $table->decimal('selling_price', 10, 2)->nullable();  // for POS (null = not for sale)
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('category');
            $table->index('name');
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->unsignedInteger('quantity');            // unsigned = can never go below 0
            $table->date('expiration_date')->nullable();
            $table->date('received_date');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->timestamps();
            $table->index('expiration_date');
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->enum('type', ['stock_in', 'usage', 'sale', 'adjustment', 'expired']);
            $table->integer('quantity');                    // + when added, - when used/sold
            $table->nullableMorphs('reference');            // e.g. the visit or transaction that used it
            $table->string('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();  // log rows are never edited
            $table->index(['inventory_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('suppliers');
    }
};
