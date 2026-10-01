<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Permissions and verification codes
|--------------------------------------------------------------------------
| permissions      : things a role is allowed to do (e.g. "pos.manage")
| permission_role  : which role has which permission (Super Admin can edit this)
| verification_codes : 6-digit email codes for registration and password reset
|                      (capstone Fig 6.3 and Fig 6.4)
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();       // e.g. "inventory.update"
            $table->string('name');                 // e.g. "Update inventory"
            $table->string('module');               // e.g. "Inventory"
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']); // a role gets each permission once
        });

        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('code');                 // stored HASHED
            $table->enum('purpose', ['register', 'password_reset']);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
    }
};
