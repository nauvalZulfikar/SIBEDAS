<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmDashboardCache extends Command
{
    protected $signature = 'cache:warm-dashboard';
    protected $description = 'Pre-warm all dashboard caches after scraping';

    public function handle(): int
    {
        $this->info('Warming dashboard caches...');

        // Clear all existing caches first
        Cache::flush();
        $this->info('  Flushed old cache');

        // Refresh kecamatan stats
        $this->call('kecamatan-stats:refresh');

        // Warm menu cache
        Cache::remember('menus_all', 86400, fn() => \App\Models\Menu::all());
        $this->info('  Warmed: menus');

        // Warm bigdata resume
        $resume = \App\Models\BigdataResume::latest()->first();
        if ($resume) {
            Cache::put('bigdata_resume_latest_simbg', $resume, 86400);
            $this->info('  Warmed: bigdata_resume');
        }

        // Warm data settings
        Cache::remember('data_settings_all', 86400, fn() => \App\Models\DataSetting::all());
        $this->info('  Warmed: data_settings');

        $this->info('Done.');
        return self::SUCCESS;
    }
}
