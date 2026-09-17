<?php

namespace ImranDevBd\AttendanceHub\Commands;

use Illuminate\Console\Command;
use ImranDevBd\AttendanceHub\Support\DeviceCatalog;

class ListDeviceCatalogCommand extends Command
{
    protected $signature = 'attendance:catalog
                            {--provider= : Filter models by brand provider (e.g. zkteco, hikvision, anviz, fingertec, essl, virdi, suprema, deli, granding, soyal)}
                            {--search= : Search models by name, series, or keyword}';

    protected $description = 'Display the supported hardware devices and models catalog with communication protocols and driver mappings';

    public function handle(): int
    {
        $providerFilter = $this->option('provider');
        $searchFilter = $this->option('search');

        if ($searchFilter) {
            $models = DeviceCatalog::search($searchFilter);
            $this->info("Found " . count($models) . " models matching '{$searchFilter}':");
        } elseif ($providerFilter) {
            $models = DeviceCatalog::forProvider($providerFilter);
            $this->info("Found " . count($models) . " models for provider '{$providerFilter}':");
        } else {
            $models = DeviceCatalog::all();
            $this->info("Displaying all " . count($models) . " supported hardware models across 10 global providers:");
        }

        if (empty($models)) {
            $this->warn('No models matched your criteria.');
            return self::SUCCESS;
        }

        $headers = ['Provider', 'Model', 'Series', 'Biometrics', 'Protocol / Port', 'ADMS', 'Driver', 'Market'];
        $rows = [];

        foreach ($models as $entry) {
            $bio = [];
            if (!empty($entry['fp'])) $bio[] = 'FP';
            if (!empty($entry['face'])) $bio[] = 'Face';
            if (!empty($entry['palm'])) $bio[] = 'Palm';
            if (!empty($entry['rfid'])) $bio[] = 'RFID';

            $rows[] = [
                $entry['provider'],
                $entry['model'],
                $entry['series'] ?? '-',
                implode('/', $bio),
                ($entry['tcp'] ? 'TCP' : 'Serial/Wiegand') . " (:{$entry['port']})",
                !empty($entry['adms']) ? 'Yes' : 'No',
                $entry['driver'],
                $entry['bd_market'] ?? 'High',
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->line("Summary by brand: " . collect(DeviceCatalog::summary())->map(fn ($c, $p) => "{$p}: {$c}")->implode(' | '));
        $this->line("To connect directly by model: <fg=yellow>AttendanceHub::model('UA860')->connect([...])</>");

        return self::SUCCESS;
    }
}
