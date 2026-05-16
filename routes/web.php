<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

// ================== AUTH TERPADU ==================
use App\Http\Controllers\Auth\UnifiedLoginController;

// ================== ADMIN ==================
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PengumumanController;
use App\Http\Controllers\Admin\WaController;
use App\Http\Controllers\Admin\SurveiController;

// ================== SIA MASTER (READ ONLY VIA API) ==================
use App\Http\Controllers\Admin\SiaMasterController as AdminSiaMasterController;
use App\Http\Controllers\Kepsek\SiaMasterController as KepsekSiaMasterController;

// ================== ORTU ==================
use App\Http\Controllers\Ortu\DashboardController as OrtuDashboardController;
use App\Http\Controllers\Ortu\ChatController as OrtuChatController;
use App\Http\Controllers\Ortu\JadwalController as OrtuJadwalController;
use App\Http\Controllers\Ortu\KehadiranController as OrtuKehadiranController;
use App\Http\Controllers\Ortu\NilaiController as OrtuNilaiController;
use App\Http\Controllers\Ortu\EkskulController as OrtuEkskulController;
use App\Http\Controllers\Ortu\AspirasiController as OrtuAspirasiController;
use App\Http\Controllers\Ortu\ProfilController as OrtuProfilController;
use App\Http\Controllers\Ortu\PengumumanController as OrtuPengumumanController;

// ================== KEPSEK ==================
use App\Http\Controllers\Kepsek\DashboardController as KepsekDashboardController;
use App\Http\Controllers\Kepsek\PengumumanController as KepsekPengumumanController;
use App\Http\Controllers\Kepsek\ProfilController as KepsekProfilController;

// ================== WALI KELAS ==================
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\PengumumanController as GuruPengumumanController;
use App\Http\Controllers\Guru\MonitoringController;
use App\Http\Controllers\Guru\ChatController;
use App\Http\Controllers\Guru\ProfilController as GuruProfilController;

// ================== WEBHOOKS ==================
use App\Http\Controllers\Webhook\FonnteWebhookController;

// ================== ROOT ==================
Route::get('/', function () {
    if (auth()->check()) {
        return match (auth()->user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'kepsek' => redirect()->route('kepsek.dashboard'),
            'walkel' => redirect()->route('guru.dashboard'),
            'ortu' => redirect()->route('ortu.dashboard'),
            default => redirect()->route('login'),
        };
    }

    return redirect()->route('login');
})->name('root');

