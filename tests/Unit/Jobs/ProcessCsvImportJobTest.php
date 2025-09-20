<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessCsvImportJob;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessCsvImportJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_job_processes_valid_csv_data()
    {
        $csvContent = "first_name,last_name,email,grade,admission_number\nJohn,Doe,john@example.com,10,STU001";
        $filePath = 'temp/test.csv';
        Storage::put($filePath, $csvContent);
        
        $job = new ProcessCsvImportJob($filePath);
        $job->handle();
        
        $this->assertDatabaseHas('students', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'grade' => '10',
            'admission_number' => 'STU001'
        ]);
    }

    public function test_job_handles_duplicate_admission_numbers()
    {
        // Create existing student
        Student::factory()->create(['admission_number' => 'STU001']);
        
        $csvContent = "first_name,last_name,email,grade,admission_number\nJane,Smith,jane@example.com,10,STU001";
        $filePath = 'temp/test.csv';
        Storage::put($filePath, $csvContent);
        
        $job = new ProcessCsvImportJob($filePath, ['skip_duplicates' => true]);
        $job->handle();
        
        // Should not create duplicate
        $this->assertEquals(1, Student::where('admission_number', 'STU001')->count());
    }

    public function test_job_validates_required_fields()
    {
        $csvContent = "first_name,last_name\nJohn,Doe"; // Missing email
        $filePath = 'temp/test.csv';
        Storage::put($filePath, $csvContent);
        
        $this->expectException(\Exception::class);
        
        $job = new ProcessCsvImportJob($filePath);
        $job->handle();
    }

    public function test_job_handles_empty_file()
    {
        $filePath = 'temp/empty.csv';
        Storage::put($filePath, '');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CSV file is empty');
        
        $job = new ProcessCsvImportJob($filePath);
        $job->handle();
    }

    public function test_job_parses_dates_correctly()
    {
        $csvContent = "first_name,last_name,email,grade,admission_number,date_of_birth\nJohn,Doe,john@example.com,10,STU001,2005-06-15";
        $filePath = 'temp/test.csv';
        Storage::put($filePath, $csvContent);
        
        $job = new ProcessCsvImportJob($filePath);
        $job->handle();
        
        $this->assertDatabaseHas('students', [
            'first_name' => 'John',
            'date_of_birth' => '2005-06-15'
        ]);
    }

    public function test_job_generates_admission_number_when_missing()
    {
        $csvContent = "first_name,last_name,email,grade\nJohn,Doe,john@example.com,10";
        $filePath = 'temp/test.csv';
        Storage::put($filePath, $csvContent);
        
        $job = new ProcessCsvImportJob($filePath);
        $job->handle();
        
        $student = Student::where('first_name', 'John')->first();
        $this->assertNotNull($student);
        $this->assertNotEmpty($student->admission_number);
        $this->assertStringStartsWith('STU', $student->admission_number);
    }

    public function test_job_handles_json_fields()
    {
        $csvContent = "first_name,last_name,email,grade,admission_number,medical_conditions,allergies\nJohn,Doe,john@example.com,10,STU001,asthma;diabetes,peanuts;shellfish";
        $filePath = 'temp/test.csv';
        Storage::put($filePath, $csvContent);
        
        $job = new ProcessCsvImportJob($filePath);
        $job->handle();
        
        $student = Student::where('admission_number', 'STU001')->first();
        $this->assertEquals(['asthma', 'diabetes'], $student->medical_conditions);
        $this->assertEquals(['peanuts', 'shellfish'], $student->allergies);
    }

    public function test_job_cleans_up_file_after_processing()
    {
        $csvContent = "first_name,last_name,email,grade,admission_number\nJohn,Doe,john@example.com,10,STU001";
        $filePath = 'temp/test.csv';
        Storage::put($filePath, $csvContent);
        
        $this->assertTrue(Storage::exists($filePath));
        
        $job = new ProcessCsvImportJob($filePath);
        $job->handle();
        
        $this->assertFalse(Storage::exists($filePath));
    }
}