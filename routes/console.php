<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwalkan command pengiriman email terjadwal
// Dijalankan setiap menit untuk memeriksa email yang perlu dikirim
Schedule::command('emails:send-scheduled')->everyMinute();
