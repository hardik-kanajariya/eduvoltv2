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
        // Remove tenant_id foreign keys and columns from existing tables

        // Remove from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        // Remove from students table
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id_grade']);
            $table->dropIndex(['tenant_id_section']);
            $table->dropColumn('tenant_id');
        });

        // Remove from teachers table
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id_department']);
            $table->dropColumn('tenant_id');
        });

        // Drop the entire tenants table as we no longer need multi-tenancy
        Schema::dropIfExists('tenants');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate tenants table
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable();
            $table->string('subdomain')->nullable()->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('settings')->nullable();
            $table->datetime('trial_ends_at')->nullable();
            $table->timestamps();
        });

        // Add tenant_id back to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->after('id')->constrained('tenants')->onDelete('cascade');
            $table->index(['tenant_id']);
        });

        // Add tenant_id back to students table
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'grade']);
            $table->index(['tenant_id', 'section']);
        });

        // Add tenant_id back to teachers table
        Schema::table('teachers', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'department']);
        });
    }
};
