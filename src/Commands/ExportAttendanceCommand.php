<?php

namespace ImranDevBd\AttendanceHub\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;

class ExportAttendanceCommand extends Command
{
    protected $signature = 'attendance:export 
                            {--from= : Start date (YYYY-MM-DD, defaults to 7 days ago)}
                            {--to= : End date (YYYY-MM-DD, defaults to today)}
                            {--format=csv : Export format: csv or json}
                            {--output= : Optional output file path}';

    protected $description = 'Calculate and export daily attendance summary reports';

    public function handle(): int
    {
        $fromStr = $this->option('from') ?: now()->subDays(7)->format('Y-m-d');
        $toStr = $this->option('to') ?: now()->format('Y-m-d');
        $format = strtolower($this->option('format') ?: 'csv');
        $outputFile = $this->option('output');

        $startDate = Carbon::parse($fromStr);
        $endDate = Carbon::parse($toStr);

        $this->info("Calculating attendance summaries from {$fromStr} to {$toStr}...");

        $calculator = AttendanceHub::calculator();
        $records = collect();

        $current = $startDate->copy();
        while ($current->lessThanOrEqualTo($endDate)) {
            $daySummaries = $calculator->calculateDay($current);
            foreach ($daySummaries as $summary) {
                $records->push($summary->toArray());
            }
            $current->addDay();
        }

        if ($records->isEmpty()) {
            $this->warn('No attendance records found for the specified period.');
            return self::SUCCESS;
        }

        if ($format === 'json') {
            $content = $records->toJson(JSON_PRETTY_PRINT);
            if ($outputFile) {
                file_put_contents($outputFile, $content);
                $this->info("Exported " . $records->count() . " records to JSON: {$outputFile}");
            } else {
                $this->line($content);
            }
            return self::SUCCESS;
        }

        // CSV Format
        $headers = array_keys($records->first());
        $csvContent = implode(',', $headers) . "\n";

        foreach ($records as $row) {
            $csvContent .= implode(',', array_map(fn ($val) => '"' . str_replace('"', '""', (string) $val) . '"', array_values($row))) . "\n";
        }

        if ($outputFile) {
            file_put_contents($outputFile, $csvContent);
            $this->info("Exported " . $records->count() . " records to CSV: {$outputFile}");
        } else {
            $this->table($headers, $records->take(20)->toArray());
            if ($records->count() > 20) {
                $this->line("... and " . ($records->count() - 20) . " more records (use --output=path.csv to save all).");
            }
        }

        return self::SUCCESS;
    }
}
