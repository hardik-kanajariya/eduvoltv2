<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AcademicPerformanceReportService
{
    public function generateStudentPerformanceReport(int $studentId, array $parameters = []): array
    {
        $academicYear = $parameters['academic_year'] ?? date('Y');
        $term = $parameters['term'] ?? null;

        // Get student basic info
        $student = $this->getStudentInfo($studentId);

        // Get student grades across all subjects
        $grades = $this->getStudentGrades($studentId, $academicYear, $term);

        // Calculate performance metrics
        $performanceMetrics = $this->calculateStudentPerformanceMetrics($grades);

        // Get class rankings
        $rankings = $this->getStudentRankings($studentId, $academicYear, $term);

        // Get attendance correlation
        $attendanceCorrelation = $this->getAttendancePerformanceCorrelation($studentId, $academicYear);

        return [
            'student' => $student,
            'academic_year' => $academicYear,
            'term' => $term,
            'grades' => $grades,
            'performance_metrics' => $performanceMetrics,
            'rankings' => $rankings,
            'attendance_correlation' => $attendanceCorrelation,
            'recommendations' => $this->generateStudentRecommendations($performanceMetrics, $grades),
            'charts' => $this->generateStudentPerformanceCharts($grades, $performanceMetrics),
        ];
    }

    public function generateClassPerformanceReport(string $className, array $parameters = []): array
    {
        $academicYear = $parameters['academic_year'] ?? date('Y');
        $term = $parameters['term'] ?? null;
        $section = $parameters['section'] ?? null;

        // Get class students
        $students = $this->getClassStudents($className, $section);

        // Get all grades for the class
        $classGrades = $this->getClassGrades($className, $section, $academicYear, $term);

        // Calculate class statistics
        $classStatistics = $this->calculateClassStatistics($classGrades);

        // Get subject-wise performance
        $subjectPerformance = $this->getSubjectWisePerformance($classGrades);

        // Get top and bottom performers
        $topPerformers = $this->getTopPerformers($classGrades, 5);
        $bottomPerformers = $this->getBottomPerformers($classGrades, 5);

        // Calculate improvement trends
        $improvementTrends = $this->calculateClassImprovementTrends($className, $section, $academicYear);

        return [
            'class' => $className,
            'section' => $section,
            'academic_year' => $academicYear,
            'term' => $term,
            'total_students' => $students->count(),
            'class_statistics' => $classStatistics,
            'subject_performance' => $subjectPerformance,
            'top_performers' => $topPerformers,
            'bottom_performers' => $bottomPerformers,
            'improvement_trends' => $improvementTrends,
            'grade_distribution' => $this->getGradeDistribution($classGrades),
            'charts' => $this->generateClassPerformanceCharts($classGrades, $subjectPerformance),
        ];
    }

    public function generateSubjectPerformanceReport(string $subjectName, array $parameters = []): array
    {
        $academicYear = $parameters['academic_year'] ?? date('Y');
        $term = $parameters['term'] ?? null;
        $class = $parameters['class'] ?? null;

        // Get subject grades across all classes or specific class
        $subjectGrades = $this->getSubjectGrades($subjectName, $academicYear, $term, $class);

        // Calculate subject statistics
        $subjectStatistics = $this->calculateSubjectStatistics($subjectGrades);

        // Get class-wise performance for the subject
        $classWisePerformance = $this->getClassWiseSubjectPerformance($subjectGrades);

        // Get difficulty analysis
        $difficultyAnalysis = $this->analyzeSubjectDifficulty($subjectGrades);

        // Get teacher effectiveness (if teacher info is available)
        $teacherEffectiveness = $this->analyzeTeacherEffectiveness($subjectName, $academicYear);

        return [
            'subject' => $subjectName,
            'academic_year' => $academicYear,
            'term' => $term,
            'class_filter' => $class,
            'total_students' => $subjectGrades->count(),
            'subject_statistics' => $subjectStatistics,
            'class_wise_performance' => $classWisePerformance,
            'difficulty_analysis' => $difficultyAnalysis,
            'teacher_effectiveness' => $teacherEffectiveness,
            'grade_trends' => $this->getSubjectGradeTrends($subjectName, $academicYear),
            'charts' => $this->generateSubjectPerformanceCharts($subjectGrades, $classWisePerformance),
        ];
    }

    public function generateProgressTrackingReport(int $studentId, array $parameters = []): array
    {
        $startDate = isset($parameters['start_date'])
            ? Carbon::parse($parameters['start_date'])
            : now()->subYear();

        $endDate = isset($parameters['end_date'])
            ? Carbon::parse($parameters['end_date'])
            : now();

        // Get student info
        $student = $this->getStudentInfo($studentId);

        // Get historical grades
        $historicalGrades = $this->getHistoricalGrades($studentId, $startDate, $endDate);

        // Calculate progress trends
        $progressTrends = $this->calculateProgressTrends($historicalGrades);

        // Identify improvement and decline patterns
        $patterns = $this->identifyPerformancePatterns($historicalGrades);

        // Get goal tracking (if goals are defined)
        $goalProgress = $this->trackAcademicGoals($studentId, $historicalGrades);

        return [
            'student' => $student,
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'historical_grades' => $historicalGrades,
            'progress_trends' => $progressTrends,
            'performance_patterns' => $patterns,
            'goal_progress' => $goalProgress,
            'overall_trajectory' => $this->calculateOverallTrajectory($progressTrends),
            'predictions' => $this->generatePerformancePredictions($progressTrends, $patterns),
            'charts' => $this->generateProgressTrackingCharts($historicalGrades, $progressTrends),
        ];
    }

    protected function getStudentInfo(int $studentId): array
    {
        $student = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->where('students.id', $studentId)
            ->select([
                'students.id',
                'students.admission_number',
                'users.first_name',
                'users.last_name',
                'students.class',
                'students.section',
                'students.enrollment_date'
            ])
            ->first();

        return [
            'id' => $student->id,
            'admission_number' => $student->admission_number,
            'name' => $student->first_name . ' ' . $student->last_name,
            'class' => $student->class,
            'section' => $student->section,
            'enrollment_date' => $student->enrollment_date,
        ];
    }

    protected function getStudentGrades(int $studentId, string $academicYear, ?string $term = null): Collection
    {
        $query = DB::table('grades')
            ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->join('students', 'grades.student_id', '=', 'students.id')
            ->where('grades.student_id', $studentId)
            ->where('grades.academic_year', $academicYear);

        if ($term) {
            $query->where('grades.term', $term);
        }

        return collect($query->select([
            'subjects.name as subject_name',
            'grades.current_grade',
            'grades.previous_grade',
            'grades.total_marks',
            'grades.obtained_marks',
            'grades.percentage',
            'grades.letter_grade',
            'grades.gpa_points',
            'grades.term',
            'grades.exam_type',
            'grades.grade_date'
        ])->get());
    }

    protected function getClassStudents(string $className, ?string $section = null): Collection
    {
        $query = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->where('students.class', $className);

        if ($section) {
            $query->where('students.section', $section);
        }

        return collect($query->select([
            'students.id',
            'students.admission_number',
            'users.first_name',
            'users.last_name',
            'students.class',
            'students.section'
        ])->get());
    }

    protected function getClassGrades(string $className, ?string $section, string $academicYear, ?string $term = null): Collection
    {
        $query = DB::table('grades')
            ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->join('students', 'grades.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->where('students.class', $className)
            ->where('grades.academic_year', $academicYear);

        if ($section) {
            $query->where('students.section', $section);
        }

        if ($term) {
            $query->where('grades.term', $term);
        }

        return collect($query->select([
            'students.id as student_id',
            'students.admission_number',
            'users.first_name',
            'users.last_name',
            'subjects.name as subject_name',
            'grades.current_grade',
            'grades.previous_grade',
            'grades.percentage',
            'grades.letter_grade',
            'grades.gpa_points',
            'grades.term'
        ])->get());
    }

    protected function getSubjectGrades(string $subjectName, string $academicYear, ?string $term = null, ?string $class = null): Collection
    {
        $query = DB::table('grades')
            ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->join('students', 'grades.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->where('subjects.name', $subjectName)
            ->where('grades.academic_year', $academicYear);

        if ($term) {
            $query->where('grades.term', $term);
        }

        if ($class) {
            $query->where('students.class', $class);
        }

        return collect($query->select([
            'students.id as student_id',
            'students.admission_number',
            'users.first_name',
            'users.last_name',
            'students.class',
            'students.section',
            'grades.current_grade',
            'grades.percentage',
            'grades.letter_grade',
            'grades.gpa_points'
        ])->get());
    }

    protected function calculateStudentPerformanceMetrics(Collection $grades): array
    {
        if ($grades->isEmpty()) {
            return [
                'overall_gpa' => 0,
                'average_percentage' => 0,
                'total_subjects' => 0,
                'subjects_passed' => 0,
                'subjects_failed' => 0,
                'performance_level' => 'No Data',
            ];
        }

        $totalSubjects = $grades->count();
        $averagePercentage = $grades->avg('percentage');
        $overallGpa = $grades->avg('gpa_points');
        $subjectsPassed = $grades->where('current_grade', '>=', 60)->count(); // Assuming 60 is passing grade
        $subjectsFailed = $totalSubjects - $subjectsPassed;

        return [
            'overall_gpa' => round($overallGpa, 2),
            'average_percentage' => round($averagePercentage, 2),
            'total_subjects' => $totalSubjects,
            'subjects_passed' => $subjectsPassed,
            'subjects_failed' => $subjectsFailed,
            'performance_level' => $this->getPerformanceLevel($averagePercentage),
            'strongest_subject' => $grades->sortByDesc('percentage')->first()?->subject_name,
            'weakest_subject' => $grades->sortBy('percentage')->first()?->subject_name,
        ];
    }

    protected function calculateClassStatistics(Collection $classGrades): array
    {
        if ($classGrades->isEmpty()) {
            return [];
        }

        // Group by student to get individual GPAs
        $studentGpas = $classGrades->groupBy('student_id')->map(function ($studentGrades) {
            return $studentGrades->avg('gpa_points');
        });

        // Calculate class-wide statistics
        $averagePercentages = $classGrades->pluck('percentage')->filter();

        return [
            'class_average_gpa' => round($studentGpas->avg(), 2),
            'class_average_percentage' => round($averagePercentages->avg(), 2),
            'highest_gpa' => round($studentGpas->max(), 2),
            'lowest_gpa' => round($studentGpas->min(), 2),
            'median_gpa' => round($studentGpas->median(), 2),
            'students_above_average' => $studentGpas->where('>', $studentGpas->avg())->count(),
            'students_below_average' => $studentGpas->where('<', $studentGpas->avg())->count(),
            'total_students_graded' => $studentGpas->count(),
        ];
    }

    protected function getSubjectWisePerformance(Collection $classGrades): array
    {
        return $classGrades->groupBy('subject_name')->map(function ($subjectGrades, $subject) {
            $percentages = $subjectGrades->pluck('percentage')->filter();

            return [
                'subject' => $subject,
                'total_students' => $subjectGrades->count(),
                'average_percentage' => round($percentages->avg(), 2),
                'highest_score' => $percentages->max(),
                'lowest_score' => $percentages->min(),
                'passing_rate' => round(($subjectGrades->where('current_grade', '>=', 60)->count() / $subjectGrades->count()) * 100, 2),
                'difficulty_level' => $this->assessSubjectDifficulty($percentages->avg()),
            ];
        })->values()->toArray();
    }

    protected function getTopPerformers(Collection $classGrades, int $limit = 5): array
    {
        // Calculate average GPA per student
        $studentPerformances = $classGrades->groupBy('student_id')->map(function ($studentGrades) {
            $firstRecord = $studentGrades->first();
            return [
                'student_id' => $firstRecord->student_id,
                'admission_number' => $firstRecord->admission_number,
                'name' => $firstRecord->first_name . ' ' . $firstRecord->last_name,
                'average_gpa' => $studentGrades->avg('gpa_points'),
                'average_percentage' => $studentGrades->avg('percentage'),
            ];
        });

        return $studentPerformances->sortByDesc('average_gpa')->take($limit)->values()->toArray();
    }

    protected function getBottomPerformers(Collection $classGrades, int $limit = 5): array
    {
        // Calculate average GPA per student
        $studentPerformances = $classGrades->groupBy('student_id')->map(function ($studentGrades) {
            $firstRecord = $studentGrades->first();
            return [
                'student_id' => $firstRecord->student_id,
                'admission_number' => $firstRecord->admission_number,
                'name' => $firstRecord->first_name . ' ' . $firstRecord->last_name,
                'average_gpa' => $studentGrades->avg('gpa_points'),
                'average_percentage' => $studentGrades->avg('percentage'),
            ];
        });

        return $studentPerformances->sortBy('average_gpa')->take($limit)->values()->toArray();
    }

    protected function getPerformanceLevel(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'Excellent',
            $percentage >= 80 => 'Good',
            $percentage >= 70 => 'Satisfactory',
            $percentage >= 60 => 'Needs Improvement',
            default => 'Poor',
        };
    }

    protected function assessSubjectDifficulty(float $averagePercentage): string
    {
        return match (true) {
            $averagePercentage >= 85 => 'Easy',
            $averagePercentage >= 75 => 'Moderate',
            $averagePercentage >= 65 => 'Challenging',
            default => 'Difficult',
        };
    }

    protected function getGradeDistribution(Collection $classGrades): array
    {
        $gradeRanges = $classGrades->groupBy(function ($record) {
            $percentage = $record->percentage ?? 0;
            return match (true) {
                $percentage >= 90 => 'A (90-100)',
                $percentage >= 80 => 'B (80-89)',
                $percentage >= 70 => 'C (70-79)',
                $percentage >= 60 => 'D (60-69)',
                default => 'F (0-59)',
            };
        })->map(function ($group) {
            return $group->count();
        });

        return $gradeRanges->toArray();
    }

    protected function getStudentRankings(int $studentId, string $academicYear, ?string $term = null): array
    {
        // This would involve complex ranking calculations
        // Simplified implementation for now
        return [
            'class_rank' => null,
            'grade_rank' => null,
            'subject_ranks' => [],
        ];
    }

    protected function getAttendancePerformanceCorrelation(int $studentId, string $academicYear): array
    {
        // This would correlate attendance with academic performance
        // Simplified implementation for now
        return [
            'attendance_rate' => null,
            'correlation_score' => null,
            'impact_analysis' => 'Insufficient data for correlation analysis',
        ];
    }

    protected function calculateClassImprovementTrends(string $className, ?string $section, string $academicYear): array
    {
        // This would compare current term with previous terms
        // Simplified implementation for now
        return [
            'overall_trend' => 'stable',
            'improvement_percentage' => 0,
            'subjects_improved' => [],
            'subjects_declined' => [],
        ];
    }

    protected function calculateSubjectStatistics(Collection $subjectGrades): array
    {
        if ($subjectGrades->isEmpty()) {
            return [];
        }

        $percentages = $subjectGrades->pluck('percentage')->filter();

        return [
            'total_students' => $subjectGrades->count(),
            'average_percentage' => round($percentages->avg(), 2),
            'highest_score' => $percentages->max(),
            'lowest_score' => $percentages->min(),
            'median_score' => $percentages->median(),
            'standard_deviation' => round($this->calculateStandardDeviation($percentages), 2),
            'passing_rate' => round(($subjectGrades->where('current_grade', '>=', 60)->count() / $subjectGrades->count()) * 100, 2),
            'grade_distribution' => $this->getGradeDistribution($subjectGrades),
        ];
    }

    protected function getClassWiseSubjectPerformance(Collection $subjectGrades): array
    {
        return $subjectGrades->groupBy('class')->map(function ($classData, $className) {
            $percentages = $classData->pluck('percentage')->filter();

            return [
                'class' => $className,
                'student_count' => $classData->count(),
                'average_percentage' => round($percentages->avg(), 2),
                'highest_score' => $percentages->max(),
                'lowest_score' => $percentages->min(),
                'passing_rate' => round(($classData->where('current_grade', '>=', 60)->count() / $classData->count()) * 100, 2),
            ];
        })->values()->toArray();
    }

    protected function analyzeSubjectDifficulty(Collection $subjectGrades): array
    {
        $percentages = $subjectGrades->pluck('percentage')->filter();
        $averagePercentage = $percentages->avg();

        return [
            'difficulty_level' => $this->assessSubjectDifficulty($averagePercentage),
            'average_score' => round($averagePercentage, 2),
            'score_variance' => round($this->calculateVariance($percentages), 2),
            'recommendations' => $this->generateSubjectRecommendations($averagePercentage, $percentages),
        ];
    }

    protected function analyzeTeacherEffectiveness(string $subjectName, string $academicYear): array
    {
        // This would analyze teacher effectiveness based on student performance
        // Requires teacher-subject-class mapping
        return [
            'effectiveness_score' => null,
            'comparison_with_peers' => null,
            'student_improvement_rate' => null,
            'recommendations' => [],
        ];
    }

    protected function getSubjectGradeTrends(string $subjectName, string $academicYear): array
    {
        // This would show trends over multiple terms/exams
        return [];
    }

    protected function generateStudentRecommendations(array $metrics, Collection $grades): array
    {
        $recommendations = [];

        if ($metrics['average_percentage'] < 70) {
            $recommendations[] = "Consider additional tutoring or study support";
            $recommendations[] = "Review study habits and time management";
        }

        if ($metrics['subjects_failed'] > 0) {
            $recommendations[] = "Focus on improving performance in failed subjects";
            $recommendations[] = "Meet with subject teachers for targeted support";
        }

        if (isset($metrics['weakest_subject'])) {
            $recommendations[] = "Prioritize improvement in {$metrics['weakest_subject']}";
        }

        return $recommendations;
    }

    protected function generateSubjectRecommendations(float $averagePercentage, Collection $percentages): array
    {
        $recommendations = [];

        if ($averagePercentage < 70) {
            $recommendations[] = "Review curriculum difficulty and teaching methods";
            $recommendations[] = "Consider additional instructional support";
        }

        $variance = $this->calculateVariance($percentages);
        if ($variance > 400) { // High variance
            $recommendations[] = "Address large performance gaps between students";
            $recommendations[] = "Implement differentiated instruction strategies";
        }

        return $recommendations;
    }

    protected function calculateStandardDeviation(Collection $numbers): float
    {
        if ($numbers->isEmpty()) {
            return 0;
        }

        $mean = $numbers->avg();
        $variance = $numbers->sum(function ($number) use ($mean) {
            return pow($number - $mean, 2);
        }) / $numbers->count();

        return sqrt($variance);
    }

    protected function calculateVariance(Collection $numbers): float
    {
        if ($numbers->isEmpty()) {
            return 0;
        }

        $mean = $numbers->avg();
        return $numbers->sum(function ($number) use ($mean) {
            return pow($number - $mean, 2);
        }) / $numbers->count();
    }

    protected function getHistoricalGrades(int $studentId, Carbon $startDate, Carbon $endDate): Collection
    {
        // This would get grades over time for trend analysis
        return collect();
    }

    protected function calculateProgressTrends(Collection $historicalGrades): array
    {
        // Calculate trends over time
        return [];
    }

    protected function identifyPerformancePatterns(Collection $historicalGrades): array
    {
        // Identify improvement/decline patterns
        return [];
    }

    protected function trackAcademicGoals(int $studentId, Collection $grades): array
    {
        // Track progress towards academic goals
        return [];
    }

    protected function calculateOverallTrajectory(array $trends): string
    {
        return 'stable'; // Simplified
    }

    protected function generatePerformancePredictions(array $trends, array $patterns): array
    {
        return []; // Simplified
    }

    protected function generateStudentPerformanceCharts(Collection $grades, array $metrics): array
    {
        if ($grades->isEmpty()) {
            return [];
        }

        return [
            'subject_performance_radar' => [
                'type' => 'radar',
                'title' => 'Subject Performance Overview',
                'data' => [
                    'labels' => $grades->pluck('subject_name')->toArray(),
                    'datasets' => [[
                        'label' => 'Current Performance',
                        'data' => $grades->pluck('percentage')->toArray(),
                        'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                        'borderColor' => '#3B82F6',
                    ]],
                ],
            ],
        ];
    }

    protected function generateClassPerformanceCharts(Collection $classGrades, array $subjectPerformance): array
    {
        return [
            'class_distribution' => [
                'type' => 'bar',
                'title' => 'Grade Distribution',
                'data' => [
                    'labels' => array_keys($this->getGradeDistribution($classGrades)),
                    'datasets' => [[
                        'label' => 'Number of Students',
                        'data' => array_values($this->getGradeDistribution($classGrades)),
                        'backgroundColor' => '#10B981',
                    ]],
                ],
            ],
            'subject_comparison' => [
                'type' => 'bar',
                'title' => 'Subject-wise Performance',
                'data' => [
                    'labels' => array_column($subjectPerformance, 'subject'),
                    'datasets' => [[
                        'label' => 'Average Percentage',
                        'data' => array_column($subjectPerformance, 'average_percentage'),
                        'backgroundColor' => '#3B82F6',
                    ]],
                ],
            ],
        ];
    }

    protected function generateSubjectPerformanceCharts(Collection $subjectGrades, array $classWisePerformance): array
    {
        return [
            'class_comparison' => [
                'type' => 'bar',
                'title' => 'Class-wise Subject Performance',
                'data' => [
                    'labels' => array_column($classWisePerformance, 'class'),
                    'datasets' => [[
                        'label' => 'Average Percentage',
                        'data' => array_column($classWisePerformance, 'average_percentage'),
                        'backgroundColor' => '#8B5CF6',
                    ]],
                ],
            ],
        ];
    }

    protected function generateProgressTrackingCharts(Collection $historicalGrades, array $trends): array
    {
        return []; // Simplified for now
    }
}
