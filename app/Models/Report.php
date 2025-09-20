<?php

namespace App\Models;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'category',
        'parameters',
        'fields',
        'output_format',
        'is_scheduled',
        'schedule_frequency',
        'last_generated_at',
        'next_generation_at',
        'created_by',
        'tenant_id',
        'is_active',
        'status',
        'cached_data',
        'cache_expires_at',
    ];

    protected $casts = [
        'type' => ReportType::class,
        'category' => ReportCategory::class,
        'status' => ReportStatus::class,
        'parameters' => 'array',
        'fields' => 'array',
        'cached_data' => 'array',
        'is_scheduled' => 'boolean',
        'is_active' => 'boolean',
        'last_generated_at' => 'datetime',
        'next_generation_at' => 'datetime',
        'cache_expires_at' => 'datetime',
    ];

    protected $attributes = [
        'output_format' => 'html',
        'is_scheduled' => false,
        'is_active' => true,
        'status' => ReportStatus::DRAFT,
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    public function scopeByType($query, ReportType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, ReportCategory $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByStatus($query, ReportStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForUser($query, User $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('teacher')) {
            return $query->whereIn('category', [
                ReportCategory::STUDENT,
                ReportCategory::CLASS,
            ]);
        }

        if ($user->hasRole('parent')) {
            return $query->where('category', ReportCategory::STUDENT);
        }

        return $query->where('created_by', $user->id);
    }

    public function scopeDueForGeneration($query)
    {
        return $query->scheduled()
            ->active()
            ->where('next_generation_at', '<=', now())
            ->where('status', ReportStatus::SCHEDULED);
    }

    // Accessors
    public function getDisplayTypeAttribute(): string
    {
        return $this->type->getDisplayName();
    }

    public function getDisplayCategoryAttribute(): string
    {
        return $this->category->getDisplayName();
    }

    public function getDisplayStatusAttribute(): string
    {
        return $this->status->getDisplayName();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->getColor();
    }

    public function getAvailableFieldsAttribute(): array
    {
        return $this->type->getAvailableFields();
    }

    public function getSelectedFieldsAttribute(): array
    {
        return $this->fields ?? $this->getAvailableFieldsAttribute();
    }

    public function getIsCachedAttribute(): bool
    {
        return $this->cached_data !== null 
            && $this->cache_expires_at 
            && $this->cache_expires_at->isFuture();
    }

    public function getCacheAgeAttribute(): ?string
    {
        if (!$this->last_generated_at) {
            return null;
        }

        return $this->last_generated_at->diffForHumans();
    }

    public function getNextScheduleAttribute(): ?string
    {
        if (!$this->is_scheduled || !$this->next_generation_at) {
            return null;
        }

        return $this->next_generation_at->diffForHumans();
    }

    // Helper Methods
    public function canBeGeneratedBy(User $user): bool
    {
        if (!$this->is_active || !$this->status->isGeneratable()) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        $allowedRoles = $this->category->getAvailableRoles();
        return $user->hasAnyRole($allowedRoles);
    }

    public function canBeEditedBy(User $user): bool
    {
        if (!$this->status->isEditable()) {
            return false;
        }

        return $user->hasRole('admin') || $this->created_by === $user->id;
    }

    public function canBeScheduledBy(User $user): bool
    {
        if (!$this->status->canBeScheduled()) {
            return false;
        }

        return $user->hasRole('admin') || $this->created_by === $user->id;
    }

    public function markAsGenerating(): void
    {
        $this->update(['status' => ReportStatus::GENERATING]);
    }

    public function markAsCompleted(array $data = null): void
    {
        $updateData = [
            'status' => ReportStatus::COMPLETED,
            'last_generated_at' => now(),
        ];

        if ($data !== null) {
            $updateData['cached_data'] = $data;
            $updateData['cache_expires_at'] = now()->addHours(24);
        }

        $this->update($updateData);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => ReportStatus::FAILED]);
    }

    public function clearCache(): void
    {
        $this->update([
            'cached_data' => null,
            'cache_expires_at' => null,
        ]);
    }

    public function updateSchedule(): void
    {
        if (!$this->is_scheduled || !$this->schedule_frequency) {
            return;
        }

        $nextGeneration = match ($this->schedule_frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            default => null,
        };

        if ($nextGeneration) {
            $this->update([
                'next_generation_at' => $nextGeneration,
                'status' => ReportStatus::SCHEDULED,
            ]);
        }
    }

    public static function createFromTemplate(array $template, User $user): self
    {
        return self::create([
            'name' => $template['name'],
            'description' => $template['description'] ?? null,
            'type' => $template['type'],
            'category' => $template['category'],
            'parameters' => $template['parameters'] ?? [],
            'fields' => $template['fields'] ?? null,
            'output_format' => $template['output_format'] ?? 'html',
            'created_by' => $user->id,
            'tenant_id' => $user->tenant_id ?? null,
        ]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (!$report->created_by) {
                $report->created_by = Auth::id();
            }

            if (!$report->tenant_id && Auth::user()?->tenant_id) {
                $report->tenant_id = Auth::user()->tenant_id;
            }
        });
    }
}
