<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('videos:purge-expired')->daily();

// Off-site backups. One run a night; the Monday run also emails a summary so
// that backups silently stopping is noticed within a week.
Schedule::command('backup:run')->dailyAt('02:00')->withoutOverlapping()->skip(fn () => now()->isMonday());
Schedule::command('backup:run --report')->mondays()->at('02:00')->withoutOverlapping();
