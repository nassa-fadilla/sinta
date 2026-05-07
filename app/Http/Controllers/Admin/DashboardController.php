<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use App\Services\SiaClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(SiaClient $sia)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. SUMMARY DARI SIA + TAHUN AJARAN AKTIF
        |--------------------------------------------------------------------------
        */
        $summaryResp = $this->callSia($sia, 'dashboardSummary');
        $summary = $this->toArray($summaryResp['data'] ?? []);

        $tahunAjaranAktifData = $this->resolveActiveTahunAjaran($sia, $summary);
        $tahunAjaranAktifId = $tahunAjaranAktifData['id'] ?? null;
        $tahunAjaranAktif = $tahunAjaranAktifData['label'] ?? '—';
        $semesterAktif = $tahunAjaranAktifData['semester'] ?? null;

        $filterTa = $this->buildTahunAjaranParams($tahunAjaranAktifId, $semesterAktif);

        /*
        |--------------------------------------------------------------------------
        | 2. DATA ROMBEL & SISWA AKTIF BERDASARKAN TAHUN AJARAN AKTIF
        |--------------------------------------------------------------------------
        | Total rombel, total siswa, dan komposisi gender dihitung ulang dari data
        | rombel aktif pada tahun ajaran aktif agar tidak tercampur dengan data lama.
        */
        $rombelAktif = $this->fetchRombelAktifByTahunAjaran($sia, $tahunAjaranAktifId, $tahunAjaranAktif);

        $totalKelas = $rombelAktif->count();

        /*
        |--------------------------------------------------------------------------
        | Total guru dan siswa memakai total keseluruhan dari summary SIA.
        |--------------------------------------------------------------------------
        | Data ini merepresentasikan jumlah master data secara umum, bukan hanya
        | data pada tahun ajaran aktif. Yang tetap difilter berdasarkan tahun ajaran
        | aktif adalah rombel, jadwal, nilai, top siswa, dan presensi.
        */
        $totalGuru = (int) (
            $summary['total_guru']
            ?? $summary['guru']
            ?? 0
        );

        $totalSiswa = (int) (
            $summary['total_siswa']
            ?? $summary['siswa']
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Komposisi gender juga mengikuti summary keseluruhan SIA.
        |--------------------------------------------------------------------------
        */
        $gender = $this->toArray($summary['gender'] ?? []);

        $genderL = (int) (
            $gender['L']
            ?? $gender['l']
            ?? $gender['laki_laki']
            ?? $gender['laki']
            ?? 0
        );

        $genderP = (int) (
            $gender['P']
            ?? $gender['p']
            ?? $gender['perempuan']
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | 3. PRESENSI HARI INI
        |--------------------------------------------------------------------------
        | Endpoint dashboard SIA tetap dipanggil dengan parameter tahun ajaran aktif.
        | Jika endpoint belum mendukung parameter, helper callSia akan tetap aman.
        */
        $presensiResp = $this->callSia($sia, 'dashboardPresensiToday', $filterTa);
        $presensi = $this->toArray($presensiResp['data'] ?? []);

        $presensiTotal = (int) ($presensi['total'] ?? 0);
        $presensiHadir = (int) ($presensi['hadir'] ?? 0);

        $presensiPersen = isset($presensi['persen'])
            ? (float) $presensi['persen']
            : (
                $presensiTotal > 0
                ? round(($presensiHadir / max($presensiTotal, 1)) * 100, 1)
                : 0
            );

        /*
        |--------------------------------------------------------------------------
        | 4. TREN PRESENSI BULANAN
        |--------------------------------------------------------------------------
        */
        $trendMonthlyResp = $this->callSia($sia, 'dashboardPresensiTrendMonthly', $filterTa);
        $trendMonthlyRaw = $trendMonthlyResp['data'] ?? [];

        $trendLabelsMonthly = [];
        $trendSeriesMonthly = [];

        if (is_array($trendMonthlyRaw)) {
            if ($this->isAssoc($trendMonthlyRaw)) {
                $trendLabelsMonthly = array_values($trendMonthlyRaw['labels'] ?? []);
                $trendSeriesMonthly = array_map(
                    fn($v) => (float) $v,
                    array_values($trendMonthlyRaw['series'] ?? [])
                );
            } else {
                foreach ($trendMonthlyRaw as $row) {
                    $row = $this->toArray($row);

                    if (!$this->matchesTahunAjaran($row, $tahunAjaranAktifId, $tahunAjaranAktif)) {
                        continue;
                    }

                    $trendLabelsMonthly[] = (string) (
                        $row['label']
                        ?? $row['bulan']
                        ?? $row['month']
                        ?? '-'
                    );

                    $trendSeriesMonthly[] = (float) (
                        $row['persen']
                        ?? $row['percentage']
                        ?? $row['value']
                        ?? 0
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 5. REKAP NILAI PER KELAS
        |--------------------------------------------------------------------------
        */
        $rekapResp = $this->callSia($sia, 'dashboardRekapNilaiPerKelas', $filterTa);

        $rekapNilaiKelas = collect($this->toList($rekapResp['data'] ?? []))
            ->filter(fn($row) => $this->matchesTahunAjaran($row, $tahunAjaranAktifId, $tahunAjaranAktif))
            ->map(function ($row) {
                return (object) [
                    'nama_rombel' => $row['nama_rombel']
                        ?? $row['rombel']
                        ?? $row['kelas']
                        ?? '-',
                    'rata_rata' => (float) (
                        $row['rata_rata']
                        ?? $row['rata']
                        ?? $row['nilai_akhir']
                        ?? $row['avg']
                        ?? 0
                    ),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | 6. REKAP NILAI PER TINGKAT
        |--------------------------------------------------------------------------
        */
        $rekapTingkatResp = $this->callSia($sia, 'dashboardRekapNilaiPerTingkat', $filterTa);

        $rekapNilaiTingkat = collect($this->toList($rekapTingkatResp['data'] ?? []))
            ->filter(fn($row) => $this->matchesTahunAjaran($row, $tahunAjaranAktifId, $tahunAjaranAktif))
            ->map(function ($row) {
                return (object) [
                    'tingkat' => $row['tingkat']
                        ?? $row['level']
                        ?? '-',
                    'rata_rata' => (float) (
                        $row['rata_rata']
                        ?? $row['rata']
                        ?? $row['nilai_akhir']
                        ?? $row['avg']
                        ?? 0
                    ),
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | 7. RATA-RATA NILAI GLOBAL
        |--------------------------------------------------------------------------
        */
        $rataResp = $this->callSia($sia, 'dashboardRataNilaiGlobal', $filterTa);
        $rataData = $this->toArray($rataResp['data'] ?? []);

        $rataNilaiGlobal = (float) (
            $rataData['rata_rata']
            ?? $rataData['value']
            ?? $rataData['nilai_akhir']
            ?? $rataData['avg']
            ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | 8. TOP SISWA
        |--------------------------------------------------------------------------
        */
        $topResp = $this->callSia($sia, 'dashboardTopSiswa', $filterTa);

        $topSiswa = collect($this->toList($topResp['data'] ?? []))
            ->filter(fn($row) => $this->matchesTahunAjaran($row, $tahunAjaranAktifId, $tahunAjaranAktif))
            ->map(function ($row) {
                $siswa = $this->toArray($row['siswa'] ?? []);
                $rombel = $this->toArray(
                    $row['rombel']
                    ?? $siswa['rombel']
                    ?? $siswa['rombel_aktif']
                    ?? []
                );

                $nama = $row['nama']
                    ?? $siswa['nama']
                    ?? '-';

                $namaRombel = $row['nama_rombel']
                    ?? $row['rombel_nama']
                    ?? $row['kelas']
                    ?? $this->extractDisplayValue($row['rombel'] ?? null, ['nama_rombel', 'nama', 'label'])
                    ?? $this->extractDisplayValue($siswa['rombel'] ?? null, ['nama_rombel', 'nama', 'label'])
                    ?? $this->extractDisplayValue($siswa['rombel_aktif'] ?? null, ['nama_rombel', 'nama', 'label'])
                    ?? ($rombel['nama_rombel'] ?? null)
                    ?? '-';

                $tingkat = $row['tingkat']
                    ?? ($rombel['tingkat'] ?? null);

                if (
                    is_scalar($tingkat)
                    && is_scalar($namaRombel)
                    && $namaRombel !== '-'
                    && trim((string) $tingkat) !== ''
                    && !str_starts_with(strtoupper((string) $namaRombel), strtoupper((string) $tingkat))
                ) {
                    $namaRombel = (string) $tingkat . (string) $namaRombel;
                }

                return (object) [
                    'nama' => is_scalar($nama) ? (string) $nama : '-',
                    'nama_rombel' => is_scalar($namaRombel) ? (string) $namaRombel : '-',
                    'rata_rata' => (float) (
                        $row['rata_rata']
                        ?? $row['rata']
                        ?? $row['nilai_akhir']
                        ?? $row['avg']
                        ?? 0
                    ),
                ];
            })
            ->take(5)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | 9. PENGUMUMAN AKTIF (LOKAL SINTA)
        |--------------------------------------------------------------------------
        | Pengumuman tetap dari DB SINTA karena merupakan modul internal SINTA.
        */
        $today = Carbon::today();

        $pengumumanAktif = Pengumuman::where('is_active', 1)
            ->where('status', 'approved')
            ->where(function ($q) use ($today) {
                $q->whereNull('publish_at')
                    ->orWhereDate('publish_at', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('expire_at')
                    ->orWhereDate('expire_at', '>=', $today);
            })
            ->latest('id')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 10. JADWAL DENGAN TAB HARI SENIN - JUMAT
        |--------------------------------------------------------------------------
        | Jadwal difilter berdasarkan hari yang dipilih dan tahun ajaran aktif.
        */
        $hariTabs = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $hariSekarang = Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l');
        if (!in_array($hariSekarang, $hariTabs, true)) {
            $hariSekarang = 'Senin';
        }

        $hariRequest = trim((string) request('hari', ''));
        $hariAktif = in_array($hariRequest, $hariTabs, true) ? $hariRequest : $hariSekarang;

        $jadwalParams = array_merge($filterTa, [
            'hari' => $hariAktif,
        ]);

        $jadwalResp = $sia->masterJadwal($jadwalParams);

        $now = Carbon::now('Asia/Jakarta');
        $rombelAktifIds = $rombelAktif
            ->pluck('id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->values()
            ->all();

        $jadwalHariIni = collect($this->toList($jadwalResp['data'] ?? []))
            ->filter(function ($row) use ($tahunAjaranAktifId, $tahunAjaranAktif, $rombelAktifIds) {
                if (!$this->matchesTahunAjaran($row, $tahunAjaranAktifId, $tahunAjaranAktif)) {
                    return false;
                }

                if (empty($rombelAktifIds)) {
                    return true;
                }

                $rombelId = $this->extractRombelId($row);

                if (!$rombelId) {
                    return true;
                }

                return in_array((string) $rombelId, $rombelAktifIds, true);
            })
            ->map(function ($row) use ($now, $hariAktif, $hariSekarang) {
                $rombel = $this->extractDisplayValue($row['rombel'] ?? null, ['nama_rombel', 'nama', 'label']);
                $mapel = $this->extractDisplayValue($row['mapel'] ?? null, ['nama_mapel', 'nama', 'label']);
                $guru = $this->extractDisplayValue($row['guru'] ?? null, ['nama', 'label']);

                $jamMulaiRaw = (string) ($row['jam_mulai'] ?? '');
                $jamSelesaiRaw = (string) ($row['jam_selesai'] ?? '');

                $jamMulai = $this->parseJam($jamMulaiRaw);
                $jamSelesai = $this->parseJam($jamSelesaiRaw);

                $isTodayTab = $hariAktif === $hariSekarang;
                $isActiveNow = false;
                $isUpcoming = false;

                if ($isTodayTab && $jamMulai && $jamSelesai) {
                    $currentMinutes = ((int) $now->format('H')) * 60 + ((int) $now->format('i'));
                    $startMinutes = ((int) $jamMulai->format('H')) * 60 + ((int) $jamMulai->format('i'));
                    $endMinutes = ((int) $jamSelesai->format('H')) * 60 + ((int) $jamSelesai->format('i'));

                    $isActiveNow = $currentMinutes >= $startMinutes && $currentMinutes <= $endMinutes;
                    $isUpcoming = $currentMinutes < $startMinutes;
                }

                return (object) [
                    'id' => $row['id'] ?? null,
                    'jam_mulai' => $row['jam_mulai'] ?? null,
                    'jam_selesai' => $row['jam_selesai'] ?? null,
                    'rombel' => $rombel ?? '-',
                    'mapel' => $mapel ?? '-',
                    'guru' => $guru ?? '-',
                    'hari' => $row['hari'] ?? null,
                    'is_active_now' => $isActiveNow,
                    'is_upcoming' => $isUpcoming,
                ];
            })
            ->sortBy(function ($item) {
                return (string) ($item->jam_mulai ?? '99:99:99');
            })
            ->take(50)
            ->values();

        return view('admin.dashboard', [
            'tahunAjaranAktif' => $tahunAjaranAktif,

            'totalGuru' => $totalGuru,
            'totalSiswa' => $totalSiswa,
            'totalKelas' => $totalKelas,

            'genderL' => $genderL,
            'genderP' => $genderP,
            'genderCounts' => [
                'L' => $genderL,
                'P' => $genderP,
            ],

            'presensiTotalToday' => $presensiTotal,
            'presensiHadirToday' => $presensiHadir,
            'presensiPercent' => $presensiPersen,

            'trendLabelsMonthly' => $trendLabelsMonthly,
            'trendSeriesMonthly' => $trendSeriesMonthly,

            'rekapNilaiKelas' => $rekapNilaiKelas,
            'rekapNilaiTingkat' => $rekapNilaiTingkat,
            'rataNilaiGlobal' => $rataNilaiGlobal,
            'topSiswa' => $topSiswa,

            'pengumumanAktif' => $pengumumanAktif,

            'hariTabs' => $hariTabs,
            'hariAktif' => $hariAktif,
            'hariSekarang' => $hariSekarang,
            'jadwalHariIni' => $jadwalHariIni,
        ]);
    }

    private function resolveActiveTahunAjaran(SiaClient $sia, array $summary = []): array
    {
        $ta = $this->toArray(
            $summary['tahun_ajaran_aktif']
            ?? $summary['tahun_ajaran']
            ?? []
        );

        if (empty($ta)) {
            try {
                $resp = $sia->masterTahunAjaranAktif();
                $ta = $this->toArray($resp['data'] ?? []);
            } catch (\Throwable $e) {
                report($e);
                $ta = [];
            }
        }

        $id = $ta['id']
            ?? $ta['tahun_ajaran_id']
            ?? null;

        $nama = trim((string) (
            $ta['nama_tahun']
            ?? $ta['nama']
            ?? $ta['label']
            ?? ''
        ));

        $semester = trim((string) ($ta['semester'] ?? ''));

        $label = $nama !== ''
            ? $nama . ($semester !== '' ? ' (' . $semester . ')' : '')
            : '—';

        return [
            'id' => is_numeric($id) ? (int) $id : null,
            'nama' => $nama !== '' ? $nama : null,
            'semester' => $semester !== '' ? $semester : null,
            'label' => $label,
        ];
    }

    private function buildTahunAjaranParams(?int $tahunAjaranId, ?string $semester = null): array
    {
        $params = [];

        if ($tahunAjaranId) {
            $params['tahun_ajaran_id'] = $tahunAjaranId;
            $params['ta_id'] = $tahunAjaranId;
        }

        if ($semester) {
            $params['semester'] = $semester;
        }

        return $params;
    }

    private function fetchRombelAktifByTahunAjaran(
        SiaClient $sia,
        ?int $tahunAjaranId,
        ?string $tahunAjaranLabel
    ): Collection {
        $rows = collect();

        try {
            $res = $sia->masterRombel(null, [
                'aktif' => 1,
                'tahun_ajaran_id' => $tahunAjaranId,
            ]);

            $rows = $rows->merge($this->toList($res['data'] ?? []));
        } catch (\Throwable $e) {
            report($e);
        }

        if ($rows->isEmpty()) {
            try {
                $res = $sia->masterRombel(null, [
                    'aktif' => 1,
                ]);

                $rows = $rows->merge($this->toList($res['data'] ?? []));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $rows
            ->filter(fn($row) => $this->matchesTahunAjaran($row, $tahunAjaranId, $tahunAjaranLabel))
            ->map(function ($row) {
                $row = $this->toArray($row);

                return [
                    'id' => $row['id'] ?? $row['rombel_id'] ?? null,
                    'nama_rombel' => $row['nama_rombel']
                        ?? $row['nama']
                        ?? $row['label']
                        ?? '-',
                    'tingkat' => $row['tingkat'] ?? null,
                    'aktif' => $row['aktif'] ?? null,
                    'tahun_ajaran_id' => $this->extractTahunAjaranId($row),
                    'tahun_ajaran' => $this->extractTahunAjaranLabel($row),
                ];
            })
            ->filter(fn($row) => !empty($row['id']))
            ->unique(fn($row) => (string) ($row['id'] ?? ''))
            ->values();
    }

    private function fetchSiswaFromRombelAktif(
        SiaClient $sia,
        Collection $rombelAktif,
        ?int $tahunAjaranId,
        ?string $tahunAjaranLabel
    ): Collection {
        $rows = collect();

        foreach ($rombelAktif as $rombel) {
            $rombelId = $rombel['id'] ?? null;

            if (!$rombelId) {
                continue;
            }

            try {
                $res = $sia->masterRombelAnggota($rombelId);
                $anggota = collect($this->toList($res['data'] ?? []));

                $anggota = $anggota->map(function ($row) use ($rombel) {
                    $row = $this->toArray($row);

                    $row['_rombel_id'] = $rombel['id'] ?? null;
                    $row['_rombel_nama'] = $rombel['nama_rombel'] ?? null;
                    $row['_tahun_ajaran_id'] = $rombel['tahun_ajaran_id'] ?? null;
                    $row['_tahun_ajaran'] = $rombel['tahun_ajaran'] ?? null;

                    return $row;
                });

                $rows = $rows->merge($anggota);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $rows
            ->filter(fn($row) => $this->matchesTahunAjaran($row, $tahunAjaranId, $tahunAjaranLabel))
            ->map(function ($row) {
                $row = $this->toArray($row);

                $siswa = $this->toArray($row['siswa'] ?? []);

                $id = $row['id']
                    ?? $row['siswa_id']
                    ?? $siswa['id']
                    ?? null;

                $nis = $row['nis']
                    ?? $siswa['nis']
                    ?? null;

                $nama = $row['nama']
                    ?? $row['siswa_nama']
                    ?? $siswa['nama']
                    ?? '-';

                $jk = $this->normalizeJenisKelamin(
                    $row['jk']
                    ?? $row['jenis_kelamin']
                    ?? $siswa['jk']
                    ?? $siswa['jenis_kelamin']
                    ?? null
                );

                return [
                    'id' => $id,
                    'nis' => $nis,
                    'nama' => $nama,
                    'jk' => $jk,
                ];
            })
            ->filter(function ($row) {
                return !empty($row['id']) || !empty($row['nis']) || !empty($row['nama']);
            })
            ->unique(function ($row) {
                if (!empty($row['id'])) {
                    return 'id:' . (string) $row['id'];
                }

                if (!empty($row['nis'])) {
                    return 'nis:' . (string) $row['nis'];
                }

                return 'nama:' . mb_strtolower((string) $row['nama']);
            })
            ->values();
    }

    private function callSia(SiaClient $sia, string $method, array $params = []): array
    {
        try {
            if (!method_exists($sia, $method)) {
                return [];
            }

            if (!empty($params)) {
                try {
                    $response = $sia->{$method}($params);
                } catch (\ArgumentCountError $e) {
                    $response = $sia->{$method}();
                }
            } else {
                $response = $sia->{$method}();
            }

            return is_array($response) ? $response : [];
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    private function matchesTahunAjaran($row, ?int $tahunAjaranId, ?string $tahunAjaranLabel): bool
    {
        if (!$tahunAjaranId && (!$tahunAjaranLabel || $tahunAjaranLabel === '—')) {
            return true;
        }

        $row = $this->toArray($row);

        $rowTaId = $this->extractTahunAjaranId($row);

        if ($tahunAjaranId && $rowTaId) {
            return (int) $rowTaId === (int) $tahunAjaranId;
        }

        $rowTaLabel = $this->extractTahunAjaranLabel($row);

        if ($tahunAjaranLabel && $tahunAjaranLabel !== '—' && $rowTaLabel) {
            $active = $this->normalizeText($tahunAjaranLabel);
            $rowLabel = $this->normalizeText($rowTaLabel);

            return $rowLabel === $active
                || str_contains($rowLabel, $active)
                || str_contains($active, $rowLabel);
        }

        /*
        |--------------------------------------------------------------------------
        | Jika data dari endpoint tidak membawa field tahun ajaran, jangan dibuang.
        |--------------------------------------------------------------------------
        | Beberapa endpoint summary SIA biasanya sudah mengembalikan data aktif.
        */
        return true;
    }

    private function extractTahunAjaranId($row): ?int
    {
        $row = $this->toArray($row);

        $candidates = [
            $row['tahun_ajaran_id'] ?? null,
            $row['ta_id'] ?? null,
            $row['_tahun_ajaran_id'] ?? null,
            data_get($row, 'tahun_ajaran.id'),
            data_get($row, 'tahun_ajaran_aktif.id'),
            data_get($row, 'rombel.tahun_ajaran_id'),
            data_get($row, 'rombel.tahun_ajaran.id'),
            data_get($row, 'siswa.rombel_aktif.tahun_ajaran_id'),
            data_get($row, 'siswa.rombel_aktif.tahun_ajaran.id'),
        ];

        foreach ($candidates as $value) {
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function extractTahunAjaranLabel($row): ?string
    {
        $row = $this->toArray($row);

        $direct = [
            $row['tahun_ajaran'] ?? null,
            $row['tahunAjaran'] ?? null,
            $row['tahun_ajaran_label'] ?? null,
            $row['nama_tahun'] ?? null,
            $row['tahun_ajaran_nama'] ?? null,
            $row['_tahun_ajaran'] ?? null,
        ];

        foreach ($direct as $value) {
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        $objects = [
            $row['tahun_ajaran'] ?? null,
            $row['tahun_ajaran_aktif'] ?? null,
            data_get($row, 'rombel.tahun_ajaran'),
            data_get($row, 'siswa.rombel_aktif.tahun_ajaran'),
        ];

        foreach ($objects as $value) {
            $arr = $this->toArray($value);

            if (empty($arr)) {
                continue;
            }

            $nama = trim((string) (
                $arr['nama_tahun']
                ?? $arr['nama']
                ?? $arr['label']
                ?? ''
            ));

            $semester = trim((string) ($arr['semester'] ?? ''));

            if ($nama !== '') {
                return $nama . ($semester !== '' ? ' (' . $semester . ')' : '');
            }
        }

        return null;
    }

    private function extractRombelId($row): ?int
    {
        $row = $this->toArray($row);

        $candidates = [
            $row['rombel_id'] ?? null,
            data_get($row, 'rombel.id'),
            data_get($row, 'kelas.id'),
        ];

        foreach ($candidates as $value) {
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
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

    private function parseJam(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('H:i:s', $value, 'Asia/Jakarta');
        } catch (\Throwable $e) {
            try {
                return Carbon::createFromFormat('H:i', substr($value, 0, 5), 'Asia/Jakarta');
            } catch (\Throwable $e) {
                return null;
            }
        }
    }

    private function normalizeText(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/\s+/', ' ', $value);

        return $value ?? '';
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

    private function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function extractDisplayValue($value, array $preferredKeys = []): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_scalar($value)) {
            $text = trim((string) $value);
            return $text !== '' ? $text : null;
        }

        $arr = $this->toArray($value);

        foreach ($preferredKeys as $key) {
            if (isset($arr[$key]) && is_scalar($arr[$key])) {
                $text = trim((string) $arr[$key]);
                if ($text !== '') {
                    return $text;
                }
            }
        }

        foreach ($arr as $item) {
            if (is_scalar($item)) {
                $text = trim((string) $item);
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }
}