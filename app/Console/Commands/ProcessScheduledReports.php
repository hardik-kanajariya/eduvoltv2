<?php

namespace App\Console\Commands;

use App\Jobs\GenerateScheduledReport;
use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reports:process-scheduled 
                            {--dry-run : Show what would be processed without actually dispatching jobs}
                            {--limit=10 : Maximum number of reports to process}';

    /**
     * The console command description.
     */
    protected $description = 'Process scheduled reports that are due to run';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Processing scheduled reports...');

        // Find reports that are due to run
        $dueReports = Report::query()
            ->where('is_scheduled', true)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('next_run_at', '<=', now())
                    ->orWhereNull('next_run_at');
            })
            ->whereNotNull('schedule_frequency')
            ->with('creator')
            ->limit($limit)
            ->get();

        if ($dueReports->isEmpty()) {
            $this->info('No scheduled reports due for processing.');
            return self::SUCCESS;
        }

        $this->info("Found {$dueReports->count()} scheduled reports due for processing.");

        $processed = 0;
        $errors = 0;

        foreach ($dueReports as $report) {
            try {
                $this->processReport($report, $isDryRun);
                $processed++;
            } catch (\Exception $e) {
                $errors++;
                $this->error("Error processing report {$report->id}: {$e->getMessage()}");
                Log::error('Scheduled report processing error', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processing complete. Processed: {$processed}, Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function processReport(Report $report, bool $isDryRun): void
    {
        $this->line("Processing report: {$report->name} (ID: {$report->id})");
        $this->line("  - Created by: {$report->creator->name}");
        $this->line("  - Frequency: {$report->schedule_frequency}");
        $this->line("  - Next run: " . ($report->next_run_at?->format('Y-m-d H:i:s') ?? 'Not set'));

        if ($isDryRun) {
            $this->line("  - [DRY RUN] Would dispatch GenerateScheduledReport job");
            return;
        }

        // Dispatch the job to generate the report
        GenerateScheduledReport::dispatch($report, $report->creator);

        $this->line("  - ✓ Job dispatched successfully");

        // Log the dispatch
        Log::info('Scheduled report job dispatched', [
            'report_id' => $report->id,
            'report_name' => $report->name,
            'user_id' => $report->creator->id,
        ]);
    }
}
