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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');

            // Document metadata
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', [
                'academic_records',
                'medical_documents',
                'id_documents',
                'certificates',
                'reports',
                'forms',
                'photos',
                'other'
            ])->index();

            // File information
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->string('file_hash', 64); // SHA-256 hash for duplicate detection

            // Versioning
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_document_id')->nullable()->constrained('documents')->onDelete('cascade');

            // Security and access
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();

            // Status and lifecycle
            $table->enum('status', ['active', 'archived', 'deleted'])->default('active')->index();
            $table->timestamp('expires_at')->nullable();
            $table->json('access_permissions')->nullable(); // JSON field for granular permissions

            // Audit trail
            $table->json('metadata')->nullable(); // Additional document metadata
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['student_id', 'category']);
            $table->index(['student_id', 'status']);
            $table->index(['uploaded_by', 'created_at']);
            $table->index(['file_hash']); // For duplicate detection
            $table->index(['expires_at']);
            $table->unique(['file_path']); // Ensure unique file paths
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
