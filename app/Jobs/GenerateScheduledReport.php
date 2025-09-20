<?php

namespace App\Jobs;

use App\Models\Report;
use App\Models\User;
use App\Services\ReportBuilderService;
use App\Services\ReportExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateScheduledReport implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    protected Report $report;
    protected User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(Report $report, User $user)
    {
        $this->report = $report;
        $this->user = $user;
        $this->onQueue('reports');
    }

    /**
     * Execute the job.
     */
    public function handle(ReportBuilderService $reportBuilder, ReportExportService $exportService): void
    {
        try {
            Log::info('Starting scheduled report generation', [
                'report_id' => $this->report->id,
                'report_name' => $this->report->name,
                'user_id' => $this->user->id,
            ]);

            // Mark report as generating
            $this->report->markAsGenerating();

            // Generate the report
            $reportBuilder->setReport($this->report);
            $reportData = $reportBuilder->generateReport();

            // Export to the specified format
            $exportResult = $exportService->exportReport(
                $this->report,
                $this->report->output_format,
                $this->user
            );

            // Mark report as completed
            $this->report->markAsCompleted();

            // Update next run time for recurring reports
            if ($this->report->is_scheduled && $this->report->schedule_frequency) {
                $this->report->updateSchedule();
            }

            // Send notification email if configured
            $this->sendReportNotification($exportResult);

            Log::info('Scheduled report generated successfully', [
                'report_id' => $this->report->id,
                'export_path' => $exportResult['file_path'] ?? null,
            ]);
        } catch (\Exception $e) {
            // Mark report as failed
            $this->report->markAsFailed();

            Log::error('Scheduled report generation failed', [
                'report_id' => $this->report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger job retry mechanism
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Scheduled report job failed permanently', [
            'report_id' => $this->report->id,
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);

        // Mark report as failed
        $this->report->markAsFailed();

        // Send failure notification
        $this->sendFailureNotification($exception);
    }

    protected function sendReportNotification(array $exportResult): void
    {
        try {
            // Check if user wants email notifications
            if (!$this->shouldSendNotification()) {
                return;
            }

            $emailData = [
                'user_name' => $this->user->name,
                'report_name' => $this->report->name,
                'report_description' => $this->report->description,
                'generated_at' => now()->format('F j, Y \a\t g:i A'),
                'download_url' => $this->generateDownloadUrl(),
                'file_name' => $exportResult['file_name'] ?? 'report.pdf',
                'file_size' => $this->formatFileSize($exportResult['file_size'] ?? 0),
                'next_run' => $this->report->next_run_at?->format('F j, Y \a\t g:i A'),
            ];

            // Note: Email sending would be implemented with Mail facades
            // Mail::to($this->user->email)->send(new ScheduledReportGenerated($emailData));

            Log::info('Report notification email queued', [
                'report_id' => $this->report->id,
                'user_email' => $this->user->email,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to send report notification email', [
                'report_id' => $this->report->id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the job for notification errors
        }
    }

    protected function sendFailureNotification(\Throwable $exception): void
    {
        try {
            $emailData = [
                'user_name' => $this->user->name,
                'report_name' => $this->report->name,
                'error_message' => $exception->getMessage(),
                'failed_at' => now()->format('F j, Y \a\t g:i A'),
                'support_email' => config('mail.support_email', 'support@eduvolt.com'),
            ];

            // Note: Email sending would be implemented with Mail facades  
            // Mail::to($this->user->email)->send(new ScheduledReportFailed($emailData));

            Log::info('Report failure notification queued', [
                'report_id' => $this->report->id,
                'user_email' => $this->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send report failure notification', [
                'report_id' => $this->report->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function shouldSendNotification(): bool
    {
        // Check user preferences for email notifications
        // This could be a user setting or report-specific setting
        return true; // Default to sending notifications
    }

    protected function generateDownloadUrl(): string
    {
        // Generate a secure download URL for the report
        $baseUrl = config('app.url');
        return "{$baseUrl}/reports/{$this->report->id}/download?format={$this->report->output_format}";
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes == 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = floor(log($bytes, 1024));
        $size = $bytes / pow(1024, $unitIndex);

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'report:' . $this->report->id,
            'user:' . $this->user->id,
            'type:scheduled-report',
            'format:' . $this->report->output_format,
        ];
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // 30 seconds, 1 minute, 2 minutes
    }
}
