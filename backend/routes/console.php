<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// jadwal tugas latar belakang (background cron jobs)
Schedule::command('billing:evaluate-subscriptions')->daily();
Schedule::command('media:clean-staging')->daily();
Schedule::command('media:clean-retention')->monthly();
