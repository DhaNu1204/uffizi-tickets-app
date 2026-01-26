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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20)->comment('E.164 format');
            $table->enum('channel', ['whatsapp', 'sms']);
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamps();

            // Unique constraint: one conversation per phone+channel combo
            $table->unique(['phone_number', 'channel']);

            // Indexes for common queries
            $table->index('status');
            $table->index('last_message_at');
            $table->index('unread_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
