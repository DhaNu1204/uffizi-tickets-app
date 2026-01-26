<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes booking_id nullable to support manual messages (messages sent without a booking).
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['booking_id']);

            // Modify the column to be nullable
            $table->unsignedBigInteger('booking_id')->nullable()->change();

            // Re-add the foreign key constraint with nullable support
            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This will fail if there are any messages with null booking_id
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);

            $table->unsignedBigInteger('booking_id')->nullable(false)->change();

            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->onDelete('cascade');
        });
    }
};
