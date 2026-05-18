<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Services\SiaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class JadwalController extends Controller
{
    public function index(Request $request, SiaClient $sia)
    {
        Carbon::setLocale('id');

        $user = $request->user();

        $jadwalByHari = [];
        $infoTahunAjaran = null;
        $infoSemester = null;
        $activeTahunAjaranId = null;

        $rombelNama = null;
        $waliKelasNama = null;
        $rombelId = null;

        /*
        |--------------------------------------------------------------------------
        | Hari yang ditampilkan sebagai tab jadwal
        |--------------------------------------------------------------------------
        | Jadwal pembelajaran orang tua tetap ditampilkan untuk Senin sampai Jumat.
        | Namun, hari aktif mengikuti hari real-time. Jika hari ini Sabtu/Minggu,
        | sistem tidak memaksa ke Senin, sehingga halaman menampilkan hari sebenarnya
        | dengan kondisi tidak ada jadwal.
        */
        $hariMap = [
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
        ];

        $allHariMap = [
            'senin' => 'Senin',
            'selasa' => 'Selasa',
            'rabu' => 'Rabu',
            'kamis' => 'Kamis',
            'jumat' => 'Jumat',
            'sabtu' => 'Sabtu',
            'minggu' => 'Minggu',
        ];

        $today = Carbon::now('Asia/Jakarta');

        $dowToKey = [
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
            7 => 'minggu',
        ];

        $todayKey = $dowToKey[$today->dayOfWeekIso] ?? 'senin';
        $todayLabel = $allHariMap[$todayKey] ?? ucfirst($todayKey);
        $todayDate = $today->translatedFormat('d F Y');

        /*
        |--------------------------------------------------------------------------
        | Hari aktif dari request
        |--------------------------------------------------------------------------
        | Jika request hari valid Senin-Jumat, tampilkan hari tersebut.
        | Jika tidak ada request, gunakan hari real-time.
        | Jika request tidak valid, kembalikan ke hari real-time, bukan ke Senin.
        */
        $hariRequest = strtolower(trim((string) $request->get('hari', '')));

        if ($hariRequest !== '' && isset($hariMap[$hariRequest])) {
            $hariAktifKey = $hariRequest;
        } elseif ($hariRequest !== '' && isset($allHariMap[$hariRequest])) {
            $hariAktifKey = $hariRequest;
        } else {
            $hariAktifKey = $todayKey;
        }

        $hariAktifLabel = $allHariMap[$hariAktifKey] ?? ucfirst($hariAktifKey);

        /*
        |--------------------------------------------------------------------------
        | 1. Ambil tahun ajaran aktif dari API SIA
        |--------------------------------------------------------------------------
        */
        $activePeriod = $this->resolveActiveAcademicPeriod($sia);

        $activeTahunAjaranId = $activePeriod['id'] ?? null;
        $infoTahunAjaran = $activePeriod['nama_tahun'] ?? null;
        $infoSemester = $activePeriod['semester'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | 2. Identifikasi NIS dari akun orang tua
        |--------------------------------------------------------------------------
        */
        $nis = trim((string) ($user?->sia_user_id ?? ''));

        /*
        |--------------------------------------------------------------------------
        | 3. Ambil detail siswa dari API SIA
        |--------------------------------------------------------------------------
        */
        $siswaApi = null;
        $agamaSiswa = null;

        if ($nis !== '') {
            $siswaApi = $this->resolveSiswaDetailByNis($nis, $sia);
        }

        if (is_array($siswaApi) && !empty($siswaApi)) {
            $agamaSiswa = $this->pickString(
                data_get($siswaApi, 'agama'),
                data_get($siswaApi, 'agama_siswa'),
                data_get($siswaApi, 'biodata.agama'),
                data_get($siswaApi, 'detail.agama'),
                data_get($siswaApi, 'profil.agama')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Ambil info rombel aktif siswa langsung dari data siswa
        |--------------------------------------------------------------------------
        | Data rombel sudah tersedia dari getSiswaByNis() + masterSiswaDetail().
        | Tidak perlu memanggil getNilaiByNis() hanya untuk mendapat rombel.
        */
        $dataSiswa = is_array($siswaApi) ? $siswaApi : [];

        if (!empty($dataSiswa)) {
            [$rombelId, $rombelNama, $waliKelasNama] = $this->resolveRombelAktifInfo($dataSiswa, $sia);
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Ambil jadwal rombel aktif dari API SIA
        |--------------------------------------------------------------------------
        | Jadwal tetap dibaca dari rombel aktif siswa. Setelah data diterima, jadwal
        | hanya dipertahankan untuk Senin-Jumat. Jadi ketika hari aktif adalah Sabtu
        | atau Minggu, tidak ada jadwal yang ditampilkan.
        */
        if ($rombelId) {
            try {
                $resp = $sia->masterRombelJadwal($rombelId);
                $jadwalRaw = is_array($resp['data'] ?? null) ? $resp['data'] : [];

                $agamaSiswaNormalized = $this->normalizeAgama($agamaSiswa);

                $normalized = collect($jadwalRaw)
                    ->map(function ($row) use ($agamaSiswaNormalized) {
                        $row = is_array($row) ? $row : [];

                        $hariLabel = $this->pickString($row['hari'] ?? null, '-');
                        $hariKey = $this->normalizeHariKey($hariLabel);

                        $mapelNameRaw = data_get($row, 'mapel.nama_mapel')
                            ?? data_get($row, 'mata_pelajaran.nama_mapel')
                            ?? data_get($row, 'mapel.nama')
                            ?? data_get($row, 'mata_pelajaran.nama')
                            ?? (is_array($row['mapel'] ?? null) ? null : ($row['mapel'] ?? null))
                            ?? $row['nama_mapel']
                            ?? '-';

                        $mapelName = $this->pickString($mapelNameRaw, '-');

                        $guruNameRaw = data_get($row, 'guru.nama')
                            ?? data_get($row, 'pengajar.nama')
                            ?? (is_array($row['guru'] ?? null) ? null : ($row['guru'] ?? null))
                            ?? $row['nama_guru']
                            ?? '-';

                        $guruName = $this->pickString($guruNameRaw, '-');

                        $mapelAgama = $this->getAgamaFromMapel($mapelName);

                        if ($mapelAgama !== null) {
                            if (!$agamaSiswaNormalized || $mapelAgama !== $agamaSiswaNormalized) {
                                return null;
                            }
                        }

                        return [
                            'id' => $row['id'] ?? null,
                            'hari_key' => $hariKey,
                            'hari_label' => $hariLabel,
                            'jam_mulai' => isset($row['jam_mulai']) ? substr((string) $row['jam_mulai'], 0, 5) : null,
                            'jam_selesai' => isset($row['jam_selesai']) ? substr((string) $row['jam_selesai'], 0, 5) : null,
                            'mapel' => $mapelName,
                            'guru' => $guruName,
                        ];
                    })
                    ->filter()
                    ->values();

                $jadwalByHari = $normalized
                    ->filter(fn($row) => isset($hariMap[$row['hari_key'] ?? '']))
                    ->groupBy('hari_key')
                    ->map(function ($items) {
                        return $items
                            ->sortBy(fn($row) => $row['jam_mulai'] ?? '99:99')
                            ->values()
                            ->all();
                    })
                    ->toArray();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('ortu.jadwal.index', [
            'hariMap' => $hariMap,
            'hariAktifKey' => $hariAktifKey,
            'hariAktifLabel' => $hariAktifLabel,
            'jadwalByHari' => $jadwalByHari,
            'infoTahunAjaran' => $infoTahunAjaran,
            'infoSemester' => $infoSemester,
            'todayKey' => $todayKey,
            'todayLabel' => $todayLabel,
            'todayDate' => $todayDate,
            'rombelNama' => $rombelNama,
            'waliKelasNama' => $waliKelasNama,
        ]);
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
            $id = $basic['id'] ?? null;

            if (!$id) {
                return $basic;
            }

            try {
                $detail = $sia->masterSiswaDetail($id);

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

    private function normalizeHariKey(?string $hari): string
    {
        $hari = strtolower(trim((string) $hari));
        $hari = str_replace([' ', '-', '_'], '', $hari);

        return match ($hari) {
            'senin', 'mon', 'monday' => 'senin',
            'selasa', 'selasae', 'tuesday', 'tue' => 'selasa',
            'rabu', 'wednesday', 'wed' => 'rabu',
            'kamis', 'thursday', 'thu' => 'kamis',
            'jumat', 'jum\'at', 'jumat', 'friday', 'fri' => 'jumat',
            'sabtu', 'saturday', 'sat' => 'sabtu',
            'minggu', 'ahad', 'sunday', 'sun' => 'minggu',
            default => $hari,
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