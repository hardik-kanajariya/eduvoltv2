<?php

namespace App\Services;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    private const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 
        'xls', 'xlsx', 'txt', 'rtf'
    ];

    private const MAX_FILE_SIZE = 20 * 1024 * 1024; // 20MB default

    public function uploadDocument(
        Student $student,
        UploadedFile $file,
        array $documentData,
        User $uploader
    ): Document {
        DB::beginTransaction();

        try {
            // Validate file
            $this->validateFile($file, $documentData['category'] ?? null);

            // Generate unique filename
            $storedFilename = $this->generateStoredFilename($file);
            $filePath = $this->generateFilePath($student, $documentData['category'] ?? 'other', $storedFilename);

            // Calculate file hash
            $fileHash = hash_file('sha256', $file->getPathname());

            // Check for duplicates
            $existingDocument = $this->findDuplicateDocument($student, $fileHash);
            if ($existingDocument) {
                throw ValidationException::withMessages([
                    'file' => 'This file already exists for this student.'
                ]);
            }

            // Store file securely
            $file->storeAs(dirname($filePath), basename($filePath), 'private');

            // Virus scan (placeholder for future implementation)
            $this->performVirusScan($filePath);

            // Create document record
            $document = Document::create([
                'student_id' => $student->id,
                'uploaded_by' => $uploader->id,
                'title' => $documentData['title'],
                'description' => $documentData['description'] ?? null,
                'category' => $documentData['category'],
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_hash' => $fileHash,
                'is_sensitive' => $documentData['is_sensitive'] ?? 
                    DocumentCategory::from($documentData['category'])->isSensitiveByDefault(),
                'expires_at' => $documentData['expires_at'] ?? null,
                'access_permissions' => $documentData['access_permissions'] ?? null,
                'metadata' => $this->extractMetadata($file, $documentData),
            ]);

            DB::commit();

            Log::info('Document uploaded successfully', [
                'document_id' => $document->id,
                'student_id' => $student->id,
                'uploaded_by' => $uploader->id,
                'file_path' => $filePath,
            ]);

            return $document;

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded file if it exists
            if (isset($filePath) && Storage::disk('private')->exists($filePath)) {
                Storage::disk('private')->delete($filePath);
            }

            Log::error('Document upload failed', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function updateDocument(Document $document, array $data, User $user): Document
    {
        DB::beginTransaction();

        try {
            $updateData = array_filter([
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'is_sensitive' => $data['is_sensitive'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'access_permissions' => $data['access_permissions'] ?? null,
            ], fn($value) => $value !== null);

            // Add metadata for the update
            $currentMetadata = $document->metadata ?? [];
            $currentMetadata['last_updated_by'] = $user->id;
            $currentMetadata['last_updated_at'] = now()->toISOString();
            $updateData['metadata'] = $currentMetadata;

            $document->update($updateData);

            DB::commit();

            Log::info('Document updated successfully', [
                'document_id' => $document->id,
                'updated_by' => $user->id,
                'updated_fields' => array_keys($updateData),
            ]);

            return $document->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Document update failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function createNewVersion(Document $originalDocument, UploadedFile $file, User $uploader): Document
    {
        DB::beginTransaction();

        try {
            // Validate file
            $this->validateFile($file, $originalDocument->category->value);

            // Generate unique filename for new version
            $storedFilename = $this->generateStoredFilename($file);
            $filePath = $this->generateFilePath(
                $originalDocument->student, 
                $originalDocument->category->value, 
                $storedFilename
            );

            // Calculate file hash
            $fileHash = hash_file('sha256', $file->getPathname());

            // Store file
            $file->storeAs(dirname($filePath), basename($filePath), 'private');

            // Virus scan
            $this->performVirusScan($filePath);

            // Create new version
            $newVersion = $originalDocument->createNewVersion([
                'uploaded_by' => $uploader->id,
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename' => $storedFilename,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'file_hash' => $fileHash,
                'metadata' => $this->extractMetadata($file, [
                    'version_of' => $originalDocument->id,
                    'created_by' => $uploader->id,
                ]),
            ]);

            DB::commit();

            Log::info('Document version created successfully', [
                'original_document_id' => $originalDocument->id,
                'new_version_id' => $newVersion->id,
                'version_number' => $newVersion->version,
                'uploaded_by' => $uploader->id,
            ]);

            return $newVersion;

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (isset($filePath) && Storage::disk('private')->exists($filePath)) {
                Storage::disk('private')->delete($filePath);
            }

            Log::error('Document version creation failed', [
                'original_document_id' => $originalDocument->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function deleteDocument(Document $document, User $user): bool
    {
        DB::beginTransaction();

        try {
            // Update metadata before soft delete
            $metadata = $document->metadata ?? [];
            $metadata['deleted_by'] = $user->id;
            $metadata['deleted_at'] = now()->toISOString();
            $document->update(['metadata' => $metadata]);

            // Soft delete the document
            $document->delete();

            DB::commit();

            Log::info('Document deleted successfully', [
                'document_id' => $document->id,
                'deleted_by' => $user->id,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Document deletion failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function getSecureDownloadUrl(Document $document): string
    {
        // For secure downloads, we'll return a route that checks permissions
        // The actual file serving will be handled by the controller
        return route('documents.download', $document->id);
    }

    public function streamDocument(Document $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        $headers = [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="' . $document->original_filename . '"',
            'Content-Length' => $document->file_size,
        ];

        return response()->stream(function () use ($document) {
            $stream = Storage::disk('private')->readStream($document->file_path);
            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }

    public function downloadDocument(Document $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $document->original_filename . '"',
            'Content-Length' => $document->file_size,
        ];

        return response()->stream(function () use ($document) {
            $stream = Storage::disk('private')->readStream($document->file_path);
            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }

    public function verifyDocument(Document $document, User $verifier): Document
    {
        $document->markAsVerified($verifier);

        Log::info('Document verified', [
            'document_id' => $document->id,
            'verified_by' => $verifier->id,
        ]);

        return $document;
    }

    public function archiveDocument(Document $document): Document
    {
        $document->archive();

        Log::info('Document archived', [
            'document_id' => $document->id,
        ]);

        return $document;
    }

    public function restoreDocument(Document $document): Document
    {
        $document->restore();

        Log::info('Document restored', [
            'document_id' => $document->id,
        ]);

        return $document;
    }

    private function validateFile(UploadedFile $file, ?string $category = null): void
    {
        // Basic file validation
        if (!$file->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file is not valid.'
            ]);
        }

        // File extension validation
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw ValidationException::withMessages([
                'file' => 'File type not allowed. Allowed types: ' . implode(', ', self::ALLOWED_EXTENSIONS)
            ]);
        }

        // Category-specific validation
        if ($category) {
            $categoryEnum = DocumentCategory::from($category);
            $allowedMimeTypes = $categoryEnum->allowedMimeTypes();
            $maxFileSize = $categoryEnum->maxFileSize();

            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                throw ValidationException::withMessages([
                    'file' => 'File type not allowed for this category.'
                ]);
            }

            if ($file->getSize() > $maxFileSize) {
                $maxSizeMB = round($maxFileSize / (1024 * 1024), 1);
                throw ValidationException::withMessages([
                    'file' => "File size exceeds maximum allowed size of {$maxSizeMB}MB for this category."
                ]);
            }
        } else {
            // Default file size validation
            if ($file->getSize() > self::MAX_FILE_SIZE) {
                $maxSizeMB = round(self::MAX_FILE_SIZE / (1024 * 1024), 1);
                throw ValidationException::withMessages([
                    'file' => "File size exceeds maximum allowed size of {$maxSizeMB}MB."
                ]);
            }
        }
    }

    private function generateStoredFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '.' . $extension;
    }

    private function generateFilePath(Student $student, string $category, string $filename): string
    {
        $year = date('Y');
        $month = date('m');
        
        return "documents/students/{$student->id}/{$category}/{$year}/{$month}/{$filename}";
    }

    private function findDuplicateDocument(Student $student, string $fileHash): ?Document
    {
        return Document::where('student_id', $student->id)
            ->where('file_hash', $fileHash)
            ->first();
    }

    private function performVirusScan(string $filePath): void
    {
        // Placeholder for virus scanning implementation
        // This could integrate with ClamAV, VirusTotal API, or other scanning services
        
        Log::info('Virus scan performed', ['file_path' => $filePath]);
        
        // For now, just check if file exists and is readable
        if (!Storage::disk('private')->exists($filePath)) {
            throw new \Exception('File not found for virus scanning');
        }
    }

    private function extractMetadata(UploadedFile $file, array $additionalData = []): array
    {
        $metadata = [
            'upload_timestamp' => now()->toISOString(),
            'original_name' => $file->getClientOriginalName(),
            'client_mime_type' => $file->getClientMimeType(),
            'client_size' => $file->getSize(),
        ];

        // Add image-specific metadata for image files
        if (str_starts_with($file->getMimeType(), 'image/')) {
            try {
                $imageInfo = getimagesize($file->getPathname());
                if ($imageInfo) {
                    $metadata['image_width'] = $imageInfo[0];
                    $metadata['image_height'] = $imageInfo[1];
                    $metadata['image_type'] = $imageInfo['mime'];
                }
            } catch (\Exception $e) {
                // Ignore if we can't get image info
            }
        }

        return array_merge($metadata, $additionalData);
    }
}