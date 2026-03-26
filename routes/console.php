<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command("app:start-scraping-data --confirm")->dailyAt("00:00");
Schedule::command("app:monitor-scraping")->everyThirtyMinutes();

