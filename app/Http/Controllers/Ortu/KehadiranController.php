<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Services\SiaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class KehadiranController extends Controller
{
    public function index(Request $request, SiaClient $sia)
    {
        Carbon::setLocale('id');

        $user = $request->user();
        $nis = trim((string) ($user?->sia_user_id ?? ''));

        $presensiList = [];
        $weekdayColumns = [];
        $tableRows = [];

        $rekap = [
            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpa' => 0,
            'total' => 0,
            'persen' => null,
        ];

        $infoTahunAjaran = null;
        $infoSemester = null;
        $activeTahunAjaranId = null;

        $rombelId = null;
        $rombelName = null;
        $waliKelasName = null;

        $now = Carbon::now('Asia/Jakarta');

        /*
        |--------------------------------------------------------------------------
        | 1. Periode bulan
        |--------------------------------------------------------------------------
        */
        $bulanParam = trim((string) $request->get('bulan', ''));

        if ($bulanParam !== '') {
            try {
                $periode = Carbon::createFromFormat('Y-m', $bulanParam, 'Asia/Jakarta')->startOfMonth();
            } catch (\Throwable $e) {
                $periode = $now->copy()->startOfMonth();
            }
        } else {
            $periode = $now->copy()->startOfMonth();
        }

        $bulanKey = $periode->format('Y-m');
        $bulanLabel = $periode->translatedFormat('F Y');

        /*
        |--------------------------------------------------------------------------
        | 2. Tahun ajaran aktif dari API SIA
        |--------------------------------------------------------------------------
        */
        $activePeriod = $this->resolveActiveAcademicPeriod($sia);

        $activeTahunAjaranId = $activePeriod['id'] ?? null;
        $infoTahunAjaran = $activePeriod['nama_tahun'] ?? null;
        $infoSemester = $activePeriod['semester'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | 3. Jika NIS kosong
        |--------------------------------------------------------------------------
        */
        if ($nis === '') {
            return view('ortu.kehadiran.index', [
                'presensiList' => [],
                'rekap' => $rekap,
                'bulanKey' => $bulanKey,
                'bulanLabel' => $bulanLabel,
                'todayDate' => $now->translatedFormat('d F Y'),
                'weekdayColumns' => [],
                'tableRows' => [],
                'rombelName' => null,
                'waliKelasName' => null,
                'infoTahunAjaran' => $infoTahunAjaran,
                'infoSemester' => $infoSemester,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Detail siswa dari API SIA
        |--------------------------------------------------------------------------
        */
        $siswaApi = $this->resolveSiswaDetailByNis($nis, $sia);

        /*
        |--------------------------------------------------------------------------
        | 5. Ambil data siswa dari endpoint nilai sebagai penguat rombel
        |--------------------------------------------------------------------------
        | Alur ini disamakan dengan NilaiController/JadwalController karena data
        | kelas dan wali kelas sudah terbaca di halaman nilai. Data nilai tidak
        | dipakai untuk isi presensi, hanya untuk melengkapi node siswa/rombel.
        */
        $nilaiResp = $this->fetchNilaiByNis($sia, $nis, [
            'tahun_ajaran_id' => $activeTahunAjaranId,
            'tahun_ajaran' => $infoTahunAjaran,
            'semester' => $infoSemester,
        ]);

        $dataSiswaNilai = data_get($nilaiResp, 'data.siswa', []);
        $dataSiswa = $this->mergeSiswaData($siswaApi, is_array($dataSiswaNilai) ? $dataSiswaNilai : []);

        /*
        |--------------------------------------------------------------------------
        | 6. Ambil info rombel aktif mengikuti alur NilaiController
        |--------------------------------------------------------------------------
        | Kelas diambil dari rombel aktif siswa. Wali kelas dilengkapi melalui
        | masterRombelDetail berdasarkan relasi wali_kelas/guru pada rombel.
        */
        if (!empty($dataSiswa)) {
            [$rombelId, $rombelName, $waliKelasName] = $this->resolveRombelAktifInfo($dataSiswa, $sia);
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Ambil presensi dari API SIA
        |--------------------------------------------------------------------------
        | Filter tetap dikirim ke API SIA. Setelah data diterima, SINTA tetap
        | melakukan penyaringan ulang bulan, hari kerja, tahun ajaran, dan rombel.
        */
        try {
            $resp = $sia->getPresensiByNis($nis, [
                'bulan' => $bulanKey,
                'tahun_ajaran_id' => $activeTahunAjaranId,
                'tahun_ajaran' => $infoTahunAjaran,
                'rombel_id' => $rombelId,
                'rombel' => $rombelId,
            ]);

            $dataNode = $resp['data'] ?? [];

            $listSource = data_get($dataNode, 'detail')
                ?? data_get($dataNode, 'list')
                ?? data_get($dataNode, 'presensi')
                ?? (is_array($dataNode) ? $dataNode : []);

            if (is_array($listSource)) {
                $rawRows = collect($listSource)
                    ->filter(fn($row) => is_array($row) || $row instanceof \Illuminate\Contracts\Support\Arrayable)
                    ->map(function ($row) {
                        return $row instanceof \Illuminate\Contracts\Support\Arrayable
                            ? $row->toArray()
                            : (array) $row;
                    })
                    ->values();

                $hasAcademicContext = $rawRows->contains(function ($row) {
                    return $this->hasAcademicContext($row);
                });

                $presensiList = $rawRows
                    ->map(function ($row) {
                        $tanggalRaw = $this->extractTanggalRaw($row);
                        $tanggalObj = $this->parseTanggal($tanggalRaw);

                        $status = $this->normalizeStatus($row['status'] ?? null);

                        $mapelRaw = $row['mapel']
                            ?? $row['nama_mapel']
                            ?? data_get($row, 'mata_pelajaran')
                            ?? null;

                        $mapel = '-';

                        if (is_array($mapelRaw)) {
                            $mapel = $mapelRaw['nama_mapel']
                                ?? $mapelRaw['nama']
                                ?? '-';
                        } elseif (is_string($mapelRaw) && trim($mapelRaw) !== '') {
                            $mapel = trim($mapelRaw);
                        }

                        return [
                            'tanggal_raw' => $tanggalRaw,
                            'tanggal_obj' => $tanggalObj,
                            'tanggal' => $tanggalObj ? $tanggalObj->translatedFormat('d/m/Y H:i') : '-',
                            'tanggal_key' => $tanggalObj ? $tanggalObj->format('Y-m-d') : null,
                            'mapel' => $mapel,
                            'status' => $status,
                            'keterangan' => $row['keterangan'] ?? ($row['catatan'] ?? null),

                            'tahun_ajaran_id' => data_get($row, 'tahun_ajaran_id')
                                ?? data_get($row, 'tahun_ajaran.id')
                                ?? data_get($row, 'ta.id'),

                            'tahun_ajaran' => $this->pickString(
                                data_get($row, 'tahun_ajaran.nama_tahun'),
                                data_get($row, 'tahun_ajaran.nama'),
                                data_get($row, 'tahun_ajaran'),
                                data_get($row, 'nama_tahun'),
                                data_get($row, 'ta.nama_tahun'),
                                data_get($row, 'ta.nama')
                            ),

                            'rombel_id' => data_get($row, 'rombel_id')
                                ?? data_get($row, 'rombel.id')
                                ?? data_get($row, 'rombel.rombel_id'),
                        ];
                    })
                    ->filter(function ($row) use ($bulanKey) {
                        return $row['tanggal_obj']
                            && $row['tanggal_obj']->format('Y-m') === $bulanKey;
                    })
                    ->filter(function ($row) {
                        /*
                        |--------------------------------------------------------------------------
                        | Kehadiran hanya Senin sampai Jumat
                        |--------------------------------------------------------------------------
                        */
                        return $row['tanggal_obj']
                            && $row['tanggal_obj']->dayOfWeekIso <= 5;
                    })
                    ->filter(function ($row) use ($hasAcademicContext, $activeTahunAjaranId, $infoTahunAjaran, $rombelId) {
                        /*
                        |--------------------------------------------------------------------------
                        | Validasi ulang konteks akademik
                        |--------------------------------------------------------------------------
                        | Jika API mengirim konteks tahun ajaran/rombel, wajib cocok.
                        | Jika API belum mengirim konteks, data tidak digugurkan agar halaman
                        | tidak kosong total, karena filter sudah dikirim pada request API.
                        */
                        if (!$hasAcademicContext) {
                            return true;
                        }

                        return $this->rowMatchesAcademicContext(
                            $row,
                            $activeTahunAjaranId,
                            $infoTahunAjaran,
                            $rombelId
                        );
                    })
                    ->sortBy(function ($row) {
                        return $row['tanggal_obj']
                            ? $row['tanggal_obj']->timestamp
                            : 0;
                    })
                    ->values()
                    ->all();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Rekap kehadiran
        |--------------------------------------------------------------------------
        */
        foreach ($presensiList as $row) {
            $st = $row['status'] ?? '';

            if ($st === 'hadir') {
                $rekap['hadir']++;
            } elseif ($st === 'izin') {
                $rekap['izin']++;
            } elseif ($st === 'sakit') {
                $rekap['sakit']++;
            } elseif ($st === 'alpa') {
                $rekap['alpa']++;
            }
        }

        $rekap['total'] = $rekap['hadir'] + $rekap['izin'] + $rekap['sakit'] + $rekap['alpa'];
        $rekap['persen'] = $rekap['total'] > 0
            ? round(($rekap['hadir'] / $rekap['total']) * 100)
            : null;

        /*
        |--------------------------------------------------------------------------
        | 9. Kolom hari kerja dalam bulan
        |--------------------------------------------------------------------------
        */
        $weekdayColumns = $this->buildWeekdayColumns($periode);

        /*
        |--------------------------------------------------------------------------
        | 10. Tabel kehadiran per mata pelajaran
        |--------------------------------------------------------------------------
        */
        $mapelNames = collect($presensiList)
            ->pluck('mapel')
            ->filter(fn($v) => is_string($v) && trim($v) !== '' && trim($v) !== '-')
            ->unique()
            ->sort()
            ->values();

        $tableRows = $mapelNames->map(function ($mapel, $index) use ($weekdayColumns, $presensiList) {
            $cells = [];

            $totals = [
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpa' => 0,
            ];

            foreach ($weekdayColumns as $col) {
                $match = collect($presensiList)
                    ->first(function ($item) use ($mapel, $col) {
                        return ($item['mapel'] ?? null) === $mapel
                            && ($item['tanggal_key'] ?? null) === $col['date_key'];
                    });

                $status = $match['status'] ?? null;

                $kode = match ($status) {
                    'hadir' => 'H',
                    'izin' => 'I',
                    'sakit' => 'S',
                    'alpa' => 'A',
                    default => '',
                };

                if ($status && array_key_exists($status, $totals)) {
                    $totals[$status]++;
                }

                $cells[$col['date_key']] = [
                    'status' => $status,
                    'kode' => $kode,
                    'tooltip' => $match
                        ? ($col['tooltip'] . ' • ' . strtoupper($kode) . ' • ' . ($match['tanggal'] ?? '-'))
                        : ($col['tooltip'] . ' • Tidak ada presensi'),
                ];
            }

            return [
                'no' => $index + 1,
                'mapel' => $mapel,
                'cells' => $cells,
                'totals' => $totals,
            ];
        })->all();

        return view('ortu.kehadiran.index', [
            'presensiList' => $presensiList,
            'rekap' => $rekap,
            'bulanKey' => $bulanKey,
            'bulanLabel' => $bulanLabel,
            'todayDate' => $now->translatedFormat('d F Y'),
            'weekdayColumns' => $weekdayColumns,
            'tableRows' => $tableRows,
            'rombelName' => $rombelName,
            'waliKelasName' => $waliKelasName,
            'infoTahunAjaran' => $infoTahunAjaran,
            'infoSemester' => $infoSemester,
        ]);
    }

    private function resolveActiveAcademicPeriod(SiaClient $sia): array
    {
        try {
            if (method_exists($sia, 'masterTahunAjaranAktif')) {
                $resp = $sia->masterTahunAjaranAktif();
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
            if (method_exists($sia, 'dashboardSummary')) {
                $resp = $sia->dashboardSummary();
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

    private function resolveSiswaDetailByNis(string $nis, SiaClient $sia): ?array
    {
        try {
            $resp = $sia->getSiswaByNis($nis);

            if (
                !is_array($resp) ||
                !(($resp['success'] ?? false) === true ||
                    ($resp['status'] ?? false) === true ||
                    ($resp['status'] ?? null) === 'success') ||
                empty($resp['data']) ||
                !is_array($resp['data'])
            ) {
                return null;
            }

            $basic = $resp['data'];
            $siswaId = $basic['id'] ?? null;

            if (!$siswaId) {
                return $basic;
            }

            try {
                $detail = $sia->masterSiswaDetail($siswaId);

                if (
                    is_array($detail) &&
                    (($detail['success'] ?? false) === true ||
                        ($detail['status'] ?? false) === true ||
                        ($detail['status'] ?? null) === 'success') &&
                    !empty($detail['data']) &&
                    is_array($detail['data'])
                ) {
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

    private function mergeSiswaData(?array $siswaApi, array $dataSiswaNilai): array
    {
        $siswaApi = is_array($siswaApi) ? $siswaApi : [];

        /*
        |--------------------------------------------------------------------------
        | Urutan merge mengikuti NilaiController
        |--------------------------------------------------------------------------
        | Data dari endpoint siswa diprioritaskan. Data siswa dari nilai menjadi
        | pelengkap jika struktur rombel/wali kelas lebih lengkap di endpoint nilai.
        */
        return array_replace_recursive($dataSiswaNilai, $siswaApi);
    }

    private function resolveRombelAktifInfo(array $dataSiswa, SiaClient $sia): array
    {
        $rombelId = data_get($dataSiswa, 'rombel_aktif.id')
            ?? data_get($dataSiswa, 'rombel_aktif.rombel_id')
            ?? data_get($dataSiswa, 'rombel.id')
            ?? data_get($dataSiswa, 'rombel.rombel_id')
            ?? data_get($dataSiswa, 'rombel_id');

        $rombelName = data_get($dataSiswa, 'rombel_aktif.nama_rombel')
            ?? data_get($dataSiswa, 'rombel_aktif.nama')
            ?? data_get($dataSiswa, 'rombel.nama_rombel')
            ?? data_get($dataSiswa, 'rombel.nama')
            ?? data_get($dataSiswa, 'rombel_nama')
            ?? data_get($dataSiswa, 'nama_rombel');

        $waliKelasName = data_get($dataSiswa, 'rombel_aktif.wali_kelas.nama')
            ?? data_get($dataSiswa, 'rombel_aktif.wali_kelas')
            ?? data_get($dataSiswa, 'rombel_aktif.guru.nama')
            ?? data_get($dataSiswa, 'rombel_aktif.guru')
            ?? data_get($dataSiswa, 'rombel.wali_kelas.nama')
            ?? data_get($dataSiswa, 'rombel.wali_kelas')
            ?? data_get($dataSiswa, 'rombel.guru.nama')
            ?? data_get($dataSiswa, 'rombel.guru')
            ?? data_get($dataSiswa, 'wali_kelas.nama')
            ?? data_get($dataSiswa, 'wali_kelas')
            ?? data_get($dataSiswa, 'guru.nama')
            ?? data_get($dataSiswa, 'guru')
            ?? data_get($dataSiswa, 'nama_wali_kelas')
            ?? data_get($dataSiswa, 'nama_guru');

        if ($rombelId) {
            try {
                $rombelDetailResp = $sia->masterRombelDetail($rombelId);
                $rombelData = is_array($rombelDetailResp['data'] ?? null) ? $rombelDetailResp['data'] : [];

                $rombelName = $rombelName
                    ?: ($rombelData['nama_rombel'] ?? null)
                    ?: ($rombelData['nama'] ?? null)
                    ?: data_get($rombelData, 'rombel.nama_rombel')
                    ?: data_get($rombelData, 'rombel.nama');

                /*
                |--------------------------------------------------------------------------
                | Wali kelas dari detail rombel
                |--------------------------------------------------------------------------
                | Pada API SIA, wali kelas dapat muncul sebagai wali_kelas atau guru
                | karena rombel memiliki guru_id. Semua kemungkinan dibaca aman.
                */
                $waliKelasRaw = $rombelData['wali_kelas'] ?? null;
                $guruRaw = $rombelData['guru'] ?? null;

                if (!$waliKelasName && is_array($waliKelasRaw)) {
                    $waliKelasName = $waliKelasRaw['nama']
                        ?? $waliKelasRaw['name']
                        ?? null;
                } elseif (!$waliKelasName && is_string($waliKelasRaw) && trim($waliKelasRaw) !== '') {
                    $waliKelasName = trim($waliKelasRaw);
                }

                if (!$waliKelasName && is_array($guruRaw)) {
                    $waliKelasName = $guruRaw['nama']
                        ?? $guruRaw['name']
                        ?? null;
                } elseif (!$waliKelasName && is_string($guruRaw) && trim($guruRaw) !== '') {
                    $waliKelasName = trim($guruRaw);
                }

                if (!$waliKelasName) {
                    $waliKelasName = data_get($rombelData, 'wali_kelas.nama')
                        ?? data_get($rombelData, 'waliKelas.nama')
                        ?? data_get($rombelData, 'guru.nama')
                        ?? data_get($rombelData, 'nama_wali_kelas')
                        ?? data_get($rombelData, 'nama_guru');
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return [
            $rombelId,
            $this->pickString($rombelName),
            $this->pickString($waliKelasName),
        ];
    }

    private function fetchNilaiByNis(SiaClient $sia, string $nis, array $filters = []): array
    {
        $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

        try {
            return $sia->getNilaiByNis($nis, $filters);
        } catch (\Throwable $e) {
            report($e);

            return [
                'success' => false,
                'status' => false,
                'data' => [],
            ];
        }
    }

    private function hasAcademicContext(array $row): bool
    {
        return data_get($row, 'tahun_ajaran_id') !== null
            || data_get($row, 'tahun_ajaran.id') !== null
            || data_get($row, 'ta.id') !== null
            || data_get($row, 'tahun_ajaran') !== null
            || data_get($row, 'nama_tahun') !== null
            || data_get($row, 'rombel_id') !== null
            || data_get($row, 'rombel.id') !== null
            || data_get($row, 'rombel.rombel_id') !== null;
    }

    private function rowMatchesAcademicContext(
        array $row,
        $activeTahunAjaranId = null,
        ?string $infoTahunAjaran = null,
        $rombelId = null
    ): bool {
        $rowTahunAjaranId = $row['tahun_ajaran_id'] ?? null;
        $rowTahunAjaran = $this->pickString($row['tahun_ajaran'] ?? null);
        $rowRombelId = $row['rombel_id'] ?? null;

        if ($activeTahunAjaranId !== null && $rowTahunAjaranId !== null) {
            if ((string) $rowTahunAjaranId !== (string) $activeTahunAjaranId) {
                return false;
            }
        }

        if ($infoTahunAjaran !== null && $rowTahunAjaran !== null) {
            if (trim((string) $rowTahunAjaran) !== trim((string) $infoTahunAjaran)) {
                return false;
            }
        }

        if ($rombelId !== null && $rowRombelId !== null) {
            if ((string) $rowRombelId !== (string) $rombelId) {
                return false;
            }
        }

        return true;
    }

    private function buildWeekdayColumns(Carbon $periode): array
    {
        $start = $periode->copy()->startOfMonth();
        $end = $periode->copy()->endOfMonth();

        $columns = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->dayOfWeekIso <= 5) {
                $weekNo = (int) ceil($current->day / 7);

                $columns[] = [
                    'date_key' => $current->format('Y-m-d'),
                    'tanggal' => $current->format('j'),
                    'hari_singkat' => $this->hariSingkat($current->dayOfWeekIso),
                    'tooltip' => $current->translatedFormat('l, d F Y'),
                    'week_no' => $weekNo,
                ];
            }

            $current->addDay();
        }

        return $columns;
    }

    private function hariSingkat(int $dayOfWeekIso): string
    {
        return match ($dayOfWeekIso) {
            1 => 'Sen',
            2 => 'Sel',
            3 => 'Rab',
            4 => 'Kam',
            5 => 'Jum',
            default => '',
        };
    }

    private function extractTanggalRaw(array $row): ?string
    {
        return $row['dipindai_pada']
            ?? $row['tanggal']
            ?? $row['waktu']
            ?? $row['created_at']
            ?? null;
    }

    private function parseTanggal(?string $tanggal): ?Carbon
    {
        if (!$tanggal) {
            return null;
        }

        try {
            return Carbon::parse($tanggal, 'Asia/Jakarta');
        } catch (\Throwable $e) {
            return null;
        }
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
            default => ucfirst(strtolower($value)),
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
}