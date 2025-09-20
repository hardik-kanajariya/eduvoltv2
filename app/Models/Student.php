<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admission_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'enrollment_date',
        'student_id',
        'grade',
        'section',
        'status',
        'blood_group',
        'photo',
        // Parent/Guardian Information
        'parent_name',
        'parent_phone',
        'parent_email',
        'parent_relationship',
        // Emergency Contact
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        // Medical Information
        'medical_conditions',
        'allergies',
        'medications',
        'emergency_medical_info',
        // Academic Information
        'previous_school',
        'admission_date',
        'academic_year',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
        'admission_date' => 'date',
        'medical_conditions' => 'array',
        'allergies' => 'array',
        'medications' => 'array',
    ];

    /**
     * Get the full name of the student.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the user associated with this student.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    // TODO: Add these relationships when models are created
    // public function enrollments(): HasMany
    // {
    //     return $this->hasMany(Enrollment::class);
    // }
    
    // public function attendances(): HasMany
    // {
    //     return $this->hasMany(Attendance::class);
    // }

    /**
     * Scope to filter by grade.
     */
    public function scopeInGrade($query, string $grade)
    {
        return $query->where('grade', $grade);
    }

    /**
     * Scope to filter by section.
     */
    public function scopeInSection($query, string $section)
    {
        return $query->where('section', $section);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to search students.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('admission_number', 'like', "%{$search}%")
              ->orWhere('student_id', 'like', "%{$search}%");
        });
    }
}
