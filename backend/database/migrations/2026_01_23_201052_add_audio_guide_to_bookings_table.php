<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds audio guide tracking for Timed Entry Tickets (product 961802).
     * - has_audio_guide: Detected from Bokun rate code (TG2 = true, TG1 = false)
     * - audio_guide_sent_at: When audio guide PDF was sent to client
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Detected from Bokun rate code during sync
            // Rate 2263305 (TG2) = Entry Ticket + Audio Guide = true
            // Rate 1861234 (TG1) = Entry Ticket ONLY = false
            $table->boolean('has_audio_guide')->default(false)->after('guide_name');

            // Timestamp when audio guide PDF was sent to client
            $table->timestamp('audio_guide_sent_at')->nullable()->after('tickets_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['has_audio_guide', 'audio_guide_sent_at']);
        });
    }
};
