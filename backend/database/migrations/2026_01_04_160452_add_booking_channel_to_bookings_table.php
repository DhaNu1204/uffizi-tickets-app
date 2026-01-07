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
            // Track booking source: 'direct', 'GetYourGuide', 'Viator', etc.
            $table->string('booking_channel', 50)->nullable()->after('bokun_product_id');
            $table->index('booking_channel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['booking_channel']);
            $table->dropColumn('booking_channel');
        });
    }
};
