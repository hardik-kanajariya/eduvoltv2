<?php

namespace App\Jobs;

use App\Models\Student;
use App\Rules\PhoneNumber;
use App\Rules\AcademicGrade;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProcessCsvImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    
    protected string $filePath;
    protected array $options;
    protected string $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, array $options = [], string $jobId = null)
    {
        $this->filePath = $filePath;
        $this->options = $options;
        $this->jobId = $jobId ?? Str::uuid()->toString();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $results = [
            'job_id' => $this->jobId,
            'processed' => 0,
            'success' => 0,
            'errors' => 0,
            'warnings' => 0,
            'error_details' => [],
            'warning_details' => [],
            'started_at' => now(),
        ];

        try {
            Log::info("Starting CSV import job", ['job_id' => $this->jobId, 'file' => $this->filePath]);

            $fileContent = Storage::get($this->filePath);
            $lines = explode("\n", $fileContent);
            
            if (empty($lines)) {
                throw new \Exception('CSV file is empty');
            }

            // Parse header row
            $headers = $this->parseCsvLine($lines[0]);
            $requiredFields = ['first_name', 'last_name', 'email'];
            
            // Validate headers
            $missingFields = array_diff($requiredFields, $headers);
            if (!empty($missingFields)) {
                throw new \Exception('Missing required fields: ' . implode(', ', $missingFields));
            }

            $totalRows = count($lines) - 1; // Exclude header
            
            // Process each data row
            for ($i = 1; $i < count($lines); $i++) {
                if (empty(trim($lines[$i]))) {
                    continue; // Skip empty lines
                }

                $results['processed']++;
                
                try {
                    $data = $this->parseCsvLine($lines[$i]);
                    $studentData = $this->mapCsvToStudentData($headers, $data);
                    
                    // Validate student data
                    $validation = $this->validateStudentData($studentData, $i + 1);
                    
                    if (!$validation['valid']) {
                        $results['errors']++;
                        $results['error_details'][] = [
                            'row' => $i + 1,
                            'errors' => $validation['errors'],
                            'data' => $studentData
                        ];
                        continue;
                    }

                    // Check for duplicates
                    $duplicate = $this->checkForDuplicates($studentData);
                    if ($duplicate) {
                        if ($this->options['skip_duplicates'] ?? true) {
                            $results['warnings']++;
                            $results['warning_details'][] = [
                                'row' => $i + 1,
                                'message' => 'Duplicate student skipped',
                                'existing_student' => $duplicate->admission_number,
                                'data' => $studentData
                            ];
                            continue;
                        } else {
                            // Update existing student
                            $this->updateExistingStudent($duplicate, $studentData);
                            $results['success']++;
                            continue;
                        }
                    }

                    // Create new student
                    $this->createNewStudent($studentData);
                    $results['success']++;

                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['error_details'][] = [
                        'row' => $i + 1,
                        'errors' => [$e->getMessage()],
                        'data' => $data ?? []
                    ];
                    
                    Log::error("Error processing CSV row", [
                        'job_id' => $this->jobId,
                        'row' => $i + 1,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $results['completed_at'] = now();
            
            // Store results (in a real implementation, you'd store this in cache or database)
            $this->storeJobResults($results);
            
            // Clean up temporary file
            Storage::delete($this->filePath);
            
            Log::info("CSV import job completed", $results);

        } catch (\Exception $e) {
            $results['status'] = 'failed';
            $results['error'] = $e->getMessage();
            $results['completed_at'] = now();
            
            $this->storeJobResults($results);
            
            Log::error("CSV import job failed", [
                'job_id' => $this->jobId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Parse CSV line handling quotes and commas.
     */
    private function parseCsvLine(string $line): array
    {
        return str_getcsv($line);
    }

    /**
     * Map CSV data to student model fields.
     */
    private function mapCsvToStudentData(array $headers, array $data): array
    {
        $mapped = [];
        
        foreach ($headers as $index => $header) {
            $value = $data[$index] ?? '';
            
            // Map CSV headers to student fields
            switch (strtolower(trim($header))) {
                case 'first_name':
                case 'firstname':
                    $mapped['first_name'] = trim($value);
                    break;
                case 'last_name':
                case 'lastname':
                case 'surname':
                    $mapped['last_name'] = trim($value);
                    break;
                case 'email':
                case 'email_address':
                    $mapped['email'] = trim($value);
                    break;
                case 'phone':
                case 'phone_number':
                    $mapped['phone'] = trim($value);
                    break;
                case 'date_of_birth':
                case 'birth_date':
                case 'dob':
                    $mapped['date_of_birth'] = $this->parseDate($value);
                    break;
                case 'gender':
                    $mapped['gender'] = strtolower(trim($value));
                    break;
                case 'grade':
                case 'class':
                    $mapped['grade'] = trim($value);
                    break;
                case 'section':
                    $mapped['section'] = trim($value);
                    break;
                case 'admission_number':
                case 'student_id':
                    $mapped['admission_number'] = trim($value);
                    break;
                case 'parent_name':
                case 'guardian_name':
                    $mapped['parent_name'] = trim($value);
                    break;
                case 'parent_email':
                case 'guardian_email':
                    $mapped['parent_email'] = trim($value);
                    break;
                case 'parent_phone':
                case 'guardian_phone':
                    $mapped['parent_phone'] = trim($value);
                    break;
                case 'address':
                    $mapped['address'] = trim($value);
                    break;
                case 'blood_group':
                    $mapped['blood_group'] = trim($value);
                    break;
                case 'medical_conditions':
                    $mapped['medical_conditions'] = $this->parseJsonField($value);
                    break;
                case 'allergies':
                    $mapped['allergies'] = $this->parseJsonField($value);
                    break;
            }
        }

        // Set defaults
        $mapped['status'] = $mapped['status'] ?? 'active';
        $mapped['enrollment_date'] = $mapped['enrollment_date'] ?? now();
        $mapped['academic_year'] = $mapped['academic_year'] ?? $this->getCurrentAcademicYear();
        
        // Generate admission number if not provided
        if (empty($mapped['admission_number'])) {
            $mapped['admission_number'] = $this->generateAdmissionNumber();
        }

        return $mapped;
    }

    /**
     * Validate student data.
     */
    private function validateStudentData(array $data, int $rowNumber): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => ['nullable', new PhoneNumber()],
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'grade' => 'required|string|max:10',
            'section' => 'nullable|string|max:10',
            'admission_number' => 'required|string|max:50|unique:students,admission_number',
            'parent_email' => 'nullable|email|max:255',
            'parent_phone' => ['nullable', new PhoneNumber()],
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
        ];

        $validator = Validator::make($data, $rules);
        
        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->all(),
        ];
    }

    /**
     * Check for duplicate students.
     */
    private function checkForDuplicates(array $data): ?Student
    {
        return Student::where('admission_number', $data['admission_number'])
            ->orWhere(function ($query) use ($data) {
                $query->where('email', $data['email'])
                      ->where('first_name', $data['first_name'])
                      ->where('last_name', $data['last_name']);
            })
            ->first();
    }

    /**
     * Create new student.
     */
    private function createNewStudent(array $data): Student
    {
        return Student::create($data);
    }

    /**
     * Update existing student.
     */
    private function updateExistingStudent(Student $student, array $data): Student
    {
        // Only update non-empty fields
        foreach ($data as $key => $value) {
            if (!empty($value) && $key !== 'admission_number') {
                $student->$key = $value;
            }
        }
        
        $student->save();
        return $student;
    }

    /**
     * Parse date from various formats.
     */
    private function parseDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $date = Carbon::parse($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse JSON field from CSV (semicolon or comma separated).
     */
    private function parseJsonField(?string $value): ?array
    {
        if (empty($value)) {
            return null;
        }

        // Split by semicolon or comma
        $items = preg_split('/[;,]/', $value);
        return array_map('trim', array_filter($items));
    }

    /**
     * Get current academic year.
     */
    private function getCurrentAcademicYear(): string
    {
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;
        return "{$currentYear}-" . substr($nextYear, 2);
    }

    /**
     * Generate unique admission number.
     */
    private function generateAdmissionNumber(): string
    {
        do {
            $number = 'STU' . date('y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Student::where('admission_number', $number)->exists());

        return $number;
    }

    /**
     * Store job results (implementation would use cache or database).
     */
    private function storeJobResults(array $results): void
    {
        // In a real implementation, store in cache or database
        // Cache::put("csv_import_job_{$this->jobId}", $results, now()->addHours(24));
        
        Log::info("Storing job results", ['job_id' => $this->jobId, 'results' => $results]);
    }
}