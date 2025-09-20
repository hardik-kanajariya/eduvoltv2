<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\Student;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any documents.
     */
    public function viewAny(User $user, ?Student $student = null): bool
    {
        // Admin can view all documents
        if ($user->hasRole('Admin')) {
            return true;
        }

        // If a specific student is provided, check access to that student's documents
        if ($student) {
            return $this->canAccessStudentDocuments($user, $student);
        }

        // Otherwise, user can view documents they have access to
        return $user->hasAnyRole(['Teacher', 'Student', 'Parent']);
    }

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document): bool
    {
        return $document->hasAccess($user);
    }

    /**
     * Determine whether the user can create documents.
     */
    public function create(User $user, ?Student $student = null): bool
    {
        // Admin can create documents for any student
        if ($user->hasRole('Admin')) {
            return true;
        }

        // If a specific student is provided, check access
        if ($student) {
            return $this->canAccessStudentDocuments($user, $student);
        }

        // Teachers, students, and parents can create documents
        return $user->hasAnyRole(['Teacher', 'Student', 'Parent']);
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, Document $document): bool
    {
        // Admin can update any document
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Uploader can update their uploaded documents
        if ($document->uploaded_by === $user->id) {
            return true;
        }

        // Check if user has access to the student
        return $this->canAccessStudentDocuments($user, $document->student);
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, Document $document): bool
    {
        // Admin can delete any document
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Uploader can delete their uploaded documents
        if ($document->uploaded_by === $user->id) {
            return true;
        }

        // Teachers can delete documents of students in their classes
        if ($user->hasRole('Teacher')) {
            return $this->canAccessStudentDocuments($user, $document->student);
        }

        // Students cannot delete their own documents (only admin/teachers can)
        // Parents cannot delete their children's documents (only admin/teachers can)
        return false;
    }

    /**
     * Determine whether the user can restore the document.
     */
    public function restore(User $user, Document $document): bool
    {
        // Same permissions as update
        return $this->update($user, $document);
    }

    /**
     * Determine whether the user can permanently delete the document.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        // Only admin can permanently delete documents
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can verify documents.
     */
    public function verify(User $user, Document $document): bool
    {
        // Admin can verify any document
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Teachers can verify documents of students in their classes
        if ($user->hasRole('Teacher')) {
            return $this->canAccessStudentDocuments($user, $document->student);
        }

        // Students and parents cannot verify documents
        return false;
    }

    /**
     * Check if user can access a specific student's documents.
     */
    private function canAccessStudentDocuments(User $user, Student $student): bool
    {
        // Admin has access to all students
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Student has access to their own documents
        if ($user->hasRole('Student') && $student->user_id === $user->id) {
            return true;
        }

        // Parent has access to their children's documents
        if ($user->hasRole('Parent')) {
            $childrenIds = $user->students()->pluck('id')->toArray();
            return in_array($student->id, $childrenIds);
        }

        // Teacher has access to students in their classes
        if ($user->hasRole('Teacher')) {
            // This would need to be implemented based on your class/teacher relationship
            // For now, we'll check if the teacher is associated with the student
            return $student->classes()
                ->whereHas('teachers', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->exists();
        }

        return false;
    }
}
