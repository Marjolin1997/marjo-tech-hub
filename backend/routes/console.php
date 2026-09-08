<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('hub:about', function () {
    $this->info('Marjo Tech Hub API');
})->purpose('Display the application name');
