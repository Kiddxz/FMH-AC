<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Online payment with PayMongo (requested by the adviser)
|--------------------------------------------------------------------------
| transactions.pay_token            : the secret part of the bill's pay link (/pay/{token}),
|                                      so a walk-in customer can pay without an account
| transactions.paymongo_checkout_id : the latest PayMongo checkout page made for the bill
| payments.method                   : adds "paymongo" (paid online, confirmed by PayMongo)
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('pay_token', 64)->nullable()->unique()->after('cashier_id');
            $table->string('paymongo_checkout_id')->nullable()->after('pay_token');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', ['cash', 'gcash', 'maya', 'card', 'paymongo'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', ['cash', 'gcash', 'maya', 'card'])->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['pay_token']);
            $table->dropColumn(['pay_token', 'paymongo_checkout_id']);
        });
    }
};