// ================== AUTH TERPADU (GUEST) ==================
Route::middleware('guest')->group(function () {
    Route::get('/login', [UnifiedLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [UnifiedLoginController::class, 'login'])->name('login.store');

    // Redirect route login lama ke login terpadu
    Route::redirect('/admin/login', '/login')->name('login.admin');
    Route::redirect('/kepsek/login', '/login')->name('kepsek.login');
    Route::redirect('/guru/login', '/login')->name('guru.login');
    Route::redirect('/ortu/login', '/login')->name('ortu.login');
});

// ================== ADMIN PANEL ==================
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {

        Route::get('/', fn() => redirect()->route('admin.dashboard'));

        // Dashboard Admin SINTA
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Notifikasi real-time admin (polling endpoint)
        Route::get('/notifikasi', [DashboardController::class, 'getNotifikasi'])->name('notifikasi');
        Route::post('/notifikasi/reset-sia', [DashboardController::class, 'resetNotifikasiSia'])->name('notifikasi.reset-sia');

        /*
        |--------------------------------------------------------------------------
        | SIA MASTER (READ ONLY)
        |--------------------------------------------------------------------------
        */
        Route::prefix('sia-master')->as('sia-master.')->group(function () {
            // SISWA
            Route::get('/siswa', [AdminSiaMasterController::class, 'siswaIndex'])->name('siswa.index');
            Route::get('/siswa/{id}', [AdminSiaMasterController::class, 'siswaShow'])->name('siswa.show');

            // GURU
            Route::get('/guru', [AdminSiaMasterController::class, 'guruIndex'])->name('guru.index');
            Route::get('/guru/{id}', [AdminSiaMasterController::class, 'guruShow'])->name('guru.show');

            // TAHUN AJARAN
            Route::get('/tahun-ajaran', [AdminSiaMasterController::class, 'tahunAjaranIndex'])->name('tahun-ajaran.index');

            // MAPEL
            Route::get('/mapel', [AdminSiaMasterController::class, 'mapelIndex'])->name('mapel.index');
            Route::get('/mapel/{id}', [AdminSiaMasterController::class, 'mapelShow'])->name('mapel.show');

            // JADWAL
            Route::get('/jadwal', [AdminSiaMasterController::class, 'jadwalIndex'])->name('jadwal.index');
            Route::get('/jadwal/{id}', [AdminSiaMasterController::class, 'jadwalShow'])->name('jadwal.show');

            // ROMBEL
            Route::get('/rombel', [AdminSiaMasterController::class, 'rombelIndex'])->name('rombel.index');
            Route::get('/rombel/{id}', [AdminSiaMasterController::class, 'rombelShow'])->name('rombel.show');
            Route::get('/rombel/{id}/anggota', [AdminSiaMasterController::class, 'rombelAnggota'])->name('rombel.anggota');
            Route::get('/rombel/{id}/jadwal', [AdminSiaMasterController::class, 'rombelJadwal'])->name('rombel.jadwal');

            // PRESENSI
            Route::get('/presensi', [AdminSiaMasterController::class, 'presensiIndex'])->name('presensi.index');
            Route::get('/presensi/{id}', [AdminSiaMasterController::class, 'presensiShow'])->name('presensi.show');

            // NILAI
            Route::get('/nilai', [AdminSiaMasterController::class, 'nilaiIndex'])->name('nilai.index');
            Route::get('/nilai/{nilai}', [AdminSiaMasterController::class, 'nilaiShow'])->name('nilai.show');

            // EKSKUL
            Route::get('/ekskul', [AdminSiaMasterController::class, 'ekskulIndex'])->name('ekskul.index');
            Route::get('/ekskul/{id}', [AdminSiaMasterController::class, 'ekskulShow'])->name('ekskul.show');
        });

        /*
        |--------------------------------------------------------------------------
        | FITUR INTERNAL SINTA YANG TETAP DIPAKAI
        |--------------------------------------------------------------------------
        */

        // Pengumuman
        Route::get('pengumuman/{pengumuman}/pdf', [PengumumanController::class, 'pdfView'])
            ->name('pengumuman.pdf.view');

        Route::get('pengumuman/{pengumuman}/pdf/download', [PengumumanController::class, 'pdfDownload'])
            ->name('pengumuman.pdf.download');

        Route::resource('pengumuman', PengumumanController::class);

        // Chat Admin (WA)
        Route::prefix('chat')->name('chat.')->group(function () {
            Route::get('/', [WaController::class, 'index'])->name('index');
            Route::get('/create', [WaController::class, 'create'])->name('create');
            Route::post('/', [WaController::class, 'store'])->name('store');

            Route::get('/manual', [WaController::class, 'showManualForm'])->name('manual');
            Route::post('/manual', [WaController::class, 'sendManualMessage'])->name('manual.send');

            Route::get('/{id}', [WaController::class, 'show'])->name('show');

            Route::get('/{id}/fetch-new-messages', [WaController::class, 'fetchNewMessages'])
                ->name('fetchNewMessages');

            Route::get('/{id}/reply', function ($id) {
                return redirect()->route('admin.chat.show', $id);
            })->name('reply.redirect');

            Route::post('/{id}/reply', [WaController::class, 'reply'])->name('reply');
        });

        // Survei Ortu
        Route::resource('survei', SurveiController::class);

        Route::post('survei/{survei}/pertanyaan', [SurveiController::class, 'storePertanyaan'])
            ->name('survei.storePertanyaan');

        Route::put('survei/pertanyaan/{pertanyaan}', [SurveiController::class, 'updatePertanyaan'])
            ->name('survei.updatePertanyaan');

        Route::delete('survei/pertanyaan/{pertanyaan}', [SurveiController::class, 'destroyPertanyaan'])
            ->name('survei.destroyPertanyaan');

        Route::get('survei/pertanyaan/{pertanyaan}/opsi', [SurveiController::class, 'getOpsi'])
            ->name('survei.getOpsi');

        Route::put('survei/opsi/{opsi}', [SurveiController::class, 'updateOpsi'])
            ->name('survei.updateOpsi');

        Route::post('survei/{pertanyaan}/opsi', [SurveiController::class, 'storeOpsi'])
            ->name('survei.storeOpsi');

        Route::delete('survei/opsi/{opsi}', [SurveiController::class, 'destroyOpsi'])
            ->name('survei.destroyOpsi');

        Route::post('survei/{survei}/reorder', [SurveiController::class, 'reorderPertanyaan'])
            ->name('survei.reorderPertanyaan');

        Route::get('survei/{survei}/hasil', [SurveiController::class, 'hasil'])
            ->name('survei.hasil');

        Route::get('survei/{survei}/export/excel', [SurveiController::class, 'exportExcel'])
            ->name('survei.export.excel');

        Route::get('survei/{survei}/export/pdf', [SurveiController::class, 'exportPdf'])
            ->name('survei.export.pdf');
    });

