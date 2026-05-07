<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler SINTA
|--------------------------------------------------------------------------
| peringatan:kirim  : evaluasi dan kirim notifikasi peringatan siswa dari SIA
| pengumuman:kirim  : kirim notifikasi pengumuman aktif kepada orang tua
|--------------------------------------------------------------------------
*/

Schedule::command('peringatan:kirim')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

Schedule::command('pengumuman:kirim')
    ->everyMinute()
    ->withoutOverlapping();