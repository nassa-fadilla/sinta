<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Models\Survei;
use App\Models\SurveiRespon;
use App\Services\SiaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFIKASI REAL-TIME KEPSEK
    |--------------------------------------------------------------------------
    | Endpoint polling: GET /kepsek/notifikasi
    | Trigger: pengumuman pending, survei respon baru, data SIA baru
    */
    public function getNotifikasi(): JsonResponse
    {
        $items = [];

        // ── 1. PENGUMUMAN MENUNGGU PERSETUJUAN ───────────────────────────────
        $pending = Pengumuman::where('status', 'draft')->count();
        if ($pending > 0) {
            $items[] = [
                'id' => 'pengumuman_pending',
                'icon' => 'megaphone',
                'judul' => 'Pengumuman Menunggu',
                'pesan' => $pending . ' pengumuman menunggu persetujuan Anda',
                'url' => route('kepsek.pengumuman.index'),
                'waktu' => null,
            ];
        }

        // ── 2. SURVEI RESPON BARU (24 jam terakhir) ──────────────────────────
        $responBaru = SurveiRespon::with('survei:id,judul')
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->get();

        if ($responBaru->isNotEmpty()) {
            $grouped = $responBaru->groupBy('survei_id');
            foreach ($grouped as $surveiId => $responList) {
                $namaSurvei = $responList->first()?->survei?->judul ?? 'Survei';
                $jml = $responList->count();
                $items[] = [
                    'id' => 'survei_' . $surveiId,
                    'icon' => 'clipboard',
                    'judul' => 'Survei Diisi',
                    'pesan' => $jml . ' ortu mengisi "' . \Illuminate\Support\Str::limit($namaSurvei, 40) . '"',
                    'url' => route('kepsek.dashboard'),
                    'waktu' => $responList->first()?->created_at?->diffForHumans(),
                ];
            }
        }

        // ── 3. DATA BARU DARI SIA (siswa / guru) — sama persis dengan admin ──
        try {
            $summaryNow = $this->sia->dashboardSummary();
            $totalSiswaNow = (int) ($summaryNow['data']['total_siswa'] ?? $summaryNow['data']['siswa'] ?? 0);
            $totalGuruNow = (int) ($summaryNow['data']['total_guru'] ?? $summaryNow['data']['guru'] ?? 0);

            $snapSiswa = (int) Cache::get('sinta.notif.sia_total_siswa', $totalSiswaNow);
            $snapGuru = (int) Cache::get('sinta.notif.sia_total_guru', $totalGuruNow);

            if (!Cache::has('sinta.notif.sia_total_siswa'))
                Cache::forever('sinta.notif.sia_total_siswa', $totalSiswaNow);
            if (!Cache::has('sinta.notif.sia_total_guru'))
                Cache::forever('sinta.notif.sia_total_guru', $totalGuruNow);

            if ($totalSiswaNow - $snapSiswa > 0) {
                $items[] = [
                    'id' => 'sia_siswa',
                    'icon' => 'users',
                    'judul' => 'Data Siswa Baru',
                    'pesan' => ($totalSiswaNow - $snapSiswa) . ' data siswa baru terdeteksi dari SIA',
                    'url' => route('kepsek.sia-master.siswa.index'),
                    'waktu' => null,
                ];
            }

            if ($totalGuruNow - $snapGuru > 0) {
                $items[] = [
                    'id' => 'sia_guru',
                    'icon' => 'user-check',
                    'judul' => 'Data Guru Baru',
                    'pesan' => ($totalGuruNow - $snapGuru) . ' data guru baru terdeteksi dari SIA',
                    'url' => route('kepsek.sia-master.guru.index'),
                    'waktu' => null,
                ];
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
        try {
            $summary = $this->sia->dashboardSummary();
            Cache::forever('sinta.notif.sia_total_siswa', (int) ($summary['data']['total_siswa'] ?? $summary['data']['siswa'] ?? 0));
            Cache::forever('sinta.notif.sia_total_guru', (int) ($summary['data']['total_guru'] ?? $summary['data']['guru'] ?? 0));
        } catch (\Throwable) {
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Dashboard Kepala Sekolah
     */
    public function index()
    {
        Carbon::setLocale('id');

        $user = auth()->user();

        /*
        |----------------------------------------------------------------------
        | 1. Statistik Pengumuman (lokal SINTA)
        |----------------------------------------------------------------------
        | Data pengumuman adalah data internal SINTA, sehingga tidak difilter
        | berdasarkan tahun ajaran SIA.
        */
        $pending = Pengumuman::where('status', 'draft')->count();
        $approved = Pengumuman::where('status', 'approved')->count();
        $rejected = Pengumuman::where('status', 'rejected')->count();

        /*
        |----------------------------------------------------------------------
        | 2. Ringkasan Survei (lokal SINTA)
        |----------------------------------------------------------------------
        | Survei juga merupakan data internal SINTA.
        */
        $surveiList = Survei::with('pertanyaan')->get();
        $hasil = [];

        foreach ($surveiList as $s) {
            $respon = SurveiRespon::where('survei_id', $s->id)->get();

            $decode = function ($raw) {
                if (is_array($raw)) {
                    return $raw;
                }

                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    return is_array($decoded) ? $decoded : [];
                }

                return [];
            };

            $ringkasan = [];

            foreach ($s->pertanyaan as $p) {
                $ringkasan[$p->pertanyaan] = [];
            }

            foreach ($respon as $r) {
                $jawaban = $decode($r->jawaban);

                foreach ($jawaban as $pid => $val) {
                    $pertanyaan = $s->pertanyaan->firstWhere('id', (int) $pid)?->pertanyaan;

                    if (!$pertanyaan) {
                        continue;
                    }

                    $vals = is_array($val) ? $val : [$val];

                    foreach ($vals as $v) {
                        $v = trim((string) $v);

                        if ($v === '') {
                            continue;
                        }

                        $ringkasan[$pertanyaan][$v] = ($ringkasan[$pertanyaan][$v] ?? 0) + 1;
                    }
                }
            }

            $hasil[] = [
                'judul' => $s->judul,
                'ringkasan' => $ringkasan,
            ];
        }

        /*
        |----------------------------------------------------------------------
        | 3. Tahun ajaran aktif dari API SIA
        |----------------------------------------------------------------------
        | Tahun ajaran aktif menjadi konteks utama seluruh data akademik.
        */
        $summaryResp = $this->safeCallSia('dashboardSummary');
        $summary = $this->toArray($summaryResp['data'] ?? []);

        $activePeriod = $this->resolveActiveAcademicPeriod($summary);

        $activeTahunAjaranId = $activePeriod['id'] ?? null;
        $activeTahunAjaranNama = $activePeriod['nama_tahun'] ?? null;
        $activeSemester = $activePeriod['semester'] ?? null;

        $tahunAjaranAktif = $this->formatTahunAjaranLabel($activePeriod);

        $academicFilters = $this->buildAcademicFilters(
            $activeTahunAjaranId,
            $activeTahunAjaranNama,
            $activeSemester
        );

        /*
        |----------------------------------------------------------------------
        | 4. Summary akademik aktif dari SIA
        |----------------------------------------------------------------------
        | Jika endpoint summary SIA belum memfilter tahun ajaran aktif, SINTA
        | menghitung ulang rombel dan siswa berdasarkan rombel tahun ajaran aktif.
        */
        $totalGuru = (int) ($summary['total_guru'] ?? 0);
        $totalSiswa = (int) ($summary['total_siswa'] ?? 0);
        $totalRombel = (int) ($summary['total_rombel'] ?? 0);
        $genderSummary = $this->toArray($summary['gender'] ?? []);

        $activeRombelSummary = $this->resolveActiveRombelSummary(
            $activeTahunAjaranId,
            $activeTahunAjaranNama
        );

        if (($activeRombelSummary['total_rombel'] ?? 0) > 0) {
            $totalRombel = (int) $activeRombelSummary['total_rombel'];

            if (($activeRombelSummary['total_siswa'] ?? 0) > 0) {
                $totalSiswa = (int) $activeRombelSummary['total_siswa'];
            }

            if (!empty($activeRombelSummary['gender'])) {
                $genderSummary = $activeRombelSummary['gender'];
            }
        }

        /*
        |----------------------------------------------------------------------
        | 5. Presensi hari ini
        |----------------------------------------------------------------------
        | Filter tahun ajaran aktif dikirim jika endpoint SIA mendukung parameter.
        | Jika tidak, fallback tetap memakai method lama agar tidak merusak fungsi.
        */
        $presensiResp = $this->safeCallSiaWithFilters('dashboardPresensiToday', $academicFilters);
        $presensi = $this->toArray($presensiResp['data'] ?? []);

        $presensiToday = [
            'total' => (int) ($presensi['total'] ?? 0),
            'hadir' => (int) ($presensi['hadir'] ?? 0),
            'persen' => isset($presensi['persen'])
                ? (float) $presensi['persen']
                : (
                    ((int) ($presensi['total'] ?? 0)) > 0
                    ? round(((int) ($presensi['hadir'] ?? 0)) / max((int) ($presensi['total'] ?? 1), 1) * 100, 1)
                    : null
                ),
        ];

        /*
        |----------------------------------------------------------------------
        | 6. Tren presensi 7 hari
        |----------------------------------------------------------------------
        */
        $trend7Resp = $this->safeCallSiaWithFilters('dashboardPresensiTrend7', $academicFilters);

        $presensiTrend7 = collect($this->toList($trend7Resp['data'] ?? []))
            ->filter(function ($row) use ($activeTahunAjaranId, $activeTahunAjaranNama) {
                if (!$this->hasAcademicContext($row)) {
                    return true;
                }

                return $this->rowMatchesAcademicContext(
                    $row,
                    $activeTahunAjaranId,
                    $activeTahunAjaranNama
                );
            })
            ->map(function ($row) {
                return [
                    'tanggal' => (string) (
                        $row['tanggal']
                        ?? $row['label']
                        ?? $row['hari']
                        ?? '-'
                    ),
                    'persen' => (float) (
                        $row['persen']
                        ?? $row['percentage']
                        ?? $row['value']
                        ?? 0
                    ),
                ];
            })
            ->values()
            ->all();

        /*
        |----------------------------------------------------------------------
        | 7. Rekap nilai per tingkat
        |----------------------------------------------------------------------
        */
        $rekapNilaiTingkatResp = $this->safeCallSiaWithFilters(
            'dashboardRekapNilaiPerTingkat',
            $academicFilters
        );

        $rekapNilaiTingkat = collect($this->toList($rekapNilaiTingkatResp['data'] ?? []))
            ->filter(function ($row) use ($activeTahunAjaranId, $activeTahunAjaranNama) {
                if (!$this->hasAcademicContext($row)) {
                    return true;
                }

                return $this->rowMatchesAcademicContext(
                    $row,
                    $activeTahunAjaranId,
                    $activeTahunAjaranNama
                );
            })
            ->map(function ($row) {
                return [
                    'tingkat' => (string) ($row['tingkat'] ?? '-'),
                    'rata_rata' => (float) ($row['rata_rata'] ?? $row['rata'] ?? 0),
                ];
            })
            ->values()
            ->all();

        /*
        |----------------------------------------------------------------------
        | 8. Rata-rata nilai global
        |----------------------------------------------------------------------
        */
        $rataNilaiGlobalResp = $this->safeCallSiaWithFilters(
            'dashboardRataNilaiGlobal',
            $academicFilters
        );

        $rataNilaiGlobalRaw = $this->toArray($rataNilaiGlobalResp['data'] ?? []);

        $rataNilaiGlobal = isset($rataNilaiGlobalRaw['rata_rata'])
            ? (float) $rataNilaiGlobalRaw['rata_rata']
            : (
                isset($rataNilaiGlobalRaw['value'])
                ? (float) $rataNilaiGlobalRaw['value']
                : null
            );

        /*
        |----------------------------------------------------------------------
        | 9. Top siswa
        |----------------------------------------------------------------------
        */
        $topSiswaResp = $this->safeCallSiaWithFilters('dashboardTopSiswa', $academicFilters);

        $topSiswa = collect($this->toList($topSiswaResp['data'] ?? []))
            ->filter(function ($row) use ($activeTahunAjaranId, $activeTahunAjaranNama) {
                if (!$this->hasAcademicContext($row)) {
                    return true;
                }

                return $this->rowMatchesAcademicContext(
                    $row,
                    $activeTahunAjaranId,
                    $activeTahunAjaranNama
                );
            })
            ->map(function ($row) {
                $siswa = $this->toArray($row['siswa'] ?? []);
                $rombel = $this->toArray($row['rombel'] ?? []);
                $kelas = $this->toArray($row['kelas'] ?? []);

                $nama = $row['nama']
                    ?? $siswa['nama']
                    ?? '-';

                $rombelNama = $row['nama_rombel']
                    ?? $row['rombel_nama']
                    ?? $rombel['nama_rombel']
                    ?? $rombel['nama']
                    ?? $kelas['nama_rombel']
                    ?? $kelas['nama']
                    ?? $siswa['rombel_nama']
                    ?? $siswa['kelas']
                    ?? (
                        is_scalar($row['rombel'] ?? null)
                        ? (string) $row['rombel']
                        : '-'
                    );

                return [
                    'nama' => is_scalar($nama) ? (string) $nama : '-',
                    'rombel' => is_scalar($rombelNama) ? (string) $rombelNama : '-',
                    'rata_rata' => (float) (
                        $row['rata_rata']
                        ?? $row['rata']
                        ?? $row['nilai_rata']
                        ?? $row['nilai_rata_rata']
                        ?? 0
                    ),
                ];
            })
            ->values()
            ->all();

        /*
        |----------------------------------------------------------------------
        | 10. Profil kepsek dari SIA
        |----------------------------------------------------------------------
        | Tidak query DB SIA langsung, tetap memakai endpoint guru.
        */
        $guruSia = $this->resolveKepsekFromSia($user);

        /*
        |----------------------------------------------------------------------
        | 11. Kirim ke view
        |----------------------------------------------------------------------
        */
        return view('kepsek.dashboard', [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'hasil' => $hasil,

            'guruSia' => $guruSia,
            'tahunAjaranAktif' => $tahunAjaranAktif,

            'totalGuru' => $totalGuru,
            'totalSiswa' => $totalSiswa,
            'totalRombel' => $totalRombel,
            'genderSummary' => $genderSummary,

            'presensiToday' => $presensiToday,
            'presensiTrend7' => $presensiTrend7,

            'rekapNilaiTingkat' => $rekapNilaiTingkat,
            'rataNilaiGlobal' => $rataNilaiGlobal,
            'topSiswa' => $topSiswa,
        ]);
    }

    private function resolveActiveAcademicPeriod(array $summary = []): array
    {
        $ta = $this->toArray($summary['tahun_ajaran_aktif'] ?? []);

        if (!empty($ta)) {
            return [
                'id' => $ta['id'] ?? $ta['tahun_ajaran_id'] ?? null,
                'nama_tahun' => $this->pickString(
                    $ta['nama_tahun'] ?? null,
                    $ta['nama'] ?? null,
                    $ta['tahun_ajaran'] ?? null
                ),
                'semester' => $this->normalizeSemesterLabel(
                    $ta['semester'] ?? $ta['semester_aktif'] ?? null
                ),
                'status' => $ta['status'] ?? null,
            ];
        }

        try {
            if (method_exists($this->sia, 'masterTahunAjaranAktif')) {
                $resp = $this->sia->masterTahunAjaranAktif();
                $data = $this->toArray($resp['data'] ?? []);

                if (!empty($data)) {
                    return [
                        'id' => $data['id'] ?? $data['tahun_ajaran_id'] ?? null,
                        'nama_tahun' => $this->pickString(
                            $data['nama_tahun'] ?? null,
                            $data['nama'] ?? null,
                            $data['tahun_ajaran'] ?? null
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

        return [
            'id' => null,
            'nama_tahun' => null,
            'semester' => null,
            'status' => null,
        ];
    }

    private function formatTahunAjaranLabel(array $period): string
    {
        $nama = $this->pickString(
            $period['nama_tahun'] ?? null,
            $period['nama'] ?? null
        );

        $semester = $this->normalizeSemesterLabel(
            $period['semester'] ?? $period['semester_aktif'] ?? null
        );

        if (!$nama) {
            return '—';
        }

        return $semester ? $nama . ' (' . $semester . ')' : $nama;
    }

    private function buildAcademicFilters($tahunAjaranId = null, ?string $tahunAjaranNama = null, ?string $semester = null): array
    {
        return array_filter([
            'tahun_ajaran_id' => $tahunAjaranId,
            'tahun_ajaran' => $tahunAjaranNama,
            'semester' => $semester,
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function resolveActiveRombelSummary($activeTahunAjaranId = null, ?string $activeTahunAjaranNama = null): array
    {
        $rombelRows = collect();

        try {
            if (method_exists($this->sia, 'masterRombel')) {
                try {
                    $resp = $this->sia->masterRombel(null, array_filter([
                        'tahun_ajaran_id' => $activeTahunAjaranId,
                        'tahun_ajaran' => $activeTahunAjaranNama,
                        'aktif' => 1,
                    ], fn($value) => $value !== null && $value !== ''));

                    $rombelRows = collect($this->toList($resp['data'] ?? []));
                } catch (\Throwable $e) {
                    $resp = $this->sia->masterRombel();
                    $rombelRows = collect($this->toList($resp['data'] ?? []));
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $rombelRows = $rombelRows
            ->map(fn($row) => $this->normalizeRombelRow($row))
            ->filter(function ($row) use ($activeTahunAjaranId, $activeTahunAjaranNama) {
                if (!$this->hasAcademicContext($row)) {
                    return false;
                }

                return $this->rowMatchesAcademicContext(
                    $row,
                    $activeTahunAjaranId,
                    $activeTahunAjaranNama
                );
            })
            ->filter(function ($row) {
                $aktif = $row['aktif'] ?? null;

                if ($aktif === null || $aktif === '') {
                    return true;
                }

                return (string) $aktif === '1'
                    || $aktif === true
                    || strtolower((string) $aktif) === 'aktif';
            })
            ->unique(fn($row) => (string) ($row['id'] ?? '') . '|' . (string) ($row['nama_rombel'] ?? ''))
            ->values();

        $gender = [
            'laki' => 0,
            'perempuan' => 0,
            'L' => 0,
            'P' => 0,
        ];

        $totalSiswa = 0;

        foreach ($rombelRows as $rombel) {
            $anggota = $this->fetchRombelAnggota($rombel['id'] ?? null);

            $totalSiswa += count($anggota);

            foreach ($anggota as $siswa) {
                $jk = $this->normalizeJenisKelamin(
                    $siswa['jk']
                    ?? $siswa['jenis_kelamin']
                    ?? data_get($siswa, 'siswa.jk')
                    ?? data_get($siswa, 'siswa.jenis_kelamin')
                    ?? null
                );

                if ($jk === 'L') {
                    $gender['laki']++;
                    $gender['L']++;
                } elseif ($jk === 'P') {
                    $gender['perempuan']++;
                    $gender['P']++;
                }
            }
        }

        return [
            'total_rombel' => $rombelRows->count(),
            'total_siswa' => $totalSiswa,
            'gender' => $gender,
        ];
    }

    private function fetchRombelAnggota($rombelId): array
    {
        if (!$rombelId) {
            return [];
        }

        try {
            if (method_exists($this->sia, 'masterRombelAnggota')) {
                $resp = $this->sia->masterRombelAnggota($rombelId);
                return $this->toList($resp['data'] ?? []);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            if (method_exists($this->sia, 'masterRombelDetail')) {
                $resp = $this->sia->masterRombelDetail($rombelId);
                $detail = $this->toArray($resp['data'] ?? []);

                $candidates = [
                    $detail['siswa'] ?? null,
                    $detail['anggota'] ?? null,
                    $detail['rombel_anggota'] ?? null,
                    $detail['data_siswa'] ?? null,
                ];

                foreach ($candidates as $candidate) {
                    if (is_array($candidate)) {
                        return $this->toList($candidate);
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return [];
    }

    private function normalizeRombelRow($row): array
    {
        $row = $this->toArray($row);

        $tahunAjaran = $this->toArray(
            $row['tahun_ajaran']
            ?? $row['tahun_ajaran_detail']
            ?? $row['tahun_ajaran_obj']
            ?? $row['ta']
            ?? []
        );

        $wali = $this->toArray(
            $row['wali_kelas']
            ?? $row['walikelas']
            ?? $row['guru']
            ?? []
        );

        return [
            'id' => $row['id'] ?? $row['rombel_id'] ?? null,
            'nama_rombel' => $this->pickString(
                $row['nama_rombel'] ?? null,
                $row['nama'] ?? null,
                $row['label'] ?? null,
                $row['rombel'] ?? null
            ),
            'tingkat' => $this->pickString(
                $row['tingkat'] ?? null,
                $row['level'] ?? null
            ),
            'aktif' => $row['aktif']
                ?? $row['status']
                ?? null,
            'tahun_ajaran_id' => $row['tahun_ajaran_id']
                ?? $tahunAjaran['id']
                ?? null,
            'tahun_ajaran' => $this->pickString(
                $tahunAjaran['nama_tahun'] ?? null,
                $tahunAjaran['nama'] ?? null,
                $row['nama_tahun'] ?? null,
                is_string($row['tahun_ajaran'] ?? null) ? $row['tahun_ajaran'] : null,
                $row['tahun_ajaran_label'] ?? null
            ),
            'wali_kelas' => $this->pickString(
                $row['nama_wali_kelas'] ?? null,
                $row['wali_kelas_nama'] ?? null,
                $wali['nama'] ?? null,
                $wali['name'] ?? null,
                is_string($row['wali_kelas'] ?? null) ? $row['wali_kelas'] : null
            ),
        ];
    }

    private function safeCallSia(string $method): array
    {
        try {
            if (method_exists($this->sia, $method)) {
                return $this->sia->{$method}();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'success' => false,
            'status' => false,
            'data' => [],
        ];
    }

    private function safeCallSiaWithFilters(string $method, array $filters = []): array
    {
        try {
            if (method_exists($this->sia, $method)) {
                try {
                    return $this->sia->{$method}($filters);
                } catch (\ArgumentCountError $e) {
                    return $this->sia->{$method}();
                } catch (\TypeError $e) {
                    return $this->sia->{$method}();
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'success' => false,
            'status' => false,
            'data' => [],
        ];
    }

    private function resolveKepsekFromSia($user): ?object
    {
        $lookupKey = trim((string) ($user?->sia_user_id ?? ''));

        if ($lookupKey !== '') {
            try {
                if (method_exists($this->sia, 'getGuruByKey')) {
                    $resp = $this->sia->getGuruByKey($lookupKey);
                    $data = $this->toArray($resp['data'] ?? []);

                    if (!empty($data)) {
                        return (object) [
                            'id' => $data['id'] ?? null,
                            'nama' => $data['nama'] ?? ($user->name ?? null),
                            'nip' => $data['nip'] ?? null,
                            'nuptk' => $data['nuptk'] ?? $lookupKey,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($user && !empty($user->name)) {
            try {
                $guruResp = $this->sia->masterGuru($user->name, [
                    'per_page' => 10,
                ]);

                $guruList = collect($this->toList($guruResp['data'] ?? []));

                $exact = $guruList->first(function ($g) use ($user) {
                    $nama = strtolower(trim((string) ($g['nama'] ?? '')));
                    return $nama === strtolower(trim((string) $user->name));
                });

                $picked = $exact ?: $guruList->first();

                if ($picked) {
                    return (object) [
                        'id' => $picked['id'] ?? null,
                        'nama' => $picked['nama'] ?? $user->name,
                        'nip' => $picked['nip'] ?? null,
                        'nuptk' => $picked['nuptk'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return null;
    }

    private function hasAcademicContext(array $row): bool
    {
        return data_get($row, 'tahun_ajaran_id') !== null
            || data_get($row, 'tahun_ajaran.id') !== null
            || data_get($row, 'tahun_ajaran') !== null
            || data_get($row, 'tahun_ajaran.nama_tahun') !== null
            || data_get($row, 'tahun_ajaran.nama') !== null
            || data_get($row, 'tahun_ajaran_label') !== null
            || data_get($row, 'nama_tahun') !== null
            || data_get($row, 'ta.id') !== null
            || data_get($row, 'ta.nama_tahun') !== null
            || data_get($row, 'ta.nama') !== null;
    }

    private function rowMatchesAcademicContext(
        array $row,
        $activeTahunAjaranId = null,
        ?string $activeTahunAjaranNama = null
    ): bool {
        $rowTahunAjaranId = data_get($row, 'tahun_ajaran_id')
            ?? data_get($row, 'tahun_ajaran.id')
            ?? data_get($row, 'ta.id');

        $rowTahunAjaran = $this->pickString(
            data_get($row, 'tahun_ajaran.nama_tahun'),
            data_get($row, 'tahun_ajaran.nama'),
            is_string(data_get($row, 'tahun_ajaran')) ? data_get($row, 'tahun_ajaran') : null,
            data_get($row, 'tahun_ajaran_label'),
            data_get($row, 'nama_tahun'),
            data_get($row, 'ta.nama_tahun'),
            data_get($row, 'ta.nama')
        );

        if ($activeTahunAjaranId !== null && $activeTahunAjaranId !== '') {
            if ($rowTahunAjaranId !== null && $rowTahunAjaranId !== '') {
                return (string) $rowTahunAjaranId === (string) $activeTahunAjaranId;
            }
        }

        if ($activeTahunAjaranNama !== null && trim((string) $activeTahunAjaranNama) !== '') {
            if ($rowTahunAjaran !== null && trim((string) $rowTahunAjaran) !== '') {
                return trim((string) $rowTahunAjaran) === trim((string) $activeTahunAjaranNama);
            }
        }

        return true;
    }

    private function normalizeJenisKelamin($value): ?string
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

    private function toArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        return [];
    }

    private function toList($value): array
    {
        if ($value instanceof Collection) {
            return $value->map(fn($item) => $this->toArray($item))->all();
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return array_map(fn($item) => $this->toArray($item), $value);
            }

            if (isset($value['data']) && is_array($value['data'])) {
                return $this->toList($value['data']);
            }

            if (isset($value['items']) && is_array($value['items'])) {
                return $this->toList($value['items']);
            }

            if (isset($value['rows']) && is_array($value['rows'])) {
                return $this->toList($value['rows']);
            }

            return [$value];
        }

        if (is_object($value)) {
            return $this->toList((array) $value);
        }

        return [];
    }
}