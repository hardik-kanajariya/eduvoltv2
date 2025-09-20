<?php

namespace App\Http\Controllers;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Student;
use App\Services\DocumentService;
use App\Services\DocumentBulkOperationsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private DocumentBulkOperationsService $bulkService
    ) {}

    /**
     * Display a listing of documents for a student.
     */
    public function index(Request $request, Student $student): JsonResponse
    {
        Gate::authorize('viewAny', [Document::class, $student]);

        $query = $student->documents()
            ->with(['uploader:id,name', 'verifier:id,name'])
            ->active();

        // Apply filters
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('is_verified')) {
            if ($request->boolean('is_verified')) {
                $query->verified();
            } else {
                $query->where('is_verified', false);
            }
        }

        if ($request->filled('is_sensitive')) {
            $query->where('is_sensitive', $request->boolean('is_sensitive'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('original_filename', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $documents = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $documents,
            'categories' => DocumentCategory::options(),
        ]);
    }

    /**
     * Store a newly created document.
     */
    public function store(Request $request, Student $student): JsonResponse
    {
        Gate::authorize('create', [Document::class, $student]);

        $validated = $request->validate([
            'file' => 'required|file|max:20480', // 20MB max
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => ['required', new Enum(DocumentCategory::class)],
            'is_sensitive' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'access_permissions' => 'nullable|array',
            'access_permissions.user_ids' => 'nullable|array',
            'access_permissions.user_ids.*' => 'exists:users,id',
            'access_permissions.roles' => 'nullable|array',
            'access_permissions.roles.*' => 'string',
        ]);

        try {
            $document = $this->documentService->uploadDocument(
                $student,
                $request->file('file'),
                $validated,
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully.',
                'data' => $document->load(['uploader:id,name']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified document metadata.
     */
    public function show(Document $document): JsonResponse
    {
        Gate::authorize('view', $document);

        $document->load([
            'student:id,first_name,last_name,student_id',
            'uploader:id,name',
            'verifier:id,name',
            'parentDocument:id,title,version',
            'versions:id,title,version,created_at',
        ]);

        return response()->json([
            'success' => true,
            'data' => $document,
        ]);
    }

    /**
     * Update the specified document.
     */
    public function update(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('update', $document);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => ['sometimes', 'required', new Enum(DocumentCategory::class)],
            'is_sensitive' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'access_permissions' => 'nullable|array',
        ]);

        try {
            $updatedDocument = $this->documentService->updateDocument(
                $document,
                $validated,
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully.',
                'data' => $updatedDocument,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified document.
     */
    public function destroy(Document $document): JsonResponse
    {
        Gate::authorize('delete', $document);

        try {
            $this->documentService->deleteDocument($document, Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download or stream the document file.
     */
    public function download(Document $document, Request $request): StreamedResponse
    {
        Gate::authorize('view', $document);

        $action = $request->get('action', 'download'); // 'download' or 'view'

        if ($action === 'view') {
            return $this->documentService->streamDocument($document);
        }

        return $this->documentService->downloadDocument($document);
    }

    /**
     * Create a new version of an existing document.
     */
    public function createVersion(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('update', $document);

        $validated = $request->validate([
            'file' => 'required|file|max:20480', // 20MB max
        ]);

        try {
            $newVersion = $this->documentService->createNewVersion(
                $document,
                $request->file('file'),
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'New document version created successfully.',
                'data' => $newVersion->load(['uploader:id,name']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create new version.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verify a document.
     */
    public function verify(Document $document): JsonResponse
    {
        Gate::authorize('verify', $document);

        try {
            $verifiedDocument = $this->documentService->verifyDocument(
                $document,
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Document verified successfully.',
                'data' => $verifiedDocument,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify document.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Archive a document.
     */
    public function archive(Document $document): JsonResponse
    {
        Gate::authorize('update', $document);

        try {
            $archivedDocument = $this->documentService->archiveDocument($document);

            return response()->json([
                'success' => true,
                'message' => 'Document archived successfully.',
                'data' => $archivedDocument,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive document.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Restore an archived document.
     */
    public function restore(Document $document): JsonResponse
    {
        Gate::authorize('update', $document);

        try {
            $restoredDocument = $this->documentService->restoreDocument($document);

            return response()->json([
                'success' => true,
                'message' => 'Document restored successfully.',
                'data' => $restoredDocument,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore document.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get document categories and their information.
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DocumentCategory::options(),
        ]);
    }

    /**
     * Get upload progress for large files (WebSocket/polling endpoint).
     */
    public function uploadProgress(Request $request): JsonResponse
    {
        $uploadId = $request->get('upload_id');

        // This would integrate with a file upload progress tracking system
        // For now, return a placeholder response

        return response()->json([
            'success' => true,
            'data' => [
                'upload_id' => $uploadId,
                'progress' => 100, // Percentage
                'status' => 'completed',
                'message' => 'Upload completed successfully',
            ],
        ]);
    }

    /**
     * Bulk upload documents for multiple students.
     */
    public function bulkUpload(Request $request): JsonResponse
    {
        Gate::authorize('create', Document::class);

        $validated = $request->validate([
            'files' => 'required|array|min:1',
            'files.*.student_id' => 'required|exists:students,id',
            'files.*.file' => 'required|file|max:20480',
            'files.*.document_data' => 'nullable|array',
            'files.*.document_data.title' => 'required|string|max:255',
            'files.*.document_data.description' => 'nullable|string|max:1000',
            'files.*.document_data.category' => ['required', new Enum(DocumentCategory::class)],
            'files.*.document_data.is_sensitive' => 'boolean',
            'global_settings' => 'nullable|array',
        ]);

        try {
            $results = $this->bulkService->bulkUpload(
                $validated['files'],
                $validated['global_settings'] ?? [],
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk upload completed.',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk upload failed.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bulk categorize documents.
     */
    public function bulkCategorize(Request $request): JsonResponse
    {
        Gate::authorize('update', Document::class);

        $validated = $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'exists:documents,id',
            'category' => ['required', new Enum(DocumentCategory::class)],
        ]);

        try {
            $results = $this->bulkService->bulkCategorize(
                $validated['document_ids'],
                DocumentCategory::from($validated['category']),
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk categorization completed.',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk categorization failed.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bulk delete documents.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        Gate::authorize('delete', Document::class);

        $validated = $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'exists:documents,id',
        ]);

        try {
            $results = $this->bulkService->bulkDelete(
                $validated['document_ids'],
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk deletion completed.',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk deletion failed.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bulk archive documents.
     */
    public function bulkArchive(Request $request): JsonResponse
    {
        Gate::authorize('update', Document::class);

        $validated = $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'exists:documents,id',
        ]);

        try {
            $results = $this->bulkService->bulkArchive(
                $validated['document_ids'],
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk archiving completed.',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk archiving failed.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create bulk download (ZIP file) of multiple documents.
     */
    public function bulkDownload(Request $request): JsonResponse
    {
        Gate::authorize('view', Document::class);

        $validated = $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'exists:documents,id',
        ]);

        try {
            $zipFilename = $this->bulkService->createBulkDownload(
                $validated['document_ids'],
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk download prepared.',
                'data' => [
                    'download_url' => route('documents.bulk.download.file', $zipFilename),
                    'filename' => $zipFilename,
                    'expires_at' => now()->addHours(2)->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk download preparation failed.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download the prepared bulk download file.
     */
    public function downloadBulkFile(string $filename): StreamedResponse
    {
        try {
            $filePath = $this->bulkService->getBulkDownloadPath($filename);

            return response()->stream(function () use ($filePath) {
                readfile($filePath);
            }, 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => filesize($filePath),
            ]);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * Get bulk operation statistics.
     */
    public function bulkStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'since' => 'nullable|date',
        ]);

        try {
            $since = $validated['since'] ? \Carbon\Carbon::parse($validated['since']) : null;
            $stats = $this->bulkService->getBulkOperationStats(Auth::user(), $since);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
