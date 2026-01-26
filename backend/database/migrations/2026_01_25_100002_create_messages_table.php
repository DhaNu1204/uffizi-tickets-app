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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->enum('channel', ['whatsapp', 'sms', 'email']);
            $table->string('external_id', 100)->nullable()->comment('Twilio SID or mail message ID');
            $table->string('recipient', 255)->comment('Phone number or email address');
            $table->text('content');
            $table->string('subject', 255)->nullable()->comment('Email subject');
            $table->foreignId('template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->json('template_variables')->nullable();
            $table->enum('status', ['pending', 'queued', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('external_id');
            $table->index('status');
            $table->index(['booking_id', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
