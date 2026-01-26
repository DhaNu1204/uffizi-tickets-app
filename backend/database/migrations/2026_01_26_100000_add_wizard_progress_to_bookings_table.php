<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('wizard_started_at')->nullable()->after('audio_guide_sent_at');
            $table->unsignedTinyInteger('wizard_last_step')->nullable()->after('wizard_started_at');
            $table->timestamp('wizard_abandoned_at')->nullable()->after('wizard_last_step');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['wizard_started_at', 'wizard_last_step', 'wizard_abandoned_at']);
        });
    }
};
