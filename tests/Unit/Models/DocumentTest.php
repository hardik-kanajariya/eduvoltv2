<?php

namespace Tests\Unit\Models;

use App\Enums\DocumentCategory;
use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_has_correct_fillable_attributes(): void
    {
        $expectedFillable = [
            'student_id',
            'uploaded_by',
            'title',
            'description',
            'category',
            'original_filename',
            'stored_filename',
            'file_path',
            'mime_type',
            'file_size',
            'file_hash',
            'version',
            'parent_document_id',
            'is_sensitive',
            'is_verified',
            'verified_by',
            'verified_at',
            'status',
            'expires_at',
            'access_permissions',
            'metadata',
        ];

        $document = new Document();
        $this->assertEquals($expectedFillable, $document->getFillable());
    }

    public function test_document_belongs_to_student(): void
    {
        $student = Student::factory()->create();
        $user = User::factory()->create();
        
        $document = Document::create([
            'student_id' => $student->id,
            'uploaded_by' => $user->id,
            'title' => 'Test Document',
            'category' => DocumentCategory::ACADEMIC_RECORDS,
            'original_filename' => 'test.pdf',
            'stored_filename' => 'stored_test.pdf',
            'file_path' => 'documents/test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'file_hash' => 'testhash',
        ]);

        $this->assertInstanceOf(Student::class, $document->student);
        $this->assertEquals($student->id, $document->student->id);
    }

    public function test_mark_as_verified(): void
    {
        $student = Student::factory()->create();
        $user = User::factory()->create();
        $verifier = User::factory()->create();
        
        $document = Document::create([
            'student_id' => $student->id,
            'uploaded_by' => $user->id,
            'title' => 'Test Document',
            'category' => DocumentCategory::ACADEMIC_RECORDS,
            'original_filename' => 'test.pdf',
            'stored_filename' => 'stored_test.pdf',
            'file_path' => 'documents/test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'file_hash' => 'testhash',
            'is_verified' => false,
        ]);

        $this->assertFalse($document->is_verified);
        $this->assertNull($document->verified_by);
        $this->assertNull($document->verified_at);

        $document->markAsVerified($verifier);

        $this->assertTrue($document->fresh()->is_verified);
        $this->assertEquals($verifier->id, $document->fresh()->verified_by);
        $this->assertNotNull($document->fresh()->verified_at);
    }
}
