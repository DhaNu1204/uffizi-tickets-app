<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Add direction column (outbound = sent by us, inbound = received from customer)
            $table->enum('direction', ['outbound', 'inbound'])->default('outbound')->after('channel');

            // Add conversation_id for linking messages to conversations
            $table->foreignId('conversation_id')->nullable()->after('booking_id')
                ->constrained()->onDelete('set null');

            // Add sender_name for inbound messages (customer's name if available)
            $table->string('sender_name', 100)->nullable()->after('recipient');

            // Index for efficient conversation message queries
            $table->index(['conversation_id', 'created_at']);
            $table->index('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropIndex(['conversation_id', 'created_at']);
            $table->dropIndex(['direction']);
            $table->dropColumn(['direction', 'conversation_id', 'sender_name']);
        });
    }
};
