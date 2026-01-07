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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('bokun_booking_id')->unique();
            $table->string('bokun_product_id');
            $table->string('product_name');
            $table->string('customer_name');
            $table->dateTime('tour_date');
            $table->integer('pax');
            $table->string('status')->default('PENDING_TICKET'); // PENDING_TICKET, TICKET_PURCHASED
            $table->string('reference_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