// ================== WEBHOOKS ==================
Route::match(['POST', 'GET'], '/webhook/fonnte', [FonnteWebhookController::class, 'handle'])
    ->name('webhook.fonnte')
    ->withoutMiddleware([VerifyCsrfToken::class]);

// ================== PORTAL KEPSEK ==================
Route::middleware(['auth', 'role:kepsek'])
    ->prefix('kepsek')
    ->as('kepsek.')
    ->group(function () {

        Route::get('/dashboard', [KepsekDashboardController::class, 'index'])->name('dashboard');

        // Notifikasi real-time kepsek (polling endpoint)
        Route::get('/notifikasi', [KepsekDashboardController::class, 'getNotifikasi'])->name('notifikasi');
        Route::post('/notifikasi/reset-sia', [KepsekDashboardController::class, 'resetNotifikasiSia'])->name('notifikasi.reset-sia');

        Route::get('/profil', [KepsekProfilController::class, 'show'])->name('profil');
        Route::get('/profil/foto', [KepsekProfilController::class, 'photo'])->name('profil.photo');

        /*
        |--------------------------------------------------------------------------
        | PENGUMUMAN (APPROVAL)
        |--------------------------------------------------------------------------
        */
        Route::prefix('pengumuman')->as('pengumuman.')->group(function () {
            Route::get('/', [KepsekPengumumanController::class, 'index'])->name('index');

            Route::get('/{pengumuman}/pdf', [KepsekPengumumanController::class, 'pdfView'])
                ->name('pdf.view');

            Route::get('/{pengumuman}/pdf/download', [KepsekPengumumanController::class, 'pdfDownload'])
                ->name('pdf.download');

            Route::get('/{pengumuman}', [KepsekPengumumanController::class, 'show'])->name('show');
            Route::put('/{pengumuman}/approve', [KepsekPengumumanController::class, 'approve'])->name('approve');
            Route::put('/{pengumuman}/reject', [KepsekPengumumanController::class, 'reject'])->name('reject');
        });

        /*
        |--------------------------------------------------------------------------
        | SIA MASTER (READ ONLY)
        |--------------------------------------------------------------------------
        */
        Route::prefix('sia-master')->as('sia-master.')->group(function () {
            // SISWA
            Route::get('/siswa', [KepsekSiaMasterController::class, 'siswaIndex'])->name('siswa.index');
            Route::get('/siswa/{id}', [KepsekSiaMasterController::class, 'siswaShow'])->name('siswa.show');

            // GURU
            Route::get('/guru', [KepsekSiaMasterController::class, 'guruIndex'])->name('guru.index');
            Route::get('/guru/{id}', [KepsekSiaMasterController::class, 'guruShow'])->name('guru.show');

            // TAHUN AJARAN
            Route::get('/tahun-ajaran', [KepsekSiaMasterController::class, 'tahunAjaranIndex'])->name('tahun-ajaran.index');

            // MAPEL
            Route::get('/mapel', [KepsekSiaMasterController::class, 'mapelIndex'])->name('mapel.index');
            Route::get('/mapel/{id}', [KepsekSiaMasterController::class, 'mapelShow'])->name('mapel.show');

            // JADWAL
            Route::get('/jadwal', [KepsekSiaMasterController::class, 'jadwalIndex'])->name('jadwal.index');
            Route::get('/jadwal/{id}', [KepsekSiaMasterController::class, 'jadwalShow'])->name('jadwal.show');

            // ROMBEL
            Route::get('/rombel', [KepsekSiaMasterController::class, 'rombelIndex'])->name('rombel.index');
            Route::get('/rombel/{id}', [KepsekSiaMasterController::class, 'rombelShow'])->name('rombel.show');
            Route::get('/rombel/{id}/anggota', [KepsekSiaMasterController::class, 'rombelAnggota'])->name('rombel.anggota');
            Route::get('/rombel/{id}/jadwal', [KepsekSiaMasterController::class, 'rombelJadwal'])->name('rombel.jadwal');

            // PRESENSI
            Route::get('/presensi', [KepsekSiaMasterController::class, 'presensiIndex'])->name('presensi.index');
            Route::get('/presensi/{id}', [KepsekSiaMasterController::class, 'presensiShow'])->name('presensi.show');

            // NILAI
            Route::get('/nilai', [KepsekSiaMasterController::class, 'nilaiIndex'])->name('nilai.index');
            Route::get('/nilai/{nilai}', [KepsekSiaMasterController::class, 'nilaiShow'])->name('nilai.show');

            // EKSKUL
            Route::get('/ekskul', [KepsekSiaMasterController::class, 'ekskulIndex'])->name('ekskul.index');
            Route::get('/ekskul/{id}', [KepsekSiaMasterController::class, 'ekskulShow'])->name('ekskul.show');
        });
    });

