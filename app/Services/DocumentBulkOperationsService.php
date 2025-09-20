<?php

namespace App\Services;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DocumentBulkOperationsService
{
    public function __construct(
        private DocumentService $documentService
    ) {}

    /**
     * Bulk upload documents for multiple students.
     */
    public function bulkUpload(
        array $files,
        array $documentData,
        User $uploader
    ): array {
        $results = [
            'successful' => [],
            'failed' => [],
            'total' => count($files),
        ];

        DB::beginTransaction();

        try {
            foreach ($files as $fileData) {
                try {
                    $student = Student::findOrFail($fileData['student_id']);
                    $file = $fileData['file'];
                    
                    $document = $this->documentService->uploadDocument(
                        $student,
                        $file,
                        array_merge($documentData, $fileData['document_data'] ?? []),
                        $uploader
                    );

                    $results['successful'][] = [
                        'student_id' => $student->id,
                        'student_name' => $student->full_name,
                        'document_id' => $document->id,
                        'filename' => $file->getClientOriginalName(),
                    ];

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'student_id' => $fileData['student_id'] ?? null,
                        'filename' => isset($fileData['file']) ? $fileData['file']->getClientOriginalName() : 'Unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info('Bulk document upload completed', [
                'total' => $results['total'],
                'successful' => count($results['successful']),
                'failed' => count($results['failed']),
                'uploaded_by' => $uploader->id,
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk document upload failed', [
                'error' => $e->getMessage(),
                'uploaded_by' => $uploader->id,
            ]);

            throw $e;
        }
    }

