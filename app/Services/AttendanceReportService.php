<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceReportService
{
    public function generateDailyAttendanceReport(array $parameters = []): array
    {
        $date = $parameters['date'] ?? now()->format('Y-m-d');
        $class = $parameters['class'] ?? null;
        $section = $parameters['section'] ?? null;

        $query = $this->buildBaseAttendanceQuery();

        // Filter by date
        $query->whereDate('attendances.date', $date);

        // Apply class and section filters
        if ($class) {
            $query->where('students.class', $class);
        }

        if ($section) {
            $query->where('students.section', $section);
        }

        $attendanceData = $query->get();

        return [
            'date' => $date,
            'class' => $class,
            'section' => $section,
            'total_students' => $attendanceData->count(),
            'present_count' => $attendanceData->where('status', 'present')->count(),
            'absent_count' => $attendanceData->where('status', 'absent')->count(),
            'late_count' => $attendanceData->where('status', 'late')->count(),
            'excused_count' => $attendanceData->where('status', 'excused')->count(),
            'attendance_rate' => $this->calculateAttendanceRate($attendanceData),
            'students' => $attendanceData->map(function ($record) {
                return $this->formatStudentRecord($record);
            })->toArray(),
            'charts' => $this->generateDailyCharts($attendanceData),
        ];
    }

    public function generateWeeklyAttendanceReport(array $parameters = []): array
    {
        $startDate = isset($parameters['start_date'])
            ? Carbon::parse($parameters['start_date'])
            : now()->startOfWeek();

        $endDate = $startDate->copy()->endOfWeek();
        $class = $parameters['class'] ?? null;
        $section = $parameters['section'] ?? null;

        // Get daily attendance for the week
        $dailyData = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayData = $this->generateDailyAttendanceReport([
                'date' => $date->format('Y-m-d'),
                'class' => $class,
                'section' => $section,
            ]);

            $dailyData->push([
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'present_count' => $dayData['present_count'],
                'absent_count' => $dayData['absent_count'],
                'late_count' => $dayData['late_count'],
                'attendance_rate' => $dayData['attendance_rate'],
            ]);
        }

        // Calculate weekly statistics
        $weeklyStats = $this->calculateWeeklyStatistics($dailyData);

        return [
            'week_start' => $startDate->format('Y-m-d'),
            'week_end' => $endDate->format('Y-m-d'),
            'class' => $class,
            'section' => $section,
            'daily_data' => $dailyData->toArray(),
            'weekly_stats' => $weeklyStats,
            'charts' => $this->generateWeeklyCharts($dailyData),
        ];
    }

    public function generateMonthlyAttendanceReport(array $parameters = []): array
    {
        $month = $parameters['month'] ?? now()->month;
        $year = $parameters['year'] ?? now()->year;
        $class = $parameters['class'] ?? null;
        $section = $parameters['section'] ?? null;

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $query = $this->buildBaseAttendanceQuery();

        // Filter by month
        $query->whereBetween('attendances.date', [$startDate, $endDate]);

        // Apply class and section filters
        if ($class) {
            $query->where('students.class', $class);
        }

        if ($section) {
            $query->where('students.section', $section);
        }

        // Group by student to get individual statistics
        $query->select([
            'students.id',
            'students.admission_number',
            'users.first_name',
            'users.last_name',
            'students.class',
            'students.section',
            DB::raw('COUNT(attendances.id) as total_days'),
            DB::raw('SUM(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) as present_days'),
            DB::raw('SUM(CASE WHEN attendances.status = "absent" THEN 1 ELSE 0 END) as absent_days'),
            DB::raw('SUM(CASE WHEN attendances.status = "late" THEN 1 ELSE 0 END) as late_days'),
            DB::raw('SUM(CASE WHEN attendances.status = "excused" THEN 1 ELSE 0 END) as excused_days'),
            DB::raw('(SUM(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) * 100.0 / COUNT(attendances.id)) as attendance_percentage')
        ])->groupBy(['students.id', 'students.admission_number', 'users.first_name', 'users.last_name', 'students.class', 'students.section']);

        $monthlyData = $query->get();

        // Calculate overall statistics
        $overallStats = $this->calculateMonthlyStatistics($monthlyData);

        return [
            'month' => $month,
            'year' => $year,
            'month_name' => $startDate->format('F'),
            'class' => $class,
            'section' => $section,
            'total_school_days' => $this->getSchoolDaysInMonth($year, $month),
            'students' => $monthlyData->map(function ($record) {
                return $this->formatMonthlyStudentRecord($record);
            })->toArray(),
            'overall_stats' => $overallStats,
            'charts' => $this->generateMonthlyCharts($monthlyData),
        ];
    }

    public function generateAttendanceTrendsReport(array $parameters = []): array
    {
        $startDate = isset($parameters['start_date'])
            ? Carbon::parse($parameters['start_date'])
            : now()->subMonths(6);

        $endDate = isset($parameters['end_date'])
            ? Carbon::parse($parameters['end_date'])
            : now();

        $class = $parameters['class'] ?? null;
        $section = $parameters['section'] ?? null;

        // Generate monthly trends
        $monthlyTrends = collect();
        $current = $startDate->copy()->startOfMonth();

        while ($current->lte($endDate)) {
            $monthData = $this->generateMonthlyAttendanceReport([
                'month' => $current->month,
                'year' => $current->year,
                'class' => $class,
                'section' => $section,
            ]);

            $monthlyTrends->push([
                'month' => $current->format('Y-m'),
                'month_name' => $current->format('F Y'),
                'attendance_rate' => $monthData['overall_stats']['average_attendance_rate'],
                'total_students' => count($monthData['students']),
                'chronic_absentees' => collect($monthData['students'])->where('attendance_percentage', '<', 80)->count(),
            ]);

            $current->addMonth();
        }

        // Identify students with declining attendance
        $decliningStudents = $this->identifyDecliningAttendance($startDate, $endDate, $class, $section);

        return [
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'class' => $class,
            'section' => $section,
            'monthly_trends' => $monthlyTrends->toArray(),
            'declining_students' => $decliningStudents,
            'insights' => $this->generateAttendanceInsights($monthlyTrends, $decliningStudents),
            'charts' => $this->generateTrendCharts($monthlyTrends),
        ];
    }

    public function generateAbsenteeismReport(array $parameters = []): array
    {
        $startDate = isset($parameters['start_date'])
            ? Carbon::parse($parameters['start_date'])
            : now()->subDays(30);

        $endDate = isset($parameters['end_date'])
            ? Carbon::parse($parameters['end_date'])
            : now();

        $minAbsences = $parameters['min_absences'] ?? 5;
        $class = $parameters['class'] ?? null;

        $query = $this->buildBaseAttendanceQuery();

        // Filter by date range
        $query->whereBetween('attendances.date', [$startDate, $endDate]);

        // Filter by class if specified
        if ($class) {
            $query->where('students.class', $class);
        }

        // Group by student and filter by minimum absences
        $query->select([
            'students.id',
            'students.admission_number',
            'users.first_name',
            'users.last_name',
            'students.class',
            'students.section',
            'students.parent_contact',
            DB::raw('COUNT(attendances.id) as total_marked_days'),
            DB::raw('SUM(CASE WHEN attendances.status = "absent" THEN 1 ELSE 0 END) as absent_days'),
            DB::raw('SUM(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) as present_days'),
            DB::raw('(SUM(CASE WHEN attendances.status = "absent" THEN 1 ELSE 0 END) * 100.0 / COUNT(attendances.id)) as absence_rate'),
            DB::raw('MAX(CASE WHEN attendances.status = "absent" THEN attendances.date END) as last_absence_date')
        ])
            ->groupBy(['students.id', 'students.admission_number', 'users.first_name', 'users.last_name', 'students.class', 'students.section', 'students.parent_contact'])
            ->havingRaw('SUM(CASE WHEN attendances.status = "absent" THEN 1 ELSE 0 END) >= ?', [$minAbsences]);

        $absenteeData = $query->get();

        // Categorize absenteeism levels
        $categorizedData = $this->categorizeAbsenteeism($absenteeData);

        return [
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'min_absences' => $minAbsences,
            'class' => $class,
            'total_chronic_absentees' => $absenteeData->count(),
            'categorized_data' => $categorizedData,
            'students' => $absenteeData->map(function ($record) {
                return $this->formatAbsenteeRecord($record);
            })->toArray(),
            'recommendations' => $this->generateAbsenteeismRecommendations($categorizedData),
            'charts' => $this->generateAbsenteeismCharts($absenteeData),
        ];
    }

    protected function buildBaseAttendanceQuery()
    {
        return DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->leftJoin('attendances', 'students.id', '=', 'attendances.student_id')
            ->select([
                'students.id',
                'students.admission_number',
                'users.first_name',
                'users.last_name',
                'students.class',
                'students.section',
                'attendances.date',
                'attendances.status',
                'attendances.remarks'
            ]);
    }

    protected function calculateAttendanceRate(Collection $data): float
    {
        if ($data->isEmpty()) {
            return 0;
        }

        $totalStudents = $data->count();
        $presentStudents = $data->where('status', 'present')->count();

        return round(($presentStudents / $totalStudents) * 100, 2);
    }

    protected function formatStudentRecord($record): array
    {
        return [
            'admission_number' => $record->admission_number,
            'student_name' => $record->first_name . ' ' . $record->last_name,
            'class' => $record->class,
            'section' => $record->section,
            'status' => $record->status,
            'status_color' => $this->getStatusColor($record->status),
            'remarks' => $record->remarks,
        ];
    }

    protected function formatMonthlyStudentRecord($record): array
    {
        return [
            'admission_number' => $record->admission_number,
            'student_name' => $record->first_name . ' ' . $record->last_name,
            'class' => $record->class,
            'section' => $record->section,
            'total_days' => $record->total_days,
            'present_days' => $record->present_days,
            'absent_days' => $record->absent_days,
            'late_days' => $record->late_days,
            'excused_days' => $record->excused_days,
            'attendance_percentage' => round($record->attendance_percentage, 2),
            'performance_level' => $this->getAttendancePerformanceLevel($record->attendance_percentage),
        ];
    }

    protected function formatAbsenteeRecord($record): array
    {
        return [
            'admission_number' => $record->admission_number,
            'student_name' => $record->first_name . ' ' . $record->last_name,
            'class' => $record->class,
            'section' => $record->section,
            'parent_contact' => $record->parent_contact,
            'total_marked_days' => $record->total_marked_days,
            'absent_days' => $record->absent_days,
            'present_days' => $record->present_days,
            'absence_rate' => round($record->absence_rate, 2),
            'last_absence_date' => $record->last_absence_date,
            'risk_level' => $this->getAbsenteeismRiskLevel($record->absence_rate),
        ];
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'present' => 'green',
            'late' => 'yellow',
            'excused' => 'blue',
            default => 'red',
        };
    }

    protected function getAttendancePerformanceLevel(float $percentage): string
    {
        return match (true) {
            $percentage >= 95 => 'Excellent',
            $percentage >= 90 => 'Good',
            $percentage >= 85 => 'Satisfactory',
            $percentage >= 80 => 'Needs Attention',
            default => 'Critical',
        };
    }

    protected function getAbsenteeismRiskLevel(float $absenceRate): string
    {
        return match (true) {
            $absenceRate >= 30 => 'High Risk',
            $absenceRate >= 20 => 'Moderate Risk',
            $absenceRate >= 10 => 'Low Risk',
            default => 'Normal',
        };
    }

    protected function calculateWeeklyStatistics(Collection $dailyData): array
    {
        return [
            'total_school_days' => $dailyData->count(),
            'average_attendance_rate' => round($dailyData->avg('attendance_rate'), 2),
            'best_day' => $dailyData->sortByDesc('attendance_rate')->first(),
            'worst_day' => $dailyData->sortBy('attendance_rate')->first(),
            'total_present' => $dailyData->sum('present_count'),
            'total_absent' => $dailyData->sum('absent_count'),
            'total_late' => $dailyData->sum('late_count'),
        ];
    }

    protected function calculateMonthlyStatistics(Collection $monthlyData): array
    {
        return [
            'total_students' => $monthlyData->count(),
            'average_attendance_rate' => round($monthlyData->avg('attendance_percentage'), 2),
            'students_above_90_percent' => $monthlyData->where('attendance_percentage', '>=', 90)->count(),
            'students_below_80_percent' => $monthlyData->where('attendance_percentage', '<', 80)->count(),
            'perfect_attendance' => $monthlyData->where('attendance_percentage', 100)->count(),
            'highest_attendance' => $monthlyData->max('attendance_percentage'),
            'lowest_attendance' => $monthlyData->min('attendance_percentage'),
        ];
    }

    protected function categorizeAbsenteeism(Collection $data): array
    {
        return [
            'low_risk' => $data->whereBetween('absence_rate', [0, 10])->count(),
            'moderate_risk' => $data->whereBetween('absence_rate', [10, 20])->count(),
            'high_risk' => $data->whereBetween('absence_rate', [20, 30])->count(),
            'critical_risk' => $data->where('absence_rate', '>=', 30)->count(),
        ];
    }

    protected function identifyDecliningAttendance(Carbon $startDate, Carbon $endDate, $class = null, $section = null): array
    {
        // This would involve comparing attendance rates over time periods
        // Simplified implementation for now
        return [];
    }

    protected function generateAttendanceInsights(Collection $trends, array $declining): array
    {
        $insights = [];

        // Overall trend
        $firstMonth = $trends->first();
        $lastMonth = $trends->last();

        if ($firstMonth && $lastMonth) {
            $trendChange = $lastMonth['attendance_rate'] - $firstMonth['attendance_rate'];

            if ($trendChange > 2) {
                $insights[] = "Overall attendance has improved by " . round($trendChange, 1) . "% over the period.";
            } elseif ($trendChange < -2) {
                $insights[] = "Overall attendance has declined by " . round(abs($trendChange), 1) . "% over the period.";
            } else {
                $insights[] = "Overall attendance has remained stable over the period.";
            }
        }

        // Chronic absenteeism trend
        $avgChronicAbsentees = $trends->avg('chronic_absentees');
        if ($avgChronicAbsentees > 0) {
            $insights[] = "On average, " . round($avgChronicAbsentees) . " students show chronic absenteeism patterns.";
        }

        return $insights;
    }

    protected function generateAbsenteeismRecommendations(array $categorizedData): array
    {
        $recommendations = [];

        if ($categorizedData['critical_risk'] > 0) {
            $recommendations[] = "Immediate intervention required for {$categorizedData['critical_risk']} students with critical absenteeism.";
            $recommendations[] = "Schedule parent meetings and develop attendance improvement plans.";
        }

        if ($categorizedData['high_risk'] > 0) {
            $recommendations[] = "Monitor {$categorizedData['high_risk']} students with high absenteeism closely.";
            $recommendations[] = "Implement early warning notifications for these students.";
        }

        if ($categorizedData['moderate_risk'] > 0) {
            $recommendations[] = "Provide additional support for {$categorizedData['moderate_risk']} students showing moderate absenteeism.";
        }

        return $recommendations;
    }

    protected function getSchoolDaysInMonth(int $year, int $month): int
    {
        // This should be configurable based on school calendar
        // For now, estimate excluding weekends
        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        $schoolDays = 0;
        while ($start->lte($end)) {
            if ($start->isWeekday()) {
                $schoolDays++;
            }
            $start->addDay();
        }

        return $schoolDays;
    }

    protected function generateDailyCharts(Collection $data): array
    {
        $statusCounts = $data->countBy('status');

        return [
            'status_pie' => [
                'type' => 'pie',
                'title' => 'Daily Attendance Status',
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

    protected function generateWeeklyCharts(Collection $dailyData): array
    {
        return [
            'weekly_trend' => [
                'type' => 'line',
                'title' => 'Weekly Attendance Trend',
                'data' => [
                    'labels' => $dailyData->pluck('day_name')->toArray(),
                    'datasets' => [[
                        'label' => 'Attendance Rate (%)',
                        'data' => $dailyData->pluck('attendance_rate')->toArray(),
                        'borderColor' => '#3B82F6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    ]],
                ],
            ],
        ];
    }

    protected function generateMonthlyCharts(Collection $data): array
    {
        $performanceLevels = $data->groupBy(function ($record) {
            return $this->getAttendancePerformanceLevel($record->attendance_percentage);
        })->map(function ($group) {
            return $group->count();
        });

        return [
            'performance_distribution' => [
                'type' => 'bar',
                'title' => 'Monthly Attendance Performance Distribution',
                'data' => [
                    'labels' => $performanceLevels->keys()->toArray(),
                    'datasets' => [[
                        'label' => 'Number of Students',
                        'data' => $performanceLevels->values()->toArray(),
                        'backgroundColor' => '#10B981',
                    ]],
                ],
            ],
        ];
    }

    protected function generateTrendCharts(Collection $trends): array
    {
        return [
            'monthly_trends' => [
                'type' => 'line',
                'title' => 'Monthly Attendance Trends',
                'data' => [
                    'labels' => $trends->pluck('month_name')->toArray(),
                    'datasets' => [[
                        'label' => 'Attendance Rate (%)',
                        'data' => $trends->pluck('attendance_rate')->toArray(),
                        'borderColor' => '#3B82F6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    ]],
                ],
            ],
        ];
    }

    protected function generateAbsenteeismCharts(Collection $data): array
    {
        $riskLevels = $data->groupBy(function ($record) {
            return $this->getAbsenteeismRiskLevel($record->absence_rate);
        })->map(function ($group) {
            return $group->count();
        });

        return [
            'risk_distribution' => [
                'type' => 'doughnut',
                'title' => 'Absenteeism Risk Distribution',
                'data' => [
                    'labels' => $riskLevels->keys()->toArray(),
                    'datasets' => [[
                        'data' => $riskLevels->values()->toArray(),
                        'backgroundColor' => ['#10B981', '#F59E0B', '#F97316', '#EF4444'],
                    ]],
                ],
            ],
        ];
    }
}
