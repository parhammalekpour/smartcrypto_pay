<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Hello');
})->purpose('Display an inspiring quote');


Schedule::command('blockchain:scan --limit=50')
    ->everyMinute();