// ================== PORTAL WALI KELAS ==================
Route::middleware(['auth', 'role:walkel'])
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {

        Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');

        // Notifikasi real-time walkel (polling endpoint)
        Route::get('/notifikasi', [GuruDashboardController::class, 'getNotifikasi'])->name('notifikasi');
        Route::post('/notifikasi/reset-sia', [GuruDashboardController::class, 'resetNotifikasiSia'])->name('notifikasi.reset-sia');

        Route::get('/profil', [GuruProfilController::class, 'show'])->name('profil');
        Route::get('/profil/foto', [GuruProfilController::class, 'photo'])->name('profil.photo');

        // Pengumuman
        Route::get('/pengumuman', [GuruPengumumanController::class, 'index'])->name('pengumuman.index');
        Route::get('/pengumuman/{pengumuman}/pdf', [GuruPengumumanController::class, 'pdfView'])->name('pengumuman.pdf.view');
        Route::get('/pengumuman/{pengumuman}/pdf/download', [GuruPengumumanController::class, 'pdfDownload'])->name('pengumuman.pdf.download');
        Route::get('/pengumuman/{pengumuman}', [GuruPengumumanController::class, 'show'])->name('pengumuman.show');

        // Monitoring Walikelas
        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/monitoring/rombel/{rombel}/siswa/{nis}', [MonitoringController::class, 'siswaShow'])->name('monitoring.siswa.show');

        // Chat Walikelas
        Route::prefix('chat')->as('chat.')->group(function () {
            Route::get('/', [ChatController::class, 'index'])->name('index');
            Route::get('/create', [ChatController::class, 'create'])->name('create');
            Route::post('/', [ChatController::class, 'store'])->name('store');

            Route::get('/{thread}/fetch-new-messages', [ChatController::class, 'fetchNewMessages'])
                ->name('fetchNewMessages');

            Route::get('/{thread}', [ChatController::class, 'show'])->name('show');

            Route::get('/{thread}/send', function (int $thread) {
                return redirect()->route('guru.chat.show', $thread);
            })->name('send.redirect');

            Route::post('/{thread}/send', [ChatController::class, 'send'])->name('send');
        });
    });

