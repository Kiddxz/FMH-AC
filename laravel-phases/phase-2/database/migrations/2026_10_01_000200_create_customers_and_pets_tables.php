<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Customers (pet owners) and Pets
|--------------------------------------------------------------------------
| A customer may have an online account (user_id) or be a walk-in
| without an account (user_id = NULL), as described in the capstone paper.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('contact_number', 20)->index();  // used to find walk-ins quickly / avoid duplicates
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_walk_in')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['last_name', 'first_name']);
        });

        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('name');
            $table->enum('species', ['dog', 'cat', 'bird', 'rabbit', 'hamster', 'reptile', 'other']);
            $table->string('breed')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->date('birthdate')->nullable();          // age is computed from this
            $table->string('color')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'deceased', 'archived'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pets');
        Schema::dropIfExists('customers');
    }
};
