<?php

use Database\Seeders\Support\PlaceholderImages;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sanctum:prune-expired --hours=24')->daily();

Artisan::command('estatein:fixtures', function () {
    $files = PlaceholderImages::generate(database_path('seeders/fixtures'));

    $this->info('Wrote '.count($files).' placeholder images to database/seeders/fixtures.');
})->purpose('Regenerate the placeholder seed images');
