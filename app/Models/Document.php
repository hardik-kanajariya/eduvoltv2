<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
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

    protected $casts = [
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
    ];

    protected $attributes = [
        'version' => 1,
        'status' => 'active',
        'is_sensitive' => false,
        'is_verified' => false,
    ];

    /**
     * Relationships
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_document_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, DocumentCategory|string $category)
    {
        if ($category instanceof DocumentCategory) {
            return $query->where('category', $category->value);
        }

        return $query->where('category', $category);
    }

    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Accessors
     */
    public function getCategoryLabelAttribute(): string
    {
        return $this->category?->label() ?? 'Unknown';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? 'Unknown';
    }

    public function getFileSizeHumanAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('documents.download', $this->id);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsLatestVersionAttribute(): bool
    {
        if (!$this->parent_document_id) {
            return !$this->versions()->exists();
        }

        return false;
    }

    /**
     * Model events
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            // Delete physical file when model is deleted
            if ($document->file_path && Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }

            // Delete all versions
            $document->versions()->delete();
        });
    }

    /**
     * Helper methods
     */
    public function markAsVerified(User $verifier): void
    {
        $this->update([
            'is_verified' => true,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    public function restore(): void
    {
        $this->update(['status' => 'active']);
    }

    public function hasAccess(User $user): bool
    {
        // Admin has access to all documents
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Owner student has access to their documents
        if ($user->hasRole('Student') && $this->student->user_id === $user->id) {
            return true;
        }

        // Parent has access to their children's documents
        if ($user->hasRole('Parent')) {
            $studentIds = $user->students()->pluck('id')->toArray();
            return in_array($this->student_id, $studentIds);
        }

        // Teachers have access to documents of students in their classes
        if ($user->hasRole('Teacher')) {
            // This would need to be implemented based on your class/teacher relationship
            return $this->student->classes()
                ->whereHas('teachers', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->exists();
        }

        // Uploader has access to documents they uploaded
        if ($this->uploaded_by === $user->id) {
            return true;
        }

        // Check custom access permissions
        if ($this->access_permissions) {
            return in_array($user->id, $this->access_permissions['user_ids'] ?? []) ||
                !empty(array_intersect($user->getRoleNames()->toArray(), $this->access_permissions['roles'] ?? []));
        }

        return false;
    }

    public function createNewVersion(array $fileData): self
    {
        $newVersion = new self($fileData);
        $newVersion->parent_document_id = $this->id;
        $newVersion->version = $this->getLatestVersionNumber() + 1;
        $newVersion->student_id = $this->student_id;
        $newVersion->save();

        return $newVersion;
    }

    private function getLatestVersionNumber(): int
    {
        $latestVersion = $this->versions()->orderBy('version', 'desc')->first();
        return $latestVersion ? $latestVersion->version : $this->version;
    }
}
