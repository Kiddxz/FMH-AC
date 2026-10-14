<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The notifications of the bell that a user already read (clicked, "Mark as read" or "Mark all as read").
 * The notifications themselves are not saved: they are made from the bookings, bills, waivers and inventory.
 * Only the key of each one that was read is saved here, so the number on the bell goes down.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('note_key', 64);
            $table->timestamps();

            $table->unique(['user_id', 'note_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
    }
};
