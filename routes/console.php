<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('app:info', function () {
    $this->comment(config('app.name'));
})->purpose('Display the application name');
