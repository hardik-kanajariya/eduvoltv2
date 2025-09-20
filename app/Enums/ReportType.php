<?php

namespace App\Enums;

enum ReportType: string
{
    case ATTENDANCE = 'attendance';
    case ACADEMIC = 'academic';
    case FINANCIAL = 'financial';
    case STUDENT_PROFILE = 'student_profile';
    case TEACHER_PERFORMANCE = 'teacher_performance';
    case EXAM_RESULTS = 'exam_results';
    case TIMETABLE = 'timetable';
    case BEHAVIORAL = 'behavioral';
    case CUSTOM = 'custom';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::ATTENDANCE => 'Attendance Reports',
            self::ACADEMIC => 'Academic Performance',
            self::FINANCIAL => 'Financial Reports',
            self::STUDENT_PROFILE => 'Student Profiles',
            self::TEACHER_PERFORMANCE => 'Teacher Performance',
            self::EXAM_RESULTS => 'Exam Results',
            self::TIMETABLE => 'Timetable Reports',
            self::BEHAVIORAL => 'Behavioral Reports',
            self::CUSTOM => 'Custom Reports',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ATTENDANCE => 'Daily, weekly, and monthly attendance tracking with trends analysis',
            self::ACADEMIC => 'Student grades, progress tracking, and performance analytics',
            self::FINANCIAL => 'Fee collection, payment tracking, and financial summaries',
            self::STUDENT_PROFILE => 'Comprehensive student information and enrollment details',
            self::TEACHER_PERFORMANCE => 'Teaching effectiveness and professional development tracking',
            self::EXAM_RESULTS => 'Examination scores, rankings, and statistical analysis',
            self::TIMETABLE => 'Class schedules, room utilization, and resource allocation',
            self::BEHAVIORAL => 'Disciplinary actions, behavior tracking, and intervention reports',
            self::CUSTOM => 'User-defined reports with flexible field selection',
        };
    }

    public function getAvailableFields(): array
    {
        return match ($this) {
            self::ATTENDANCE => [
                'student_name', 'admission_number', 'class', 'date', 'status',
                'total_days', 'present_days', 'absent_days', 'late_days',
                'attendance_percentage', 'consecutive_absences'
            ],
            self::ACADEMIC => [
                'student_name', 'admission_number', 'class', 'subject',
                'current_grade', 'previous_grade', 'grade_trend', 'gpa',
                'rank_in_class', 'rank_in_grade', 'improvement_score'
            ],
            self::FINANCIAL => [
                'student_name', 'admission_number', 'class', 'fee_type',
                'amount_due', 'amount_paid', 'balance', 'payment_date',
                'payment_method', 'discount_applied', 'late_fee'
            ],
            self::STUDENT_PROFILE => [
                'admission_number', 'full_name', 'date_of_birth', 'gender',
                'class', 'section', 'enrollment_date', 'parent_contact',
                'emergency_contact', 'medical_conditions', 'transport_route'
            ],
            self::TEACHER_PERFORMANCE => [
                'teacher_name', 'employee_id', 'department', 'subjects_taught',
                'class_performance_avg', 'student_feedback_score',
                'attendance_rate', 'professional_development_hours'
            ],
            self::EXAM_RESULTS => [
                'student_name', 'admission_number', 'exam_name', 'subject',
                'marks_obtained', 'total_marks', 'percentage', 'grade',
                'rank', 'class_average', 'highest_score', 'lowest_score'
            ],
            self::TIMETABLE => [
                'class_name', 'subject', 'teacher', 'time_slot', 'day_of_week',
                'room_number', 'duration', 'resource_requirements'
            ],
            self::BEHAVIORAL => [
                'student_name', 'admission_number', 'incident_date', 'incident_type',
                'severity_level', 'action_taken', 'parent_notified', 'follow_up_required'
            ],
            self::CUSTOM => [
                // Custom reports can include any available field
                'student_name', 'admission_number', 'class', 'teacher_name',
                'date', 'subject', 'grade', 'attendance_status', 'fee_status'
            ],
        };
    }

    public static function getTypesForCategory(ReportCategory $category): array
    {
        return match ($category) {
            ReportCategory::STUDENT => [
                self::ATTENDANCE, self::ACADEMIC, self::STUDENT_PROFILE, 
                self::EXAM_RESULTS, self::BEHAVIORAL
            ],
            ReportCategory::CLASS => [
                self::ATTENDANCE, self::ACADEMIC, self::EXAM_RESULTS, self::TIMETABLE
            ],
            ReportCategory::TEACHER => [
                self::TEACHER_PERFORMANCE, self::TIMETABLE
            ],
            ReportCategory::SCHOOL => [
                self::FINANCIAL, self::ATTENDANCE, self::ACADEMIC, 
                self::TEACHER_PERFORMANCE, self::EXAM_RESULTS
            ],
            ReportCategory::ADMINISTRATIVE => [
                self::FINANCIAL, self::TIMETABLE, self::CUSTOM
            ],
        };
    }
}