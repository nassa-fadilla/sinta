<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Services\SiaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFIKASI REAL-TIME WALKEL
    |--------------------------------------------------------------------------
    | Endpoint polling: GET /guru/notifikasi
    | Dipanggil tiap 30 detik dari header walkel.
    | Semua notifikasi di-scope ke rombel binaan walkel yang sedang login.
    */
    public function getNotifikasi(): JsonResponse
    {
        $user = Auth::user();
        $items = [];

        // ── Resolve guru & rombel binaan ────────────────────────────────────
        $guruSia = $this->resolveGuruFromApi($user);
        $activePeriod = $this->resolveActiveAcademicPeriod();
        $rombel = $guruSia
            ? $this->resolveRombelWalikelas(
                guru: $guruSia,
                activeTahunAjaranId: $activePeriod['id'] ?? null,
                activeTahunAjaran: $activePeriod['nama_tahun'] ?? null,
            )
            : null;

        $rombelId = $rombel['id'] ?? null;
        $rombelNama = $rombel['nama_rombel'] ?? 'Rombel';
        $cacheKey = 'sinta.notif.walkel.' . $user->id . '.';

        // ── 1. CHAT BARU DARI ORTU ───────────────────────────────────────────
        // Hitung pesan masuk (direction='in') di thread yang di-assign ke walkel ini.
        $chatUnread = DB::table('chat_messages as m')
            ->join('chat_threads as t', 't.id', '=', 'm.chat_thread_id')
            ->where('t.assigned_to_user_id', $user->id)
            ->where('m.direction', 'in')
            ->where('m.sender_type', 'parent')
            ->whereNull('m.read_at')
            ->count();

        if ($chatUnread > 0) {
            $items[] = [
                'id' => 'chat',
                'icon' => 'chat',
                'judul' => 'Pesan Ortu',
                'pesan' => $chatUnread . ' pesan baru belum dibaca',
                'url' => route('guru.chat.index'),
                'waktu' => null,
            ];
        }

        // ── 2. PENGUMUMAN BARU (disetujui, scope rombel/tingkat walkel) ──────
        $pengumumanBaru = Pengumuman::where('status', 'approved')
            ->where('publish_at', '>=', now()->subDay())
            ->orderByDesc('publish_at')
            ->get([
                'id',
                'judul',
                'target_scope',
                'target_tingkat',
                'target_rombel_id',
                'target_rombel',
                'target_kelas',
                'publish_at'
            ])
            ->filter(function ($p) use ($rombel) {
                $scope = strtolower(trim((string) ($p->target_scope ?? '')));
                if (in_array($scope, ['', 'all', 'semua', 'umum']))
                    return true;
                if (in_array($scope, ['tingkat', 'level'])) {
                    $tingkat = $this->resolveTingkatRombel($rombel);
                    return $tingkat && $this->normalizeTingkat($p->target_tingkat) === $tingkat;
                }
                if (in_array($scope, ['kelas', 'rombel'])) {
                    if ($p->target_rombel_id && $rombel && (string) $p->target_rombel_id === (string) ($rombel['id'] ?? ''))
                        return true;
                    $targetNama = strtoupper(trim((string) ($p->target_rombel ?? $p->target_kelas ?? '')));
                    $myNama = strtoupper(trim((string) ($rombel['nama_rombel'] ?? '')));
                    return $targetNama && $myNama && $targetNama === $myNama;
                }
                return false;
            });

        foreach ($pengumumanBaru as $p) {
            $items[] = [
                'id' => 'pengumuman_' . $p->id,
                'icon' => 'megaphone',
                'judul' => 'Pengumuman Baru',
                'pesan' => '"' . \Illuminate\Support\Str::limit($p->judul, 50) . '"',
                'url' => route('guru.pengumuman.show', $p->id),
                'waktu' => $p->publish_at?->diffForHumans(),
            ];
        }

        // ── 3‑5. DATA SIA ROMBEL: NILAI, PRESENSI, EKSKUL ───────────────────
        // Strategi: snapshot jumlah siswa rombel + total sesi presensi + total ekskul
        // di Cache. Kalau angka berubah = ada data baru.
        if ($rombelId) {
            // 3. PRESENSI BARU
            try {
                $presensiRes = $this->sia->masterRombelSesiPresensi($rombelId);
                $presensiData = $presensiRes['data'] ?? [];
                $totalPresensi = is_array($presensiData) ? count($presensiData) : 0;

                $snapKey = $cacheKey . 'presensi_' . $rombelId;
                $snap = (int) Cache::get($snapKey, $totalPresensi);

                if (!Cache::has($snapKey))
                    Cache::forever($snapKey, $totalPresensi);

                $selisih = $totalPresensi - $snap;
                if ($selisih > 0) {
                    $items[] = [
                        'id' => 'sia_presensi',
                        'icon' => 'clipboard-check',
                        'judul' => 'Presensi Baru',
                        'pesan' => $selisih . ' sesi presensi baru tercatat di ' . $rombelNama,
                        'url' => route('guru.monitoring.index'),
                        'waktu' => null,
                    ];
                }
            } catch (\Throwable) {
            }

            // 4. NILAI BARU — cek via anggota rombel, snapshot total record nilai
            try {
                $anggotaRes = $this->sia->masterRombelAnggota($rombelId);
                $anggota = $anggotaRes['data'] ?? [];
                $totalAnggota = is_array($anggota) ? count($anggota) : 0;

                // Ambil nilai dari siswa pertama sebagai sampel untuk deteksi perubahan
                $sampelNis = null;
                if (is_array($anggota) && !empty($anggota)) {
                    $first = reset($anggota);
                    $sampelNis = data_get($first, 'nis')
                        ?? data_get($first, 'siswa.nis')
                        ?? data_get($first, 'nisn');
                }

                if ($sampelNis) {
                    $nilaiRes = $this->sia->getNilaiByNis($sampelNis);
                    $totalNilai = is_array($nilaiRes['data'] ?? null) ? count($nilaiRes['data']) : 0;

                    $snapKey = $cacheKey . 'nilai_' . $rombelId;
                    $snap = (int) Cache::get($snapKey, $totalNilai);
                    if (!Cache::has($snapKey))
                        Cache::forever($snapKey, $totalNilai);

                    $selisih = $totalNilai - $snap;
                    if ($selisih > 0) {
                        $items[] = [
                            'id' => 'sia_nilai',
                            'icon' => 'academic-cap',
                            'judul' => 'Nilai Baru Diinput',
                            'pesan' => 'Ada nilai baru yang diinputkan untuk siswa ' . $rombelNama,
                            'url' => route('guru.monitoring.index'),
                            'waktu' => null,
                        ];
                    }
                }
            } catch (\Throwable) {
            }

            // 5. EKSKUL BARU DIIKUTI SISWA ROMBEL
            try {
                $ekskulRes = $this->sia->masterEkskul(['rombel_id' => $rombelId]);
                $ekskulData = $ekskulRes['data'] ?? [];
                $totalEkskul = is_array($ekskulData) ? count($ekskulData) : 0;

                $snapKey = $cacheKey . 'ekskul_' . $rombelId;
                $snap = (int) Cache::get($snapKey, $totalEkskul);
                if (!Cache::has($snapKey))
                    Cache::forever($snapKey, $totalEkskul);

                $selisih = $totalEkskul - $snap;
                if ($selisih > 0) {
                    $items[] = [
                        'id' => 'sia_ekskul',
                        'icon' => 'star',
                        'judul' => 'Ekskul Baru',
                        'pesan' => $selisih . ' kegiatan ekskul baru diikuti siswa ' . $rombelNama,
                        'url' => route('guru.monitoring.index'),
                        'waktu' => null,
                    ];
                }
            } catch (\Throwable) {
            }
        }

        return response()->json([
            'total' => count($items),
            'items' => $items,
        ]);
    }

    /**
     * Reset snapshot SIA walkel setelah admin/walkel klik notif SIA.
     */
    public function resetNotifikasiSia(): JsonResponse
    {
        $user = Auth::user();
        $cacheKey = 'sinta.notif.walkel.' . $user->id . '.';

        $guruSia = $this->resolveGuruFromApi($user);
        $activePeriod = $this->resolveActiveAcademicPeriod();
        $rombel = $guruSia
            ? $this->resolveRombelWalikelas(
                guru: $guruSia,
                activeTahunAjaranId: $activePeriod['id'] ?? null,
                activeTahunAjaran: $activePeriod['nama_tahun'] ?? null,
            )
            : null;

        $rombelId = $rombel['id'] ?? null;

        if ($rombelId) {
            try {
                $presensiRes = $this->sia->masterRombelSesiPresensi($rombelId);
                $total = is_array($presensiRes['data'] ?? null) ? count($presensiRes['data']) : 0;
                Cache::forever($cacheKey . 'presensi_' . $rombelId, $total);
            } catch (\Throwable) {
            }

            try {
                $ekskulRes = $this->sia->masterEkskul(['rombel_id' => $rombelId]);
                $total = is_array($ekskulRes['data'] ?? null) ? count($ekskulRes['data']) : 0;
                Cache::forever($cacheKey . 'ekskul_' . $rombelId, $total);
            } catch (\Throwable) {
            }

            try {
                $anggotaRes = $this->sia->masterRombelAnggota($rombelId);
                $anggota = $anggotaRes['data'] ?? [];
                $first = is_array($anggota) && !empty($anggota) ? reset($anggota) : null;
                $sampelNis = $first
                    ? (data_get($first, 'nis') ?? data_get($first, 'siswa.nis') ?? data_get($first, 'nisn'))
                    : null;
                if ($sampelNis) {
                    $nilaiRes = $this->sia->getNilaiByNis($sampelNis);
                    $total = is_array($nilaiRes['data'] ?? null) ? count($nilaiRes['data']) : 0;
                    Cache::forever($cacheKey . 'nilai_' . $rombelId, $total);
                }
            } catch (\Throwable) {
            }
        }

        return response()->json(['ok' => true]);
    }

    public function index()
    {
        Carbon::setLocale('id');

        $user = Auth::user();
        $role = strtolower((string) ($user?->role ?? 'guru'));
        $isWalikelas = in_array($role, ['walkel'], true);

        /*
        |--------------------------------------------------------------------------
        | 1. Periode akademik aktif dari API SIA
        |--------------------------------------------------------------------------
        | Semua data dashboard wali kelas harus mengikuti tahun ajaran dan semester
        | aktif dari SIA agar jadwal, rombel, siswa, dan pengumuman tidak tercampur
        | dengan data periode lama.
        */
        $activePeriod = $this->resolveActiveAcademicPeriod();

        $activeTahunAjaranId = $activePeriod['id'] ?? null;
        $activeTahunAjaran = $activePeriod['nama_tahun'] ?? null;
        $activeSemester = $activePeriod['semester'] ?? null;

        $rombel = null;
        $jadwalHariIni = collect();
        $jadwalSeminggu = collect();

        $chartHariLabels = [];
        $chartHariJp = [];

        $totalJpMingguan = 0;
        $totalMapelRombel = 0;
        $totalSiswaRombel = 0;
        $totalGuruRombel = 0;
        $totalLakiRombel = 0;
        $totalPerempuanRombel = 0;

        if ($isWalikelas) {
            /*
            |--------------------------------------------------------------------------
            | 2. Identifikasi guru dari API SIA
            |--------------------------------------------------------------------------
            */
            $guruSia = $this->resolveGuruFromApi($user);

            if ($guruSia) {
                /*
                |--------------------------------------------------------------------------
                | 3. Ambil rombel binaan aktif sesuai tahun ajaran aktif
                |--------------------------------------------------------------------------
                */
                $rombel = $this->resolveRombelWalikelas(
                    guru: $guruSia,
                    activeTahunAjaranId: $activeTahunAjaranId,
                    activeTahunAjaran: $activeTahunAjaran
                );

                if ($rombel) {
                    /*
                    |--------------------------------------------------------------------------
                    | 4. Ringkasan siswa rombel aktif
                    |--------------------------------------------------------------------------
                    */
                    $ringkasanSiswa = $this->summarizeSiswaRombel(
                        rombel: $rombel,
                        activeTahunAjaranId: $activeTahunAjaranId,
                        activeTahunAjaran: $activeTahunAjaran
                    );

                    $totalSiswaRombel = $ringkasanSiswa['total'] ?? 0;
                    $totalLakiRombel = $ringkasanSiswa['laki'] ?? 0;
                    $totalPerempuanRombel = $ringkasanSiswa['perempuan'] ?? 0;

                    /*
                    |--------------------------------------------------------------------------
                    | 5. Jadwal rombel aktif sesuai tahun ajaran dan semester aktif
                    |--------------------------------------------------------------------------
                    */
                    $jadwalAll = $this->getJadwalRombel(
                        rombelId: $rombel['id'] ?? null,
                        activeTahunAjaranId: $activeTahunAjaranId,
                        activeTahunAjaran: $activeTahunAjaran,
                        activeSemester: $activeSemester
                    );

                    $urutanHari = [
                        'Senin' => 1,
                        'Selasa' => 2,
                        'Rabu' => 3,
                        'Kamis' => 4,
                        'Jumat' => 5,
                        'Sabtu' => 6,
                        'Minggu' => 7,
                    ];

                    $jadwalSeminggu = $jadwalAll
                        ->map(function ($j) {
                            return [
                                'id' => data_get($j, 'id'),
                                'rombel' => $this->extractDisplayValue(data_get($j, 'rombel'), ['nama_rombel', 'nama', 'label'])
                                    ?? $this->extractDisplayValue(data_get($j, 'rombel_aktif'), ['nama_rombel', 'nama', 'label'])
                                    ?? '-',
                                'mapel' => $this->extractDisplayValue(data_get($j, 'mapel'), ['nama_mapel', 'nama', 'label'])
                                    ?? $this->extractDisplayValue(data_get($j, 'mata_pelajaran'), ['nama_mapel', 'nama', 'label'])
                                    ?? $this->pickString(data_get($j, 'nama_mapel'), '-'),
                                'guru' => $this->extractDisplayValue(data_get($j, 'guru'), ['nama', 'name', 'label'])
                                    ?? $this->extractDisplayValue(data_get($j, 'pengajar'), ['nama', 'name', 'label'])
                                    ?? $this->pickString(data_get($j, 'nama_guru'), '-'),
                                'hari' => $this->normalizeHariLabel(data_get($j, 'hari', '-')),
                                'jam_mulai' => data_get($j, 'jam_mulai'),
                                'jam_selesai' => data_get($j, 'jam_selesai'),
                                'durasi_jp' => (int) (data_get($j, 'durasi_jp') ?? 0),
                            ];
                        })
                        ->filter(function ($j) {
                            return !empty($j['hari']) && $j['hari'] !== '-';
                        })
                        ->sortBy(function ($j) use ($urutanHari) {
                            return [
                                $urutanHari[$j['hari']] ?? 99,
                                $j['jam_mulai'] ?? '99:99:99',
                            ];
                        })
                        ->groupBy('hari')
                        ->map(fn($rows) => $rows->values());

                    $hariIni = $this->normalizeHariLabel(Carbon::now('Asia/Jakarta')->translatedFormat('l'));
                    $jadwalHariIni = collect($jadwalSeminggu->get($hariIni, []));

                    /*
                    |--------------------------------------------------------------------------
                    | 6. Rekap JP mingguan
                    |--------------------------------------------------------------------------
                    */
                    $rekapHarian = $jadwalAll
                        ->groupBy(function ($j) {
                            return $this->normalizeHariLabel(data_get($j, 'hari', '-'));
                        })
                        ->map(function ($rows, $hari) {
                            return [
                                'hari' => $hari,
                                'total_jp' => collect($rows)->sum(function ($r) {
                                    return (int) (data_get($r, 'durasi_jp') ?? 0);
                                }),
                            ];
                        })
                        ->values()
                        ->sortBy(function ($row) use ($urutanHari) {
                            return $urutanHari[$row['hari']] ?? 99;
                        })
                        ->values();

                    $chartHariLabels = $rekapHarian->pluck('hari')->all();
                    $chartHariJp = $rekapHarian->pluck('total_jp')->map(fn($v) => (int) $v)->all();
                    $totalJpMingguan = array_sum($chartHariJp);

                    /*
                    |--------------------------------------------------------------------------
                    | 7. Statistik mapel dan guru pada rombel aktif
                    |--------------------------------------------------------------------------
                    */
                    $totalMapelRombel = $jadwalAll
                        ->map(function ($j) {
                            return data_get($j, 'mapel.id')
                                ?? data_get($j, 'mata_pelajaran.id')
                                ?? data_get($j, 'mata_pelajaran_id')
                                ?? $this->normalizeKey(
                                    $this->extractDisplayValue(data_get($j, 'mapel'), ['nama_mapel', 'nama'])
                                    ?? $this->extractDisplayValue(data_get($j, 'mata_pelajaran'), ['nama_mapel', 'nama'])
                                    ?? data_get($j, 'nama_mapel')
                                );
                        })
                        ->filter()
                        ->unique()
                        ->count();

                    $totalGuruRombel = $jadwalAll
                        ->map(function ($j) {
                            return data_get($j, 'guru.id')
                                ?? data_get($j, 'pengajar.id')
                                ?? data_get($j, 'guru_id')
                                ?? $this->normalizeKey(
                                    $this->extractDisplayValue(data_get($j, 'guru'), ['nama', 'name'])
                                    ?? $this->extractDisplayValue(data_get($j, 'pengajar'), ['nama', 'name'])
                                    ?? data_get($j, 'nama_guru')
                                );
                        })
                        ->filter()
                        ->unique()
                        ->count();
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Pengumuman sesuai rombel binaan
        |--------------------------------------------------------------------------
        | Data pengumuman tetap dari DB SINTA, tetapi pencocokan target tingkat
        | dan rombel memakai data rombel aktif dari API SIA.
        */
        $pengumuman = $this->resolvePengumumanUntukGuru($rombel);

        $aktif = $pengumuman->count();

        $baru = $pengumuman
            ->filter(function ($item) {
                if (empty($item->publish_at)) {
                    return false;
                }

                return Carbon::parse($item->publish_at)->gte(now()->subDays(7));
            })
            ->count();

        return view('guru.dashboard', [
            'aktif' => $aktif,
            'baru' => $baru,
            'pengumuman' => $pengumuman,
            'user' => $user,
            'role' => $role,
            'isWalikelas' => $isWalikelas,

            'rombel' => $rombel ? (object) $rombel : null,

            'jadwalHariIni' => $jadwalHariIni,
            'jadwalSeminggu' => $jadwalSeminggu,

            'chartHariLabels' => $chartHariLabels,
            'chartHariJp' => $chartHariJp,

            'totalJpMingguan' => $totalJpMingguan,
            'totalMapelRombel' => $totalMapelRombel,
            'totalSiswaRombel' => $totalSiswaRombel,
            'totalGuruRombel' => $totalGuruRombel,
            'totalLakiRombel' => $totalLakiRombel,
            'totalPerempuanRombel' => $totalPerempuanRombel,

            'activeTahunAjaranId' => $activeTahunAjaranId,
            'activeTahunAjaran' => $activeTahunAjaran,
            'activeSemester' => $activeSemester,
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

    private function resolvePengumumanUntukGuru(?array $rombel): Collection
    {
        $tingkatRombel = $this->resolveTingkatRombel($rombel);
        $rombelId = data_get($rombel, 'id');
        $rombelNama = $this->normalizeRombelName(
            data_get($rombel, 'nama_rombel')
            ?? data_get($rombel, 'nama')
            ?? data_get($rombel, 'label')
        );

        return Pengumuman::query()
            ->aktif()
            ->orderByDesc('publish_at')
            ->orderByDesc('id')
            ->get()
            ->filter(function ($item) use ($tingkatRombel, $rombelId, $rombelNama) {
                $scope = $this->normalizeScope($item->target_scope ?? null);

                if ($scope === 'all') {
                    return true;
                }

                if ($scope === 'tingkat') {
                    if ($tingkatRombel === null) {
                        return false;
                    }

                    $targetTingkat = $this->normalizeTingkat($item->target_tingkat ?? null);

                    return $targetTingkat !== null && $targetTingkat === $tingkatRombel;
                }

                if (in_array($scope, ['kelas', 'rombel'], true)) {
                    $targetId = $item->target_rombel_id ?? $item->rombel_id ?? null;

                    $targetNama = $this->normalizeRombelName(
                        $item->target_rombel ?? $item->target_kelas ?? null
                    );

                    if ($targetId !== null && $rombelId !== null && (string) $targetId === (string) $rombelId) {
                        return true;
                    }

                    if ($targetNama && $rombelNama && $targetNama === $rombelNama) {
                        return true;
                    }

                    return false;
                }

                return false;
            })
            ->values();
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

    private function resolveTingkatRombel(?array $rombel): ?string
    {
        if (!$rombel) {
            return null;
        }

        return $this->normalizeTingkat(
            $rombel['nama_rombel'] ?? null,
            $rombel['nama'] ?? null,
            $rombel['label'] ?? null,
            $rombel['rombel'] ?? null,
            $rombel['tingkat'] ?? null
        );
    }

    private function normalizeTingkat(...$values): ?string
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                $nested = $this->normalizeTingkat(
                    $value['nama_rombel'] ?? null,
                    $value['nama'] ?? null,
                    $value['label'] ?? null,
                    $value['rombel'] ?? null,
                    $value['tingkat'] ?? null
                );

                if ($nested !== null) {
                    return $nested;
                }

                continue;
            }

            if (is_object($value)) {
                $nested = $this->normalizeTingkat(
                    $value->nama_rombel ?? null,
                    $value->nama ?? null,
                    $value->label ?? null,
                    $value->rombel ?? null,
                    $value->tingkat ?? null
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

            if (
                $compact === '12' ||
                str_contains($compact, 'KELAS12') ||
                str_contains($compact, 'TINGKAT12') ||
                preg_match('/(^|[^0-9])12([^0-9]|$)/', $text)
            ) {
                return 'XII';
            }

            if (
                $compact === '11' ||
                str_contains($compact, 'KELAS11') ||
                str_contains($compact, 'TINGKAT11') ||
                preg_match('/(^|[^0-9])11([^0-9]|$)/', $text)
            ) {
                return 'XI';
            }

            if (
                $compact === '10' ||
                str_contains($compact, 'KELAS10') ||
                str_contains($compact, 'TINGKAT10') ||
                preg_match('/(^|[^0-9])10([^0-9]|$)/', $text)
            ) {
                return 'X';
            }

            /*
            |--------------------------------------------------------------------------
            | Urutan wajib: XII -> XI -> X
            |--------------------------------------------------------------------------
            | Agar XIIA tidak terbaca sebagai XI atau X.
            */
            if (str_starts_with($compact, 'XII')) {
                return 'XII';
            }

            if (str_starts_with($compact, 'XI')) {
                return 'XI';
            }

            if (str_starts_with($compact, 'X')) {
                return 'X';
            }

            if (preg_match('/\bKELAS\s*XII\b|\bTINGKAT\s*XII\b|\bXII\b/', $text)) {
                return 'XII';
            }

            if (preg_match('/\bKELAS\s*XI\b|\bTINGKAT\s*XI\b|\bXI\b/', $text)) {
                return 'XI';
            }

            if (preg_match('/\bKELAS\s*X\b|\bTINGKAT\s*X\b|\bX\b/', $text)) {
                return 'X';
            }
        }

        return null;
    }

    private function resolveGuruFromApi($user): ?array
    {
        if (!$user) {
            return null;
        }

        $identifier = trim((string) ($user->sia_user_id ?? ''));
        $name = trim((string) ($user->name ?? ''));
        $email = trim((string) ($user->email ?? ''));

        if ($identifier !== '') {
            try {
                if (method_exists($this->sia, 'getGuruByKey')) {
                    $res = $this->sia->getGuruByKey($identifier);
                    $data = $this->extractData($res);

                    if (!empty($data)) {
                        return $this->normalizeGuruRow($data);
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }

            try {
                if (method_exists($this->sia, 'masterGuru')) {
                    $res = $this->sia->masterGuru($identifier);
                    $list = collect($this->extractListData($res));

                    $match = $list->first(function ($g) use ($identifier) {
                        $guru = $this->normalizeGuruRow($g);

                        return (string) ($guru['id'] ?? '') === $identifier
                            || (string) ($guru['nip'] ?? '') === $identifier
                            || (string) ($guru['nuptk'] ?? '') === $identifier;
                    });

                    if (is_array($match)) {
                        return $this->normalizeGuruRow($match);
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($name !== '') {
            try {
                if (method_exists($this->sia, 'masterGuru')) {
                    $res = $this->sia->masterGuru($name);
                    $list = collect($this->extractListData($res));

                    $match = $list->first(function ($g) use ($name, $email) {
                        $guru = $this->normalizeGuruRow($g);

                        $namaMatch = $this->normalizeName($guru['nama'] ?? '') === $this->normalizeName($name);
                        $emailMatch = $email !== ''
                            && !empty($guru['email'])
                            && mb_strtolower((string) $guru['email']) === mb_strtolower($email);

                        return $namaMatch || $emailMatch;
                    });

                    if (is_array($match)) {
                        return $this->normalizeGuruRow($match);
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return null;
    }

    private function resolveRombelWalikelas(
        array $guru,
        $activeTahunAjaranId = null,
        ?string $activeTahunAjaran = null
    ): ?array {
        $guruId = $guru['id'] ?? null;

        if (!$guruId) {
            return null;
        }

        $rombel = null;

        /*
        |--------------------------------------------------------------------------
        | 1. Cari rombel dengan filter guru, aktif, dan tahun ajaran aktif
        |--------------------------------------------------------------------------
        */
        try {
            $filters = array_filter([
                'guru_id' => $guruId,
                'aktif' => 1,
                'tahun_ajaran_id' => $activeTahunAjaranId,
                'tahun_ajaran' => $activeTahunAjaran,
            ], fn($value) => $value !== null && $value !== '');

            $res = $this->sia->masterRombel(null, $filters);
            $list = collect($this->extractListData($res));

            $rombel = $list->first(function ($r) use ($guru, $activeTahunAjaranId, $activeTahunAjaran) {
                return $this->isRombelMilikGuru($r, $guru)
                    && $this->rowMatchesActiveAcademicPeriod($r, $activeTahunAjaranId, $activeTahunAjaran);
            });
        } catch (\Throwable $e) {
            report($e);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Fallback: aktif saja, tetap divalidasi tahun ajaran aktif
        |--------------------------------------------------------------------------
        */
        if (!$rombel) {
            try {
                $res = $this->sia->masterRombel(null, [
                    'guru_id' => $guruId,
                    'aktif' => 1,
                ]);

                $list = collect($this->extractListData($res));

                $rombel = $list->first(function ($r) use ($guru, $activeTahunAjaranId, $activeTahunAjaran) {
                    return $this->isRombelMilikGuru($r, $guru)
                        && $this->rowMatchesActiveAcademicPeriod($r, $activeTahunAjaranId, $activeTahunAjaran);
                });
            } catch (\Throwable $e) {
                report($e);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Fallback terakhir: tanpa aktif, tetapi tetap pilih yang tahun ajaran aktif
        |--------------------------------------------------------------------------
        */
        if (!$rombel) {
            try {
                $res = $this->sia->masterRombel(null, [
                    'guru_id' => $guruId,
                ]);

                $list = collect($this->extractListData($res));

                $rombel = $list->first(function ($r) use ($guru, $activeTahunAjaranId, $activeTahunAjaran) {
                    return $this->isRombelMilikGuru($r, $guru)
                        && $this->rowMatchesActiveAcademicPeriod($r, $activeTahunAjaranId, $activeTahunAjaran);
                });
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (!$rombel || !is_array($rombel)) {
            return null;
        }

        $rombel = $this->normalizeRombelRow($rombel);

        /*
        |--------------------------------------------------------------------------
        | 4. Lengkapi dari detail rombel dan validasi ulang tahun ajaran aktif
        |--------------------------------------------------------------------------
        */
        if (!empty($rombel['id'])) {
            try {
                $detailRes = $this->sia->masterRombelDetail($rombel['id']);
                $detailData = $this->extractData($detailRes);

                if (!empty($detailData)) {
                    if (!$this->rowMatchesActiveAcademicPeriod($detailData, $activeTahunAjaranId, $activeTahunAjaran)) {
                        return null;
                    }

                    $detail = $this->normalizeRombelRow($detailData);

                    $rombel = array_merge($rombel, array_filter($detail, function ($value) {
                        return $value !== null && $value !== '' && $value !== '-';
                    }));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $rombel['tingkat'] = $this->resolveTingkatRombel($rombel) ?? '-';

        return [
            'id' => $rombel['id'] ?? null,
            'nama_rombel' => $rombel['nama_rombel'] ?? '-',
            'tingkat' => $rombel['tingkat'] ?? '-',
            'aktif' => $rombel['aktif'] ?? null,
            'wali_kelas' => $rombel['wali_kelas'] ?? '-',
            'tahun_ajaran_id' => $rombel['tahun_ajaran_id'] ?? $activeTahunAjaranId,
            'tahun_ajaran' => $rombel['tahun_ajaran'] ?: ($activeTahunAjaran ?: '-'),
            'semester' => $rombel['semester'] ?? null,
            'ruang_kelas' => $rombel['ruang_kelas'] ?? '-',
        ];
    }

    private function isRombelMilikGuru($row, array $guru): bool
    {
        $rombel = $this->normalizeRombelRow($row);

        $guruId = trim((string) ($guru['id'] ?? ''));
        $guruNip = trim((string) ($guru['nip'] ?? ''));
        $guruNuptk = trim((string) ($guru['nuptk'] ?? ''));
        $guruNama = $this->normalizeName($guru['nama'] ?? '');

        $waliId = trim((string) ($rombel['wali_kelas_id'] ?? ''));
        $guruIdAlt = trim((string) ($rombel['guru_id'] ?? ''));
        $waliNip = trim((string) ($rombel['wali_kelas_nip'] ?? ''));
        $waliNuptk = trim((string) ($rombel['wali_kelas_nuptk'] ?? ''));
        $waliNama = $this->normalizeName($rombel['wali_kelas'] ?? '');

        if ($guruId !== '' && ($waliId === $guruId || $guruIdAlt === $guruId)) {
            return true;
        }

        if ($guruNip !== '' && $waliNip !== '' && $guruNip === $waliNip) {
            return true;
        }

        if ($guruNuptk !== '' && $waliNuptk !== '' && $guruNuptk === $waliNuptk) {
            return true;
        }

        if ($guruNama !== '' && $waliNama !== '' && $guruNama === $waliNama) {
            return true;
        }

        return false;
    }

    private function normalizeGuruRow($row): array
    {
        $row = $this->arr($row);

        return [
            'id' => $row['id'] ?? $row['guru_id'] ?? null,
            'nama' => $this->pickString(
                $row['nama'] ?? null,
                $row['name'] ?? null,
                $row['nama_guru'] ?? null,
                ''
            ),
            'nip' => $this->pickString(
                $row['nip'] ?? null,
                $row['nomor_induk'] ?? null,
                ''
            ),
            'nuptk' => $this->pickString(
                $row['nuptk'] ?? null,
                ''
            ),
            'email' => $this->pickString(
                $row['email'] ?? null,
                ''
            ),
        ];
    }

    private function normalizeRombelRow($row): array
    {
        $row = $this->arr($row);

        $waliRaw = $row['wali_kelas']
            ?? $row['walikelas']
            ?? $row['wali']
            ?? $row['guru']
            ?? null;

        $wali = $this->arr($waliRaw);

        $tahunAjaranRaw = $row['tahun_ajaran'] ?? null;

        $tahunAjaran = $this->extractDisplayValue($tahunAjaranRaw, ['nama_tahun', 'nama', 'label'])
            ?? (is_string($tahunAjaranRaw) ? $tahunAjaranRaw : '-');

        $tahunAjaranId = $row['tahun_ajaran_id']
            ?? data_get($row, 'tahun_ajaran.id')
            ?? data_get($row, 'ta.id')
            ?? null;

        $semester = $this->normalizeSemesterLabel(
            $row['semester']
            ?? data_get($row, 'tahun_ajaran.semester')
            ?? data_get($row, 'ta.semester')
            ?? null
        );

        $ruangRaw = $row['ruang_kelas'] ?? $row['ruang'] ?? null;

        $ruangKelas = $this->extractDisplayValue($ruangRaw, ['nama_ruang', 'nama', 'label'])
            ?? (is_string($ruangRaw) ? $ruangRaw : '-');

        $namaRombel = $this->pickString(
            $row['nama_rombel'] ?? null,
            $row['nama'] ?? null,
            $row['label'] ?? null,
            $row['rombel'] ?? null,
            '-'
        );

        return [
            'id' => $row['id']
                ?? $row['rombel_id']
                ?? null,

            'nama_rombel' => $namaRombel,

            'tingkat' => $this->normalizeTingkat(
                $namaRombel,
                $row['tingkat'] ?? null
            ),

            'aktif' => $row['aktif']
                ?? $row['status']
                ?? null,

            'tahun_ajaran_id' => $tahunAjaranId,
            'tahun_ajaran' => $tahunAjaran ?: '-',
            'semester' => $semester,

            'wali_kelas_id' => $row['wali_kelas_id']
                ?? $row['walikelas_id']
                ?? $row['guru_id']
                ?? $wali['id']
                ?? $wali['guru_id']
                ?? null,

            'guru_id' => $row['guru_id']
                ?? $wali['id']
                ?? $wali['guru_id']
                ?? null,

            'wali_kelas' => $this->pickString(
                $row['nama_wali_kelas'] ?? null,
                $row['wali_kelas_nama'] ?? null,
                $row['walikelas_nama'] ?? null,
                is_string($waliRaw) ? $waliRaw : null,
                $wali['nama'] ?? null,
                $wali['name'] ?? null,
                $wali['nama_guru'] ?? null,
                '-'
            ),

            'wali_kelas_nip' => $this->pickString(
                $row['wali_kelas_nip'] ?? null,
                $row['nip_wali_kelas'] ?? null,
                $wali['nip'] ?? null,
                ''
            ),

            'wali_kelas_nuptk' => $this->pickString(
                $row['wali_kelas_nuptk'] ?? null,
                $row['nuptk_wali_kelas'] ?? null,
                $wali['nuptk'] ?? null,
                ''
            ),

            'ruang_kelas' => $ruangKelas ?: '-',
        ];
    }

    private function summarizeSiswaRombel(
        array $rombel,
        $activeTahunAjaranId = null,
        ?string $activeTahunAjaran = null
    ): array {
        $rows = [];

        try {
            $detailRes = $this->sia->masterRombelDetail($rombel['id'] ?? null);

            if ($this->responseOk($detailRes)) {
                $detail = $this->asArray($detailRes['data'] ?? []);

                if ($this->rowMatchesActiveAcademicPeriod($detail, $activeTahunAjaranId, $activeTahunAjaran)) {
                    $rows = $this->extractSiswaRowsFromDetail($detail);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if (empty($rows)) {
            try {
                $anggotaRes = $this->sia->masterRombelAnggota($rombel['id'] ?? null);
                $rows = $this->asArray($anggotaRes['data'] ?? []);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $rows = collect($rows)
            ->filter(function ($row) use ($activeTahunAjaranId, $activeTahunAjaran) {
                $row = $this->arr($row);

                /*
                |--------------------------------------------------------------------------
                | Jika baris anggota membawa konteks tahun ajaran, wajib cocok.
                | Jika tidak membawa konteks, data tetap diterima karena rombel sudah
                | divalidasi sebagai rombel aktif.
                |--------------------------------------------------------------------------
                */
                if (!$this->hasAcademicContext($row)) {
                    return true;
                }

                return $this->rowMatchesActiveAcademicPeriod($row, $activeTahunAjaranId, $activeTahunAjaran);
            })
            ->values()
            ->all();

        $total = count($rows);
        $laki = 0;
        $perempuan = 0;

        foreach ($rows as $row) {
            $jk = $this->resolveJenisKelamin($this->arr($row));

            if ($jk === 'L') {
                $laki++;
            } elseif ($jk === 'P') {
                $perempuan++;
            }
        }

        return [
            'total' => $total,
            'laki' => $laki,
            'perempuan' => $perempuan,
        ];
    }

    private function extractSiswaRowsFromDetail(array $detail): array
    {
        $candidates = [
            $detail['siswa'] ?? null,
            $detail['anggota'] ?? null,
            data_get($detail, 'siswa_aktif'),
            data_get($detail, 'rombel_anggota'),
            data_get($detail, 'data_siswa'),
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && !empty($candidate)) {
                return $candidate;
            }
        }

        return [];
    }

    private function resolveJenisKelamin(array $row): ?string
    {
        $candidates = [
            $row['jk'] ?? null,
            $row['jenis_kelamin'] ?? null,
            data_get($row, 'siswa.jk'),
            data_get($row, 'siswa.jenis_kelamin'),
            data_get($row, 'detail_siswa.jk'),
            data_get($row, 'detail_siswa.jenis_kelamin'),
            data_get($row, 'biodata.jk'),
            data_get($row, 'biodata.jenis_kelamin'),
        ];

        foreach ($candidates as $value) {
            $normalized = $this->normalizeJenisKelaminValue($value);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeJenisKelaminValue($value): ?string
    {
        $value = strtoupper(trim((string) ($value ?? '')));

        if ($value === '') {
            return null;
        }

        return match ($value) {
            'L', 'LAKI', 'LAKI-LAKI', 'LAKILAKI', 'MALE', 'M' => 'L',
            'P', 'PEREMPUAN', 'WANITA', 'FEMALE', 'F' => 'P',
            default => null,
        };
    }

    private function getJadwalRombel(
        $rombelId,
        $activeTahunAjaranId = null,
        ?string $activeTahunAjaran = null,
        ?string $activeSemester = null
    ): Collection {
        if (!$rombelId) {
            return collect();
        }

        $filters = array_filter([
            'rombel' => $rombelId,
            'rombel_id' => $rombelId,
            'tahun_ajaran_id' => $activeTahunAjaranId,
            'tahun_ajaran' => $activeTahunAjaran,
            'semester' => $activeSemester,
        ], fn($value) => $value !== null && $value !== '');

        try {
            /*
            |--------------------------------------------------------------------------
            | Prioritas: endpoint jadwal rombel.
            |--------------------------------------------------------------------------
            | Endpoint ini biasanya lebih aman karena jadwal sudah berada dalam konteks
            | rombel tertentu.
            */
            if (method_exists($this->sia, 'masterRombelJadwal')) {
                $res = $this->sia->masterRombelJadwal($rombelId, $filters);
                $rows = collect($this->asArray($res['data'] ?? []));

                return $rows
                    ->filter(function ($row) use ($activeTahunAjaranId, $activeTahunAjaran, $activeSemester) {
                        return $this->rowJadwalMatchesActivePeriod(
                            $this->arr($row),
                            $activeTahunAjaranId,
                            $activeTahunAjaran,
                            $activeSemester
                        );
                    })
                    ->values();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $res = $this->sia->masterJadwal($filters);
            $rows = collect($this->asArray($res['data'] ?? []));

            return $rows
                ->filter(function ($row) use ($rombelId, $activeTahunAjaranId, $activeTahunAjaran, $activeSemester) {
                    $row = $this->arr($row);

                    $rowRombelId = data_get($row, 'rombel_id')
                        ?? data_get($row, 'rombel.id')
                        ?? data_get($row, 'rombel.rombel_id');

                    if ($rowRombelId !== null && (string) $rowRombelId !== (string) $rombelId) {
                        return false;
                    }

                    return $this->rowJadwalMatchesActivePeriod(
                        $row,
                        $activeTahunAjaranId,
                        $activeTahunAjaran,
                        $activeSemester
                    );
                })
                ->values();
        } catch (\Throwable $e) {
            report($e);
            return collect();
        }
    }

    private function rowJadwalMatchesActivePeriod(
        array $row,
        $activeTahunAjaranId = null,
        ?string $activeTahunAjaran = null,
        ?string $activeSemester = null
    ): bool {
        $rowTahunAjaranId = data_get($row, 'tahun_ajaran_id')
            ?? data_get($row, 'tahun_ajaran.id')
            ?? data_get($row, 'ta.id')
            ?? data_get($row, 'rombel.tahun_ajaran_id')
            ?? data_get($row, 'rombel.tahun_ajaran.id');

        $rowTahunAjaran = $this->pickString(
            data_get($row, 'tahun_ajaran.nama_tahun'),
            data_get($row, 'tahun_ajaran.nama'),
            is_string(data_get($row, 'tahun_ajaran')) ? data_get($row, 'tahun_ajaran') : null,
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

        if ($activeTahunAjaranId !== null && $activeTahunAjaranId !== '') {
            if ($rowTahunAjaranId !== null && $rowTahunAjaranId !== '') {
                if ((string) $rowTahunAjaranId !== (string) $activeTahunAjaranId) {
                    return false;
                }
            }
        }

        if ($activeTahunAjaran !== null && trim($activeTahunAjaran) !== '') {
            if ($rowTahunAjaran !== null && $rowTahunAjaran !== '') {
                if (trim((string) $rowTahunAjaran) !== trim((string) $activeTahunAjaran)) {
                    return false;
                }
            }
        }

        if ($activeSemester !== null && trim($activeSemester) !== '') {
            $activeSemesterNormalized = $this->normalizeSemesterLabel($activeSemester);

            if ($rowSemester !== null && $rowSemester !== '') {
                if ($rowSemester !== $activeSemesterNormalized) {
                    return false;
                }
            }
        }

        return true;
    }

    private function rowMatchesActiveAcademicPeriod(
        array $row,
        $activeTahunAjaranId = null,
        ?string $activeTahunAjaran = null
    ): bool {
        $rowTahunAjaranId = data_get($row, 'tahun_ajaran_id')
            ?? data_get($row, 'tahun_ajaran.id')
            ?? data_get($row, 'ta.id')
            ?? data_get($row, 'pivot_tahun_ajaran_id')
            ?? data_get($row, 'pivot.tahun_ajaran_id');

        $rowTahunAjaran = $this->pickString(
            data_get($row, 'tahun_ajaran.nama_tahun'),
            data_get($row, 'tahun_ajaran.nama'),
            is_string(data_get($row, 'tahun_ajaran')) ? data_get($row, 'tahun_ajaran') : null,
            data_get($row, 'nama_tahun'),
            data_get($row, 'ta.nama_tahun'),
            data_get($row, 'ta.nama')
        );

        if ($activeTahunAjaranId !== null && $activeTahunAjaranId !== '') {
            if ($rowTahunAjaranId !== null && $rowTahunAjaranId !== '') {
                return (string) $rowTahunAjaranId === (string) $activeTahunAjaranId;
            }
        }

        if ($activeTahunAjaran !== null && trim($activeTahunAjaran) !== '') {
            if ($rowTahunAjaran !== null && $rowTahunAjaran !== '') {
                return trim((string) $rowTahunAjaran) === trim((string) $activeTahunAjaran);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Jika baris tidak membawa konteks tahun ajaran sama sekali, data diterima.
        | Keamanan utamanya ada pada request API yang sudah dikirim dengan filter
        | tahun ajaran aktif dan validasi detail rombel.
        |--------------------------------------------------------------------------
        */
        return true;
    }

    private function hasAcademicContext(array $row): bool
    {
        return data_get($row, 'tahun_ajaran_id') !== null
            || data_get($row, 'tahun_ajaran.id') !== null
            || data_get($row, 'ta.id') !== null
            || data_get($row, 'tahun_ajaran') !== null
            || data_get($row, 'nama_tahun') !== null
            || data_get($row, 'pivot_tahun_ajaran_id') !== null
            || data_get($row, 'pivot.tahun_ajaran_id') !== null;
    }

    private function extractData($response)
    {
        if (!is_array($response)) {
            return [];
        }

        $data = $response['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    private function extractListData($response): array
    {
        $data = $this->extractData($response);

        if (empty($data)) {
            return [];
        }

        if (array_is_list($data)) {
            return $data;
        }

        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        if (isset($data['items']) && is_array($data['items'])) {
            return $data['items'];
        }

        if (isset($data['rows']) && is_array($data['rows'])) {
            return $data['rows'];
        }

        return [$data];
    }

    private function asArray($data): array
    {
        return is_array($data) ? $data : [];
    }

    private function arr($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return get_object_vars($value);
        }

        return [];
    }

    private function extractDisplayValue($value, array $preferredKeys = []): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_scalar($value)) {
            return trim((string) $value) !== '' ? trim((string) $value) : null;
        }

        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (!is_array($value)) {
            return null;
        }

        foreach ($preferredKeys as $key) {
            if (isset($value[$key]) && is_scalar($value[$key]) && trim((string) $value[$key]) !== '') {
                return trim((string) $value[$key]);
            }
        }

        foreach ($value as $item) {
            if (is_scalar($item) && trim((string) $item) !== '') {
                return trim((string) $item);
            }
        }

        return null;
    }

    private function pickString(...$values): string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_numeric($value)) {
                return (string) $value;
            }
        }

        return '';
    }

    private function normalizeName(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);

        $replacements = [
            'm. pd' => 'm.pd',
            's. pd' => 's.pd',
            'm. si' => 'm.si',
            's. kom' => 's.kom',
            'm. kom' => 'm.kom',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $value ?? '');
    }

    private function normalizeHariLabel($value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return match ($value) {
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat', 'jum\'at', 'jum at', 'jum’at' => 'Jumat',
            'sabtu' => 'Sabtu',
            'minggu' => 'Minggu',
            default => $value !== '' ? ucfirst($value) : '-',
        };
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

    private function normalizeKey($value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        if ($value === '' || $value === '-') {
            return null;
        }

        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
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

    private function responseOk($response): bool
    {
        return is_array($response)
            && (
                ($response['success'] ?? false) === true ||
                ($response['status'] ?? false) === true ||
                ($response['status'] ?? null) === 'success'
            );
    }
}