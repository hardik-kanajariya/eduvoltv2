<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'parent_name',
        'parent_phone',
        'parent_email',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
    ];

    /**
     * Get the full name of the student.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

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
}
