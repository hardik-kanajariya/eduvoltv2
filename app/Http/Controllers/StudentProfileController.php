<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\UpdateProfileRequest;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    /**
     * Display the comprehensive student profile.
     */
    public function show(Student $student): View
    {
        Gate::authorize('view', $student);

        // Decode JSON fields for display
        $student->medical_conditions = json_decode($student->medical_conditions ?? '[]', true);
        $student->allergies = json_decode($student->allergies ?? '[]', true);
        $student->medications = json_decode($student->medications ?? '[]', true);
        $student->documents = json_decode($student->documents ?? '[]', true);

        return view('students.profile.show', compact('student'));
    }

    /**
     * Show the profile edit form.
     */
    public function edit(Student $student): View
    {
        Gate::authorize('update', $student);

        // Decode JSON fields for editing
        $student->medical_conditions = json_decode($student->medical_conditions ?? '[]', true);
        $student->allergies = json_decode($student->allergies ?? '[]', true);
        $student->medications = json_decode($student->medications ?? '[]', true);

        return view('students.profile.edit', compact('student'));
    }

    /**
     * Update the student profile.
     */
    public function update(UpdateProfileRequest $request, Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);

        try {
            $validated = $request->validated();
            $user = Auth::user();

            // Role-based field restrictions
            if ($user->hasRole('student') && $user->email === $student->email) {
                // Students can only update limited fields
                $validated = $request->only([
                    'phone',
                    'address',
                    'emergency_contact_name',
                    'emergency_contact_phone',
                    'emergency_contact_relationship',
                    'medical_conditions',
                    'allergies',
                    'medications'
                ]);
            } elseif ($user->hasRole('parent') && $user->email === $student->parent_email) {
                // Parents can update contact and medical information
                $validated = $request->only([
                    'phone',
                    'address',
                    'parent_name',
                    'parent_phone',
                    'parent_email',
                    'emergency_contact_name',
                    'emergency_contact_phone',
                    'emergency_contact_relationship',
                    'medical_conditions',
                    'allergies',
                    'medications',
                    'emergency_medical_info'
                ]);
            }

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

            return redirect()->route('students.profile.show', $student)
                ->with('success', 'Profile updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update profile. Please try again.');
        }
    }

    /**
     * Show the medical information section.
     */
    public function medical(Student $student): View
    {
        Gate::authorize('view', $student);

        // Decode medical JSON fields
        $student->medical_conditions = json_decode($student->medical_conditions ?? '[]', true);
        $student->allergies = json_decode($student->allergies ?? '[]', true);
        $student->medications = json_decode($student->medications ?? '[]', true);

        return view('students.profile.medical', compact('student'));
    }

    /**
     * Show the academic history section.
     */
    public function academic(Student $student): View
    {
        Gate::authorize('view', $student);

        // In future, this will include enrollment history, grades, etc.
        // For now, we show basic academic information
        $academicHistory = [
            'current_grade' => $student->grade_level,
            'current_section' => $student->class_section,
            'enrollment_date' => $student->enrollment_date,
            'academic_year' => $student->academic_year,
            'previous_school' => $student->previous_school,
            'status' => $student->status,
        ];

        return view('students.profile.academic', compact('student', 'academicHistory'));
    }

    /**
     * Show the documents section.
     */
    public function documents(Student $student): View
    {
        Gate::authorize('view', $student);

        // Decode documents JSON field
        $documents = json_decode($student->documents ?? '[]', true);

        return view('students.profile.documents', compact('student', 'documents'));
    }

    /**
     * Upload additional documents.
     */
    public function uploadDocuments(Request $request, Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);

        $request->validate([
            'documents' => ['required', 'array'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'], // 5MB max
            'document_types' => ['required', 'array'],
            'document_types.*' => ['string', 'max:100'],
        ]);

        try {
            $existingDocuments = json_decode($student->documents ?? '[]', true);
            $newDocuments = [];

            foreach ($request->file('documents') as $index => $file) {
                $path = $file->store('students/documents', 'public');
                $documentType = $request->document_types[$index] ?? 'Other';

                $newDocuments[] = [
                    'path' => $path,
                    'type' => $documentType,
                    'original_name' => $file->getClientOriginalName(),
                    'uploaded_at' => now()->toISOString(),
                ];
            }

            $allDocuments = array_merge($existingDocuments, $newDocuments);
            $student->update(['documents' => json_encode($allDocuments)]);

            return redirect()->route('students.profile.documents', $student)
                ->with('success', 'Documents uploaded successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to upload documents. Please try again.');
        }
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(Request $request, Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);

        $request->validate([
            'document_index' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $documents = json_decode($student->documents ?? '[]', true);
            $documentIndex = $request->document_index;

            if (isset($documents[$documentIndex])) {
                // Delete file from storage
                Storage::disk('public')->delete($documents[$documentIndex]['path']);

                // Remove from array
                unset($documents[$documentIndex]);

                // Re-index array
                $documents = array_values($documents);

                $student->update(['documents' => json_encode($documents)]);

                return redirect()->route('students.profile.documents', $student)
                    ->with('success', 'Document deleted successfully.');
            }

            return redirect()->back()
                ->with('error', 'Document not found.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete document. Please try again.');
        }
    }

    /**
     * Show the contact information section.
     */
    public function contacts(Student $student): View
    {
        Gate::authorize('view', $student);

        return view('students.profile.contacts', compact('student'));
    }

    /**
     * Print student profile (for official use).
     */
    public function print(Student $student): View
    {
        Gate::authorize('view', $student);

        // Decode JSON fields for printing
        $student->medical_conditions = json_decode($student->medical_conditions ?? '[]', true);
        $student->allergies = json_decode($student->allergies ?? '[]', true);
        $student->medications = json_decode($student->medications ?? '[]', true);

        return view('students.profile.print', compact('student'));
    }
}
