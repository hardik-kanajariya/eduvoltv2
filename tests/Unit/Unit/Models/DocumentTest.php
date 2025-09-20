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

    public function test_document_casts_attributes_correctly(): void
    {
        $expectedCasts = [
            'id' => 'int',
            'is_sensitive' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
            'access_permissions' => 'array',
            'metadata' => 'array',
            'file_size' => 'integer',
            'version' => 'integer',
            'category' => DocumentCategory::class,
            'status' => DocumentStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

        $document = new Document();
        $this->assertEquals($expectedCasts, $document->getCasts());
    }

    public function test_document_has_default_attributes(): void
    {
        $document = new Document();

        $this->assertEquals(1, $document->version);
        $this->assertEquals('active', $document->status);
        $this->assertFalse($document->is_sensitive);
        $this->assertFalse($document->is_verified);
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

    public function test_document_belongs_to_uploader(): void
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

        $this->assertInstanceOf(User::class, $document->uploader);
        $this->assertEquals($user->id, $document->uploader->id);
    }

    public function test_category_label_accessor(): void
    {
        $document = new Document();
        $document->category = DocumentCategory::ACADEMIC_RECORDS;

        $this->assertEquals('Academic Records', $document->category_label);
    }

    public function test_status_label_accessor(): void
    {
        $document = new Document();
        $document->status = DocumentStatus::ACTIVE;

        $this->assertEquals('Active', $document->status_label);
    }

    public function test_file_size_human_accessor(): void
    {
        $document = new Document();

        // Test bytes
        $document->file_size = 512;
        $this->assertEquals('512 B', $document->file_size_human);

        // Test kilobytes
        $document->file_size = 1536; // 1.5 KB
        $this->assertEquals('1.5 KB', $document->file_size_human);

        // Test megabytes
        $document->file_size = 2097152; // 2 MB
        $this->assertEquals('2 MB', $document->file_size_human);
    }

    public function test_is_expired_accessor(): void
    {
        $document = new Document();

        // No expiration date
        $this->assertFalse($document->is_expired);

        // Future expiration
        $document->expires_at = now()->addDay();
        $this->assertFalse($document->is_expired);

        // Past expiration
        $document->expires_at = now()->subDay();
        $this->assertTrue($document->is_expired);
    }

    public function test_active_scope(): void
    {
        $student = Student::factory()->create();
        $user = User::factory()->create();

        // Create active document
        $activeDoc = Document::create([
            'student_id' => $student->id,
            'uploaded_by' => $user->id,
            'title' => 'Active Document',
            'category' => DocumentCategory::ACADEMIC_RECORDS,
            'original_filename' => 'active.pdf',
            'stored_filename' => 'stored_active.pdf',
            'file_path' => 'documents/active.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'file_hash' => 'activehash',
            'status' => DocumentStatus::ACTIVE,
        ]);

        // Create archived document
        Document::create([
            'student_id' => $student->id,
            'uploaded_by' => $user->id,
            'title' => 'Archived Document',
            'category' => DocumentCategory::ACADEMIC_RECORDS,
            'original_filename' => 'archived.pdf',
            'stored_filename' => 'stored_archived.pdf',
            'file_path' => 'documents/archived.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'file_hash' => 'archivedhash',
            'status' => DocumentStatus::ARCHIVED,
        ]);

        $activeDocuments = Document::active()->get();

        $this->assertCount(1, $activeDocuments);
        $this->assertEquals($activeDoc->id, $activeDocuments->first()->id);
    }

    public function test_by_category_scope(): void
    {
        $student = Student::factory()->create();
        $user = User::factory()->create();

        // Create academic document
        $academicDoc = Document::create([
            'student_id' => $student->id,
            'uploaded_by' => $user->id,
            'title' => 'Academic Document',
            'category' => DocumentCategory::ACADEMIC_RECORDS,
            'original_filename' => 'academic.pdf',
            'stored_filename' => 'stored_academic.pdf',
            'file_path' => 'documents/academic.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'file_hash' => 'academichash',
        ]);

        // Create medical document
        Document::create([
            'student_id' => $student->id,
            'uploaded_by' => $user->id,
            'title' => 'Medical Document',
            'category' => DocumentCategory::MEDICAL_DOCUMENTS,
            'original_filename' => 'medical.pdf',
            'stored_filename' => 'stored_medical.pdf',
            'file_path' => 'documents/medical.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'file_hash' => 'medicalhash',
        ]);

        $academicDocs = Document::byCategory(DocumentCategory::ACADEMIC_RECORDS)->get();

        $this->assertCount(1, $academicDocs);
        $this->assertEquals($academicDoc->id, $academicDocs->first()->id);
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

    public function test_archive_and_restore(): void
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
            'status' => DocumentStatus::ACTIVE,
        ]);

        // Test archive
        $document->archive();
        $this->assertEquals(DocumentStatus::ARCHIVED, $document->fresh()->status);

        // Test restore
        $document->restore();
        $this->assertEquals(DocumentStatus::ACTIVE, $document->fresh()->status);
    }
}
Tests\Unit\Unit\Models;

use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $this->assertTrue(true);
    }
}
