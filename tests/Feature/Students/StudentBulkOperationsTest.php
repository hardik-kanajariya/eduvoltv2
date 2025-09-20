<?php

namespace Tests\Feature\Students;

use App\Models\Student;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StudentBulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and users
        $adminRole = Role::factory()->create(['name' => 'Admin']);
        $teacherRole = Role::factory()->create(['name' => 'Teacher']);
        $studentRole = Role::factory()->create(['name' => 'Student']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole($teacherRole);

        $this->student = User::factory()->create();
        $this->student->assignRole($studentRole);

        Storage::fake('local');
        Queue::fake();
    }

    public function test_admin_can_import_csv()
    {
        $file = UploadedFile::fake()->createWithContent(
            'students.csv',
            "first_name,last_name,email,grade\nJohn,Doe,john@example.com,10"
        );

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.import.csv'), [
                'csv_file' => $file,
                'skip_duplicates' => true,
            ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');
    }

    public function test_teacher_can_import_csv()
    {
        $file = UploadedFile::fake()->createWithContent(
            'students.csv',
            "first_name,last_name,email,grade\nJohn,Doe,john@example.com,10"
        );

        $response = $this->actingAs($this->teacher)
            ->post(route('students.bulk.import.csv'), [
                'csv_file' => $file,
                'skip_duplicates' => true,
            ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');
    }

    public function test_student_cannot_import_csv()
    {
        $file = UploadedFile::fake()->createWithContent(
            'students.csv',
            "first_name,last_name,email,grade\nJohn,Doe,john@example.com,10"
        );

        $response = $this->actingAs($this->student)
            ->post(route('students.bulk.import.csv'), [
                'csv_file' => $file,
            ]);

        $response->assertStatus(403);
    }

    public function test_csv_import_validates_file_type()
    {
        $file = UploadedFile::fake()->create('students.txt', 100);

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.import.csv'), [
                'csv_file' => $file,
            ]);

        $response->assertSessionHasErrors('csv_file');
    }

    public function test_admin_can_bulk_update_status()
    {
        $students = Student::factory()->count(3)->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.status.update'), [
                'student_ids' => $students->pluck('id')->toArray(),
                'status' => 'inactive',
            ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');
        
        foreach ($students as $student) {
            $this->assertDatabaseHas('students', [
                'id' => $student->id,
                'status' => 'inactive'
            ]);
        }
    }

    public function test_bulk_status_update_validates_status()
    {
        $students = Student::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.status.update'), [
                'student_ids' => $students->pluck('id')->toArray(),
                'status' => 'invalid_status',
            ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_bulk_status_update_requires_graduation_date()
    {
        $students = Student::factory()->count(2)->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.status.update'), [
                'student_ids' => $students->pluck('id')->toArray(),
                'status' => 'graduated',
            ]);

        $response->assertSessionHasErrors('graduation_date');
    }

    public function test_admin_can_bulk_assign_classes()
    {
        $students = Student::factory()->count(3)->create(['grade' => '9', 'section' => 'A']);

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.class.assignment'), [
                'student_ids' => $students->pluck('id')->toArray(),
                'grade' => '10',
                'section' => 'B',
                'academic_year' => '2024-25',
            ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');
        
        foreach ($students as $student) {
            $this->assertDatabaseHas('students', [
                'id' => $student->id,
                'grade' => '10',
                'section' => 'B',
                'academic_year' => '2024-25'
            ]);
        }
    }

    public function test_bulk_class_assignment_validates_student_ids()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.class.assignment'), [
                'student_ids' => [99999], // Non-existent ID
                'grade' => '10',
            ]);

        $response->assertSessionHasErrors('student_ids.0');
    }

    public function test_admin_can_send_mass_communication()
    {
        $students = Student::factory()->count(2)->create([
            'email' => 'student@example.com',
            'parent_email' => 'parent@example.com'
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.communication'), [
                'student_ids' => $students->pluck('id')->toArray(),
                'subject' => 'Test Message',
                'message' => 'This is a test message',
                'send_to_students' => true,
                'send_to_parents' => true,
            ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');
    }

    public function test_mass_communication_validates_required_fields()
    {
        $students = Student::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.communication'), [
                'student_ids' => $students->pluck('id')->toArray(),
                // Missing subject and message
            ]);

        $response->assertSessionHasErrors(['subject', 'message']);
    }

    public function test_admin_can_bulk_export_csv()
    {
        $students = Student::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.export'), [
                'student_ids' => $students->pluck('id')->toArray(),
                'format' => 'csv',
                'fields' => ['first_name', 'last_name', 'email'],
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_bulk_export_validates_format()
    {
        $students = Student::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.export'), [
                'student_ids' => $students->pluck('id')->toArray(),
                'format' => 'invalid_format',
            ]);

        $response->assertSessionHasErrors('format');
    }

    public function test_bulk_export_validates_fields()
    {
        $students = Student::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.export'), [
                'student_ids' => $students->pluck('id')->toArray(),
                'format' => 'csv',
                'fields' => ['invalid_field'],
            ]);

        $response->assertSessionHasErrors('fields.0');
    }

    public function test_guest_cannot_access_bulk_operations()
    {
        $students = Student::factory()->count(2)->create();

        $response = $this->post(route('students.bulk.status.update'), [
            'student_ids' => $students->pluck('id')->toArray(),
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_job_progress_endpoint_returns_progress()
    {
        $jobId = 'test-job-123';

        $response = $this->actingAs($this->admin)
            ->getJson(route('students.bulk.job.progress', ['jobId' => $jobId]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'job_id',
            'status',
            'progress',
            'processed',
            'total',
            'errors',
            'started_at',
            'completed_at'
        ]);
    }

    public function test_bulk_operations_require_student_selection()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.status.update'), [
                'student_ids' => [], // Empty array
                'status' => 'inactive',
            ]);

        $response->assertSessionHasErrors('student_ids');
    }

    public function test_bulk_operations_handle_large_student_sets()
    {
        $students = Student::factory()->count(100)->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->post(route('students.bulk.status.update'), [
                'student_ids' => $students->pluck('id')->toArray(),
                'status' => 'inactive',
            ]);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('success');
    }
}