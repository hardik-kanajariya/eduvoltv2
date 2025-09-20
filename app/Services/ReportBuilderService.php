<?php

namespace App\Services;

use App\Enums\ReportCategory;
use App\Enums\ReportType;
use App\Models\Report;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class ReportBuilderService
{
    protected Report $report;
    protected array $parameters;
    protected array $selectedFields;
    protected QueryBuilder $query;

    public function __construct()
    {
        //
    }

    public function setReport(Report $report): self
    {
        $this->report = $report;
        $this->parameters = $report->parameters ?? [];
        $this->selectedFields = $report->selected_fields;
        
        return $this;
    }

    public function generateReport(): array
    {
        try {
            $this->report->markAsGenerating();

            // Initialize query based on report type
            $this->initializeQuery();

            // Apply filters from parameters
            $this->applyFilters();

            // Apply field selection
            $this->applyFieldSelection();

            // Execute query and get results
            $data = $this->executeQuery();

            // Process and format data
            $processedData = $this->processData($data);

            // Add metadata and statistics
            $reportData = $this->buildReportData($processedData);

            // Cache the results
            $this->report->markAsCompleted($reportData);

            return $reportData;
        } catch (Exception $e) {
            $this->report->markAsFailed();
            throw $e;
        }
    }

    protected function initializeQuery(): void
    {
        $this->query = match ($this->report->type) {
            ReportType::ATTENDANCE => $this->buildAttendanceQuery(),
            ReportType::ACADEMIC => $this->buildAcademicQuery(),
            ReportType::STUDENT_PROFILE => $this->buildStudentProfileQuery(),
            ReportType::FINANCIAL => $this->buildFinancialQuery(),
            ReportType::TEACHER_PERFORMANCE => $this->buildTeacherPerformanceQuery(),
            ReportType::EXAM_RESULTS => $this->buildExamResultsQuery(),
            ReportType::TIMETABLE => $this->buildTimetableQuery(),
            ReportType::BEHAVIORAL => $this->buildBehavioralQuery(),
            ReportType::CUSTOM => $this->buildCustomQuery(),
        };
    }

    protected function buildAttendanceQuery(): QueryBuilder
    {
        return DB::table('students')
            ->leftJoin('attendances', 'students.id', '=', 'attendances.student_id')
            ->leftJoin('users', 'students.user_id', '=', 'users.id')
            ->select([
                'students.id',
                'students.admission_number',
                'users.first_name',
                'users.last_name',
                'students.class',
                'attendances.date',
                'attendances.status',
                'attendances.remarks'
            ]);
    }

    protected function buildAcademicQuery(): QueryBuilder
    {
        return DB::table('students')
            ->leftJoin('grades', 'students.id', '=', 'grades.student_id')
            ->leftJoin('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->leftJoin('users', 'students.user_id', '=', 'users.id')
            ->select([
                'students.id',
                'students.admission_number',
                'users.first_name',
                'users.last_name',
                'students.class',
                'subjects.name as subject_name',
                'grades.current_grade',
                'grades.previous_grade',
                'grades.gpa'
            ]);
    }

    protected function buildStudentProfileQuery(): QueryBuilder
    {
        return DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select([
                'students.id',
                'students.admission_number',
                'users.first_name',
                'users.last_name',
                'users.email',
                'students.date_of_birth',
                'students.gender',
                'students.class',
                'students.section',
                'students.enrollment_date',
                'students.parent_contact',
                'students.emergency_contact',
                'students.medical_conditions',
                'students.transport_route'
            ]);
    }

    protected function buildFinancialQuery(): QueryBuilder
    {
        return DB::table('students')
            ->leftJoin('fee_records', 'students.id', '=', 'fee_records.student_id')
            ->leftJoin('users', 'students.user_id', '=', 'users.id')
            ->select([
                'students.id',
                'students.admission_number',
                'users.first_name',
                'users.last_name',
                'students.class',
                'fee_records.fee_type',
                'fee_records.amount_due',
                'fee_records.amount_paid',
                'fee_records.balance',
                'fee_records.payment_date',
                'fee_records.payment_method'
            ]);
    }

    protected function buildTeacherPerformanceQuery(): QueryBuilder
    {
        return DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->leftJoin('subjects', 'teachers.id', '=', 'subjects.teacher_id')
            ->select([
                'teachers.id',
                'teachers.employee_id',
                'users.first_name',
                'users.last_name',
                'teachers.department',
                'subjects.name as subject_name',
                'teachers.performance_score',
                'teachers.attendance_rate'
            ]);
    }

    protected function buildExamResultsQuery(): QueryBuilder
    {
        return DB::table('exam_results')
            ->join('students', 'exam_results.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->join('exams', 'exam_results.exam_id', '=', 'exams.id')
            ->join('subjects', 'exam_results.subject_id', '=', 'subjects.id')
            ->select([
                'students.admission_number',
                'users.first_name',
                'users.last_name',
                'exams.name as exam_name',
                'subjects.name as subject_name',
                'exam_results.marks_obtained',
                'exam_results.total_marks',
                'exam_results.percentage',
                'exam_results.grade',
                'exam_results.rank'
            ]);
    }

    protected function buildTimetableQuery(): QueryBuilder
    {
        return DB::table('timetable_entries')
            ->join('classes', 'timetable_entries.class_id', '=', 'classes.id')
            ->join('subjects', 'timetable_entries.subject_id', '=', 'subjects.id')
            ->join('teachers', 'timetable_entries.teacher_id', '=', 'teachers.id')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->select([
                'classes.name as class_name',
                'subjects.name as subject_name',
                'users.first_name as teacher_first_name',
                'users.last_name as teacher_last_name',
                'timetable_entries.day_of_week',
                'timetable_entries.start_time',
                'timetable_entries.end_time',
                'timetable_entries.room_number'
            ]);
    }

    protected function buildBehavioralQuery(): QueryBuilder
    {
        return DB::table('behavioral_records')
            ->join('students', 'behavioral_records.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->select([
                'students.admission_number',
                'users.first_name',
                'users.last_name',
                'behavioral_records.incident_date',
                'behavioral_records.incident_type',
                'behavioral_records.severity_level',
                'behavioral_records.action_taken',
                'behavioral_records.parent_notified'
            ]);
    }

    protected function buildCustomQuery(): QueryBuilder
    {
        // For custom reports, start with students table and allow joins
        return DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id');
    }

    protected function applyFilters(): void
    {
        // Date range filter
        if (isset($this->parameters['date_from'])) {
            $this->query->where('created_at', '>=', $this->parameters['date_from']);
        }
        
        if (isset($this->parameters['date_to'])) {
            $this->query->where('created_at', '<=', $this->parameters['date_to']);
        }

        // Class filter
        if (isset($this->parameters['class'])) {
            $this->query->where('students.class', $this->parameters['class']);
        }

        // Section filter
        if (isset($this->parameters['section'])) {
            $this->query->where('students.section', $this->parameters['section']);
        }

        // Student filter
        if (isset($this->parameters['student_ids'])) {
            $this->query->whereIn('students.id', $this->parameters['student_ids']);
        }

        // Status filter (for attendance reports)
        if (isset($this->parameters['attendance_status'])) {
            $this->query->where('attendances.status', $this->parameters['attendance_status']);
        }

        // Grade filter (for academic reports)
        if (isset($this->parameters['min_grade'])) {
            $this->query->where('grades.current_grade', '>=', $this->parameters['min_grade']);
        }

        if (isset($this->parameters['max_grade'])) {
            $this->query->where('grades.current_grade', '<=', $this->parameters['max_grade']);
        }

        // Subject filter
        if (isset($this->parameters['subject_ids'])) {
            $this->query->whereIn('subjects.id', $this->parameters['subject_ids']);
        }

        // Teacher filter
        if (isset($this->parameters['teacher_ids'])) {
            $this->query->whereIn('teachers.id', $this->parameters['teacher_ids']);
        }

        // Exam filter
        if (isset($this->parameters['exam_ids'])) {
            $this->query->whereIn('exams.id', $this->parameters['exam_ids']);
        }

        // Custom filters for specific report types
        $this->applyTypeSpecificFilters();
    }

    protected function applyTypeSpecificFilters(): void
    {
        match ($this->report->type) {
            ReportType::ATTENDANCE => $this->applyAttendanceFilters(),
            ReportType::ACADEMIC => $this->applyAcademicFilters(),
            ReportType::FINANCIAL => $this->applyFinancialFilters(),
            default => null,
        };
    }

    protected function applyAttendanceFilters(): void
    {
        // Attendance percentage threshold
        if (isset($this->parameters['min_attendance_percentage'])) {
            $minPercentage = $this->parameters['min_attendance_percentage'];
            $this->query->havingRaw('(COUNT(CASE WHEN attendances.status = "present" THEN 1 END) * 100.0 / COUNT(*)) >= ?', [$minPercentage]);
        }

        // Consecutive absences
        if (isset($this->parameters['min_consecutive_absences'])) {
            // This would require more complex SQL - simplified for now
            $this->query->where('attendances.consecutive_absences', '>=', $this->parameters['min_consecutive_absences']);
        }
    }

    protected function applyAcademicFilters(): void
    {
        // GPA threshold
        if (isset($this->parameters['min_gpa'])) {
            $this->query->where('grades.gpa', '>=', $this->parameters['min_gpa']);
        }

        // Grade improvement filter
        if (isset($this->parameters['show_improved_only']) && $this->parameters['show_improved_only']) {
            $this->query->whereRaw('grades.current_grade > grades.previous_grade');
        }
    }

    protected function applyFinancialFilters(): void
    {
        // Outstanding balance
        if (isset($this->parameters['has_outstanding_balance']) && $this->parameters['has_outstanding_balance']) {
            $this->query->where('fee_records.balance', '>', 0);
        }

        // Payment method
        if (isset($this->parameters['payment_method'])) {
            $this->query->where('fee_records.payment_method', $this->parameters['payment_method']);
        }
    }

    protected function applyFieldSelection(): void
    {
        if (!empty($this->selectedFields)) {
            // Map display field names to database columns
            $columnMapping = $this->getColumnMapping();
            $selectedColumns = [];

            foreach ($this->selectedFields as $field) {
                if (isset($columnMapping[$field])) {
                    $selectedColumns[] = $columnMapping[$field];
                }
            }

            if (!empty($selectedColumns)) {
                $this->query->select($selectedColumns);
            }
        }
    }

    protected function getColumnMapping(): array
    {
        return [
            'student_name' => DB::raw("CONCAT(users.first_name, ' ', users.last_name) as student_name"),
            'admission_number' => 'students.admission_number',
            'class' => 'students.class',
            'section' => 'students.section',
            'date' => 'attendances.date',
            'status' => 'attendances.status',
            'attendance_percentage' => DB::raw('(COUNT(CASE WHEN attendances.status = "present" THEN 1 END) * 100.0 / COUNT(*)) as attendance_percentage'),
            'current_grade' => 'grades.current_grade',
            'gpa' => 'grades.gpa',
            'subject_name' => 'subjects.name as subject_name',
            'teacher_name' => DB::raw("CONCAT(teacher_users.first_name, ' ', teacher_users.last_name) as teacher_name"),
            'exam_name' => 'exams.name as exam_name',
            'marks_obtained' => 'exam_results.marks_obtained',
            'total_marks' => 'exam_results.total_marks',
            'percentage' => 'exam_results.percentage',
            'grade' => 'exam_results.grade',
            'rank' => 'exam_results.rank',
        ];
    }

    protected function executeQuery(): Collection
    {
        // Apply grouping if needed
        if ($this->shouldGroupResults()) {
            $this->applyGrouping();
        }

        // Apply ordering
        $this->applyOrdering();

        // Apply pagination if specified
        if (isset($this->parameters['limit'])) {
            $this->query->limit($this->parameters['limit']);
        }

        if (isset($this->parameters['offset'])) {
            $this->query->offset($this->parameters['offset']);
        }

        return collect($this->query->get());
    }

    protected function shouldGroupResults(): bool
    {
        return in_array($this->report->type, [
            ReportType::ATTENDANCE,
            ReportType::ACADEMIC,
        ]);
    }

    protected function applyGrouping(): void
    {
        match ($this->report->type) {
            ReportType::ATTENDANCE => $this->query->groupBy(['students.id', 'students.admission_number']),
            ReportType::ACADEMIC => $this->query->groupBy(['students.id', 'subjects.id']),
            default => null,
        };
    }

    protected function applyOrdering(): void
    {
        $orderBy = $this->parameters['order_by'] ?? 'id';
        $orderDirection = $this->parameters['order_direction'] ?? 'asc';

        $this->query->orderBy($orderBy, $orderDirection);
    }

    protected function processData(Collection $data): array
    {
        return match ($this->report->type) {
            ReportType::ATTENDANCE => $this->processAttendanceData($data),
            ReportType::ACADEMIC => $this->processAcademicData($data),
            ReportType::FINANCIAL => $this->processFinancialData($data),
            default => $data->toArray(),
        };
    }

    protected function processAttendanceData(Collection $data): array
    {
        return $data->map(function ($record) {
            $record->attendance_percentage = round($record->attendance_percentage ?? 0, 2);
            $record->status_color = match ($record->status ?? 'absent') {
                'present' => 'green',
                'late' => 'yellow',
                'excused' => 'blue',
                default => 'red',
            };
            return $record;
        })->toArray();
    }

    protected function processAcademicData(Collection $data): array
    {
        return $data->map(function ($record) {
            $record->grade_trend = $this->calculateGradeTrend($record);
            $record->performance_level = $this->getPerformanceLevel($record->current_grade ?? 0);
            return $record;
        })->toArray();
    }

    protected function processFinancialData(Collection $data): array
    {
        return $data->map(function ($record) {
            $record->payment_status = ($record->balance ?? 0) > 0 ? 'Outstanding' : 'Paid';
            $record->payment_status_color = ($record->balance ?? 0) > 0 ? 'red' : 'green';
            return $record;
        })->toArray();
    }

    protected function calculateGradeTrend($record): string
    {
        $current = $record->current_grade ?? 0;
        $previous = $record->previous_grade ?? 0;

        if ($current > $previous) {
            return 'improving';
        } elseif ($current < $previous) {
            return 'declining';
        } else {
            return 'stable';
        }
    }

    protected function getPerformanceLevel(float $grade): string
    {
        return match (true) {
            $grade >= 90 => 'Excellent',
            $grade >= 80 => 'Good',
            $grade >= 70 => 'Satisfactory',
            $grade >= 60 => 'Needs Improvement',
            default => 'Poor',
        };
    }

    protected function buildReportData(array $processedData): array
    {
        $statistics = $this->calculateStatistics($processedData);

        return [
            'report_id' => $this->report->id,
            'report_name' => $this->report->name,
            'report_type' => $this->report->type->value,
            'report_category' => $this->report->category->value,
            'generated_at' => now()->toISOString(),
            'generated_by' => $this->report->creator->first_name . ' ' . $this->report->creator->last_name,
            'parameters' => $this->parameters,
            'total_records' => count($processedData),
            'data' => $processedData,
            'statistics' => $statistics,
            'charts' => $this->generateChartData($processedData),
        ];
    }

    protected function calculateStatistics(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        return match ($this->report->type) {
            ReportType::ATTENDANCE => $this->calculateAttendanceStatistics($data),
            ReportType::ACADEMIC => $this->calculateAcademicStatistics($data),
            ReportType::FINANCIAL => $this->calculateFinancialStatistics($data),
            default => ['total_records' => count($data)],
        };
    }

    protected function calculateAttendanceStatistics(array $data): array
    {
        $totalStudents = count($data);
        $presentCount = collect($data)->where('status', 'present')->count();
        $absentCount = collect($data)->where('status', 'absent')->count();
        $lateCount = collect($data)->where('status', 'late')->count();

        return [
            'total_students' => $totalStudents,
            'present_count' => $presentCount,
            'absent_count' => $absentCount,
            'late_count' => $lateCount,
            'attendance_rate' => $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 2) : 0,
            'absence_rate' => $totalStudents > 0 ? round(($absentCount / $totalStudents) * 100, 2) : 0,
        ];
    }

    protected function calculateAcademicStatistics(array $data): array
    {
        $grades = collect($data)->pluck('current_grade')->filter()->values();
        
        if ($grades->isEmpty()) {
            return ['total_records' => count($data)];
        }

        return [
            'total_students' => count($data),
            'average_grade' => round($grades->avg(), 2),
            'highest_grade' => $grades->max(),
            'lowest_grade' => $grades->min(),
            'median_grade' => $grades->median(),
            'students_above_average' => $grades->filter(fn($grade) => $grade > $grades->avg())->count(),
            'students_below_average' => $grades->filter(fn($grade) => $grade < $grades->avg())->count(),
        ];
    }

    protected function calculateFinancialStatistics(array $data): array
    {
        $amounts = collect($data);
        $totalDue = $amounts->sum('amount_due');
        $totalPaid = $amounts->sum('amount_paid');
        $totalBalance = $amounts->sum('balance');

        return [
            'total_students' => count($data),
            'total_amount_due' => round($totalDue, 2),
            'total_amount_paid' => round($totalPaid, 2),
            'total_outstanding_balance' => round($totalBalance, 2),
            'collection_rate' => $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 2) : 0,
            'students_with_outstanding' => $amounts->where('balance', '>', 0)->count(),
        ];
    }

    protected function generateChartData(array $data): array
    {
        return match ($this->report->type) {
            ReportType::ATTENDANCE => $this->generateAttendanceCharts($data),
            ReportType::ACADEMIC => $this->generateAcademicCharts($data),
            ReportType::FINANCIAL => $this->generateFinancialCharts($data),
            default => [],
        };
    }

    protected function generateAttendanceCharts(array $data): array
    {
        $statusCounts = collect($data)->countBy('status');

        return [
            'attendance_status_pie' => [
                'type' => 'pie',
                'title' => 'Attendance Status Distribution',
                'data' => [
                    'labels' => $statusCounts->keys()->map(fn($key) => ucfirst($key))->toArray(),
                    'datasets' => [[
                        'data' => $statusCounts->values()->toArray(),
                        'backgroundColor' => ['#10B981', '#F59E0B', '#3B82F6', '#EF4444'],
                    ]],
                ],
            ],
        ];
    }

    protected function generateAcademicCharts(array $data): array
    {
        $gradeRanges = collect($data)->groupBy(function ($record) {
            $grade = $record->current_grade ?? 0;
            return match (true) {
                $grade >= 90 => 'A (90-100)',
                $grade >= 80 => 'B (80-89)',
                $grade >= 70 => 'C (70-79)',
                $grade >= 60 => 'D (60-69)',
                default => 'F (0-59)',
            };
        })->map(function($group) {
            return $group->count();
        });

        return [
            'grade_distribution_bar' => [
                'type' => 'bar',
                'title' => 'Grade Distribution',
                'data' => [
                    'labels' => $gradeRanges->keys()->toArray(),
                    'datasets' => [[
                        'label' => 'Number of Students',
                        'data' => $gradeRanges->values()->toArray(),
                        'backgroundColor' => '#3B82F6',
                    ]],
                ],
            ],
        ];
    }

    protected function generateFinancialCharts(array $data): array
    {
        $paymentMethods = collect($data)->countBy('payment_method');

        return [
            'payment_methods_pie' => [
                'type' => 'pie',
                'title' => 'Payment Methods Distribution',
                'data' => [
                    'labels' => $paymentMethods->keys()->map(fn($key) => ucfirst($key))->toArray(),
                    'datasets' => [[
                        'data' => $paymentMethods->values()->toArray(),
                        'backgroundColor' => ['#10B981', '#F59E0B', '#3B82F6', '#8B5CF6'],
                    ]],
                ],
            ],
        ];
    }

    public function validateParameters(array $parameters): array
    {
        $errors = [];

        // Validate date range
        if (isset($parameters['date_from']) && isset($parameters['date_to'])) {
            $dateFrom = Carbon::parse($parameters['date_from']);
            $dateTo = Carbon::parse($parameters['date_to']);

            if ($dateFrom->greaterThan($dateTo)) {
                $errors[] = 'Date from must be before date to';
            }
        }

        // Validate grade range
        if (isset($parameters['min_grade']) && isset($parameters['max_grade'])) {
            if ($parameters['min_grade'] > $parameters['max_grade']) {
                $errors[] = 'Minimum grade must be less than maximum grade';
            }
        }

        // Validate attendance percentage
        if (isset($parameters['min_attendance_percentage'])) {
            if ($parameters['min_attendance_percentage'] < 0 || $parameters['min_attendance_percentage'] > 100) {
                $errors[] = 'Attendance percentage must be between 0 and 100';
            }
        }

        return $errors;
    }

    public function getAvailableFilters(ReportType $type): array
    {
        $commonFilters = [
            'date_from' => 'Date From',
            'date_to' => 'Date To',
            'class' => 'Class',
            'section' => 'Section',
            'student_ids' => 'Specific Students',
        ];

        $typeSpecificFilters = match ($type) {
            ReportType::ATTENDANCE => [
                'attendance_status' => 'Attendance Status',
                'min_attendance_percentage' => 'Minimum Attendance %',
                'min_consecutive_absences' => 'Minimum Consecutive Absences',
            ],
            ReportType::ACADEMIC => [
                'subject_ids' => 'Subjects',
                'min_grade' => 'Minimum Grade',
                'max_grade' => 'Maximum Grade',
                'min_gpa' => 'Minimum GPA',
                'show_improved_only' => 'Show Improved Only',
            ],
            ReportType::FINANCIAL => [
                'has_outstanding_balance' => 'Has Outstanding Balance',
                'payment_method' => 'Payment Method',
            ],
            ReportType::TEACHER_PERFORMANCE => [
                'teacher_ids' => 'Specific Teachers',
                'department' => 'Department',
            ],
            ReportType::EXAM_RESULTS => [
                'exam_ids' => 'Specific Exams',
                'subject_ids' => 'Subjects',
            ],
            default => [],
        };

        return array_merge($commonFilters, $typeSpecificFilters);
    }
}