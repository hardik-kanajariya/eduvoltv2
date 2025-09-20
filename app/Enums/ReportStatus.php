<?php

namespace App\Enums;

enum ReportStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case SCHEDULED = 'scheduled';
    case GENERATING = 'generating';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case ARCHIVED = 'archived';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::SCHEDULED => 'Scheduled',
            self::GENERATING => 'Generating',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::ARCHIVED => 'Archived',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'blue',
            self::SCHEDULED => 'yellow',
            self::GENERATING => 'indigo',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::ARCHIVED => 'gray',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DRAFT => 'Report is being configured and not yet available',
            self::PUBLISHED => 'Report is available for viewing and generation',
            self::SCHEDULED => 'Report is scheduled for automatic generation',
            self::GENERATING => 'Report is currently being generated',
            self::COMPLETED => 'Report generation completed successfully',
            self::FAILED => 'Report generation failed - check logs for details',
            self::ARCHIVED => 'Report has been archived and is read-only',
        };
    }

    public function isGeneratable(): bool
    {
        return in_array($this, [self::PUBLISHED, self::SCHEDULED]);
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::FAILED]);
    }

    public function canBeScheduled(): bool
    {
        return in_array($this, [self::PUBLISHED, self::COMPLETED]);
    }
}