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
        Schema::create('download_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 16)->unique();  // Short unique token (8 chars, indexed)
            $table->foreignId('attachment_id')->constrained('message_attachments')->onDelete('cascade');
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->string('s3_path', 500);  // Full S3 object path
            $table->string('filename', 255);  // Original filename for Content-Disposition
            $table->string('mime_type', 100)->default('application/pdf');
            $table->timestamp('expires_at');  // When this link expires
            $table->unsignedInteger('download_count')->default(0);  // Track downloads
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamps();

            $table->index('expires_at');  // For cleanup job
            $table->index('attachment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_tokens');
    }
};
