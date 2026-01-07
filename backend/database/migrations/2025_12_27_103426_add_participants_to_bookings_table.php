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
            // JSON field to store all participant names with their category
            // Format: [{"name": "John Doe", "type": "Adult"}, {"name": "Jane Doe", "type": "Child"}]
            $table->json('participants')->nullable()->after('pax_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('participants');
        });
    }
};
