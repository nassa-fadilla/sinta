<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Services\SiaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFIKASI REAL-TIME ORTU
    |--------------------------------------------------------------------------
    | Endpoint polling: GET /ortu/notifikasi
    | Semua data personal siswa di-scope ke NIS milik akun ortu yang login.
    | Cache key per-user agar snapshot tiap ortu tidak saling campur.
    */
    public function getNotifikasi(): JsonResponse
    {
        $user = Auth::user();
        $nis = trim((string) ($user->sia_user_id ?? ''));
        $items = [];
        $cacheKey = 'sinta.notif.ortu.' . $user->id . '.';

        if ($nis === '') {
            return response()->json(['total' => 0, 'items' => []]);
        }

        // ── 1. CHAT BARU DARI ADMIN / WALKEL ────────────────────────────────
        // direction='in' = pesan masuk dari pihak sekolah ke ortu
        // sender_type: 'admin', 'guru', 'walkel'
        // thread scope: owner_parent_id = user ortu yang login
        $chatUnread = DB::table('chat_messages as m')
            ->join('chat_threads as t', 't.id', '=', 'm.thread_id')
            ->where('t.owner_parent_id', $user->id)
            ->where('m.direction', 'in')
            ->whereIn('m.sender_type', ['admin', 'guru', 'walkel'])
            ->whereNull('m.read_at')
            ->count();

        if ($chatUnread > 0) {
            $items[] = [
                'id' => 'chat',
                'icon' => 'chat',
                'judul' => 'Pesan Baru',
                'pesan' => $chatUnread . ' pesan baru dari pihak sekolah',
                'url' => route('ortu.chat.index'),
                'waktu' => null,
            ];
        }

        // ── 2. PENGUMUMAN BARU (24 jam, scope siswa) ─────────────────────────
        try {
            $siswaApi = $this->resolveSiswaDetailByNis($nis);
            $tingkat = $this->resolveTingkatSiswa($siswaApi);
            $rombelNama = $this->normalizeRombelName($this->resolveRombelAktifNama($siswaApi));
            $rombelId = $this->resolveRombelAktifId($siswaApi);

            $pengumumanBaru = Pengumuman::where('status', 'approved')
                ->where('publish_at', '>=', now()->subDay())
                ->orderByDesc('publish_at')
                ->get(['id', 'judul', 'target_scope', 'target_tingkat', 'publish_at'])
                ->filter(function ($p) use ($tingkat) {
                    $scope = strtolower(trim((string) ($p->target_scope ?? '')));
                    if (in_array($scope, ['', 'all', 'semua', 'umum']))
                        return true;
                    if (in_array($scope, ['tingkat', 'level'])) {
                        return $tingkat && $this->normalizeTingkat($p->target_tingkat) === $tingkat;
                    }
                    return false; // scope kelas tidak ada kolom langsung, skip
                });

            foreach ($pengumumanBaru as $p) {
                $items[] = [
                    'id' => 'pengumuman_' . $p->id,
                    'icon' => 'megaphone',
                    'judul' => 'Pengumuman Baru',
                    'pesan' => '"' . Str::limit($p->judul, 50) . '"',
                    'url' => route('ortu.pengumuman.show', $p->id),
                    'waktu' => $p->publish_at?->diffForHumans(),
                ];
            }
        } catch (\Throwable) {
        }

        // ── 3. SURVEI BARU (aspirasi yang belum diisi ortu ini) ──────────────
        try {
            $sudahDiisi = DB::table('survei_respon')
                ->where('user_id', $user->id)
                ->pluck('survei_id')
                ->toArray();

            $surveiBaru = DB::table('survei')
                ->where('is_active', 1)
                ->whereNotIn('id', $sudahDiisi)
                ->orderByDesc('created_at')
                ->limit(3)
                ->get(['id', 'judul', 'created_at']);

            foreach ($surveiBaru as $s) {
                $items[] = [
                    'id' => 'survei_' . $s->id,
                    'icon' => 'clipboard',
                    'judul' => 'Survei Baru',
                    'pesan' => '"' . Str::limit($s->judul, 50) . '" menunggu diisi',
                    'url' => route('ortu.aspirasi.isi', $s->id),
                    'waktu' => Carbon::parse($s->created_at)->diffForHumans(),
                ];
            }
        } catch (\Throwable) {
        }

        // ── 4‑7. DATA SIA PERSONAL SISWA ─────────────────────────────────────
        try {
            $activePeriod = $this->resolveActiveAcademicPeriod();
            $tahunAjaranAktif = $activePeriod['nama_tahun'] ?? null;
            $tahunAjaranAktifId = $activePeriod['id'] ?? null;
            $semesterAktif = $activePeriod['semester'] ?? null;

            // 4. NILAI BARU
            try {
                $nilaiRes = $this->sia->getNilaiByNis($nis, array_filter([
                    'tahun_ajaran_id' => $tahunAjaranAktifId,
                    'tahun_ajaran' => $tahunAjaranAktif,
                    'semester' => $semesterAktif,
                ], fn($v) => $v !== null && $v !== ''));
                $nilaiRows = count($nilaiRes['data']['nilai'] ?? $nilaiRes['data'] ?? []);

                $snapKey = $cacheKey . 'nilai';
                $snap = (int) Cache::get($snapKey, $nilaiRows);
                if (!Cache::has($snapKey))
                    Cache::forever($snapKey, $nilaiRows);

                if ($nilaiRows - $snap > 0) {
                    $items[] = [
                        'id' => 'sia_nilai',
                        'icon' => 'academic-cap',
                        'judul' => 'Nilai Baru Masuk',
                        'pesan' => ($nilaiRows - $snap) . ' nilai baru telah diinputkan guru',
                        'url' => route('ortu.nilai.index'),
                        'waktu' => null,
                    ];
                }
            } catch (\Throwable) {
            }

            // 5. KEHADIRAN BARU
            try {
                $presensiRes = $this->sia->getPresensiByNis($nis, array_filter([
                    'tahun_ajaran_id' => $tahunAjaranAktifId,
                    'tahun_ajaran' => $tahunAjaranAktif,
                    'bulan' => now()->format('Y-m'),
                ], fn($v) => $v !== null && $v !== ''));
                $presensiData = $presensiRes['data']['presensi']
                    ?? $presensiRes['data']['detail']
                    ?? $presensiRes['data']
                    ?? [];
                $presensiCount = is_array($presensiData) ? count($presensiData) : 0;

                $snapKey = $cacheKey . 'presensi';
                $snap = (int) Cache::get($snapKey, $presensiCount);
                if (!Cache::has($snapKey))
                    Cache::forever($snapKey, $presensiCount);

                if ($presensiCount - $snap > 0) {
                    $items[] = [
                        'id' => 'sia_presensi',
                        'icon' => 'clipboard-check',
                        'judul' => 'Kehadiran Baru Tercatat',
                        'pesan' => ($presensiCount - $snap) . ' catatan kehadiran baru bulan ini',
                        'url' => route('ortu.kehadiran.index'),
                        'waktu' => null,
                    ];
                }
            } catch (\Throwable) {
            }

            // 6. EKSKUL BARU
            try {
                $ekskulRes = $this->sia->getEkskulByNis($nis, array_filter([
                    'tahun_ajaran_id' => $tahunAjaranAktifId,
                    'tahun_ajaran' => $tahunAjaranAktif,
                ], fn($v) => $v !== null && $v !== ''));
                $ekskulData = $ekskulRes['data']['ekskul'] ?? $ekskulRes['data'] ?? [];
                $ekskulCount = is_array($ekskulData) ? count($ekskulData) : 0;

                $snapKey = $cacheKey . 'ekskul';
                $snap = (int) Cache::get($snapKey, $ekskulCount);
                if (!Cache::has($snapKey))
                    Cache::forever($snapKey, $ekskulCount);

                if ($ekskulCount - $snap > 0) {
                    $items[] = [
                        'id' => 'sia_ekskul',
                        'icon' => 'star',
                        'judul' => 'Ekskul Baru',
                        'pesan' => ($ekskulCount - $snap) . ' kegiatan ekskul baru diikuti anak Anda',
                        'url' => route('ortu.ekskul.index'),
                        'waktu' => null,
                    ];
                }
            } catch (\Throwable) {
            }

            // 7. JADWAL SEDANG BERLANGSUNG SEKARANG
            try {
                if (!empty($siswaApi)) {
                    $rombelIdJadwal = $this->resolveRombelAktifId($siswaApi);
                    if ($rombelIdJadwal) {
                        $jadwalRes = $this->sia->masterRombelJadwal($rombelIdJadwal, array_filter([
                            'tahun_ajaran_id' => $tahunAjaranAktifId,
                            'tahun_ajaran' => $tahunAjaranAktif,
                            'semester' => $semesterAktif,
                        ], fn($v) => $v !== null && $v !== ''));

                        $now = Carbon::now('Asia/Jakarta');
                        $hariIni = mb_strtolower($now->translatedFormat('l'));
                        $jamNow = $now->format('H:i');

                        $sedangBerlangsung = collect($jadwalRes['data'] ?? [])
                            ->filter(function ($j) use ($hariIni, $jamNow) {
                                $hari = mb_strtolower(trim((string) ($j['hari'] ?? '')));
                                $mulai = substr((string) ($j['jam_mulai'] ?? ''), 0, 5);
                                $selesai = substr((string) ($j['jam_selesai'] ?? ''), 0, 5);
                                return $hari === $hariIni
                                    && $mulai && $selesai
                                    && $jamNow >= $mulai && $jamNow <= $selesai;
                            })
                            ->first();

                        if ($sedangBerlangsung) {
                            $mapelRaw = $sedangBerlangsung['mapel'] ?? $sedangBerlangsung['mata_pelajaran'] ?? null;
                            $namaMapel = is_array($mapelRaw)
                                ? ($mapelRaw['nama_mapel'] ?? $mapelRaw['nama'] ?? 'Pelajaran')
                                : (is_string($mapelRaw) ? $mapelRaw : 'Pelajaran');
                            $mulai = substr((string) ($sedangBerlangsung['jam_mulai'] ?? ''), 0, 5);
                            $selesai = substr((string) ($sedangBerlangsung['jam_selesai'] ?? ''), 0, 5);

                            $items[] = [
                                'id' => 'sia_jadwal',
                                'icon' => 'clock',
                                'judul' => 'Pelajaran Berlangsung',
                                'pesan' => $namaMapel . ' sedang berlangsung (' . $mulai . '–' . $selesai . ')',
                                'url' => route('ortu.jadwal.index'),
                                'waktu' => null,
                            ];
                        }
                    }
                }
            } catch (\Throwable) {
            }

        } catch (\Throwable) {
        }

        return response()->json([
            'total' => count($items),
            'items' => $items,
        ]);
    }

    public function resetNotifikasiSia(): JsonResponse
    {
        $user = Auth::user();
        $nis = trim((string) ($user->sia_user_id ?? ''));
        $cacheKey = 'sinta.notif.ortu.' . $user->id . '.';

        if ($nis === '')
            return response()->json(['ok' => true]);

        try {
            $activePeriod = $this->resolveActiveAcademicPeriod();
            $tahunAjaranAktifId = $activePeriod['id'] ?? null;
            $tahunAjaranAktif = $activePeriod['nama_tahun'] ?? null;
            $semesterAktif = $activePeriod['semester'] ?? null;

            try {
                $res = $this->sia->getNilaiByNis($nis, array_filter([
                    'tahun_ajaran_id' => $tahunAjaranAktifId,
                    'tahun_ajaran' => $tahunAjaranAktif,
                    'semester' => $semesterAktif,
                ], fn($v) => $v !== null && $v !== ''));
                Cache::forever($cacheKey . 'nilai', count($res['data']['nilai'] ?? $res['data'] ?? []));
            } catch (\Throwable) {
            }

            try {
                $res = $this->sia->getPresensiByNis($nis, array_filter([
                    'tahun_ajaran_id' => $tahunAjaranAktifId,
                    'tahun_ajaran' => $tahunAjaranAktif,
                    'bulan' => now()->format('Y-m'),
                ], fn($v) => $v !== null && $v !== ''));
                $data = $res['data']['presensi'] ?? $res['data']['detail'] ?? $res['data'] ?? [];
                Cache::forever($cacheKey . 'presensi', is_array($data) ? count($data) : 0);
            } catch (\Throwable) {
            }

            try {
                $res = $this->sia->getEkskulByNis($nis, array_filter([
                    'tahun_ajaran_id' => $tahunAjaranAktifId,
                    'tahun_ajaran' => $tahunAjaranAktif,
                ], fn($v) => $v !== null && $v !== ''));
                $data = $res['data']['ekskul'] ?? $res['data'] ?? [];
                Cache::forever($cacheKey . 'ekskul', is_array($data) ? count($data) : 0);
            } catch (\Throwable) {
            }
        } catch (\Throwable) {
        }

        return response()->json(['ok' => true]);
    }

    public function index()
    {
        Carbon::setLocale('id');

        $today = Carbon::now('Asia/Jakarta');
        $user = auth()->user();
        $nis = trim((string) ($user->sia_user_id ?? ''));

        /*
        |--------------------------------------------------------------------------
        | Periode presensi dashboard
        |--------------------------------------------------------------------------
        | Dashboard hanya menampilkan presensi minggu berjalan pada hari kerja,
        | yaitu Senin sampai Jumat. Sabtu dan Minggu tidak dimasukkan ke tab
        | presensi maupun perhitungan ringkasan.
        */
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $today->copy()->startOfWeek(Carbon::MONDAY)->addDays(4)->endOfDay();

        if ($nis === '') {
            return view('ortu.dashboard', [
                'siswaApi' => null,
                'sidebarSiswa' => null,
                'pengumuman' => collect(),
                'jadwalApi' => [],
                'nilaiApi' => [],
                'presensiApi' => [],
                'presensiByHari' => [
                    'Senin' => [],
                    'Selasa' => [],
                    'Rabu' => [],
                    'Kamis' => [],
                    'Jumat' => [],
                ],
                'presensiRingkas' => [
                    'total' => 0,
                    'hadir' => 0,
                    'persen' => null,
                ],
                'ekskulApi' => [],
                'tahunAjaranAktif' => null,
                'semesterAktif' => null,
                'tahunAjaranAktifId' => null,
                'presensiWeekStart' => $weekStart,
                'presensiWeekEnd' => $weekEnd,
                'rombelId' => null,
                'rombelNama' => null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Periode akademik aktif dari API SIA
        |--------------------------------------------------------------------------
        */
        $activePeriod = $this->resolveActiveAcademicPeriod();

        $tahunAjaranAktif = $activePeriod['nama_tahun'] ?? null;
        $semesterAktif = $activePeriod['semester'] ?? null;
        $tahunAjaranAktifId = $activePeriod['id'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | 2. Detail siswa dari API SIA
        |--------------------------------------------------------------------------
        */
        $siswaApi = $this->resolveSiswaDetailByNis($nis);
        $sidebarSiswa = $this->buildSidebarSiswa($siswaApi, $nis);

        $agamaSiswa = $this->resolveAgamaSiswa($siswaApi);

        /*
        |--------------------------------------------------------------------------
        | 3. Rombel aktif siswa dari API SIA
        |--------------------------------------------------------------------------
        */
        $rombelId = $this->resolveRombelAktifId($siswaApi);
        $rombelNama = $this->resolveRombelAktifNama($siswaApi);

        /*
        |--------------------------------------------------------------------------
        | 4. Pengumuman dashboard orang tua
        |--------------------------------------------------------------------------
        */
        $pengumuman = $this->resolvePengumumanUntukOrtu($siswaApi, 3);

        /*
        |--------------------------------------------------------------------------
        | 5. Data akademik sesuai tahun ajaran aktif
        |--------------------------------------------------------------------------
        */
        $nilaiResult = $this->resolveNilai(
            nis: $nis,
            semesterAktif: $semesterAktif,
            tahunAjaranAktif: $tahunAjaranAktif,
            tahunAjaranAktifId: $tahunAjaranAktifId
        );

        $presensiResult = $this->resolvePresensi(
            nis: $nis,
            tahunAjaranAktif: $tahunAjaranAktif,
            tahunAjaranAktifId: $tahunAjaranAktifId,
            bulan: $today->format('Y-m')
        );

        $jadwalApi = $this->resolveJadwal(
            $rombelId,
            $today,
            $agamaSiswa,
            $tahunAjaranAktifId,
            $tahunAjaranAktif,
            $semesterAktif
        );

        $ekskulApi = $this->resolveEkskul($nis, $tahunAjaranAktifId, $tahunAjaranAktif);

        /*
        |--------------------------------------------------------------------------
        | 6. Presensi dashboard: hanya Senin sampai Jumat minggu berjalan
        |--------------------------------------------------------------------------
        */
        $urutanHariKerja = [
            'senin' => 1,
            'selasa' => 2,
            'rabu' => 3,
            'kamis' => 4,
            'jumat' => 5,
        ];

        $labelHariKerja = [
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
        ];

        $jadwalCollection = collect($jadwalApi)
            ->map(fn($row) => (array) $row)
            ->filter(function ($row) use ($urutanHariKerja) {
                $hariKey = mb_strtolower(trim((string) ($row['hari'] ?? '')));

                return isset($urutanHariKerja[$hariKey])
                    && filled($row['mapel'] ?? null)
                    && ($row['mapel'] ?? '-') !== '-';
            })
            ->values();

        $mapelTerjadwalByHari = $jadwalCollection
            ->groupBy(function ($row) {
                return mb_strtolower(trim((string) ($row['hari'] ?? '')));
            })
            ->map(function ($rows) {
                return collect($rows)
                    ->map(fn($row) => $this->normalizeMapelName($row['mapel'] ?? null))
                    ->filter()
                    ->unique()
                    ->values();
            });

        $presensiRows = collect($presensiResult['rows'] ?? [])
            ->filter(function ($row) use ($weekStart, $weekEnd, $mapelTerjadwalByHari, $urutanHariKerja) {
                $tanggalPresensi = $this->resolveTanggalPresensiDashboard($row);

                if (!$tanggalPresensi) {
                    return false;
                }

                if (!$tanggalPresensi->betweenIncluded($weekStart, $weekEnd)) {
                    return false;
                }

                if ($tanggalPresensi->dayOfWeekIso > 5) {
                    return false;
                }

                $hariPresensi = mb_strtolower(trim($tanggalPresensi->translatedFormat('l')));

                if (!isset($urutanHariKerja[$hariPresensi])) {
                    return false;
                }

                $mapelPresensi = $this->normalizeMapelName($row['mapel'] ?? null);

                if ($hariPresensi === '' || $mapelPresensi === '') {
                    return false;
                }

                $mapelPadaHariItu = $mapelTerjadwalByHari->get($hariPresensi, collect());

                return $mapelPadaHariItu->contains($mapelPresensi);
            })
            ->map(function ($row) {
                $tanggalPresensi = $this->resolveTanggalPresensiDashboard($row);

                $row['hari'] = $tanggalPresensi
                    ? $tanggalPresensi->translatedFormat('l')
                    : '-';

                $row['tanggal_label'] = $tanggalPresensi
                    ? $tanggalPresensi->translatedFormat('d M Y')
                    : '-';

                $row['dipindai_pada'] = $tanggalPresensi
                    ? $tanggalPresensi->format('Y-m-d H:i:s')
                    : ($row['dipindai_pada'] ?? '-');

                return $row;
            })
            ->sortBy(function ($row) {
                $tanggal = $this->resolveTanggalPresensiDashboard($row);

                return $tanggal ? $tanggal->timestamp : 0;
            })
            ->values();

        $presensiByHari = [];

        foreach ($labelHariKerja as $hari) {
            $presensiByHari[$hari] = [];
        }

        foreach ($presensiRows->groupBy('hari') as $hari => $rows) {
            if (in_array($hari, $labelHariKerja, true)) {
                $presensiByHari[$hari] = collect($rows)->values()->all();
            }
        }

        $presensiRowsArray = $presensiRows->all();

        $totalPresensi = count($presensiRowsArray);
        $hadirCount = collect($presensiRowsArray)
            ->filter(fn($row) => strtolower((string) ($row['status'] ?? '')) === 'hadir')
            ->count();

        $presensiRingkas = [
            'total' => $totalPresensi,
            'hadir' => $hadirCount,
            'persen' => $totalPresensi > 0 ? round(($hadirCount / $totalPresensi) * 100) : null,
        ];

        return view('ortu.dashboard', [
            'siswaApi' => $siswaApi,
            'sidebarSiswa' => $sidebarSiswa,
            'pengumuman' => $pengumuman,
            'jadwalApi' => $jadwalApi,
            'nilaiApi' => $nilaiResult['rows'] ?? [],
            'presensiApi' => $presensiRowsArray,
            'presensiByHari' => $presensiByHari,
            'presensiRingkas' => $presensiRingkas,
            'ekskulApi' => $ekskulApi,
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'semesterAktif' => $semesterAktif,
            'tahunAjaranAktifId' => $tahunAjaranAktifId,
            'presensiWeekStart' => $weekStart,
            'presensiWeekEnd' => $weekEnd,
            'rombelId' => $rombelId,
            'rombelNama' => $rombelNama,
        ]);
    }

    private function resolveActiveAcademicPeriod(): array
    {
        try {
            if (method_exists($this->sia, 'masterTahunAjaranAktif')) {
                $resp = $this->sia->masterTahunAjaranAktif();
                $data = is_array($resp['data'] ?? null) ? $resp['data'] : [];

                if (!empty($data)) {
                    return [
                        'id' => $data['id'] ?? null,
                        'nama_tahun' => $this->pickString(
                            $data['nama_tahun'] ?? null,
                            $data['nama'] ?? null
                        ),
                        'semester' => $this->normalizeSemesterLabel(
                            $data['semester'] ?? $data['semester_aktif'] ?? null
                        ),
                        'status' => $data['status'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            if (method_exists($this->sia, 'dashboardSummary')) {
                $resp = $this->sia->dashboardSummary();
                $ta = data_get($resp, 'data.tahun_ajaran_aktif');

                if (is_array($ta)) {
                    return [
                        'id' => $ta['id'] ?? null,
                        'nama_tahun' => $this->pickString(
                            $ta['nama_tahun'] ?? null,
                            $ta['nama'] ?? null
                        ),
                        'semester' => $this->normalizeSemesterLabel(
                            $ta['semester'] ?? $ta['semester_aktif'] ?? null
                        ),
                        'status' => $ta['status'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'id' => null,
            'nama_tahun' => null,
            'semester' => null,
            'status' => null,
        ];
    }

    private function resolveSiswaDetailByNis(string $nis): ?array
    {
        try {
            $resp = $this->sia->getSiswaByNis($nis);

            if (!$this->responseOk($resp) || empty($resp['data']) || !is_array($resp['data'])) {
                return null;
            }

            $basic = $resp['data'];
            $id = $basic['id'] ?? null;

            if (!$id) {
                return $basic;
            }

            try {
                $detail = $this->sia->masterSiswaDetail($id);

                if ($this->responseOk($detail) && !empty($detail['data']) && is_array($detail['data'])) {
                    return array_replace_recursive($basic, $detail['data']);
                }
            } catch (\Throwable $e) {
                report($e);
            }

            return $basic;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function resolveRombelAktifId(?array $siswaApi)
    {
        if (!is_array($siswaApi)) {
            return null;
        }

        return data_get($siswaApi, 'rombel_aktif.id')
            ?? data_get($siswaApi, 'rombel_aktif.rombel_id')
            ?? data_get($siswaApi, 'rombel.id')
            ?? data_get($siswaApi, 'rombel.rombel_id')
            ?? data_get($siswaApi, 'rombel_id');
    }

    private function resolveRombelAktifNama(?array $siswaApi): ?string
    {
        if (!is_array($siswaApi)) {
            return null;
        }

        return $this->pickString(
            data_get($siswaApi, 'rombel_aktif.nama_rombel'),
            data_get($siswaApi, 'rombel_aktif.nama'),
            data_get($siswaApi, 'rombel_aktif.label'),
            data_get($siswaApi, 'rombel.nama_rombel'),
            data_get($siswaApi, 'rombel.nama'),
            data_get($siswaApi, 'rombel.label'),
            data_get($siswaApi, 'nama_rombel'),
            data_get($siswaApi, 'kelas')
        );
    }

    private function buildSidebarSiswa(?array $siswaApi, string $nis): array
    {
        $namaKelas = $this->resolveRombelAktifNama($siswaApi) ?? '-';

        return [
            'nama' => $this->pickString(data_get($siswaApi, 'nama'), 'Siswa'),
            'nis' => $this->pickString(data_get($siswaApi, 'nis'), $nis),
            'nisn' => $this->pickString(data_get($siswaApi, 'nisn'), '-'),
            'kelas' => $this->pickString($namaKelas, '-') ?? '-',
        ];
    }

    private function resolveNilai(
        string $nis,
        ?string $semesterAktif = null,
        ?string $tahunAjaranAktif = null,
        $tahunAjaranAktifId = null
    ): array {
        try {
            $resp = $this->sia->getNilaiByNis($nis, array_filter([
                'tahun_ajaran_id' => $tahunAjaranAktifId,
                'tahun_ajaran' => $tahunAjaranAktif,
                'semester' => $semesterAktif,
            ], fn($v) => $v !== null && $v !== ''));

            if (!is_array($resp) || empty($resp['data']) || !is_array($resp['data'])) {
                return ['rows' => [], 'meta' => []];
            }

            $block = $resp['data'];
            $rawRows = collect($block['nilai'] ?? [])->map(fn($row) => (array) $row);

            $rows = $rawRows
                ->filter(function ($row) use ($tahunAjaranAktif, $tahunAjaranAktifId, $semesterAktif) {
                    return $this->rowMatchesTahunAjaran($row, $tahunAjaranAktif, $tahunAjaranAktifId)
                        && $this->rowMatchesSemester($row, $semesterAktif);
                })
                ->values();

            $normalizedRows = $rows->map(function ($row) {
                $mapelRaw = $row['mapel'] ?? $row['mata_pelajaran'] ?? null;
                $mapel = '-';

                if (is_array($mapelRaw)) {
                    $mapel = $this->pickString(
                        $mapelRaw['nama_mapel'] ?? null,
                        $mapelRaw['nama'] ?? null,
                        '-'
                    );
                } elseif (is_string($mapelRaw) && trim($mapelRaw) !== '') {
                    $mapel = trim($mapelRaw);
                }

                $lm1 = $this->normalizeNumber($row['lm1_nilai'] ?? $row['lm1'] ?? $row['nilai_lm1'] ?? null);
                $lm2 = $this->normalizeNumber($row['lm2_nilai'] ?? $row['lm2'] ?? $row['nilai_lm2'] ?? null);
                $lm3 = $this->normalizeNumber($row['lm3_nilai'] ?? $row['lm3'] ?? $row['nilai_lm3'] ?? null);
                $lm4 = $this->normalizeNumber($row['lm4_nilai'] ?? $row['lm4'] ?? $row['nilai_lm4'] ?? null);

                $nilaiAkhir = $this->normalizeNumber($row['nilai_akhir'] ?? $row['rata_rata'] ?? null);

                if ($nilaiAkhir === null) {
                    $available = collect([$lm1, $lm2, $lm3, $lm4])
                        ->filter(fn($v) => $v !== null)
                        ->values();

                    $nilaiAkhir = $available->count() > 0 ? round($available->avg(), 2) : null;
                }

                return [
                    'mapel' => $mapel,
                    'lm1' => $lm1 ?? '-',
                    'lm2' => $lm2 ?? '-',
                    'lm3' => $lm3 ?? '-',
                    'lm4' => $lm4 ?? '-',
                    'nilai_akhir' => $nilaiAkhir ?? '-',
                    'status' => $this->pickString(
                        is_string($row['status'] ?? null) ? str_replace('_', ' ', $row['status']) : null,
                        '-'
                    ),
                    'status_penilaian' => $this->pickString($row['status_penilaian'] ?? null, '-'),
                ];
            })->values()->all();

            return [
                'rows' => $normalizedRows,
                'meta' => [
                    'rombel_id' => data_get($block, 'siswa.rombel_aktif.id')
                        ?? data_get($block, 'siswa.rombel.id')
                        ?? data_get($block, 'siswa.rombel.rombel_id'),
                ],
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['rows' => [], 'meta' => []];
        }
    }

    private function resolvePresensi(
        string $nis,
        ?string $tahunAjaranAktif = null,
        $tahunAjaranAktifId = null,
        ?string $bulan = null
    ): array {
        try {
            $resp = $this->sia->getPresensiByNis($nis, array_filter([
                'tahun_ajaran_id' => $tahunAjaranAktifId,
                'tahun_ajaran' => $tahunAjaranAktif,
                'bulan' => $bulan,
            ], fn($v) => $v !== null && $v !== ''));

            if (!is_array($resp) || empty($resp['data']) || !is_array($resp['data'])) {
                return ['rows' => [], 'meta' => []];
            }

            $block = $resp['data'];

            $rawRows = $block['presensi']
                ?? $block['detail']
                ?? $block['list']
                ?? [];

            $rows = collect(is_array($rawRows) ? $rawRows : [])
                ->map(function ($row) {
                    $row = (array) $row;

                    $mapelRaw = $row['mapel'] ?? $row['mata_pelajaran'] ?? $row['nama_mapel'] ?? null;
                    $mapel = '-';

                    if (is_array($mapelRaw)) {
                        $mapel = $this->pickString(
                            $mapelRaw['nama_mapel'] ?? null,
                            $mapelRaw['nama'] ?? null,
                            '-'
                        ) ?? '-';
                    } elseif (is_string($mapelRaw) && trim($mapelRaw) !== '') {
                        $mapel = trim($mapelRaw);
                    }

                    return [
                        'tanggal' => $row['tanggal'] ?? null,
                        'dipindai_pada' => $row['dipindai_pada'] ?? null,
                        'mapel' => $mapel,
                        'status' => $this->normalizeStatus($row['status'] ?? null),
                    ];
                })
                ->values()
                ->all();

            return [
                'rows' => $rows,
                'meta' => [
                    'rombel_id' => data_get($block, 'siswa.rombel_aktif.id')
                        ?? data_get($block, 'siswa.rombel.id')
                        ?? data_get($block, 'siswa.rombel.rombel_id'),
                ],
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['rows' => [], 'meta' => []];
        }
    }

    private function resolveJadwal(
        $rombelId,
        Carbon $now,
        ?string $agamaSiswa = null,
        $tahunAjaranAktifId = null,
        ?string $tahunAjaranAktif = null,
        ?string $semesterAktif = null
    ): array {
        if (!$rombelId) {
            return [];
        }

        try {
            $resp = $this->sia->masterRombelJadwal($rombelId, array_filter([
                'tahun_ajaran_id' => $tahunAjaranAktifId,
                'tahun_ajaran' => $tahunAjaranAktif,
                'semester' => $semesterAktif,
            ], fn($value) => $value !== null && $value !== ''));

            $raw = $resp['data'] ?? [];

            $hariIni = strtolower($now->translatedFormat('l'));
            $jamSekarang = $now->format('H:i');
            $agamaSiswaNormalized = $this->normalizeAgama($agamaSiswa);

            return collect(is_array($raw) ? $raw : [])
                ->map(function ($row) use ($hariIni, $jamSekarang, $agamaSiswaNormalized, $tahunAjaranAktifId, $tahunAjaranAktif, $semesterAktif) {
                    $row = (array) $row;

                    if (!$this->rowJadwalMatchesActivePeriod($row, $tahunAjaranAktifId, $tahunAjaranAktif, $semesterAktif)) {
                        return null;
                    }

                    $mapelRaw = $row['mapel']
                        ?? $row['mata_pelajaran']
                        ?? null;

                    $guruRaw = $row['guru']
                        ?? $row['pengajar']
                        ?? null;

                    $mapel = '-';

                    if (is_array($mapelRaw)) {
                        $mapel = $this->pickString(
                            $mapelRaw['nama_mapel'] ?? null,
                            $mapelRaw['nama'] ?? null,
                            '-'
                        ) ?? '-';
                    } elseif (is_string($mapelRaw) && trim($mapelRaw) !== '') {
                        $mapel = trim($mapelRaw);
                    } else {
                        $mapel = $this->pickString(
                            $row['nama_mapel'] ?? null,
                            '-'
                        ) ?? '-';
                    }

                    $mapelAgama = $this->getAgamaFromMapel($mapel);

                    if ($mapelAgama !== null) {
                        if (!$agamaSiswaNormalized || $mapelAgama !== $agamaSiswaNormalized) {
                            return null;
                        }
                    }

                    $guru = '-';

                    if (is_array($guruRaw)) {
                        $guru = $this->pickString(
                            $guruRaw['nama'] ?? null,
                            $guruRaw['name'] ?? null,
                            '-'
                        ) ?? '-';
                    } elseif (is_string($guruRaw) && trim($guruRaw) !== '') {
                        $guru = trim($guruRaw);
                    } else {
                        $guru = $this->pickString(
                            $row['nama_guru'] ?? null,
                            '-'
                        ) ?? '-';
                    }

                    $hari = $this->pickString($row['hari'] ?? null, '-') ?? '-';
                    $hariNormalized = mb_strtolower(trim($hari));

                    $jamMulai = isset($row['jam_mulai']) ? substr((string) $row['jam_mulai'], 0, 5) : null;
                    $jamSelesai = isset($row['jam_selesai']) ? substr((string) $row['jam_selesai'], 0, 5) : null;

                    $isToday = $hariNormalized === $hariIni;
                    $isActive = false;
                    $isPassed = false;
                    $isUpcoming = false;

                    if ($isToday && $jamMulai && $jamSelesai) {
                        $isActive = $jamSekarang >= $jamMulai && $jamSekarang <= $jamSelesai;
                        $isPassed = $jamSekarang > $jamSelesai;
                        $isUpcoming = $jamSekarang < $jamMulai;
                    }

                    return [
                        'hari' => $hari,
                        'jam_mulai' => $jamMulai,
                        'jam_selesai' => $jamSelesai,
                        'mapel' => $mapel,
                        'guru' => $guru,
                        'is_today' => $isToday,
                        'is_active' => $isActive,
                        'is_passed' => $isPassed,
                        'is_upcoming' => $isUpcoming,
                    ];
                })
                ->filter()
                ->sortBy(function ($row) {
                    $urutanHari = [
                        'senin' => 1,
                        'selasa' => 2,
                        'rabu' => 3,
                        'kamis' => 4,
                        'jumat' => 5,
                        'sabtu' => 6,
                        'minggu' => 7,
                    ];

                    $hari = mb_strtolower(trim((string) ($row['hari'] ?? '')));
                    $jam = $row['jam_mulai'] ?? '99:99';

                    return ($urutanHari[$hari] ?? 99) . '-' . $jam;
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    private function rowJadwalMatchesActivePeriod(
        array $row,
        $tahunAjaranAktifId = null,
        ?string $tahunAjaranAktif = null,
        ?string $semesterAktif = null
    ): bool {
        $rowTahunAjaranId = data_get($row, 'tahun_ajaran_id')
            ?? data_get($row, 'tahun_ajaran.id')
            ?? data_get($row, 'ta.id')
            ?? data_get($row, 'rombel.tahun_ajaran_id')
            ?? data_get($row, 'rombel.tahun_ajaran.id');

        $rowTahunAjaran = $this->pickString(
            data_get($row, 'tahun_ajaran.nama_tahun'),
            data_get($row, 'tahun_ajaran.nama'),
            data_get($row, 'tahun_ajaran'),
            data_get($row, 'nama_tahun'),
            data_get($row, 'ta.nama_tahun'),
            data_get($row, 'ta.nama'),
            data_get($row, 'rombel.tahun_ajaran.nama_tahun'),
            data_get($row, 'rombel.tahun_ajaran.nama')
        );

        $rowSemester = $this->normalizeSemesterLabel(
            data_get($row, 'semester')
            ?? data_get($row, 'semester_label')
            ?? data_get($row, 'tahun_ajaran.semester')
            ?? data_get($row, 'ta.semester')
            ?? data_get($row, 'rombel.tahun_ajaran.semester')
        );

        if ($tahunAjaranAktifId !== null && $tahunAjaranAktifId !== '') {
            if ($rowTahunAjaranId !== null && $rowTahunAjaranId !== '') {
                if ((string) $rowTahunAjaranId !== (string) $tahunAjaranAktifId) {
                    return false;
                }
            }
        }

        if ($tahunAjaranAktif !== null && trim($tahunAjaranAktif) !== '') {
            if ($rowTahunAjaran !== null && $rowTahunAjaran !== '') {
                if (trim((string) $rowTahunAjaran) !== trim((string) $tahunAjaranAktif)) {
                    return false;
                }
            }
        }

        if ($semesterAktif !== null && trim($semesterAktif) !== '') {
            $semesterAktifNormalized = $this->normalizeSemesterLabel($semesterAktif);

            if ($rowSemester !== null && $rowSemester !== '') {
                if ($rowSemester !== $semesterAktifNormalized) {
                    return false;
                }
            }
        }

        return true;
    }

    private function resolveEkskul(string $nis, $tahunAjaranAktifId = null, ?string $tahunAjaranAktif = null): array
    {
        try {
            $resp = $this->sia->getEkskulByNis($nis, array_filter([
                'tahun_ajaran_id' => $tahunAjaranAktifId,
                'tahun_ajaran' => $tahunAjaranAktif,
            ], fn($v) => $v !== null && $v !== ''));

            $raw = $resp['data'] ?? [];

            if (is_array($raw) && isset($raw['ekskul']) && is_array($raw['ekskul'])) {
                $raw = $raw['ekskul'];
            } elseif (is_array($raw) && isset($raw['items']) && is_array($raw['items'])) {
                $raw = $raw['items'];
            }

            return collect(is_array($raw) ? $raw : [])
                ->map(function ($row) {
                    $row = (array) $row;

                    $jamMulai = isset($row['jam_mulai']) ? substr((string) $row['jam_mulai'], 0, 5) : null;
                    $jamSelesai = isset($row['jam_selesai']) ? substr((string) $row['jam_selesai'], 0, 5) : null;

                    $jam = $this->pickString($row['jam'] ?? null);

                    if (!$jam) {
                        if ($jamMulai && $jamSelesai) {
                            $jam = "{$jamMulai} - {$jamSelesai}";
                        } elseif ($jamMulai) {
                            $jam = $jamMulai;
                        }
                    }

                    $pembinaRaw = $row['pembina'] ?? $row['guru'] ?? null;
                    $pembina = null;

                    if (is_array($pembinaRaw)) {
                        $pembina = $this->pickString(
                            $pembinaRaw['nama'] ?? null,
                            $pembinaRaw['name'] ?? null,
                            null
                        );
                    } elseif (is_string($pembinaRaw) && trim($pembinaRaw) !== '') {
                        $pembina = trim($pembinaRaw);
                    }

                    return [
                        'nama' => $this->pickString(
                            $row['nama'] ?? null,
                            $row['nama_ekskul'] ?? null,
                            $row['ekskul'] ?? null,
                            '-'
                        ) ?? '-',
                        'hari' => $this->pickString($row['hari'] ?? $row['day'] ?? null, '-') ?? '-',
                        'jam' => $jam,
                        'jam_mulai' => $jamMulai,
                        'jam_selesai' => $jamSelesai,
                        'pembina' => $pembina,
                        'lokasi' => $this->pickString(
                            $row['lokasi'] ?? null,
                            $row['tempat'] ?? null,
                            $row['ruang'] ?? null
                        ),
                    ];
                })
                ->filter(fn($row) => ($row['nama'] ?? '-') !== '-')
                ->values()
                ->all();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    private function resolvePengumumanUntukOrtu(?array $siswaApi, ?int $limit = null): Collection
    {
        $tingkatSiswa = $this->resolveTingkatSiswa($siswaApi);
        $rombelSiswa = $this->normalizeRombelName($this->resolveRombelAktifNama($siswaApi));
        $rombelId = $this->resolveRombelAktifId($siswaApi);

        $items = Pengumuman::query()
            ->aktif()
            ->orderByDesc('publish_at')
            ->orderByDesc('id')
            ->get()
            ->filter(function (Pengumuman $pengumuman) use ($tingkatSiswa, $rombelSiswa, $rombelId) {
                return $this->matchesPengumumanAudience($pengumuman, $tingkatSiswa, $rombelSiswa, $rombelId);
            })
            ->values();

        if ($limit !== null) {
            return $items->take($limit)->values();
        }

        return $items;
    }

    private function matchesPengumumanAudience(
        Pengumuman $pengumuman,
        ?string $tingkatSiswa,
        ?string $rombelSiswa,
        $rombelId = null
    ): bool {
        $scope = $this->normalizeScope($pengumuman->target_scope ?? null);

        if ($scope === 'all') {
            return true;
        }

        if ($scope === 'tingkat') {
            $targetTingkat = $this->normalizeTingkat($pengumuman->target_tingkat ?? null);
            $tingkatSiswa = $this->normalizeTingkat($tingkatSiswa);

            return $targetTingkat !== null
                && $tingkatSiswa !== null
                && $targetTingkat === $tingkatSiswa;
        }

        if (in_array($scope, ['kelas', 'rombel'], true)) {
            $targetId = $pengumuman->target_rombel_id ?? $pengumuman->rombel_id ?? null;
            $targetNama = $this->normalizeRombelName(
                $pengumuman->target_rombel ?? $pengumuman->target_kelas ?? null
            );

            if ($targetId !== null && $rombelId !== null && (string) $targetId === (string) $rombelId) {
                return true;
            }

            if ($targetNama && $rombelSiswa && $targetNama === $rombelSiswa) {
                return true;
            }

            return false;
        }

        return false;
    }

    private function normalizeScope($scope): string
    {
        $scope = strtolower(trim((string) $scope));

        return match ($scope) {
            '', 'all', 'semua', 'umum' => 'all',
            'tingkat', 'level' => 'tingkat',
            'kelas', 'rombel' => 'rombel',
            default => $scope,
        };
    }

    private function resolveTingkatSiswa(?array $siswaApi): ?string
    {
        if (!is_array($siswaApi) || empty($siswaApi)) {
            return null;
        }

        return $this->normalizeTingkat(
            data_get($siswaApi, 'rombel_aktif.tingkat'),
            data_get($siswaApi, 'rombel.tingkat'),
            data_get($siswaApi, 'tingkat'),
            data_get($siswaApi, 'kelas_tingkat'),
            data_get($siswaApi, 'rombel_aktif.nama_rombel'),
            data_get($siswaApi, 'rombel.nama_rombel'),
            data_get($siswaApi, 'nama_rombel'),
            data_get($siswaApi, 'kelas')
        );
    }

    private function normalizeTingkat(...$values): ?string
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                $nested = $this->normalizeTingkat(
                    $value['tingkat'] ?? null,
                    $value['nama_rombel'] ?? null,
                    $value['nama'] ?? null,
                    $value['label'] ?? null
                );

                if ($nested !== null) {
                    return $nested;
                }

                continue;
            }

            $text = strtoupper(trim((string) $value));

            if ($text === '' || $text === '-') {
                continue;
            }

            $text = str_replace(['-', '_', '.', '/', '\\'], ' ', $text);
            $text = preg_replace('/\s+/', ' ', $text);
            $compact = preg_replace('/[^A-Z0-9]/', '', $text);

            if ($compact === '12' || str_starts_with($compact, 'XII') || preg_match('/(^|[^0-9])12([^0-9]|$)/', $text)) {
                return 'XII';
            }

            if ($compact === '11' || str_starts_with($compact, 'XI') || preg_match('/(^|[^0-9])11([^0-9]|$)/', $text)) {
                return 'XI';
            }

            if ($compact === '10' || str_starts_with($compact, 'X') || preg_match('/(^|[^0-9])10([^0-9]|$)/', $text)) {
                return 'X';
            }
        }

        return null;
    }

    private function rowMatchesTahunAjaran(array $row, ?string $selectedTahunAjaran, $selectedTahunAjaranId): bool
    {
        $rowTahunAjaranId = data_get($row, 'tahun_ajaran_id')
            ?? data_get($row, 'tahun_ajaran.id')
            ?? data_get($row, 'ta.id');

        $rowTahunAjaran = $this->pickString(
            data_get($row, 'tahun_ajaran.nama_tahun'),
            data_get($row, 'tahun_ajaran.nama'),
            data_get($row, 'tahun_ajaran'),
            data_get($row, 'tahun_ajaran_label'),
            data_get($row, 'nama_tahun'),
            data_get($row, 'ta.nama_tahun'),
            data_get($row, 'ta.nama')
        );

        if ($selectedTahunAjaranId !== null && $selectedTahunAjaranId !== '') {
            if ($rowTahunAjaranId !== null && $rowTahunAjaranId !== '') {
                return (string) $rowTahunAjaranId === (string) $selectedTahunAjaranId;
            }
        }

        if ($selectedTahunAjaran !== null && trim((string) $selectedTahunAjaran) !== '') {
            if ($rowTahunAjaran !== null && $rowTahunAjaran !== '') {
                return trim((string) $rowTahunAjaran) === trim((string) $selectedTahunAjaran);
            }
        }

        return false;
    }

    private function rowMatchesSemester(array $row, ?string $semesterAktif): bool
    {
        if (!$semesterAktif) {
            return true;
        }

        $semesterRow = $this->normalizeSemesterLabel(
            data_get($row, 'semester')
            ?? data_get($row, 'semester_label')
            ?? data_get($row, 'semester_ke')
            ?? data_get($row, 'tahun_ajaran.semester')
            ?? data_get($row, 'ta.semester')
        );

        if (!$semesterRow) {
            return false;
        }

        return $semesterRow === $this->normalizeSemesterLabel($semesterAktif);
    }

    private function resolveTanggalPresensiDashboard(array $row): ?Carbon
    {
        $dipindaiPada = $this->parseDateTime($row['dipindai_pada'] ?? null);

        if ($dipindaiPada) {
            return $dipindaiPada;
        }

        return $this->parseDateTime($row['tanggal'] ?? null);
    }

    private function parseDateTime($value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->timezone('Asia/Jakarta');
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return null;
        }

        try {
            return Carbon::parse($value, 'Asia/Jakarta')->timezone('Asia/Jakarta');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeMapelName($value): ?string
    {
        if (is_array($value)) {
            $value = $this->pickString(
                $value['nama_mapel'] ?? null,
                $value['nama'] ?? null,
                $value['label'] ?? null
            );
        }

        if (is_object($value)) {
            $value = $this->pickString(
                $value->nama_mapel ?? null,
                $value->nama ?? null,
                $value->label ?? null
            );
        }

        $value = mb_strtolower(trim((string) $value));

        if ($value === '' || $value === '-') {
            return null;
        }

        return preg_replace('/\s+/', ' ', $value);
    }

    private function normalizeRombelName($value): ?string
    {
        $value = strtoupper(trim((string) $value));

        if ($value === '' || $value === '-') {
            return null;
        }

        $value = str_replace(['-', '_', '.', '/', '\\'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    private function resolveAgamaSiswa(?array $siswaApi): ?string
    {
        if (!is_array($siswaApi) || empty($siswaApi)) {
            return null;
        }

        return $this->pickString(
            data_get($siswaApi, 'agama'),
            data_get($siswaApi, 'agama_siswa'),
            data_get($siswaApi, 'biodata.agama'),
            data_get($siswaApi, 'detail.agama'),
            data_get($siswaApi, 'profil.agama')
        );
    }

    private function normalizeAgama(?string $agama): ?string
    {
        $agama = strtolower(trim((string) $agama));

        if ($agama === '') {
            return null;
        }

        return match (true) {
            in_array($agama, ['islam'], true) => 'islam',
            in_array($agama, ['kristen', 'kristen protestan', 'protestan'], true) => 'kristen',
            in_array($agama, ['katolik', 'katholik'], true) => 'katolik',
            in_array($agama, ['hindu'], true) => 'hindu',
            in_array($agama, ['buddha', 'budha'], true) => 'buddha',
            in_array($agama, ['khonghucu', 'konghucu'], true) => 'khonghucu',
            default => $agama,
        };
    }

    private function getAgamaFromMapel(?string $mapelName): ?string
    {
        $mapel = strtolower(trim((string) $mapelName));

        if ($mapel === '') {
            return null;
        }

        return match (true) {
            Str::contains($mapel, 'pendidikan agama islam') || $mapel === 'pai' => 'islam',
            Str::contains($mapel, 'pendidikan agama kristen') || $mapel === 'pak' => 'kristen',
            Str::contains($mapel, 'pendidikan agama katolik') => 'katolik',
            Str::contains($mapel, 'pendidikan agama hindu') => 'hindu',
            Str::contains($mapel, 'pendidikan agama buddha') || Str::contains($mapel, 'pendidikan agama budha') => 'buddha',
            Str::contains($mapel, 'pendidikan agama khonghucu') || Str::contains($mapel, 'pendidikan agama konghucu') => 'khonghucu',
            default => null,
        };
    }

    private function normalizeSemesterLabel($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return match (strtolower($value)) {
            '1', 'ganjil', 'gasal' => 'Ganjil',
            '2', 'genap' => 'Genap',
            default => ucfirst($value),
        };
    }

    private function normalizeStatus($status): string
    {
        $status = strtolower(trim((string) $status));

        return match ($status) {
            'hadir' => 'hadir',
            'izin' => 'izin',
            'sakit' => 'sakit',
            'alfa', 'alpa', 'alpha' => 'alpa',
            default => $status !== '' ? $status : '-',
        };
    }

    private function responseOk($response): bool
    {
        return is_array($response)
            && (
                ($response['success'] ?? false) === true ||
                ($response['status'] ?? false) === true ||
                ($response['status'] ?? null) === 'success'
            );
    }

    private function pickString(...$values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_numeric($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function normalizeNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }
}