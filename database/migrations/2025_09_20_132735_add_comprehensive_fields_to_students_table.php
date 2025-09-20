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
        Schema::table('students', function (Blueprint $table) {
            // Add admission and academic fields
            $table->string('admission_number')->unique()->after('id');
            $table->enum('status', ['active', 'inactive', 'graduated', 'transferred', 'suspended'])->default('active')->after('section');
            $table->string('blood_group')->nullable()->after('gender');
            $table->string('photo')->nullable()->after('blood_group');
            
            // Parent/Guardian Information
            $table->string('parent_relationship')->default('parent')->after('parent_email');
            
            // Emergency Contact
            $table->string('emergency_contact_name')->nullable()->after('parent_relationship');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_phone');
            
            // Medical Information
            $table->json('medical_conditions')->nullable()->after('emergency_contact_relationship');
            $table->json('allergies')->nullable()->after('medical_conditions');
            $table->json('medications')->nullable()->after('allergies');
            $table->text('emergency_medical_info')->nullable()->after('medications');
            
            // Academic Information
            $table->string('previous_school')->nullable()->after('emergency_medical_info');
            $table->date('admission_date')->nullable()->after('previous_school');
            $table->string('academic_year')->nullable()->after('admission_date');
            
            // Add soft deletes
            $table->softDeletes();
            
            // Add indexes for performance
            $table->index(['grade', 'section']);
            $table->index(['status']);
            $table->index(['admission_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['grade', 'section']);
            $table->dropIndex(['status']);
            $table->dropIndex(['admission_date']);
            
            $table->dropColumn([
                'admission_number', 'status', 'blood_group', 'photo',
                'parent_relationship', 'emergency_contact_name', 'emergency_contact_phone',
                'emergency_contact_relationship', 'medical_conditions', 'allergies',
                'medications', 'emergency_medical_info', 'previous_school',
                'admission_date', 'academic_year'
            ]);
        });
    }
};
