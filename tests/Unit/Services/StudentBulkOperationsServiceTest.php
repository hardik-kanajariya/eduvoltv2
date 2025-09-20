<?php

namespace Tests\Unit\Services;

use App\Models\Student;
use App\Services\StudentBulkOperationsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StudentBulkOperationsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StudentBulkOperationsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StudentBulkOperationsService();
        Storage::fake('local');
        Queue::fake();
    }

    public function test_csv_import_validates_file_type()
    {
        $file = UploadedFile::fake()->create('students.pdf', 100);

        $result = $this->service->importFromCsv($file);

        $this->assertGreaterThan(0, $result['errors']);
        $this->assertStringContainsString('CSV file', implode(' ', $result['details']));
    }

    public function test_csv_import_validates_file_size()
    {
        // Create file larger than 10MB
        $file = UploadedFile::fake()->create('students.csv', 11 * 1024);

        $result = $this->service->importFromCsv($file);

        $this->assertGreaterThan(0, $result['errors']);
        $this->assertStringContainsString('10MB', implode(' ', $result['details']));
    }

    public function test_csv_import_queues_job_for_valid_file()
    {
        $file = UploadedFile::fake()->createWithContent('students.csv', 'first_name,last_name,email
John,Doe,john@example.com');

        $result = $this->service->importFromCsv($file);

        $this->assertNotNull($result['job_id']);
        $this->assertEquals(0, $result['errors']);
    }

    public function test_bulk_status_update_validates_transitions()
    {
        $student = Student::factory()->create(['status' => 'graduated']);

        $result = $this->service->bulkStatusUpdate([$student->id], 'active');

        $this->assertEquals(0, $result['success']);
        $this->assertEquals(1, $result['errors']);
        $this->assertCount(1, $result['failed_students']);
    }

    public function test_bulk_status_update_allows_valid_transitions()
    {
        $student = Student::factory()->create(['status' => 'active']);

        $result = $this->service->bulkStatusUpdate([$student->id], 'inactive');

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['errors']);
        $this->assertCount(1, $result['updated_students']);

        $student->refresh();
        $this->assertEquals('inactive', $student->status);
    }

    public function test_bulk_status_update_handles_graduation()
    {
        $student = Student::factory()->create(['status' => 'active']);

        $graduationDate = '2024-06-15';
        $result = $this->service->bulkStatusUpdate(
            [$student->id],
            'graduated',
            ['graduation_date' => $graduationDate]
        );

        $this->assertEquals(1, $result['success']);
        $student->refresh();
        $this->assertEquals('graduated', $student->status);
    }

    public function test_bulk_class_assignment_updates_grade_and_section()
    {
        $student = Student::factory()->create(['grade' => '9', 'section' => 'A']);

        $assignment = [
            'grade' => '10',
            'section' => 'B',
            'academic_year' => '2024-25'
        ];

        $result = $this->service->bulkClassAssignment([$student->id], $assignment);

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['errors']);

        $student->refresh();
        $this->assertEquals('10', $student->grade);
        $this->assertEquals('B', $student->section);
        $this->assertEquals('2024-25', $student->academic_year);
    }

    public function test_bulk_class_assignment_handles_partial_updates()
    {
        $student = Student::factory()->create(['grade' => '9', 'section' => 'A']);

        $assignment = ['grade' => '10']; // Only update grade

        $result = $this->service->bulkClassAssignment([$student->id], $assignment);

        $this->assertEquals(1, $result['success']);

        $student->refresh();
        $this->assertEquals('10', $student->grade);
        $this->assertEquals('A', $student->section); // Section unchanged
    }

    public function test_mass_communication_queues_jobs()
    {
        $students = Student::factory()->count(3)->create([
            'email' => 'student@example.com',
            'parent_email' => 'parent@example.com'
        ]);

        $message = [
            'subject' => 'Test Message',
            'body' => 'This is a test message',
        ];

        $options = [
            'send_to_students' => true,
            'send_to_parents' => true,
        ];

        $result = $this->service->massCommunication(
            $students->pluck('id')->toArray(),
            $message,
            $options
        );

        $this->assertEquals(6, $result['queued']); // 3 students + 3 parents
        $this->assertEquals(0, $result['failed']);
        $this->assertCount(6, $result['job_ids']);
    }

    public function test_mass_communication_skips_empty_emails()
    {
        $students = Student::factory()->count(2)->create();
        $students[0]->update(['email' => 'student@example.com', 'parent_email' => null]);
        $students[1]->update(['email' => null, 'parent_email' => 'parent@example.com']);

        $message = [
            'subject' => 'Test Message',
            'body' => 'This is a test message',
        ];

        $result = $this->service->massCommunication(
            $students->pluck('id')->toArray(),
            $message
        );

        $this->assertEquals(2, $result['queued']); // 1 student email + 1 parent email
    }

    public function test_export_students_csv_format()
    {
        $students = Student::factory()->count(2)->create();

        $result = $this->service->exportStudents(
            $students->pluck('id')->toArray(),
            'csv'
        );

        $this->assertEquals('csv', $result['format']);
        $this->assertStringContainsString('.csv', $result['filename']);
        $this->assertStringContainsString('admission_number', $result['content']);
        $this->assertStringContainsString($students[0]->first_name, $result['content']);
    }

    public function test_export_students_with_custom_fields()
    {
        $student = Student::factory()->create();

        $fields = ['first_name', 'last_name', 'email'];

        $result = $this->service->exportStudents(
            [$student->id],
            'csv',
            $fields
        );

        $lines = explode("\n", $result['content']);
        $header = str_getcsv($lines[0]);

        $this->assertEquals($fields, $header);
        $this->assertStringContainsString($student->first_name, $result['content']);
    }

    public function test_export_students_excel_format()
    {
        $students = Student::factory()->count(2)->create();

        $result = $this->service->exportStudents(
            $students->pluck('id')->toArray(),
            'excel'
        );

        $this->assertEquals('excel', $result['format']);
        $this->assertStringContainsString('.xlsx', $result['filename']);
    }

    public function test_export_students_pdf_format()
    {
        $students = Student::factory()->count(2)->create();

        $result = $this->service->exportStudents(
            $students->pluck('id')->toArray(),
            'pdf'
        );

        $this->assertEquals('pdf', $result['format']);
        $this->assertStringContainsString('.pdf', $result['filename']);
    }

    public function test_export_students_invalid_format_throws_exception()
    {
        $student = Student::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $this->service->exportStudents([$student->id], 'invalid');
    }

    public function test_get_job_progress_returns_structure()
    {
        $jobId = 'test-job-123';

        $progress = $this->service->getJobProgress($jobId);

        $this->assertArrayHasKey('job_id', $progress);
        $this->assertArrayHasKey('status', $progress);
        $this->assertArrayHasKey('progress', $progress);
        $this->assertArrayHasKey('processed', $progress);
        $this->assertArrayHasKey('total', $progress);
        $this->assertEquals($jobId, $progress['job_id']);
    }

    public function test_bulk_operations_with_nonexistent_student_ids()
    {
        $result = $this->service->bulkStatusUpdate([99999], 'active');

        $this->assertEquals(0, $result['success']);
        $this->assertEquals(1, $result['errors']);
    }

    public function test_bulk_operations_handle_database_errors()
    {
        $student = Student::factory()->create();

        // Simulate database error by trying to set an invalid status
        // This would be caught by the service's exception handling
        $result = $this->service->bulkStatusUpdate([$student->id], 'invalid_status');

        $this->assertEquals(0, $result['success']);
        $this->assertEquals(1, $result['errors']);
    }
}
