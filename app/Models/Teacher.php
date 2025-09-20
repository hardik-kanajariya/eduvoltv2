<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
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
        'hire_date',
        'employee_id',
        'department',
        'subject',
        'qualification',
        'experience_years',
        'salary',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'experience_years' => 'integer',
        'salary' => 'decimal:2',
    ];

    /**
     * Get the full name of the teacher.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Scope to filter by department.
     */
    public function scopeInDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope to filter by subject.
     */
    public function scopeTeachingSubject($query, string $subject)
    {
        return $query->where('subject', 'like', "%{$subject}%");
    }
}
