<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        // Check authorization
        Gate::authorize('viewAny', Student::class);

        $query = Student::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->searchByName($request->search)
                ->orWhere('admission_number', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by grade level
        if ($request->has('grade_level') && $request->grade_level !== '') {
            $query->where('grade_level', $request->grade_level);
        }

        // Filter by class section
        if ($request->has('class_section') && $request->class_section !== '') {
            $query->where('class_section', $request->class_section);
        }

        $students = $query->orderBy('admission_number')
            ->paginate(20)
            ->withQueryString();

        return view('students.index', compact('students'));
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
}
