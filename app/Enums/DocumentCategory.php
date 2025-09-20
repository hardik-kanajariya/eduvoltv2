<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case ACADEMIC_RECORDS = 'academic_records';
    case MEDICAL_DOCUMENTS = 'medical_documents';
    case ID_DOCUMENTS = 'id_documents';
    case CERTIFICATES = 'certificates';
    case REPORTS = 'reports';
    case FORMS = 'forms';
    case PHOTOS = 'photos';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ACADEMIC_RECORDS => 'Academic Records',
            self::MEDICAL_DOCUMENTS => 'Medical Documents',
            self::ID_DOCUMENTS => 'ID Documents',
            self::CERTIFICATES => 'Certificates',
            self::REPORTS => 'Reports',
            self::FORMS => 'Forms',
            self::PHOTOS => 'Photos',
            self::OTHER => 'Other',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ACADEMIC_RECORDS => 'Transcripts, report cards, certificates, and other academic documents',
            self::MEDICAL_DOCUMENTS => 'Medical records, vaccination certificates, health reports',
            self::ID_DOCUMENTS => 'Birth certificates, passports, national ID cards',
            self::CERTIFICATES => 'Awards, achievements, completion certificates',
            self::REPORTS => 'Assessment reports, behavioral reports, progress reports',
            self::FORMS => 'Application forms, consent forms, permission slips',
            self::PHOTOS => 'Profile photos, event photos, identification photos',
            self::OTHER => 'Other miscellaneous documents',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACADEMIC_RECORDS => 'academic-cap',
            self::MEDICAL_DOCUMENTS => 'heart',
            self::ID_DOCUMENTS => 'identification',
            self::CERTIFICATES => 'award',
            self::REPORTS => 'document-text',
            self::FORMS => 'clipboard-document-list',
            self::PHOTOS => 'camera',
            self::OTHER => 'document',
        };
    }

    public function allowedMimeTypes(): array
    {
        return match ($this) {
            self::ACADEMIC_RECORDS => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
            ],
            self::MEDICAL_DOCUMENTS => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            self::ID_DOCUMENTS => [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ],
            self::CERTIFICATES => [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ],
            self::REPORTS => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            self::FORMS => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
            ],
            self::PHOTOS => [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ],
            self::OTHER => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
                'text/plain',
            ],
        };
    }

    public function maxFileSize(): int
    {
        return match ($this) {
            self::PHOTOS => 5 * 1024 * 1024, // 5MB for photos
            self::OTHER => 10 * 1024 * 1024, // 10MB for others
            default => 20 * 1024 * 1024, // 20MB for documents
        };
    }

    public function isSensitiveByDefault(): bool
    {
        return match ($this) {
            self::MEDICAL_DOCUMENTS, self::ID_DOCUMENTS => true,
            default => false,
        };
    }

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'icon' => $case->icon(),
        ], self::cases());
    }
}
