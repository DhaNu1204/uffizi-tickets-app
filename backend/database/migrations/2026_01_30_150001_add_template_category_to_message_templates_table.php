<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds template_category to message_templates to differentiate between:
     * - 'ticket_only': Templates for tickets without audio guide
     * - 'ticket_with_audio': Templates that include audio guide information
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('template_category')->default('ticket_only')->after('template_type');
            $table->index('template_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropIndex(['template_category']);
            $table->dropColumn('template_category');
        });
    }
};
