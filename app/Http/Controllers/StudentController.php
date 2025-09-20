<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Models\Student;
use App\Services\StudentSearchService;
use App\Services\StudentBulkOperationsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentController extends Controller
{
    protected $searchService;
    protected $bulkOperationsService;

    public function __construct(StudentSearchService $searchService, StudentBulkOperationsService $bulkOperationsService)
    {
        $this->searchService = $searchService;
        $this->bulkOperationsService = $bulkOperationsService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        // Check authorization
        Gate::authorize('viewAny', Student::class);

        // Use the search service for advanced filtering
        $students = $this->searchService->search($request);
        
        // Get filter options for dropdowns
        $filterOptions = $this->searchService->getFilterOptions();
        
        // Get search statistics
        $searchStats = $this->searchService->getSearchStats($request);

        return view('students.index', compact('students', 'filterOptions', 'searchStats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', Student::class);

        return view('students.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        try {
            $validated = $request->validated();

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $validated['photo'] = $request->file('photo')->store('students/photos', 'public');
            }

            // Convert array fields to JSON
            $validated['medical_conditions'] = json_encode($validated['medical_conditions'] ?? []);
            $validated['allergies'] = json_encode($validated['allergies'] ?? []);
            $validated['medications'] = json_encode($validated['medications'] ?? []);

            $student = Student::create($validated);

            return redirect()->route('students.show', $student)
                ->with('success', 'Student created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create student. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student): View
    {
        Gate::authorize('view', $student);

        return view('students.show', compact('student'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student): View
    {
        Gate::authorize('update', $student);

        return view('students.edit', compact('student'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);

        try {
            $validated = $request->validated();

            // Handle photo upload
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($student->photo) {
                    Storage::disk('public')->delete($student->photo);
                }
                $validated['photo'] = $request->file('photo')->store('students/photos', 'public');
            }

            // Convert array fields to JSON
            if (isset($validated['medical_conditions'])) {
                $validated['medical_conditions'] = json_encode($validated['medical_conditions']);
            }
            if (isset($validated['allergies'])) {
                $validated['allergies'] = json_encode($validated['allergies']);
            }
            if (isset($validated['medications'])) {
                $validated['medications'] = json_encode($validated['medications']);
            }

            $student->update($validated);

            return redirect()->route('students.show', $student)
                ->with('success', 'Student updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update student. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        try {
            $student->delete();

            return redirect()->route('students.index')
                ->with('success', 'Student deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete student. Please try again.');
        }
    }

    /**
     * Restore a soft-deleted student.
     */
    public function restore(Student $student): RedirectResponse
    {
        Gate::authorize('restore', $student);

        try {
            $student->restore();

            return redirect()->route('students.show', $student)
                ->with('success', 'Student restored successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to restore student. Please try again.');
        }
    }

    /**
     * Permanently delete a student.
     */
    public function forceDelete(Student $student): RedirectResponse
    {
        Gate::authorize('forceDelete', $student);

        try {
            // Delete photo if exists
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }

            $student->forceDelete();

            return redirect()->route('students.index')
                ->with('success', 'Student permanently deleted.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to permanently delete student. Please try again.');
        }
    }

    /**
     * Bulk operations for students.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:delete,restore,activate,deactivate',
            'students' => 'required|array',
            'students.*' => 'exists:students,id',
        ]);

        try {
            $students = Student::whereIn('id', $request->students);

            switch ($request->action) {
                case 'delete':
                    $students->delete();
                    $message = 'Selected students deleted successfully.';
                    break;
                case 'restore':
                    $students->restore();
                    $message = 'Selected students restored successfully.';
                    break;
                case 'activate':
                    $students->update(['status' => 'active']);
                    $message = 'Selected students activated successfully.';
                    break;
                case 'deactivate':
                    $students->update(['status' => 'inactive']);
                    $message = 'Selected students deactivated successfully.';
                    break;
            }

            return redirect()->route('students.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to perform bulk action. Please try again.');
        }
    }

    /**
     * Export filtered students (Issue #35).
     */
    public function export(Request $request)
    {
        Gate::authorize('viewAny', Student::class);
        
        $exportData = $this->searchService->exportResults($request);
        
        return response($exportData['content'])
            ->header('Content-Type', $exportData['headers']['Content-Type'])
            ->header('Content-Disposition', 'attachment; filename="' . $exportData['filename'] . '"');
    }

    /**
     * Get advanced search options for AJAX requests (Issue #35).
     */
    public function searchOptions(Request $request)
    {
        Gate::authorize('viewAny', Student::class);
        
        return response()->json([
            'filters' => $this->searchService->getFilterOptions(),
            'stats' => $this->searchService->getSearchStats($request)
        ]);
    }

    /**
     * Perform advanced search with AJAX support (Issue #35).
     */
    public function search(Request $request)
    {
        Gate::authorize('viewAny', Student::class);
        
        $students = $this->searchService->search($request);
        
        if ($request->ajax()) {
            return response()->json([
                'students' => $students->items(),
                'pagination' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'total' => $students->total(),
                ]
            ]);
        }
        
        return redirect()->route('students.index')->with('search_results', $students);
    }

    /**
     * Import students from CSV (Issue #36).
     */
    public function importCsv(Request $request)
    {
        Gate::authorize('create', Student::class);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'skip_duplicates' => 'boolean',
            'update_existing' => 'boolean',
        ]);

        try {
            $options = [
                'skip_duplicates' => $request->boolean('skip_duplicates', true),
                'update_existing' => $request->boolean('update_existing', false),
            ];

            $result = $this->bulkOperationsService->importFromCsv($request->file('csv_file'), $options);

            if ($result['job_id']) {
                return redirect()->route('students.index')
                    ->with('success', 'CSV import started. You will be notified when it completes.')
                    ->with('job_id', $result['job_id']);
            } else {
                return redirect()->back()
                    ->with('error', 'Import failed: ' . implode(', ', $result['details']));
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk status update for students (Issue #36).
     */
    public function bulkStatusUpdate(Request $request)
    {
        Gate::authorize('update', Student::class);

        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'status' => 'required|in:active,inactive,graduated,transferred,suspended',
            'graduation_date' => 'nullable|date|required_if:status,graduated',
            'transfer_date' => 'nullable|date|required_if:status,transferred',
            'transfer_school' => 'nullable|string|max:255|required_if:status,transferred',
        ]);

        try {
            $options = [];
            if ($request->status === 'graduated') {
                $options['graduation_date'] = $request->graduation_date;
            } elseif ($request->status === 'transferred') {
                $options['transfer_date'] = $request->transfer_date;
                $options['transfer_school'] = $request->transfer_school;
            }

            $result = $this->bulkOperationsService->bulkStatusUpdate(
                $request->student_ids,
                $request->status,
                $options
            );

            return redirect()->route('students.index')
                ->with('success', "Successfully updated {$result['success']} students. {$result['errors']} errors occurred.")
                ->with('bulk_result', $result);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Bulk update failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk class assignment for students (Issue #36).
     */
    public function bulkClassAssignment(Request $request)
    {
        Gate::authorize('update', Student::class);

        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'grade' => 'nullable|string|max:10',
            'section' => 'nullable|string|max:10',
            'academic_year' => 'nullable|string|max:20',
        ]);

        try {
            $assignment = array_filter([
                'grade' => $request->grade,
                'section' => $request->section,
                'academic_year' => $request->academic_year,
            ]);

            if (empty($assignment)) {
                return redirect()->back()
                    ->with('error', 'Please select at least one field to update.');
            }

            $result = $this->bulkOperationsService->bulkClassAssignment(
                $request->student_ids,
                $assignment
            );

            return redirect()->route('students.index')
                ->with('success', "Successfully updated {$result['success']} students. {$result['errors']} errors occurred.")
                ->with('bulk_result', $result);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Bulk assignment failed: ' . $e->getMessage());
        }
    }

    /**
     * Mass communication to students and parents (Issue #36).
     */
    public function massCommunication(Request $request)
    {
        Gate::authorize('viewAny', Student::class);

        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'send_to_students' => 'boolean',
            'send_to_parents' => 'boolean',
        ]);

        try {
            $message = [
                'subject' => $request->subject,
                'body' => $request->message,
                'sender_name' => Auth::user()->name ?? config('app.name'),
            ];

            $options = [
                'send_to_students' => $request->boolean('send_to_students', true),
                'send_to_parents' => $request->boolean('send_to_parents', true),
            ];

            $result = $this->bulkOperationsService->massCommunication(
                $request->student_ids,
                $message,
                $options
            );

            return redirect()->route('students.index')
                ->with('success', "Mass communication queued for {$result['queued']} recipients.")
                ->with('communication_result', $result);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Mass communication failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk export students in various formats (Issue #36).
     */
    public function bulkExport(Request $request)
    {
        Gate::authorize('viewAny', Student::class);

        $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'format' => 'required|in:csv,excel,pdf',
            'fields' => 'nullable|array',
            'fields.*' => 'string|in:admission_number,first_name,last_name,email,phone,date_of_birth,gender,grade,section,status,parent_name,parent_email,parent_phone,address,blood_group,medical_conditions,allergies',
        ]);

        try {
            $result = $this->bulkOperationsService->exportStudents(
                $request->student_ids,
                $request->get('format'),
                $request->fields ?? []
            );

            return response($result['content'])
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Get job progress for bulk operations (Issue #36).
     */
    public function getJobProgress(Request $request, string $jobId)
    {
        Gate::authorize('viewAny', Student::class);

        $progress = $this->bulkOperationsService->getJobProgress($jobId);

        if ($request->expectsJson()) {
            return response()->json($progress);
        }

        return view('students.job-progress', compact('progress'));
    }
}