    /**
     * Bulk categorization of documents.
     */
    public function bulkCategorize(
        array $documentIds,
        DocumentCategory $newCategory,
        User $user
    ): array {
        $results = [
            'successful' => [],
            'failed' => [],
            'total' => count($documentIds),
        ];

        DB::beginTransaction();

        try {
            foreach ($documentIds as $documentId) {
                try {
                    $document = Document::findOrFail($documentId);
                    
                    // Check if user can update this document
                    if (!$document->hasAccess($user)) {
                        throw new \Exception('Access denied to document');
                    }

                    $oldCategory = $document->category;
                    $document->update(['category' => $newCategory]);

                    $results['successful'][] = [
                        'document_id' => $document->id,
                        'title' => $document->title,
                        'old_category' => $oldCategory->label(),
                        'new_category' => $newCategory->label(),
                    ];

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'document_id' => $documentId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info('Bulk document categorization completed', [
                'total' => $results['total'],
                'successful' => count($results['successful']),
                'failed' => count($results['failed']),
                'new_category' => $newCategory->value,
                'updated_by' => $user->id,
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk document categorization failed', [
                'error' => $e->getMessage(),
                'updated_by' => $user->id,
            ]);

            throw $e;
        }
    }

    /**
     * Bulk delete documents.
     */
    public function bulkDelete(array $documentIds, User $user): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'total' => count($documentIds),
        ];

        DB::beginTransaction();

        try {
            foreach ($documentIds as $documentId) {
                try {
                    $document = Document::findOrFail($documentId);
                    
                    // Check if user can delete this document
                    if (!$document->hasAccess($user)) {
                        throw new \Exception('Access denied to document');
                    }

                    $this->documentService->deleteDocument($document, $user);

                    $results['successful'][] = [
                        'document_id' => $document->id,
                        'title' => $document->title,
                        'student_name' => $document->student->full_name,
                    ];

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'document_id' => $documentId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info('Bulk document deletion completed', [
                'total' => $results['total'],
                'successful' => count($results['successful']),
                'failed' => count($results['failed']),
                'deleted_by' => $user->id,
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk document deletion failed', [
                'error' => $e->getMessage(),
                'deleted_by' => $user->id,
            ]);

            throw $e;
        }
    }

    /**
     * Bulk archive documents.
     */
    public function bulkArchive(array $documentIds, User $user): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'total' => count($documentIds),
        ];

        DB::beginTransaction();

        try {
            foreach ($documentIds as $documentId) {
                try {
                    $document = Document::findOrFail($documentId);
                    
                    if (!$document->hasAccess($user)) {
                        throw new \Exception('Access denied to document');
                    }

                    $this->documentService->archiveDocument($document);

                    $results['successful'][] = [
                        'document_id' => $document->id,
                        'title' => $document->title,
                    ];

                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'document_id' => $documentId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info('Bulk document archiving completed', [
                'total' => $results['total'],
                'successful' => count($results['successful']),
                'failed' => count($results['failed']),
                'archived_by' => $user->id,
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk document archiving failed', [
                'error' => $e->getMessage(),
                'archived_by' => $user->id,
            ]);

            throw $e;
        }
    }

    /**
     * Create a ZIP file containing multiple documents for download.
     */
    public function createBulkDownload(array $documentIds, User $user): string
    {
        // Validate access to all documents
        $documents = Document::whereIn('id', $documentIds)->get();
        
        foreach ($documents as $document) {
            if (!$document->hasAccess($user)) {
                throw new \Exception("Access denied to document: {$document->title}");
            }
        }

        // Create temporary ZIP file
        $zipFilename = 'bulk_download_' . now()->format('Y-m-d_H-i-s') . '_' . uniqid() . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFilename);
        
        // Ensure temp directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Cannot create ZIP file');
        }

        try {
            foreach ($documents as $document) {
                $filePath = Storage::disk('private')->path($document->file_path);
                
                if (file_exists($filePath)) {
                    // Create a folder structure in ZIP: student_name/category/filename
                    $studentName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $document->student->full_name);
                    $category = $document->category->label();
                    $filename = $document->original_filename;
                    
                    $zipEntryName = "{$studentName}/{$category}/{$filename}";
                    
                    // Handle duplicate filenames
                    $counter = 1;
                    $originalZipEntryName = $zipEntryName;
                    while ($zip->locateName($zipEntryName) !== false) {
                        $pathInfo = pathinfo($originalZipEntryName);
                        $zipEntryName = $pathInfo['dirname'] . '/' . 
                            $pathInfo['filename'] . "_({$counter})." . 
                            ($pathInfo['extension'] ?? '');
                        $counter++;
                    }
                    
                    $zip->addFile($filePath, $zipEntryName);
                }
            }

            $zip->close();

            Log::info('Bulk download ZIP created', [
                'zip_file' => $zipFilename,
                'document_count' => count($documents),
                'requested_by' => $user->id,
            ]);

            return $zipFilename;

        } catch (\Exception $e) {
            $zip->close();
            
            // Clean up failed ZIP file
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            
            Log::error('Bulk download ZIP creation failed', [
                'error' => $e->getMessage(),
                'requested_by' => $user->id,
            ]);

            throw $e;
        }
    }

    /**
     * Get bulk download file path.
     */
    public function getBulkDownloadPath(string $filename): string
    {
        $filePath = storage_path('app/temp/' . $filename);
        
        if (!file_exists($filePath)) {
            throw new \Exception('Download file not found or has expired');
        }

        return $filePath;
    }

    /**
     * Clean up expired bulk download files.
     */
    public function cleanupExpiredDownloads(): int
    {
        $tempDir = storage_path('app/temp');
        $cleanedCount = 0;
        
        if (!is_dir($tempDir)) {
            return 0;
        }

        $files = glob($tempDir . '/bulk_download_*.zip');
        $expireTime = now()->subHours(2)->timestamp; // Files expire after 2 hours

        foreach ($files as $file) {
            if (filemtime($file) < $expireTime) {
                unlink($file);
                $cleanedCount++;
            }
        }

        Log::info('Cleaned up expired bulk download files', [
            'count' => $cleanedCount,
        ]);

        return $cleanedCount;
    }

    /**
     * Get statistics for bulk operations.
     */
    public function getBulkOperationStats(User $user, \Carbon\Carbon $since = null): array
    {
        $since = $since ?? now()->subDays(30);

        $baseQuery = Document::where('created_at', '>=', $since);

        // Apply user-specific filtering based on role
        if ($user->hasRole('Admin')) {
            // Admin can see all stats
        } elseif ($user->hasRole('Teacher')) {
            // Teacher can see stats for their students
            $baseQuery->whereHas('student.classes.teachers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        } elseif ($user->hasRole('Student')) {
            // Student can see their own stats
            $baseQuery->whereHas('student', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        } elseif ($user->hasRole('Parent')) {
            // Parent can see their children's stats
            $studentIds = $user->students()->pluck('id');
            $baseQuery->whereIn('student_id', $studentIds);
        } else {
            // No access for other roles
            $baseQuery->whereRaw('1 = 0');
        }

        return [
            'total_documents' => $baseQuery->count(),
            'by_category' => $baseQuery->groupBy('category')
                ->selectRaw('category, count(*) as count')
                ->pluck('count', 'category')
                ->toArray(),
            'by_status' => $baseQuery->groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray(),
            'verified_count' => $baseQuery->where('is_verified', true)->count(),
            'sensitive_count' => $baseQuery->where('is_sensitive', true)->count(),
            'total_size_mb' => round($baseQuery->sum('file_size') / (1024 * 1024), 2),
            'period' => [
                'from' => $since->toDateString(),
                'to' => now()->toDateString(),
            ],
        ];
    }
}