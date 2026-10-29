<?php

use App\Models\WaiverTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| The clinic's paper "CONSENT" form, now in the system
|--------------------------------------------------------------------------
| waivers.procedure : what the clinic will do (the blank in "authorize ... to perform ____")
| waivers.details   : owner and pet details at the time the form was prepared (locked, like the text):
|                     owner, phone, address, pet, species/breed, age/sex, color, weight, temperature
| waivers.signature : the signature drawn by the owner (PNG picture)
| The "Consent" form becomes the form the staff use. The 4 old forms are turned off
| (not deleted: old waivers still use them, and the Admin can turn them on again).
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waivers', function (Blueprint $table) {
            $table->string('procedure')->nullable()->after('content_snapshot');
            $table->json('details')->nullable()->after('procedure');
            $table->longText('signature')->nullable()->after('signer_name');
        });

        WaiverTemplate::updateOrCreate(['title' => 'Consent'], [
            'waiver_type' => 'other',
            'body' => WaiverTemplate::CONSENT_TEXT,
            'is_active' => true,
        ]);

        WaiverTemplate::whereIn('title', WaiverTemplate::OLD_FORMS)->update(['is_active' => false]);
    }

    public function down(): void
    {
        WaiverTemplate::whereIn('title', WaiverTemplate::OLD_FORMS)->update(['is_active' => true]);

        Schema::table('waivers', function (Blueprint $table) {
            $table->dropColumn(['procedure', 'details', 'signature']);
        });
    }
};
