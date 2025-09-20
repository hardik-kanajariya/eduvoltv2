<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::ARCHIVED => 'Archived',
            self::DELETED => 'Deleted',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ACTIVE => 'Document is active and available for viewing',
            self::ARCHIVED => 'Document is archived but can be restored',
            self::DELETED => 'Document has been deleted and is not accessible',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::ARCHIVED => 'yellow',
            self::DELETED => 'red',
        };
    }

    public function badge(): array
    {
        return [
            'label' => $this->label(),
            'color' => $this->color(),
            'description' => $this->description(),
        ];
    }

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}