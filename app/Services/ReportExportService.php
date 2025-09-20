<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportExportService
{
    protected array $supportedFormats = ['pdf', 'excel', 'csv', 'html', 'json'];

    public function exportReport(Report $report, string $format, User $user): array
    {
        // Validate format
        if (!in_array($format, $this->supportedFormats)) {
            throw new Exception("Unsupported export format: {$format}");
        }

        // Check permissions
        if (!$report->canBeGeneratedBy($user)) {
            throw new Exception("User does not have permission to export this report");
        }

        // Generate report data if not cached or expired
        $reportData = $this->getReportData($report);

        // Export based on format
        $exportResult = match ($format) {
            'pdf' => $this->exportToPdf($report, $reportData),
            'excel' => $this->exportToExcel($report, $reportData),
            'csv' => $this->exportToCsv($report, $reportData),
            'html' => $this->exportToHtml($report, $reportData),
            'json' => $this->exportToJson($report, $reportData),
        };

        // Log export activity
        $this->logExportActivity($report, $format, $user);

        return $exportResult;
    }

    public function bulkExport(array $reportIds, string $format, User $user): array
    {
        $exportedFiles = [];
        $errors = [];

        foreach ($reportIds as $reportId) {
            try {
                $report = Report::findOrFail($reportId);
                $result = $this->exportReport($report, $format, $user);
                $exportedFiles[] = $result;
            } catch (Exception $e) {
                $errors[] = [
                    'report_id' => $reportId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Create ZIP file for multiple exports
        if (count($exportedFiles) > 1) {
            $zipResult = $this->createZipArchive($exportedFiles, $format);
            return [
                'type' => 'zip',
                'file_path' => $zipResult['file_path'],
                'file_name' => $zipResult['file_name'],
                'file_size' => $zipResult['file_size'],
                'download_url' => $zipResult['download_url'],
                'exported_count' => count($exportedFiles),
                'errors' => $errors,
            ];
        }

        return [
            'type' => 'single',
            'files' => $exportedFiles,
            'errors' => $errors,
        ];
    }

    public function scheduleExport(Report $report, string $format, array $schedule, User $user): array
    {
        // Validate schedule parameters
        $this->validateScheduleParameters($schedule);

        // Create scheduled export record
        $scheduledExport = $this->createScheduledExport($report, $format, $schedule, $user);

        return [
            'scheduled_export_id' => $scheduledExport['id'],
            'report_id' => $report->id,
            'format' => $format,
            'schedule' => $schedule,
            'next_execution' => $scheduledExport['next_execution'],
            'status' => 'scheduled',
        ];
    }

    protected function getReportData(Report $report): array
    {
        // Check if cached data is still valid
        if ($report->is_cached) {
            return $report->cached_data;
        }

        // Generate fresh report data
        $reportBuilder = app(ReportBuilderService::class);
        $reportBuilder->setReport($report);

        return $reportBuilder->generateReport();
    }

    protected function exportToPdf(Report $report, array $data): array
    {
        $fileName = $this->generateFileName($report, 'pdf');
        $filePath = 'exports/pdf/' . $fileName;

        // Generate PDF content
        $pdfContent = $this->generatePdfContent($report, $data);

        // Store the PDF file
        Storage::disk('private')->put($filePath, $pdfContent);

        return [
            'format' => 'pdf',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => Storage::disk('private')->size($filePath),
            'download_url' => route('reports.download', ['report' => $report->id, 'format' => 'pdf']),
            'expires_at' => now()->addDays(7)->toISOString(),
        ];
    }

    protected function exportToExcel(Report $report, array $data): array
    {
        $fileName = $this->generateFileName($report, 'xlsx');
        $filePath = 'exports/excel/' . $fileName;

        // Generate Excel content
        $excelContent = $this->generateExcelContent($report, $data);

        // Store the Excel file
        Storage::disk('private')->put($filePath, $excelContent);

        return [
            'format' => 'excel',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => Storage::disk('private')->size($filePath),
            'download_url' => route('reports.download', ['report' => $report->id, 'format' => 'excel']),
            'expires_at' => now()->addDays(7)->toISOString(),
        ];
    }

    protected function exportToCsv(Report $report, array $data): array
    {
        $fileName = $this->generateFileName($report, 'csv');
        $filePath = 'exports/csv/' . $fileName;

        // Generate CSV content
        $csvContent = $this->generateCsvContent($data);

        // Store the CSV file
        Storage::disk('private')->put($filePath, $csvContent);

        return [
            'format' => 'csv',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => Storage::disk('private')->size($filePath),
            'download_url' => route('reports.download', ['report' => $report->id, 'format' => 'csv']),
            'expires_at' => now()->addDays(7)->toISOString(),
        ];
    }

    protected function exportToHtml(Report $report, array $data): array
    {
        $fileName = $this->generateFileName($report, 'html');
        $filePath = 'exports/html/' . $fileName;

        // Generate HTML content
        $htmlContent = $this->generateHtmlContent($report, $data);

        // Store the HTML file
        Storage::disk('private')->put($filePath, $htmlContent);

        return [
            'format' => 'html',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => Storage::disk('private')->size($filePath),
            'download_url' => route('reports.download', ['report' => $report->id, 'format' => 'html']),
            'expires_at' => now()->addDays(7)->toISOString(),
        ];
    }

    protected function exportToJson(Report $report, array $data): array
    {
        $fileName = $this->generateFileName($report, 'json');
        $filePath = 'exports/json/' . $fileName;

        // Generate JSON content
        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Store the JSON file
        Storage::disk('private')->put($filePath, $jsonContent);

        return [
            'format' => 'json',
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => Storage::disk('private')->size($filePath),
            'download_url' => route('reports.download', ['report' => $report->id, 'format' => 'json']),
            'expires_at' => now()->addDays(7)->toISOString(),
        ];
    }

    protected function generateFileName(Report $report, string $extension): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $reportName = Str::slug($report->name);

        return "{$reportName}_{$timestamp}.{$extension}";
    }

    protected function generatePdfContent(Report $report, array $data): string
    {
        // This would use a PDF library like TCPDF, DOMPDF, or similar
        // For now, return a simple HTML-to-PDF conversion placeholder

        $html = $this->generateHtmlContent($report, $data);

        // PDF generation logic would go here
        // Using a placeholder for now
        return "PDF content for: " . $report->name . "\n" .
            "Generated at: " . now() . "\n" .
            "Data: " . json_encode($data, JSON_PRETTY_PRINT);
    }

    protected function generateExcelContent(Report $report, array $data): string
    {
        // This would use PhpSpreadsheet or similar library
        // For now, return a CSV-like content that can be opened in Excel

        $content = "Report: {$report->name}\n";
        $content .= "Generated: " . now() . "\n\n";

        // Add data table
        if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
            $firstRow = $data['data'][0];

            // Headers
            $headers = array_keys((array) $firstRow);
            $content .= implode("\t", $headers) . "\n";

            // Data rows
            foreach ($data['data'] as $row) {
                $rowData = array_values((array) $row);
                $content .= implode("\t", $rowData) . "\n";
            }
        }

        return $content;
    }

    protected function generateCsvContent(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Add metadata
        fputcsv($output, ['Report Data Export']);
        fputcsv($output, ['Generated at', now()->toDateTimeString()]);
        fputcsv($output, []); // Empty row

        // Add data
        if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
            $firstRow = $data['data'][0];

            // Headers
            $headers = array_keys((array) $firstRow);
            fputcsv($output, $headers);

            // Data rows
            foreach ($data['data'] as $row) {
                $rowData = array_values((array) $row);
                fputcsv($output, $rowData);
            }
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    protected function generateHtmlContent(Report $report, array $data): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($report->name) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .report-info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .statistics { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .stat-value { font-size: 24px; font-weight: bold; color: #2563eb; }
        .stat-label { color: #6b7280; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .charts { margin-top: 30px; }
        .chart-placeholder { background: #f0f0f0; padding: 40px; text-align: center; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($report->name) . '</h1>
        <p><strong>Report Type:</strong> ' . htmlspecialchars($report->display_type) . '</p>
        <p><strong>Generated:</strong> ' . now()->format('F j, Y \a\t g:i A') . '</p>
        <p><strong>Generated by:</strong> ' . htmlspecialchars($data['generated_by'] ?? 'System') . '</p>
    </div>';

        // Report Info
        if (isset($data['parameters']) && !empty($data['parameters'])) {
            $html .= '<div class="report-info">
                <h3>Report Parameters</h3>';

            foreach ($data['parameters'] as $key => $value) {
                $html .= '<p><strong>' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . ':</strong> ' . htmlspecialchars($value) . '</p>';
            }

            $html .= '</div>';
        }

        // Statistics
        if (isset($data['statistics']) && !empty($data['statistics'])) {
            $html .= '<div class="statistics">';

            foreach ($data['statistics'] as $key => $value) {
                $html .= '<div class="stat-card">
                    <div class="stat-value">' . htmlspecialchars($value) . '</div>
                    <div class="stat-label">' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</div>
                </div>';
            }

            $html .= '</div>';
        }

        // Data Table
        if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
            $html .= '<h3>Report Data</h3>
                <table>';

            $firstRow = $data['data'][0];
            $headers = array_keys((array) $firstRow);

            // Table headers
            $html .= '<thead><tr>';
            foreach ($headers as $header) {
                $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . '</th>';
            }
            $html .= '</tr></thead>';

            // Table body
            $html .= '<tbody>';
            foreach ($data['data'] as $row) {
                $html .= '<tr>';
                foreach ($headers as $header) {
                    $value = $row->$header ?? $row[$header] ?? '';
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody>';

            $html .= '</table>';
        }

        // Charts placeholder
        if (isset($data['charts']) && !empty($data['charts'])) {
            $html .= '<div class="charts">
                <h3>Charts and Visualizations</h3>';

            foreach ($data['charts'] as $chartName => $chartData) {
                $html .= '<div class="chart-placeholder">
                    <h4>' . htmlspecialchars($chartData['title'] ?? $chartName) . '</h4>
                    <p>Chart Type: ' . htmlspecialchars($chartData['type'] ?? 'Unknown') . '</p>
                    <p>Chart data would be rendered here in interactive view</p>
                </div>';
            }

            $html .= '</div>';
        }

        $html .= '
</body>
</html>';

        return $html;
    }

    protected function createZipArchive(array $files, string $format): array
    {
        $zipFileName = 'bulk_export_' . now()->format('Y-m-d_H-i-s') . '.zip';
        $zipFilePath = 'exports/bulk/' . $zipFileName;

        // Create temporary ZIP file content (simplified)
        $zipContent = "ZIP Archive containing " . count($files) . " {$format} files\n";
        $zipContent .= "Created: " . now() . "\n\n";

        foreach ($files as $index => $file) {
            $zipContent .= "File " . ($index + 1) . ": " . $file['file_name'] . "\n";
        }

        // Store the ZIP file
        Storage::disk('private')->put($zipFilePath, $zipContent);

        return [
            'file_path' => $zipFilePath,
            'file_name' => $zipFileName,
            'file_size' => Storage::disk('private')->size($zipFilePath),
            'download_url' => route('reports.bulk-download', ['file' => $zipFileName]),
        ];
    }

    protected function validateScheduleParameters(array $schedule): void
    {
        $requiredFields = ['frequency', 'time'];

        foreach ($requiredFields as $field) {
            if (!isset($schedule[$field])) {
                throw new Exception("Missing required schedule parameter: {$field}");
            }
        }

        $validFrequencies = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];
        if (!in_array($schedule['frequency'], $validFrequencies)) {
            throw new Exception("Invalid schedule frequency: {$schedule['frequency']}");
        }
    }

    protected function createScheduledExport(Report $report, string $format, array $schedule, User $user): array
    {
        // This would create a record in a scheduled_exports table
        // For now, return a mock scheduled export

        $nextExecution = $this->calculateNextExecution($schedule);

        return [
            'id' => Str::uuid(),
            'report_id' => $report->id,
            'format' => $format,
            'frequency' => $schedule['frequency'],
            'scheduled_time' => $schedule['time'],
            'next_execution' => $nextExecution,
            'created_by' => $user->id,
            'created_at' => now(),
        ];
    }

    protected function calculateNextExecution(array $schedule): Carbon
    {
        $frequency = $schedule['frequency'];
        $time = $schedule['time']; // Expected format: HH:MM

        $now = now();
        $nextRun = $now->copy();

        // Set the time
        [$hour, $minute] = explode(':', $time);
        $nextRun->setTime((int) $hour, (int) $minute, 0);

        // If the time has passed today, move to next occurrence
        if ($nextRun->lte($now)) {
            match ($frequency) {
                'daily' => $nextRun->addDay(),
                'weekly' => $nextRun->addWeek(),
                'monthly' => $nextRun->addMonth(),
                'quarterly' => $nextRun->addMonths(3),
                'yearly' => $nextRun->addYear(),
            };
        }

        return $nextRun;
    }

    protected function logExportActivity(Report $report, string $format, User $user): void
    {
        // Log export activity for audit purposes
        // This could be stored in an activity log table

        Log::info('Report exported', [
            'report_id' => $report->id,
            'report_name' => $report->name,
            'format' => $format,
            'user_id' => $user->id,
            'user_name' => $user->first_name . ' ' . $user->last_name,
            'timestamp' => now(),
        ]);
    }

    public function getExportHistory(Report $report, int $limit = 50): array
    {
        // This would retrieve export history from logs or database
        // For now, return a mock history

        return [
            'report_id' => $report->id,
            'exports' => [
                [
                    'id' => Str::uuid(),
                    'format' => 'pdf',
                    'exported_by' => 'John Doe',
                    'exported_at' => now()->subDays(1)->toISOString(),
                    'file_size' => '245 KB',
                    'status' => 'completed',
                ],
                [
                    'id' => Str::uuid(),
                    'format' => 'excel',
                    'exported_by' => 'Jane Smith',
                    'exported_at' => now()->subDays(3)->toISOString(),
                    'file_size' => '178 KB',
                    'status' => 'completed',
                ],
            ],
            'total_count' => 2,
        ];
    }

    public function cleanupExpiredExports(): int
    {
        // Clean up export files older than 7 days
        $expiredDate = now()->subDays(7);
        $deletedCount = 0;

        $directories = ['exports/pdf', 'exports/excel', 'exports/csv', 'exports/html', 'exports/json', 'exports/bulk'];

        foreach ($directories as $directory) {
            $files = Storage::disk('private')->files($directory);

            foreach ($files as $file) {
                $lastModified = Storage::disk('private')->lastModified($file);

                if (Carbon::createFromTimestamp($lastModified)->lt($expiredDate)) {
                    Storage::disk('private')->delete($file);
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    public function getExportStatistics(array $parameters = []): array
    {
        // This would analyze export usage statistics
        // For now, return mock statistics

        return [
            'total_exports_today' => 15,
            'total_exports_this_week' => 89,
            'total_exports_this_month' => 324,
            'most_popular_format' => 'pdf',
            'format_distribution' => [
                'pdf' => 45,
                'excel' => 30,
                'csv' => 15,
                'html' => 8,
                'json' => 2,
            ],
            'most_exported_reports' => [
                ['name' => 'Monthly Attendance Report', 'count' => 23],
                ['name' => 'Student Performance Analysis', 'count' => 18],
                ['name' => 'Class Comparison Report', 'count' => 12],
            ],
            'storage_usage' => [
                'total_size' => '2.3 GB',
                'available_space' => '7.7 GB',
                'cleanup_needed' => false,
            ],
        ];
    }
}
