<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds VOX/PopGuide audio guide integration fields to bookings.
     * - vox_dynamic_link: The unique audio guide access link for customer
     * - vox_account_id: VOX account identifier for tracking
     * - vox_created_at: When the VOX account was created
     * - ticket_type: Categorizes as 'ticket_only' or 'ticket_with_audio'
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('vox_dynamic_link')->nullable()->after('audio_guide_url');
            $table->string('vox_account_id')->nullable()->after('vox_dynamic_link');
            $table->timestamp('vox_created_at')->nullable()->after('vox_account_id');
            $table->string('ticket_type')->default('ticket_only')->after('vox_created_at');

            // Index for quick lookups
            $table->index('ticket_type');
            $table->index('vox_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['ticket_type']);
            $table->dropIndex(['vox_account_id']);
            $table->dropColumn([
                'vox_dynamic_link',
                'vox_account_id',
                'vox_created_at',
                'ticket_type',
            ]);
        });
    }
};