// ================== PORTAL ORTU ==================
Route::middleware(['auth', 'role:ortu'])
    ->prefix('ortu')
    ->as('ortu.')
    ->group(function () {

        Route::get('/dashboard', [OrtuDashboardController::class, 'index'])->name('dashboard');

        // Notifikasi real-time ortu (polling endpoint)
        Route::get('/notifikasi', [OrtuDashboardController::class, 'getNotifikasi'])->name('notifikasi');
        Route::post('/notifikasi/reset-sia', [OrtuDashboardController::class, 'resetNotifikasiSia'])->name('notifikasi.reset-sia');

        Route::get('/profil', [OrtuProfilController::class, 'show'])->name('profil');
        Route::get('/profil/foto', [OrtuProfilController::class, 'photo'])->name('profil.photo');

        // Jadwal
        Route::get('/jadwal', [OrtuJadwalController::class, 'index'])->name('jadwal.index');

        // Kehadiran
        Route::get('/kehadiran', [OrtuKehadiranController::class, 'index'])->name('kehadiran.index');

        // Nilai
        Route::get('/nilai', [OrtuNilaiController::class, 'index'])->name('nilai.index');
        Route::get('/nilai/export-pdf', [OrtuNilaiController::class, 'exportPdf'])->name('nilai.exportPdf');

        // Ekskul
        Route::get('/ekskul', [OrtuEkskulController::class, 'index'])->name('ekskul.index');

        // Chat
        Route::prefix('chat')->as('chat.')->group(function () {
            Route::get('/', [OrtuChatController::class, 'index'])->name('index');
            Route::get('/create', [OrtuChatController::class, 'create'])->name('create');
            Route::post('/', [OrtuChatController::class, 'store'])->name('store');

            Route::get('/{thread}/fetch-new-messages', [OrtuChatController::class, 'fetchNewMessages'])
                ->name('fetchNewMessages');

            Route::get('/{thread}', [OrtuChatController::class, 'show'])->name('show');

            Route::get('/{thread}/send', function ($thread) {
                return redirect()->route('ortu.chat.show', $thread);
            })->name('send.redirect');

            Route::post('/{thread}/send', [OrtuChatController::class, 'send'])->name('send');
        });

        // Pengumuman
        Route::prefix('pengumuman')->as('pengumuman.')->group(function () {
            Route::get('/', [OrtuPengumumanController::class, 'index'])->name('index');
            Route::get('/{pengumuman}', [OrtuPengumumanController::class, 'show'])->name('show');
            Route::get('/{pengumuman}/pdf', [OrtuPengumumanController::class, 'pdfView'])->name('pdf.view');
            Route::get('/{pengumuman}/pdf/download', [OrtuPengumumanController::class, 'pdfDownload'])->name('pdf.download');
        });

        // Aspirasi
        Route::prefix('aspirasi')->as('aspirasi.')->group(function () {
            Route::get('/', [OrtuAspirasiController::class, 'index'])->name('index');
            Route::get('/isi/{id}', [OrtuAspirasiController::class, 'isi'])->name('isi');
            Route::post('/kirim/{id}', [OrtuAspirasiController::class, 'kirim'])->name('kirim');
            Route::get('/riwayat', [OrtuAspirasiController::class, 'riwayat'])->name('riwayat');
            Route::get('/riwayat/{id}', [OrtuAspirasiController::class, 'showRiwayat'])->name('showRiwayat');
        });
    });

// ================== LOGOUT TERPADU ==================
Route::post('/logout', [UnifiedLoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ================== ROUTE LAMA UNTUK KOMPATIBILITAS ==================
Route::post('/kepsek/logout', [UnifiedLoginController::class, 'logout'])->name('kepsek.logout');
Route::post('/guru/logout', [UnifiedLoginController::class, 'logout'])->name('guru.logout');
Route::post('/ortu/logout', [UnifiedLoginController::class, 'logout'])->name('ortu.logout');