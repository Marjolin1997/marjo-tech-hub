<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('hub:about', function () {
    $this->info('Marjo Tech Hub API');
})->purpose('Display the application name');

Schedule::command('messaging:notify-unread')
    ->everyMinute()
    ->withoutOverlapping();
