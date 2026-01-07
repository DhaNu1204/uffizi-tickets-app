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
            // Add soft deletes for audit trail
            $table->softDeletes();

            // Add indexes for common queries
            $table->index('bokun_product_id');
            $table->index('status');
            $table->index('tour_date');
            $table->index(['status', 'tour_date']); // Composite index for filtered date queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['bokun_product_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['tour_date']);
            $table->dropIndex(['status', 'tour_date']);
        });
    }
};
