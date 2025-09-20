<?php

namespace App\Enums;

enum ReportCategory: string
{
    case STUDENT = 'student';
    case CLASS = 'class';
    case TEACHER = 'teacher';
    case SCHOOL = 'school';
    case ADMINISTRATIVE = 'administrative';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::STUDENT => 'Student Reports',
            self::CLASS => 'Class Reports',
            self::TEACHER => 'Teacher Reports',
            self::SCHOOL => 'School-wide Reports',
            self::ADMINISTRATIVE => 'Administrative Reports',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::STUDENT => 'Individual student performance, attendance, and profile reports',
            self::CLASS => 'Class-level analytics, performance comparisons, and summaries',
            self::TEACHER => 'Teacher performance, effectiveness, and workload reports',
            self::SCHOOL => 'School-wide analytics, trends, and institutional reports',
            self::ADMINISTRATIVE => 'Administrative, financial, and operational reports',
        };
    }

    public function getPermissionRequired(): string
    {
        return match ($this) {
            self::STUDENT => 'view-student-reports',
            self::CLASS => 'view-class-reports',
            self::TEACHER => 'view-teacher-reports',
            self::SCHOOL => 'view-school-reports',
            self::ADMINISTRATIVE => 'view-admin-reports',
        };
    }

    public function getAvailableRoles(): array
    {
        return match ($this) {
            self::STUDENT => ['admin', 'teacher', 'parent'],
            self::CLASS => ['admin', 'teacher'],
            self::TEACHER => ['admin'],
            self::SCHOOL => ['admin'],
            self::ADMINISTRATIVE => ['admin'],
        };
    }
}