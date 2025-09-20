<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Jobs\ProcessCsvImportJob;
use App\Jobs\SendMassEmailJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StudentBulkOperationsService
{
    /**
     * Import students from CSV file.
     */
    public function importFromCsv(UploadedFile $file, array $options = []): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'warnings' => 0,
            'details' => [],
            'job_id' => null,
        ];

        try {
            // Validate file
            $validation = $this->validateCsvFile($file);
            if (!$validation['valid']) {
                return array_merge($results, ['details' => $validation['errors']]);
            }

            // Store file temporarily
            $path = $file->store('temp/imports');
            
            // Queue job for processing
            $jobId = $this->queueCsvImportJob($path, $options);
            $results['job_id'] = $jobId;
            
            return $results;
        } catch (\Exception $e) {
            Log::error('CSV Import Error: ' . $e->getMessage());
            $results['errors']++;
            $results['details'][] = 'Import failed: ' . $e->getMessage();
            return $results;
        }
    }

    /**
     * Perform bulk status updates on students.
     */
    public function bulkStatusUpdate(array $studentIds, string $status, array $options = []): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'updated_students' => [],
            'failed_students' => [],
        ];

        try {
            DB::beginTransaction();

            foreach ($studentIds as $studentId) {
                try {
                    $student = Student::findOrFail($studentId);
                    
                    // Validate status change
                    if (!$this->canChangeStatus($student, $status)) {
                        $results['errors']++;
                        $results['failed_students'][] = [
                            'id' => $studentId,
                            'name' => $student->full_name,
                            'error' => "Cannot change status from {$student->status} to {$status}"
                        ];
                        continue;
                    }

                    $oldStatus = $student->status;
                    $student->status = $status;
                    
                    // Add additional fields based on status
                    if ($status === 'graduated') {
                        $student->graduation_date = $options['graduation_date'] ?? now();
                    } elseif ($status === 'transferred') {
                        $student->transfer_date = $options['transfer_date'] ?? now();
                        $student->transfer_school = $options['transfer_school'] ?? null;
                    }
                    
                    $student->save();

                    $results['success']++;
                    $results['updated_students'][] = [
                        'id' => $studentId,
                        'name' => $student->full_name,
                        'old_status' => $oldStatus,
                        'new_status' => $status,
                    ];

                    // Log the change
                    Log::info("Student status updated", [
                        'student_id' => $studentId,
                        'old_status' => $oldStatus,
                        'new_status' => $status,
                        'updated_by' => Auth::id(),
                    ]);

                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['failed_students'][] = [
                        'id' => $studentId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Status Update Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Bulk assign students to grades/sections.
     */
    public function bulkClassAssignment(array $studentIds, array $assignment): array
    {
        $results = [
            'success' => 0,
            'errors' => 0,
            'updated_students' => [],
            'failed_students' => [],
        ];

        try {
            DB::beginTransaction();

            foreach ($studentIds as $studentId) {
                try {
                    $student = Student::findOrFail($studentId);
                    
                    $oldGrade = $student->grade;
                    $oldSection = $student->section;
                    
                    if (isset($assignment['grade'])) {
                        $student->grade = $assignment['grade'];
                    }
                    
                    if (isset($assignment['section'])) {
                        $student->section = $assignment['section'];
                    }
                    
                    if (isset($assignment['academic_year'])) {
                        $student->academic_year = $assignment['academic_year'];
                    }
                    
                    $student->save();

                    $results['success']++;
                    $results['updated_students'][] = [
                        'id' => $studentId,
                        'name' => $student->full_name,
                        'old_grade' => $oldGrade,
                        'new_grade' => $student->grade,
                        'old_section' => $oldSection,
                        'new_section' => $student->section,
                    ];

                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['failed_students'][] = [
                        'id' => $studentId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Class Assignment Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mass communication to students and parents.
     */
    public function massCommunication(array $studentIds, array $message, array $options = []): array
    {
        $results = [
            'queued' => 0,
            'failed' => 0,
            'job_ids' => [],
        ];

        try {
            $students = Student::whereIn('id', $studentIds)->get();
            
            foreach ($students as $student) {
                // Queue email to student
                if (!empty($student->email) && ($options['send_to_students'] ?? true)) {
                    $jobId = $this->queueCommunicationJob($student->email, $message, 'student');
                    $results['job_ids'][] = $jobId;
                    $results['queued']++;
                }
                
                // Queue email to parent
                if (!empty($student->parent_email) && ($options['send_to_parents'] ?? true)) {
                    $jobId = $this->queueCommunicationJob($student->parent_email, $message, 'parent', $student);
                    $results['job_ids'][] = $jobId;
                    $results['queued']++;
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Mass Communication Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export students in various formats.
     */
    public function exportStudents(array $studentIds, string $format = 'csv', array $fields = []): array
    {
        try {
            $students = Student::whereIn('id', $studentIds)->get();
            
            // Default fields if none specified
            if (empty($fields)) {
                $fields = [
                    'admission_number', 'first_name', 'last_name', 'email', 
                    'phone', 'date_of_birth', 'gender', 'grade', 'section', 
                    'status', 'parent_name', 'parent_email', 'parent_phone'
                ];
            }

            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($students, $fields);
                case 'excel':
                    return $this->exportToExcel($students, $fields);
                case 'pdf':
                    return $this->exportToPdf($students, $fields);
                default:
                    throw new \InvalidArgumentException("Unsupported export format: {$format}");
            }

        } catch (\Exception $e) {
            Log::error('Export Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the progress of a bulk operation job.
     */
    public function getJobProgress(string $jobId): array
    {
        // This would integrate with Laravel Horizon or a custom job tracking system
        // For now, return a basic structure
        return [
            'job_id' => $jobId,
            'status' => 'pending', // pending, processing, completed, failed
            'progress' => 0, // 0-100
            'processed' => 0,
            'total' => 0,
            'errors' => [],
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Validate CSV file for import.
     */
    private function validateCsvFile(UploadedFile $file): array
    {
        $errors = [];
        
        // Check file extension
        if (!in_array($file->getClientOriginalExtension(), ['csv', 'txt'])) {
            $errors[] = 'File must be a CSV file';
        }
        
        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            $errors[] = 'File size must be less than 10MB';
        }
        
        // Check MIME type
        if (!in_array($file->getMimeType(), ['text/csv', 'text/plain', 'application/csv'])) {
            $errors[] = 'Invalid file type';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check if status change is allowed.
     */
    private function canChangeStatus(Student $student, string $newStatus): bool
    {
        $currentStatus = $student->status;
        
        // Define allowed status transitions
        $allowedTransitions = [
            'active' => ['inactive', 'graduated', 'transferred', 'suspended'],
            'inactive' => ['active', 'graduated', 'transferred'],
            'suspended' => ['active', 'inactive', 'transferred'],
            'transferred' => [], // Cannot change from transferred
            'graduated' => [], // Cannot change from graduated
        ];

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }

    /**
     * Export students to CSV format.
     */
    private function exportToCsv(Collection $students, array $fields): array
    {
        $csvContent = '';
        
        // Add headers
        $csvContent .= implode(',', $fields) . "\n";
        
        // Add data rows
        foreach ($students as $student) {
            $row = [];
            foreach ($fields as $field) {
                $value = $student->$field ?? '';
                
                // Handle special cases
                if ($field === 'medical_conditions' && is_array($value)) {
                    $value = implode(';', $value);
                } elseif ($field === 'allergies' && is_array($value)) {
                    $value = implode(';', $value);
                }
                
                $row[] = '"' . str_replace('"', '""', $value) . '"';
            }
            $csvContent .= implode(',', $row) . "\n";
        }

        $filename = 'students_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        return [
            'filename' => $filename,
            'content' => $csvContent,
            'format' => 'csv',
            'size' => strlen($csvContent),
        ];
    }

    /**
     * Export students to Excel format.
     */
    private function exportToExcel(Collection $students, array $fields): array
    {
        // For now, return CSV-like data
        // In a real implementation, you'd use a library like PhpSpreadsheet
        $data = $this->exportToCsv($students, $fields);
        $data['filename'] = str_replace('.csv', '.xlsx', $data['filename']);
        $data['format'] = 'excel';
        
        return $data;
    }

    /**
     * Export students to PDF format.
     */
    private function exportToPdf(Collection $students, array $fields): array
    {
        // For now, return a basic structure
        // In a real implementation, you'd use a library like TCPDF or DomPDF
        $filename = 'students_export_' . date('Y-m-d_H-i-s') . '.pdf';
        
        return [
            'filename' => $filename,
            'content' => '', // Would contain PDF binary data
            'format' => 'pdf',
            'size' => 0,
        ];
    }

    /**
     * Queue a CSV import job.
     */
    private function queueCsvImportJob(string $filePath, array $options): string
    {
        $jobId = Str::uuid()->toString();
        
        // Dispatch the actual job
        ProcessCsvImportJob::dispatch($filePath, $options, $jobId);
        
        return $jobId;
    }

    /**
     * Queue a communication job.
     */
    private function queueCommunicationJob(string $email, array $message, string $type, ?Student $student = null): string
    {
        $jobId = Str::uuid()->toString();
        
        // Dispatch the actual job
        SendMassEmailJob::dispatch($email, $message, $type, $student, $jobId);
        
        return $jobId;
    }
}