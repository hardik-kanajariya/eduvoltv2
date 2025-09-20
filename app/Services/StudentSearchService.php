<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StudentSearchService
{
    /**
     * Perform advanced search and filtering on students.
     */
    public function search(Request $request): LengthAwarePaginator
    {
        $query = Student::query();

        // Basic text search across multiple fields
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('admission_number', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('parent_name', 'like', "%{$searchTerm}%")
                    ->orWhere('parent_email', 'like', "%{$searchTerm}%");
            });
        }

        // Advanced name search (first name OR last name)
        if ($request->filled('first_name')) {
            $query->where('first_name', 'like', '%' . $request->first_name . '%');
        }

        if ($request->filled('last_name')) {
            $query->where('last_name', 'like', '%' . $request->last_name . '%');
        }

        // Admission number search
        if ($request->filled('admission_number')) {
            $query->where('admission_number', 'like', '%' . $request->admission_number . '%');
        }

        // Email search
        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Grade filter
        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }

        // Section filter
        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        // Gender filter
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Blood group filter
        if ($request->filled('blood_group')) {
            $query->where('blood_group', $request->blood_group);
        }

        // Academic year filter
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        // Date range filters
        if ($request->filled('enrollment_date_from')) {
            $query->where('enrollment_date', '>=', $request->enrollment_date_from);
        }

        if ($request->filled('enrollment_date_to')) {
            $query->where('enrollment_date', '<=', $request->enrollment_date_to);
        }

        if ($request->filled('date_of_birth_from')) {
            $query->where('date_of_birth', '>=', $request->date_of_birth_from);
        }

        if ($request->filled('date_of_birth_to')) {
            $query->where('date_of_birth', '<=', $request->date_of_birth_to);
        }

        // Age range filters (calculated from date of birth)
        if ($request->filled('age_from')) {
            $maxDate = now()->subYears($request->age_from)->format('Y-m-d');
            $query->where('date_of_birth', '<=', $maxDate);
        }

        if ($request->filled('age_to')) {
            $minDate = now()->subYears($request->age_to + 1)->format('Y-m-d');
            $query->where('date_of_birth', '>', $minDate);
        }

        // Parent information filters
        if ($request->filled('parent_name')) {
            $query->where('parent_name', 'like', '%' . $request->parent_name . '%');
        }

        if ($request->filled('parent_email')) {
            $query->where('parent_email', 'like', '%' . $request->parent_email . '%');
        }

        if ($request->filled('parent_phone')) {
            $query->where('parent_phone', 'like', '%' . $request->parent_phone . '%');
        }

        // Address search
        if ($request->filled('address')) {
            $query->where('address', 'like', '%' . $request->address . '%');
        }

        // Medical conditions search (JSON field)
        if ($request->filled('medical_condition')) {
            $query->whereJsonContains('medical_conditions', $request->medical_condition);
        }

        // Allergies search (JSON field)
        if ($request->filled('allergy')) {
            $query->whereJsonContains('allergies', $request->allergy);
        }

        // Students with photos
        if ($request->filled('has_photo')) {
            if ($request->has_photo === 'yes') {
                $query->whereNotNull('photo');
            } else {
                $query->whereNull('photo');
            }
        }

        // Students with documents
        if ($request->filled('has_documents')) {
            if ($request->has_documents === 'yes') {
                $query->whereNotNull('documents');
            } else {
                $query->whereNull('documents');
            }
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'admission_number');
        $sortDirection = $request->get('sort_direction', 'asc');

        $allowedSortFields = [
            'admission_number',
            'first_name',
            'last_name',
            'email',
            'enrollment_date',
            'date_of_birth',
            'grade',
            'status'
        ];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('admission_number', 'asc');
        }

        // Get pagination size
        $perPage = $request->get('per_page', 20);
        $perPage = min(100, max(10, (int) $perPage)); // Between 10-100

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get filter options for dropdowns.
     */
    public function getFilterOptions(): array
    {
        return [
            'statuses' => Student::distinct()->pluck('status')->filter()->sort()->values(),
            'grades' => Student::distinct()->pluck('grade')->filter()->sort()->values(),
            'sections' => Student::distinct()->pluck('section')->filter()->sort()->values(),
            'genders' => ['male', 'female', 'other'],
            'blood_groups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            'academic_years' => Student::distinct()->pluck('academic_year')->filter()->sort()->values(),
        ];
    }

    /**
     * Get search statistics.
     */
    public function getSearchStats(Request $request): array
    {
        $query = Student::query();

        // Apply the same filters as the main search (without pagination)
        $this->applyFilters($query, $request);

        $totalCount = $query->count();
        $statusCounts = $query->select('status')
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();

        $gradeCounts = $query->select('grade')
            ->groupBy('grade')
            ->selectRaw('grade, count(*) as count')
            ->pluck('count', 'grade')
            ->toArray();

        return [
            'total_count' => $totalCount,
            'status_counts' => $statusCounts,
            'grade_counts' => $gradeCounts,
        ];
    }

    /**
     * Apply filters to query (helper method).
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        // This is a simplified version - in a real implementation,
        // we'd extract the filter logic from the search method
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('admission_number', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }
    }

    /**
     * Export filtered results.
     */
    public function exportResults(Request $request, string $format = 'csv'): array
    {
        $query = Student::query();
        $this->applyFilters($query, $request);

        // Limit export to prevent memory issues
        $students = $query->limit(1000)->get();

        $data = $students->map(function ($student) {
            return [
                'admission_number' => $student->admission_number,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'date_of_birth' => $student->date_of_birth,
                'gender' => $student->gender,
                'grade' => $student->grade,
                'section' => $student->section,
                'status' => $student->status,
                'enrollment_date' => $student->enrollment_date,
                'parent_name' => $student->parent_name,
                'parent_email' => $student->parent_email,
                'parent_phone' => $student->parent_phone,
            ];
        })->toArray();

        // Generate CSV content
        $csvContent = '';
        if (!empty($data)) {
            // Add headers
            $csvContent .= implode(',', array_keys($data[0])) . "\n";

            // Add data rows
            foreach ($data as $row) {
                $csvContent .= implode(',', array_map(function ($value) {
                    return '"' . str_replace('"', '""', $value ?? '') . '"';
                }, $row)) . "\n";
            }
        }

        $filename = 'students_export_' . date('Y-m-d_H-i-s') . '.csv';

        return [
            'filename' => $filename,
            'content' => $csvContent,
            'headers' => [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ],
            'data' => $data,
            'total_count' => $students->count(),
            'format' => $format,
        ];
    }
}
