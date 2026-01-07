<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite index for (tour_date, status) to optimize grouped queries.
 * This migration is safe for production - only adds an index, no data modification.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Composite index for queries that filter by date range and status
            // Used by groupedByDate() and stats() methods
            $table->index(['tour_date', 'status'], 'bookings_tour_date_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_tour_date_status_index');
        });
    }
};
