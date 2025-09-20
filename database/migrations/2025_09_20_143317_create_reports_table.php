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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // attendance, academic, financial, etc.
            $table->string('category'); // student, class, teacher, school
            $table->json('parameters')->nullable(); // Report configuration and filters
            $table->json('fields')->nullable(); // Selected fields for the report
            $table->string('output_format')->default('html'); // html, pdf, excel, csv
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable(); // daily, weekly, monthly
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_generation_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('draft'); // draft, published, archived
            $table->json('cached_data')->nullable(); // Cache report results
            $table->timestamp('cache_expires_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['type', 'category']);
            $table->index(['created_by', 'tenant_id']);
            $table->index(['is_scheduled', 'next_generation_at']);
            $table->index(['status', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
