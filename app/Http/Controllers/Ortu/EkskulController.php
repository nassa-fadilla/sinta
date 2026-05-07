<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Services\SiaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EkskulController extends Controller
{
    public function index(Request $request, SiaClient $sia)
    {
        Carbon::setLocale('id');

        $user = $request->user();
        $nis = trim((string) ($user?->sia_user_id ?? ''));

        $ekskulList = [];
        $infoTahunAjaran = null;
        $infoSemester = null;
        $activeTahunAjaranId = null;

        $rombelName = null;
        $waliKelasName = null;
        $rombelId = null;

        $today = Carbon::now('Asia/Jakarta');

        /*
        |--------------------------------------------------------------------------
        | 1. Tahun ajaran aktif dari API SIA
        |--------------------------------------------------------------------------
        | Tahun ajaran aktif diambil lebih awal agar seluruh data ekskul
        | menggunakan konteks periode akademik yang sedang berjalan.
        */
        $activePeriod = $this->resolveActiveAcademicPeriod($sia);

        $activeTahunAjaranId = $activePeriod['id'] ?? null;
        $infoTahunAjaran = $activePeriod['nama_tahun'] ?? null;
        $infoSemester = $activePeriod['semester'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | 2. Jika NIS kosong
        |--------------------------------------------------------------------------
        */
        if ($nis === '') {
            return view('ortu.ekskul.index', [
                'ekskulList' => [],
                'infoTahunAjaran' => $infoTahunAjaran,
                'infoSemester' => $infoSemester,
                'rombelName' => null,
                'waliKelasName' => null,
                'totalEkskul' => 0,
                'aktifCount' => 0,
                'nonaktifCount' => 0,
                'today' => $today,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Detail siswa dari API SIA
        |--------------------------------------------------------------------------
        */
        $siswaApi = $this->resolveSiswaDetailByNis($nis, $sia);

        /*
        |--------------------------------------------------------------------------
        | 4. Ambil data siswa dari endpoint nilai sebagai penguat rombel
        |--------------------------------------------------------------------------
        | Alur ini disamakan dengan NilaiController/KehadiranController karena
        | data kelas dan wali kelas sudah terbaca dari struktur data nilai.
        | Data nilai tidak dipakai sebagai isi ekskul, hanya untuk melengkapi
        | informasi siswa, rombel, dan wali kelas.
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
        | 5. Ambil info rombel aktif siswa
        |--------------------------------------------------------------------------
        | Kelas diambil dari rombel aktif siswa. Wali kelas dilengkapi melalui
        | masterRombelDetail berdasarkan relasi wali_kelas/guru pada rombel.
        */
        if (!empty($dataSiswa)) {
            [$rombelId, $rombelName, $waliKelasName] = $this->resolveRombelAktifInfo($dataSiswa, $sia);
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Ambil data ekskul dari API SIA
        |--------------------------------------------------------------------------
        | Filter tahun ajaran dan rombel dikirim ke API. Jika API sudah mendukung
        | filter ini, maka data yang kembali langsung sesuai periode aktif.
        */
        try {
            $resp = $sia->getEkskulByNis($nis, [
                'tahun_ajaran_id' => $activeTahunAjaranId,
                'tahun_ajaran' => $infoTahunAjaran,
                'rombel_id' => $rombelId,
                'rombel' => $rombelId,
            ]);
        } catch (\Throwable $e) {
            report($e);

            $resp = [
                'success' => false,
                'status' => false,
                'data' => [],
            ];
        }

        $rawEkskul = $resp['data'] ?? [];

        if (is_array($rawEkskul) && isset($rawEkskul['ekskul']) && is_array($rawEkskul['ekskul'])) {
            $rawEkskul = $rawEkskul['ekskul'];
        } elseif (is_array($rawEkskul) && isset($rawEkskul['items']) && is_array($rawEkskul['items'])) {
            $rawEkskul = $rawEkskul['items'];
        } elseif (is_array($rawEkskul) && isset($rawEkskul['data']) && is_array($rawEkskul['data'])) {
            $rawEkskul = $rawEkskul['data'];
        } elseif (!is_array($rawEkskul) || !array_is_list($rawEkskul)) {
            $rawEkskul = [];
        }

        $rawCollection = collect($rawEkskul)
            ->filter(fn($row) => is_array($row) || $row instanceof \Illuminate\Contracts\Support\Arrayable)
            ->map(function ($row) {
                return $row instanceof \Illuminate\Contracts\Support\Arrayable
                    ? $row->toArray()
                    : (array) $row;
            })
            ->values();

        $hasAcademicContext = $rawCollection->contains(function ($row) {
            return $this->hasAcademicContext($row);
        });

        $hariIndex = [
            'Senin' => 1,
            'Selasa' => 2,
            'Rabu' => 3,
            'Kamis' => 4,
            'Jumat' => 5,
            'Sabtu' => 6,
        ];

        $ekskulList = $rawCollection
            ->map(function ($row) {
                $nama = $this->pickString(
                    $row['nama'] ?? null,
                    $row['nama_ekskul'] ?? null,
                    data_get($row, 'ekskul.nama'),
                    data_get($row, 'ekskul.nama_ekskul'),
                    $row['ekskul'] ?? null,
                    '-'
                );

                $hari = $this->normalizeHariLabel(
                    $row['hari'] ?? $row['day'] ?? null
                );

                $jamMulai = $this->formatJam($row['jam_mulai'] ?? null);
                $jamSelesai = $this->formatJam($row['jam_selesai'] ?? null);

                $jam = $this->pickString($row['jam'] ?? null);

                if (!$jam) {
                    if ($jamMulai && $jamSelesai) {
                        $jam = $jamMulai . ' - ' . $jamSelesai;
                    } elseif ($jamMulai) {
                        $jam = $jamMulai;
                    }
                }

                $pembina = $this->pickString(
                    data_get($row, 'pembina.nama'),
                    $row['pembina'] ?? null,
                    data_get($row, 'guru.nama'),
                    $row['guru'] ?? null,
                    data_get($row, 'pelatih.nama'),
                    $row['pelatih'] ?? null,
                    '-'
                );

                $tempat = $this->pickString(
                    $row['lokasi'] ?? null,
                    $row['tempat'] ?? null,
                    $row['ruang'] ?? null,
                    data_get($row, 'ruang.nama'),
                    'Belum ditentukan'
                );

                $statusRaw = $row['status'] ?? $row['aktif'] ?? $row['is_active'] ?? 'aktif';
                $status = 'aktif';

                if (is_bool($statusRaw)) {
                    $status = $statusRaw ? 'aktif' : 'nonaktif';
                } else {
                    $statusRaw = strtolower(trim((string) $statusRaw));

                    if (in_array($statusRaw, ['nonaktif', 'tidak aktif', '0', 'off', 'inactive'], true)) {
                        $status = 'nonaktif';
                    }
                }

                return [
                    'id' => $row['id'] ?? null,
                    'nama' => $nama,
                    'hari' => $hari,
                    'jam' => $jam,
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'pembina' => $pembina,
                    'tempat' => $tempat,
                    'status' => $status,
                    'catatan' => $this->pickString(
                        $row['keterangan'] ?? null,
                        $row['catatan'] ?? null
                    ),

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
            ->filter(function ($item) {
                return ($item['nama'] ?? '-') !== '-'
                    && !empty($item['hari']);
            })
            ->filter(function ($item) use ($hasAcademicContext, $activeTahunAjaranId, $infoTahunAjaran, $rombelId) {
                /*
                |--------------------------------------------------------------------------
                | Validasi ulang jika API mengirim konteks tahun ajaran/rombel
                |--------------------------------------------------------------------------
                | Jika API belum mengirim konteks tersebut, data tidak langsung
                | digugurkan agar tampilan tidak kosong total. Namun filter tetap
                | sudah dikirim ke API pada request.
                */
                if (!$hasAcademicContext) {
                    return true;
                }

                return $this->rowMatchesAcademicContext(
                    $item,
                    $activeTahunAjaranId,
                    $infoTahunAjaran,
                    $rombelId
                );
            })
            ->sort(function ($a, $b) use ($hariIndex) {
                $hariA = $hariIndex[$a['hari'] ?? ''] ?? 99;
                $hariB = $hariIndex[$b['hari'] ?? ''] ?? 99;

                if ($hariA !== $hariB) {
                    return $hariA <=> $hariB;
                }

                $jamA = (string) ($a['jam_mulai'] ?? '99:99');
                $jamB = (string) ($b['jam_mulai'] ?? '99:99');

                if ($jamA !== $jamB) {
                    return strcmp($jamA, $jamB);
                }

                return strcmp((string) ($a['nama'] ?? ''), (string) ($b['nama'] ?? ''));
            })
            ->values()
            ->all();

        $totalEkskul = count($ekskulList);
        $aktifCount = collect($ekskulList)->where('status', 'aktif')->count();
        $nonaktifCount = collect($ekskulList)->where('status', 'nonaktif')->count();

        return view('ortu.ekskul.index', [
            'ekskulList' => $ekskulList,
            'infoTahunAjaran' => $infoTahunAjaran,
            'infoSemester' => $infoSemester,
            'rombelName' => $rombelName,
            'waliKelasName' => $waliKelasName,
            'totalEkskul' => $totalEkskul,
            'aktifCount' => $aktifCount,
            'nonaktifCount' => $nonaktifCount,
            'today' => $today,
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
                | Pada API SIA, wali kelas bisa dikirim sebagai wali_kelas atau guru
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

    private function formatJam($value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return substr($value, 0, 5);
    }

    private function normalizeHariLabel($value): ?string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat', 'jum\'at', 'jum at', 'jum’at' => 'Jumat',
            'sabtu' => 'Sabtu',
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
            default => ucfirst(strtolower($value)),
        };
    }
}