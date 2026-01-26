<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds audio guide credential fields for PopGuide integration.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('audio_guide_username', 100)->nullable()->after('audio_guide_sent_at');
            $table->string('audio_guide_password', 100)->nullable()->after('audio_guide_username');
            $table->string('audio_guide_url', 500)->nullable()->after('audio_guide_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['audio_guide_username', 'audio_guide_password', 'audio_guide_url']);
        });
    }
};
