<?php

use App\Services\SiteBrandingService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('branding:publish-due', function (SiteBrandingService $branding) {
    $published = $branding->activateDueScheduledVersion();

    if ($published === null) {
        $this->info('Tidak ada versi identitas yang jatuh tempo.');

        return self::SUCCESS;
    }

    $this->info("Versi identitas #{$published->id} ({$published->label}) berhasil dipublikasikan.");

    return self::SUCCESS;
})->purpose('Publikasikan versi identitas situs yang sudah jatuh tempo');

Schedule::command('branding:publish-due')
    ->name('publish-due-site-branding')
    ->everyMinute()
    ->withoutOverlapping();
