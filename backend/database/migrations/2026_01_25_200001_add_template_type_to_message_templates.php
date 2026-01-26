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
        Schema::table('message_templates', function (Blueprint $table) {
            // Add template_type column (ticket_only or ticket_with_audio)
            $table->string('template_type', 30)->default('ticket_only')->after('language');

            // Add language display info
            $table->string('language_name', 50)->nullable()->after('language');
            $table->string('language_flag', 10)->nullable()->after('language_name');

            // Add sort order for display
            $table->integer('sort_order')->default(0)->after('is_active');
        });

        // Update index
        Schema::table('message_templates', function (Blueprint $table) {
            $table->index(['language', 'template_type', 'is_active'], 'templates_lang_type_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropIndex('templates_lang_type_active');
            $table->dropColumn(['template_type', 'language_name', 'language_flag', 'sort_order']);
        });
    }
};
