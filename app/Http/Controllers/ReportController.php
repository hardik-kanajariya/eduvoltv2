<?php

namespace App\Http\Controllers;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Report;
use App\Services\AttendanceReportService;
use App\Services\AcademicPerformanceReportService;
use App\Services\ReportBuilderService;
use App\Services\ReportExportService;
use App\Services\ChartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportBuilderService $reportBuilder,
        protected ReportExportService $exportService,
        protected AttendanceReportService $attendanceService,
        protected AcademicPerformanceReportService $academicService,
        protected ChartService $chartService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display a listing of reports
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Report::with(['creator'])
            ->forUser($user);

        // Apply filters
        if ($request->filled('type')) {
            $query->byType(ReportType::from($request->type));
        }

        if ($request->filled('category')) {
            $query->byCategory(ReportCategory::from($request->category));
        }

        if ($request->filled('status')) {
            $query->byStatus(ReportStatus::from($request->status));
        }

        if ($request->filled('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $reports = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
            'filters' => [
                'available_types' => $this->getAvailableTypes($user),
                'available_categories' => $this->getAvailableCategories($user),
                'available_statuses' => ReportStatus::cases(),
            ],
        ]);
    }

    /**
     * Store a newly created report
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:' . implode(',', array_column(ReportType::cases(), 'value')),
            'category' => 'required|string|in:' . implode(',', array_column(ReportCategory::cases(), 'value')),
            'parameters' => 'nullable|array',
            'fields' => 'nullable|array',
            'output_format' => 'nullable|string|in:html,pdf,excel,csv,json',
            'is_scheduled' => 'boolean',
            'schedule_frequency' => 'nullable|string|in:daily,weekly,monthly,quarterly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $reportType = ReportType::from($request->type);
        $reportCategory = ReportCategory::from($request->category);

        // Check permissions using Spatie roles
        $availableRoles = $reportCategory->getAvailableRoles();
        $userRoles = $user->roles->pluck('name')->toArray();
        $hasPermission = !empty(array_intersect($userRoles, $availableRoles));

        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create this type of report',
            ], 403);
        }

        // Validate parameters if provided
        if ($request->filled('parameters')) {
            $validationErrors = $this->reportBuilder->validateParameters($request->parameters);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid report parameters',
                    'errors' => $validationErrors,
                ], 422);
            }
        }

        $report = Report::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $reportType,
            'category' => $reportCategory,
            'parameters' => $request->parameters ?? [],
            'fields' => $request->fields,
            'output_format' => $request->output_format ?? 'html',
            'is_scheduled' => $request->is_scheduled ?? false,
            'schedule_frequency' => $request->schedule_frequency,
            'created_by' => $user->id,
        ]);

        // Set up scheduling if requested
        if ($report->is_scheduled && $report->schedule_frequency) {
            $report->updateSchedule();
        }

        return response()->json([
            'success' => true,
            'message' => 'Report created successfully',
            'data' => $report->load('creator'),
        ], 201);
    }

    /**
     * Display the specified report
     */
    public function show(Report $report): JsonResponse
    {
        $user = Auth::user();

        if (!$report->canBeGeneratedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this report',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $report->load('creator'),
            'available_fields' => $report->available_fields,
            'available_filters' => $this->reportBuilder->getAvailableFilters($report->type),
        ]);
    }

    /**
     * Update the specified report
     */
    public function update(Request $request, Report $report): JsonResponse
    {
        $user = Auth::user();

        if (!$report->canBeEditedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit this report',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'parameters' => 'nullable|array',
            'fields' => 'nullable|array',
            'output_format' => 'nullable|string|in:html,pdf,excel,csv,json',
            'is_scheduled' => 'boolean',
            'schedule_frequency' => 'nullable|string|in:daily,weekly,monthly,quarterly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate parameters if provided
        if ($request->filled('parameters')) {
            $validationErrors = $this->reportBuilder->validateParameters($request->parameters);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid report parameters',
                    'errors' => $validationErrors,
                ], 422);
            }
        }

        $report->update($request->only([
            'name',
            'description',
            'parameters',
            'fields',
            'output_format',
            'is_scheduled',
            'schedule_frequency'
        ]));

        // Clear cache when report is updated
        $report->clearCache();

        // Update scheduling if changed
        if ($request->has('is_scheduled') || $request->has('schedule_frequency')) {
            if ($report->is_scheduled && $report->schedule_frequency) {
                $report->updateSchedule();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Report updated successfully',
            'data' => $report->load('creator'),
        ]);
    }

    /**
     * Remove the specified report
     */
    public function destroy(Report $report): JsonResponse
    {
        $user = Auth::user();

        if (!$report->canBeEditedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this report',
            ], 403);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully',
        ]);
    }

    /**
     * Generate report data
     */
    public function generate(Report $report): JsonResponse
    {
        $user = Auth::user();

        if (!$report->canBeGeneratedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to generate this report',
            ], 403);
        }

        try {
            $this->reportBuilder->setReport($report);
            $reportData = $this->reportBuilder->generateReport();

            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export report in specified format
     */
    public function export(Request $request, Report $report): JsonResponse
    {
        $user = Auth::user();

        if (!$report->canBeGeneratedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to export this report',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'format' => 'required|string|in:pdf,excel,csv,html,json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid export format',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $exportResult = $this->exportService->exportReport(
                $report,
                $request->get('format'),
                $user
            );

            return response()->json([
                'success' => true,
                'message' => 'Report exported successfully',
                'data' => $exportResult,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download exported report
     */
    public function download(Request $request, Report $report): StreamedResponse
    {
        $user = Auth::user();

        if (!$report->canBeGeneratedBy($user)) {
            abort(403, 'You do not have permission to download this report');
        }

        $format = $request->query('format', 'pdf');
        $fileName = $this->generateDownloadFileName($report, $format);

        // Find the file in exports directory
        $filePath = $this->findExportFile($report, $format);

        if (!$filePath || !Storage::disk('private')->exists($filePath)) {
            abort(404, 'Export file not found');
        }

        $headers = [
            'Content-Type' => $this->getContentType($format),
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->stream(function () use ($filePath) {
            $stream = Storage::disk('private')->readStream($filePath);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
    }

    /**
     * Bulk export multiple reports
     */
    public function bulkExport(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'report_ids' => 'required|array|min:1',
            'report_ids.*' => 'exists:reports,id',
            'format' => 'required|string|in:pdf,excel,csv,html,json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->exportService->bulkExport(
                $request->report_ids,
                $request->get('format'),
                $user
            );

            return response()->json([
                'success' => true,
                'message' => 'Bulk export completed',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk export: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attendance reports
     */
    public function attendanceReports(Request $request): JsonResponse
    {
        $user = Auth::user();

        $userRoles = $user->roles->pluck('name')->toArray();
        if (!in_array('admin', $userRoles) && !in_array('teacher', $userRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access attendance reports',
            ], 403);
        }

        $type = $request->query('type', 'daily');
        $parameters = $request->only(['date', 'start_date', 'end_date', 'class', 'section', 'month', 'year']);

        try {
            $reportData = match ($type) {
                'daily' => $this->attendanceService->generateDailyAttendanceReport($parameters),
                'weekly' => $this->attendanceService->generateWeeklyAttendanceReport($parameters),
                'monthly' => $this->attendanceService->generateMonthlyAttendanceReport($parameters),
                'trends' => $this->attendanceService->generateAttendanceTrendsReport($parameters),
                'absenteeism' => $this->attendanceService->generateAbsenteeismReport($parameters),
                default => throw new \InvalidArgumentException('Invalid attendance report type'),
            };

            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate attendance report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get academic performance reports
     */
    public function academicReports(Request $request): JsonResponse
    {
        $user = Auth::user();

        $userRoles = $user->roles->pluck('name')->toArray();
        if (!in_array('admin', $userRoles) && !in_array('teacher', $userRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access academic reports',
            ], 403);
        }

        $type = $request->query('type', 'student');
        $parameters = $request->only(['student_id', 'class', 'section', 'subject', 'academic_year', 'term']);

        try {
            $reportData = match ($type) {
                'student' => $this->academicService->generateStudentPerformanceReport($parameters['student_id'], $parameters),
                'class' => $this->academicService->generateClassPerformanceReport($parameters['class'], $parameters),
                'subject' => $this->academicService->generateSubjectPerformanceReport($parameters['subject'], $parameters),
                'progress' => $this->academicService->generateProgressTrackingReport($parameters['student_id'], $parameters),
                default => throw new \InvalidArgumentException('Invalid academic report type'),
            };

            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate academic report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get report templates
     */
    public function templates(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name');

        $templates = collect([
            [
                'id' => 'daily_attendance',
                'name' => 'Daily Attendance Report',
                'description' => 'Track daily attendance for classes or specific students',
                'type' => ReportType::ATTENDANCE,
                'category' => ReportCategory::CLASS,
                'available_roles' => ['admin', 'teacher'],
                'parameters' => [
                    'date' => date('Y-m-d'),
                    'class' => null,
                    'section' => null,
                ],
            ],
            [
                'id' => 'monthly_attendance',
                'name' => 'Monthly Attendance Summary',
                'description' => 'Comprehensive monthly attendance analysis',
                'type' => ReportType::ATTENDANCE,
                'category' => ReportCategory::SCHOOL,
                'available_roles' => ['admin'],
                'parameters' => [
                    'month' => date('n'),
                    'year' => date('Y'),
                    'class' => null,
                ],
            ],
            [
                'id' => 'student_performance',
                'name' => 'Student Performance Report',
                'description' => 'Individual student academic performance analysis',
                'type' => ReportType::ACADEMIC,
                'category' => ReportCategory::STUDENT,
                'available_roles' => ['admin', 'teacher', 'parent'],
                'parameters' => [
                    'student_id' => null,
                    'academic_year' => date('Y'),
                    'term' => null,
                ],
            ],
            [
                'id' => 'class_performance',
                'name' => 'Class Performance Analysis',
                'description' => 'Comprehensive class-level academic performance',
                'type' => ReportType::ACADEMIC,
                'category' => ReportCategory::CLASS,
                'available_roles' => ['admin', 'teacher'],
                'parameters' => [
                    'class' => null,
                    'section' => null,
                    'academic_year' => date('Y'),
                ],
            ],
        ]);

        // Filter templates based on user roles
        $availableTemplates = $templates->filter(function ($template) use ($userRoles) {
            return $userRoles->intersect($template['available_roles'])->isNotEmpty();
        });

        return response()->json([
            'success' => true,
            'data' => $availableTemplates->values(),
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();

        $stats = [
            'total_reports' => Report::forUser($user)->count(),
            'active_reports' => Report::forUser($user)->active()->count(),
            'scheduled_reports' => Report::forUser($user)->scheduled()->count(),
            'recent_exports' => $this->getRecentExportCount($user),
            'popular_report_types' => $this->getPopularReportTypes($user),
            'usage_this_month' => $this->getMonthlyUsage($user),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Generate chart data for report
     */
    public function chart(Request $request, Report $report): JsonResponse
    {
        $user = Auth::user();

        if (!$report->canBeGeneratedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to generate charts for this report',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'chart_type' => 'nullable|string|in:line,bar,pie,doughnut,area,multi-line,multi-bar',
            'chart_config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid chart parameters',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Generate the report data first
            $this->reportBuilder->setReport($report);
            $reportData = $this->reportBuilder->generateReport();

            // Extract chart data based on report type
            $chartData = $this->extractChartData($reportData, $report->type);

            // Generate chart configuration
            $chartType = $request->get('chart_type');
            $chartConfig = $request->get('chart_config', []);

            if ($chartType) {
                // Use specific chart type
                $chart = $this->generateSpecificChart($chartData, $chartType, $chartConfig);
            } else {
                // Use default chart for report type
                $chart = $this->chartService->getReportChartConfig(
                    $report->type->value,
                    $chartData,
                    $chartConfig
                );
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'chart' => $chart,
                    'raw_data' => $chartData,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate chart: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get multiple charts for dashboard
     */
    public function dashboardCharts(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name')->toArray();

        // Check basic permissions
        if (empty(array_intersect($userRoles, ['admin', 'teacher']))) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access dashboard charts',
            ], 403);
        }

        try {
            $charts = [];

            // Attendance Overview Chart
            if (in_array('admin', $userRoles) || in_array('teacher', $userRoles)) {
                $attendanceData = $this->attendanceService->generateWeeklyAttendanceReport([
                    'start_date' => now()->subWeeks(4)->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ]);

                $charts['attendance_overview'] = $this->chartService->generateAttendanceTrendChart(
                    $attendanceData['chart_data'] ?? [],
                    ['title' => 'Weekly Attendance Trends']
                );
            }

            // Academic Performance Chart
            if (in_array('admin', $userRoles) || in_array('teacher', $userRoles)) {
                // Mock data for demonstration - would be real data in production
                $gradeData = [
                    'A' => 15,
                    'B' => 25,
                    'C' => 30,
                    'D' => 20,
                    'F' => 10,
                ];

                $charts['grade_distribution'] = $this->chartService->generateGradeDistributionChart(
                    $gradeData,
                    ['title' => 'Current Grade Distribution']
                );
            }

            // Monthly Stats Chart
            if (in_array('admin', $userRoles)) {
                $monthlyStats = [
                    'January' => 92,
                    'February' => 88,
                    'March' => 94,
                    'April' => 91,
                    'May' => 89,
                    'June' => 93,
                ];

                $charts['monthly_performance'] = $this->chartService->generateLineChart(
                    $monthlyStats,
                    [
                        'title' => 'Monthly School Performance',
                        'label' => 'Performance Score (%)',
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'data' => $charts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate dashboard charts: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Helper methods

    protected function getAvailableTypes($user): array
    {
        $userRoles = $user->getRoleNames();
        $availableTypes = [];

        foreach (ReportType::cases() as $type) {
            foreach (ReportCategory::cases() as $category) {
                if ($userRoles->intersect($category->getAvailableRoles())->isNotEmpty()) {
                    $availableTypes[] = $type;
                    break;
                }
            }
        }

        return array_unique($availableTypes);
    }

    protected function getAvailableCategories($user): array
    {
        $userRoles = $user->getRoleNames();
        $availableCategories = [];

        foreach (ReportCategory::cases() as $category) {
            if ($userRoles->intersect($category->getAvailableRoles())->isNotEmpty()) {
                $availableCategories[] = $category;
            }
        }

        return $availableCategories;
    }

    protected function generateDownloadFileName(Report $report, string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $reportName = Str::slug($report->name);

        $extension = match ($format) {
            'excel' => 'xlsx',
            default => $format,
        };

        return "{$reportName}_{$timestamp}.{$extension}";
    }

    protected function findExportFile(Report $report, string $format): ?string
    {
        $directory = "exports/{$format}";
        $files = Storage::disk('private')->files($directory);

        // Find the most recent export file for this report
        $reportSlug = Str::slug($report->name);

        $matchingFiles = array_filter($files, function ($file) use ($reportSlug) {
            return str_contains(basename($file), $reportSlug);
        });

        if (empty($matchingFiles)) {
            return null;
        }

        // Return the most recent file
        usort($matchingFiles, function ($a, $b) {
            return Storage::disk('private')->lastModified($b) - Storage::disk('private')->lastModified($a);
        });

        return $matchingFiles[0];
    }

    protected function getRecentExportCount($user): int
    {
        // This would query export logs in a real implementation
        return 5; // Mock data
    }

    protected function getPopularReportTypes($user): array
    {
        // This would analyze usage patterns in a real implementation
        return [
            ['type' => 'Attendance', 'count' => 15],
            ['type' => 'Academic', 'count' => 12],
            ['type' => 'Student Profile', 'count' => 8],
        ];
    }

    protected function getMonthlyUsage($user): array
    {
        // This would analyze monthly usage in a real implementation
        return [
            'reports_generated' => 23,
            'exports_created' => 18,
            'scheduled_reports_run' => 5,
        ];
    }

    protected function getContentType(string $format): string
    {
        return match ($format) {
            'pdf' => 'application/pdf',
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'html' => 'text/html',
            'json' => 'application/json',
            default => 'application/octet-stream',
        };
    }

    // Helper methods for chart generation

    protected function extractChartData(array $reportData, $reportType): array
    {
        // Extract relevant chart data based on report type
        switch ($reportType->value) {
            case 'attendance':
                return $reportData['chart_data'] ?? $reportData['summary'] ?? [];

            case 'academic':
                return $reportData['performance_data'] ?? $reportData['grades'] ?? [];

            case 'financial':
                return $reportData['financial_summary'] ?? [];

            case 'student_profile':
                return $reportData['demographics'] ?? [];

            default:
                return $reportData['chart_data'] ?? [];
        }
    }

    protected function generateSpecificChart(array $data, string $chartType, array $config = []): array
    {
        return match ($chartType) {
            'line' => $this->chartService->generateLineChart($data, $config),
            'bar' => $this->chartService->generateBarChart($data, $config),
            'pie' => $this->chartService->generatePieChart($data, $config),
            'doughnut' => $this->chartService->generateDoughnutChart($data, $config),
            'area' => $this->chartService->generateAreaChart($data, $config),
            'multi-line' => $this->chartService->generateMultiLineChart(
                $data['series'] ?? [],
                $data['labels'] ?? [],
                $config
            ),
            'multi-bar' => $this->chartService->generateMultiBarChart(
                $data['series'] ?? [],
                $data['labels'] ?? [],
                $config
            ),
            default => $this->chartService->generateLineChart($data, $config),
        };
    }
}
