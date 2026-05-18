<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Services\SiaClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NilaiController extends Controller
{
    public function index(Request $request, SiaClient $sia)
    {
        Carbon::setLocale('id');

        $user = $request->user();
        $nis = trim((string) ($user->sia_user_id ?? ''));
        $today = Carbon::now('Asia/Jakarta');

        if ($nis === '') {
            return view('ortu.nilai.index', [
                'nilaiList' => [],
                'infoTahunAjaran' => null,
                'infoSemester' => null,
                'rombelName' => null,
                'waliKelasName' => null,
                'globalAverage' => null,
                'totalMapel' => 0,
                'jumlahTuntas' => 0,
                'jumlahBelum' => 0,
                'trendChartLabels' => [],
                'trendChartDatasets' => [],
                'semesterList' => $this->defaultSemesterList(),
                'semesterAktif' => null,
                'tahunAjaranList' => [],
                'tahunAjaranAktif' => null,
                'today' => $today,
                'canExportPdf' => false,
                'exportDisabledReason' => 'Data siswa tidak ditemukan.',
                'siswaPdf' => [
                    'nama' => $user->name ?? '-',
                    'nis' => $nis ?: '-',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Ambil tahun ajaran aktif dari API SIA
        |--------------------------------------------------------------------------
        | Tahun ajaran aktif hanya dipakai sebagai default filter dan label periode
        | aktif. Jangan dijadikan fallback tahun ajaran untuk semua baris nilai.
        */
        $activePeriod = $this->resolveActiveAcademicPeriod($sia);
        $infoTahunAjaran = $activePeriod['nama_tahun'] ?? null;
        $infoSemester = $this->normalizeSemesterLabel($activePeriod['semester'] ?? null);
        $activeTahunAjaranId = $activePeriod['id'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | 2. Ambil detail siswa dari API SIA
        |--------------------------------------------------------------------------
        | Endpoint siswa diprioritaskan untuk rombel aktif karena rombel aktif
        | harus mengikuti tahun ajaran aktif di SIA.
        */
        $siswaApi = $this->resolveSiswaDetailByNis($nis, $sia);

        // Ambil agama siswa untuk filter mapel pendidikan agama
        $agamaSiswa = $this->normalizeAgama($this->pickString(
            data_get($siswaApi, 'agama'),
            data_get($siswaApi, 'agama_siswa'),
            data_get($siswaApi, 'biodata.agama'),
            data_get($siswaApi, 'detail.agama'),
            data_get($siswaApi, 'profil.agama')
        ));

        /*
        |--------------------------------------------------------------------------
        | 3. Ambil opsi tahun ajaran dari API SIA
        |--------------------------------------------------------------------------
        */
        $tahunAjaranMeta = $this->resolveTahunAjaranOptions($sia);

        $tahunAjaranList = collect($tahunAjaranMeta)
            ->pluck('nama')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($infoTahunAjaran && !in_array($infoTahunAjaran, $tahunAjaranList, true)) {
            array_unshift($tahunAjaranList, $infoTahunAjaran);

            $tahunAjaranMeta[] = [
                'id' => $activeTahunAjaranId,
                'nama' => $infoTahunAjaran,
                'semester' => $infoSemester,
                'status' => 'aktif',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Tentukan filter tahun ajaran
        |--------------------------------------------------------------------------
        */
        $tahunAjaranAktif = trim((string) $request->get('tahun_ajaran', ''));

        if ($tahunAjaranAktif === '' || !in_array($tahunAjaranAktif, $tahunAjaranList, true)) {
            $tahunAjaranAktif = $infoTahunAjaran && in_array($infoTahunAjaran, $tahunAjaranList, true)
                ? $infoTahunAjaran
                : ($tahunAjaranList[0] ?? null);
        }

        $selectedTahunAjaranMeta = $this->findTahunAjaranMeta($tahunAjaranMeta, $tahunAjaranAktif);
        $selectedTahunAjaranId = $selectedTahunAjaranMeta['id'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | 5. Semester filter selalu Ganjil dan Genap
        |--------------------------------------------------------------------------
        | Opsi semester tidak boleh bergantung pada data nilai yang sudah ada,
        | karena semester pada sistem hanya dua: Ganjil dan Genap.
        */
        $semesterList = $this->defaultSemesterList();

        $semesterAktif = trim((string) $request->get('semester', ''));

        if ($semesterAktif === '' || !in_array($semesterAktif, $semesterList, true)) {
            $semesterAktif = $infoSemester && in_array($infoSemester, $semesterList, true)
                ? $infoSemester
                : 'Ganjil';
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Ambil nilai siswa dari API SIA
        |--------------------------------------------------------------------------
        | Filter tetap dikirim ke API SIA. Jika API belum memfilter secara penuh,
        | SINTA tetap melakukan filter ulang berdasarkan tahun ajaran dan semester.
        */
        $nilaiResp = $this->fetchNilaiByNis($sia, $nis, [
            'tahun_ajaran_id' => $selectedTahunAjaranId,
            'tahun_ajaran' => $tahunAjaranAktif,
            'semester' => $semesterAktif,
        ]);

        $dataSiswaNilai = data_get($nilaiResp, 'data.siswa', []);
        $dataNilai = data_get($nilaiResp, 'data.nilai', []);

        $dataSiswa = $this->mergeSiswaData($siswaApi, is_array($dataSiswaNilai) ? $dataSiswaNilai : []);

        /*
        |--------------------------------------------------------------------------
        | 7. Rombel aktif siswa
        |--------------------------------------------------------------------------
        | Jangan menimpa rombel aktif dari endpoint siswa dengan rombel dari nilai
        | lama.
        */
        [$rombelId, $rombelName, $waliKelasName] = $this->resolveRombelAktifInfo($dataSiswa, $sia);

        /*
        |--------------------------------------------------------------------------
        | 8. Normalisasi baris nilai
        |--------------------------------------------------------------------------
        | Tidak ada fallback tahun ajaran aktif ke baris nilai. Baris tanpa tahun
        | ajaran tidak ditampilkan agar data lama tidak salah masuk ke tahun aktif.
        */
        $normalized = $this->normalizeNilaiRows(
            is_array($dataNilai) ? $dataNilai : [],
            $sia,
            $agamaSiswa
        );

        /*
       |--------------------------------------------------------------------------
       | 9. Filter tahun ajaran
       |--------------------------------------------------------------------------
       */

        $nilaiFiltered = $normalized;

        if ($tahunAjaranAktif || $selectedTahunAjaranId) {
            $nilaiFiltered = $nilaiFiltered
                ->filter(function ($row) use ($tahunAjaranAktif, $selectedTahunAjaranId) {
                    return $this->rowMatchesTahunAjaran($row, $tahunAjaranAktif, $selectedTahunAjaranId);
                })
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Filter semester
        |--------------------------------------------------------------------------
        | Opsi semester tetap Ganjil/Genap, tetapi data tetap disaring sesuai
        | semester yang dipilih.
        */
        if ($semesterAktif) {
            $nilaiFiltered = $nilaiFiltered
                ->where('semester', $semesterAktif)
                ->values();
        }

        $nilaiList = $nilaiFiltered->all();

        $numericRata = $nilaiFiltered
            ->pluck('nilai_akhir')
            ->filter(fn($v) => $v !== null);

        $globalAverage = $numericRata->count() > 0
            ? round($numericRata->avg(), 2)
            : null;

        $totalMapel = $nilaiFiltered->count();
        $jumlahTuntas = $nilaiFiltered->where('is_tuntas', true)->count();
        $jumlahBelum = $nilaiFiltered->where('is_tuntas', false)->count();

        [$trendChartLabels, $trendChartDatasets] = $this->buildTrendChart($normalized);

        $canExportPdf = $totalMapel > 0;
        $exportDisabledReason = $canExportPdf
            ? null
            : 'Belum ada data nilai untuk filter yang dipilih.';

        return view('ortu.nilai.index', [
            'nilaiList' => $nilaiList,

            /*
            |--------------------------------------------------------------------------
            | Periode aktif SIA
            |--------------------------------------------------------------------------
            | Ini untuk label periode aktif, bukan label filter.
            */
            'infoTahunAjaran' => $infoTahunAjaran,
            'infoSemester' => $infoSemester,

            'rombelName' => $rombelName,
            'waliKelasName' => $waliKelasName,
            'globalAverage' => $globalAverage,
            'totalMapel' => $totalMapel,
            'jumlahTuntas' => $jumlahTuntas,
            'jumlahBelum' => $jumlahBelum,
            'trendChartLabels' => $trendChartLabels,
            'trendChartDatasets' => $trendChartDatasets,

            /*
            |--------------------------------------------------------------------------
            | Filter aktif
            |--------------------------------------------------------------------------
            */
            'semesterList' => $semesterList,
            'semesterAktif' => $semesterAktif,
            'tahunAjaranList' => $tahunAjaranList,
            'tahunAjaranAktif' => $tahunAjaranAktif,

            'today' => $today,
            'canExportPdf' => $canExportPdf,
            'exportDisabledReason' => $exportDisabledReason,
            'siswaPdf' => [
                'nama' => data_get($dataSiswa, 'nama') ?: ($user->name ?? '-'),
                'nis' => data_get($dataSiswa, 'nis') ?: $nis,
            ],
        ]);
    }

    public function exportPdf(Request $request, SiaClient $sia)
    {
        Carbon::setLocale('id');

        $user = $request->user();
        $nis = trim((string) ($user->sia_user_id ?? ''));

        if ($nis === '') {
            return redirect()->route('ortu.nilai.index')
                ->with('info', 'Data siswa tidak ditemukan.');
        }

        $activePeriod = $this->resolveActiveAcademicPeriod($sia);
        $infoTahunAjaran = $activePeriod['nama_tahun'] ?? null;
        $infoSemester = $this->normalizeSemesterLabel($activePeriod['semester'] ?? null);
        $activeTahunAjaranId = $activePeriod['id'] ?? null;

        $tahunAjaranMeta = $this->resolveTahunAjaranOptions($sia);

        $tahunAjaranList = collect($tahunAjaranMeta)
            ->pluck('nama')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($infoTahunAjaran && !in_array($infoTahunAjaran, $tahunAjaranList, true)) {
            array_unshift($tahunAjaranList, $infoTahunAjaran);

            $tahunAjaranMeta[] = [
                'id' => $activeTahunAjaranId,
                'nama' => $infoTahunAjaran,
                'semester' => $infoSemester,
                'status' => 'aktif',
            ];
        }

        $tahunAjaranAktif = trim((string) $request->get('tahun_ajaran', ''));

        if ($tahunAjaranAktif === '' || !in_array($tahunAjaranAktif, $tahunAjaranList, true)) {
            $tahunAjaranAktif = $infoTahunAjaran && in_array($infoTahunAjaran, $tahunAjaranList, true)
                ? $infoTahunAjaran
                : ($tahunAjaranList[0] ?? null);
        }

        $selectedTahunAjaranMeta = $this->findTahunAjaranMeta($tahunAjaranMeta, $tahunAjaranAktif);
        $selectedTahunAjaranId = $selectedTahunAjaranMeta['id'] ?? null;

        $semesterList = $this->defaultSemesterList();

        $semesterAktif = trim((string) $request->get('semester', ''));

        if ($semesterAktif === '' || !in_array($semesterAktif, $semesterList, true)) {
            $semesterAktif = $infoSemester && in_array($infoSemester, $semesterList, true)
                ? $infoSemester
                : 'Ganjil';
        }

        $nilaiResp = $this->fetchNilaiByNis($sia, $nis, [
            'tahun_ajaran_id' => $selectedTahunAjaranId,
            'tahun_ajaran' => $tahunAjaranAktif,
            'semester' => $semesterAktif,
        ]);

        if (!is_array($nilaiResp) || empty($nilaiResp['data']) || !is_array($nilaiResp['data'])) {
            return redirect()->route('ortu.nilai.index')
                ->with('info', 'Gagal mengambil data nilai dari SIA.');
        }

        $siswaApi = $this->resolveSiswaDetailByNis($nis, $sia);
        $dataSiswaNilai = data_get($nilaiResp, 'data.siswa', []);
        $dataNilai = data_get($nilaiResp, 'data.nilai', []);
        $dataSiswa = $this->mergeSiswaData($siswaApi, is_array($dataSiswaNilai) ? $dataSiswaNilai : []);

        // Ambil agama siswa untuk filter mapel pendidikan agama
        $agamaSiswa = $this->normalizeAgama($this->pickString(
            data_get($siswaApi, 'agama'),
            data_get($siswaApi, 'agama_siswa'),
            data_get($siswaApi, 'biodata.agama'),
            data_get($siswaApi, 'detail.agama'),
            data_get($siswaApi, 'profil.agama')
        ));

        [$rombelId, $rombelName, $waliKelasName] = $this->resolveRombelAktifInfo($dataSiswa, $sia);

        $normalized = $this->normalizeNilaiRows(
            is_array($dataNilai) ? $dataNilai : [],
            $sia,
            $agamaSiswa
        );

        $nilaiFiltered = $normalized;

        if ($tahunAjaranAktif || $selectedTahunAjaranId) {
            $nilaiFiltered = $nilaiFiltered
                ->filter(function ($row) use ($tahunAjaranAktif, $selectedTahunAjaranId) {
                    return $this->rowMatchesTahunAjaran($row, $tahunAjaranAktif, $selectedTahunAjaranId);
                })
                ->values();
        }

        if ($semesterAktif) {
            $nilaiFiltered = $nilaiFiltered
                ->where('semester', $semesterAktif)
                ->values();
        }

        if ($nilaiFiltered->isEmpty()) {
            return redirect()->route('ortu.nilai.index', array_filter([
                'tahun_ajaran' => $tahunAjaranAktif,
                'semester' => $semesterAktif,
            ]))->with('info', 'Belum ada data nilai untuk filter yang dipilih.');
        }

        $siswaPdf = [
            'nama' => data_get($dataSiswa, 'nama') ?: ($user->name ?? '-'),
            'nis' => data_get($dataSiswa, 'nis') ?: $nis,
        ];

        $pdf = Pdf::loadView('ortu.nilai.pdf', [
            'siswa' => (object) $siswaPdf,
            'nilaiList' => $nilaiFiltered->all(),
            'rombelName' => $rombelName,
            'waliKelasName' => $waliKelasName,
            'infoTahunAjaran' => $tahunAjaranAktif,
            'semesterAktif' => $semesterAktif,
            'today' => Carbon::now('Asia/Jakarta'),
        ])->setPaper('a4', 'landscape');

        $filename = $this->makePdfFilename(
            $siswaPdf['nis'] ?? 'siswa',
            $tahunAjaranAktif,
            $semesterAktif
        );

        return $pdf->download($filename);
    }

    private function defaultSemesterList(): array
    {
        return ['Ganjil', 'Genap'];
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

    private function mergeSiswaData(?array $siswaApi, array $dataSiswaNilai): array
    {
        $siswaApi = is_array($siswaApi) ? $siswaApi : [];

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
            ?? data_get($dataSiswa, 'rombel.wali_kelas.nama')
            ?? data_get($dataSiswa, 'rombel.wali_kelas');

        if ($rombelId) {
            try {
                $rombelDetailResp = $sia->masterRombelDetail($rombelId);
                $rombelData = is_array($rombelDetailResp['data'] ?? null) ? $rombelDetailResp['data'] : [];

                $rombelName = $rombelName
                    ?: ($rombelData['nama_rombel'] ?? null)
                    ?: ($rombelData['nama'] ?? null);

                $waliKelasRaw = $rombelData['wali_kelas'] ?? null;

                if (!$waliKelasName && is_array($waliKelasRaw)) {
                    $waliKelasName = $waliKelasRaw['nama'] ?? null;
                } elseif (!$waliKelasName && is_string($waliKelasRaw) && trim($waliKelasRaw) !== '') {
                    $waliKelasName = trim($waliKelasRaw);
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

    private function resolveActiveAcademicPeriod(SiaClient $sia): array
    {
        try {
            if (method_exists($sia, 'masterTahunAjaranAktif')) {
                $resp = $sia->masterTahunAjaranAktif();
                $data = is_array($resp['data'] ?? null) ? $resp['data'] : [];

                if (!empty($data)) {
                    return [
                        'id' => $data['id'] ?? null,
                        'nama_tahun' => $this->pickString($data['nama_tahun'] ?? null, $data['nama'] ?? null),
                        'semester' => $this->normalizeSemesterLabel($data['semester'] ?? $data['semester_aktif'] ?? null),
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
                        'nama_tahun' => $this->pickString($ta['nama_tahun'] ?? null, $ta['nama'] ?? null),
                        'semester' => $this->normalizeSemesterLabel($ta['semester'] ?? $ta['semester_aktif'] ?? null),
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

    private function resolveTahunAjaranOptions(SiaClient $sia): array
    {
        try {
            $taResp = $sia->masterTahunAjaran();
            $raw = data_get($taResp, 'data', []);

            if (!is_array($raw)) {
                return [];
            }

            $items = array_is_list($raw)
                ? $raw
                : (is_array(data_get($raw, 'data')) ? data_get($raw, 'data') : []);

            return collect($items)
                ->map(function ($row) {
                    if (!is_array($row)) {
                        return null;
                    }

                    $nama = $this->pickString(
                        $row['nama_tahun'] ?? null,
                        $row['nama'] ?? null
                    );

                    if (!$nama) {
                        return null;
                    }

                    return [
                        'id' => $row['id'] ?? null,
                        'nama' => $nama,
                        'semester' => $this->normalizeSemesterLabel($row['semester'] ?? null),
                        'status' => $row['status'] ?? null,
                    ];
                })
                ->filter()
                ->unique(fn($row) => (string) ($row['id'] ?? '') . '|' . ($row['nama'] ?? ''))
                ->sortByDesc(fn($row) => (int) ($row['id'] ?? 0))
                ->values()
                ->all();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    private function findTahunAjaranMeta(array $tahunAjaranMeta, ?string $nama): ?array
    {
        if (!$nama) {
            return null;
        }

        $nama = trim($nama);

        return collect($tahunAjaranMeta)
            ->first(function ($row) use ($nama) {
                return trim((string) ($row['nama'] ?? '')) === $nama;
            });
    }

    private function rowMatchesTahunAjaran(array $row, ?string $selectedTahunAjaran, $selectedTahunAjaranId): bool
    {
        $rowTahunAjaranId = $row['tahun_ajaran_id'] ?? null;
        $rowTahunAjaran = $this->pickString($row['tahun_ajaran'] ?? null);

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

    private function normalizeNilaiRows(array $rows, SiaClient $sia, ?string $agamaSiswa = null): Collection
    {
        $mapelKkmByName = [];

        return collect($rows)
            ->map(function ($row) use ($sia, &$mapelKkmByName, $agamaSiswa) {
                if ($row instanceof \Illuminate\Contracts\Support\Arrayable) {
                    $row = $row->toArray();
                }

                $row = is_array($row) ? $row : [];

                $mapelName = data_get($row, 'mapel.nama_mapel')
                    ?? data_get($row, 'mata_pelajaran.nama_mapel')
                    ?? data_get($row, 'mapel.nama')
                    ?? data_get($row, 'mata_pelajaran.nama')
                    ?? data_get($row, 'mapel')
                    ?? data_get($row, 'mata_pelajaran')
                    ?? data_get($row, 'nama_mapel')
                    ?? '-';

                if (is_array($mapelName)) {
                    $mapelName = $mapelName['nama_mapel'] ?? $mapelName['nama'] ?? '-';
                }

                $mapelName = trim((string) $mapelName);

                // ── Filter mapel pendidikan agama sesuai agama siswa ──────────
                // Logika identik dengan JadwalController: deteksi apakah mapel
                // adalah mapel agama tertentu, lalu cocokkan dengan agama siswa.
                $mapelAgama = $this->getAgamaFromMapel($mapelName);
                if ($mapelAgama !== null) {
                    // Mapel ini adalah mapel agama — hanya tampilkan jika cocok
                    if (!$agamaSiswa || $mapelAgama !== $agamaSiswa) {
                        return null; // skip: bukan agama siswa ini
                    }
                }
                $mapelNameKey = strtolower($mapelName);

                $kkm = data_get($row, 'mapel.kkm')
                    ?? data_get($row, 'mata_pelajaran.kkm')
                    ?? data_get($row, 'kkm');

                if ($kkm === null && $mapelName !== '' && $mapelName !== '-') {
                    if (!array_key_exists($mapelNameKey, $mapelKkmByName)) {
                        try {
                            $listResp = $sia->masterMapel(['q' => $mapelName]);
                            $items = data_get($listResp, 'data', []);
                            $found = null;

                            foreach ($items as $m) {
                                $nama = strtolower(trim((string) ($m['nama_mapel'] ?? '')));

                                if ($nama === $mapelNameKey) {
                                    $found = $m;
                                    break;
                                }
                            }

                            if (!$found) {
                                foreach ($items as $m) {
                                    $nama = strtolower(trim((string) ($m['nama_mapel'] ?? '')));

                                    if ($nama !== '' && (str_contains($nama, $mapelNameKey) || str_contains($mapelNameKey, $nama))) {
                                        $found = $m;
                                        break;
                                    }
                                }
                            }

                            if (!$found && !empty($items)) {
                                $found = $items[0];
                            }

                            $mapelKkmByName[$mapelNameKey] = $found['kkm'] ?? null;
                        } catch (\Throwable $e) {
                            report($e);
                            $mapelKkmByName[$mapelNameKey] = null;
                        }
                    }

                    $kkm = $mapelKkmByName[$mapelNameKey];
                }

                $semester = data_get($row, 'semester')
                    ?? data_get($row, 'semester_label')
                    ?? data_get($row, 'tahun_ajaran.semester')
                    ?? data_get($row, 'ta.semester');

                $semester = $this->normalizeSemesterLabel($semester);

                $tahunAjaranId = data_get($row, 'tahun_ajaran_id')
                    ?? data_get($row, 'tahun_ajaran.id')
                    ?? data_get($row, 'ta.id');

                $tahunAjaran = data_get($row, 'tahun_ajaran.nama_tahun')
                    ?? data_get($row, 'tahun_ajaran.nama')
                    ?? data_get($row, 'tahun_ajaran')
                    ?? data_get($row, 'tahun_ajaran_label')
                    ?? data_get($row, 'nama_tahun')
                    ?? data_get($row, 'ta.nama_tahun')
                    ?? data_get($row, 'ta.nama');

                if (is_array($tahunAjaran)) {
                    $tahunAjaran = $tahunAjaran['nama_tahun'] ?? $tahunAjaran['nama'] ?? null;
                }

                $tahunAjaran = $this->pickString($tahunAjaran);

                $statusRaw = strtolower(trim((string) data_get($row, 'status', '')));

                $lm1Tp1 = $this->toFloat(data_get($row, 'lm1_tp1'));
                $lm1Tp2 = $this->toFloat(data_get($row, 'lm1_tp2'));
                $lm1Tp3 = $this->toFloat(data_get($row, 'lm1_tp3'));
                $lm1Tp4 = $this->toFloat(data_get($row, 'lm1_tp4'));
                $lm1 = $this->toFloat(data_get($row, 'lm1_nilai', data_get($row, 'lm1')));

                $lm2Tp1 = $this->toFloat(data_get($row, 'lm2_tp1'));
                $lm2Tp2 = $this->toFloat(data_get($row, 'lm2_tp2'));
                $lm2Tp3 = $this->toFloat(data_get($row, 'lm2_tp3'));
                $lm2Tp4 = $this->toFloat(data_get($row, 'lm2_tp4'));
                $lm2 = $this->toFloat(data_get($row, 'lm2_nilai', data_get($row, 'lm2')));

                $lm3Tp1 = $this->toFloat(data_get($row, 'lm3_tp1'));
                $lm3Tp2 = $this->toFloat(data_get($row, 'lm3_tp2'));
                $lm3Tp3 = $this->toFloat(data_get($row, 'lm3_tp3'));
                $lm3Tp4 = $this->toFloat(data_get($row, 'lm3_tp4'));
                $lm3 = $this->toFloat(data_get($row, 'lm3_nilai', data_get($row, 'lm3')));

                $lm4Tp1 = $this->toFloat(data_get($row, 'lm4_tp1'));
                $lm4Tp2 = $this->toFloat(data_get($row, 'lm4_tp2'));
                $lm4Tp3 = $this->toFloat(data_get($row, 'lm4_tp3'));
                $lm4Tp4 = $this->toFloat(data_get($row, 'lm4_tp4'));
                $lm4 = $this->toFloat(data_get($row, 'lm4_nilai', data_get($row, 'lm4')));

                $nilaiAkhir = $this->toFloat(data_get($row, 'nilai_akhir', data_get($row, 'rata_rata')));
                $kkmF = $this->toFloat($kkm);

                if ($nilaiAkhir === null) {
                    $available = collect([$lm1, $lm2, $lm3, $lm4])
                        ->filter(fn($value) => $value !== null)
                        ->values();

                    $nilaiAkhir = $available->count() > 0
                        ? round($available->avg(), 2)
                        : null;
                }

                $isTuntas = null;

                if (in_array($statusRaw, ['tuntas', 'lulus'], true)) {
                    $isTuntas = true;
                } elseif (in_array($statusRaw, ['tidak_tuntas', 'tidak tuntas', 'belum_tuntas', 'belum tuntas'], true)) {
                    $isTuntas = false;
                } elseif ($nilaiAkhir !== null && $kkmF !== null) {
                    $isTuntas = $nilaiAkhir >= $kkmF;
                }

                return [
                    'mapel' => $mapelName,
                    'kkm' => $kkmF,

                    'lm1_tp1' => $lm1Tp1,
                    'lm1_tp2' => $lm1Tp2,
                    'lm1_tp3' => $lm1Tp3,
                    'lm1_tp4' => $lm1Tp4,
                    'lm1' => $lm1,

                    'lm2_tp1' => $lm2Tp1,
                    'lm2_tp2' => $lm2Tp2,
                    'lm2_tp3' => $lm2Tp3,
                    'lm2_tp4' => $lm2Tp4,
                    'lm2' => $lm2,

                    'lm3_tp1' => $lm3Tp1,
                    'lm3_tp2' => $lm3Tp2,
                    'lm3_tp3' => $lm3Tp3,
                    'lm3_tp4' => $lm3Tp4,
                    'lm3' => $lm3,

                    'lm4_tp1' => $lm4Tp1,
                    'lm4_tp2' => $lm4Tp2,
                    'lm4_tp3' => $lm4Tp3,
                    'lm4_tp4' => $lm4Tp4,
                    'lm4' => $lm4,

                    'nilai_akhir' => $nilaiAkhir,
                    'semester' => $semester,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'tahun_ajaran' => $tahunAjaran,
                    'status_raw' => $statusRaw,
                    'is_tuntas' => $isTuntas,
                ];
            })
            ->filter(function ($row) {
                return !empty($row['mapel']) && $row['mapel'] !== '-';
            })
            ->values();
    }

    private function buildTrendChart(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return [[], []];
        }

        $semesterOrderMap = [
            'Ganjil' => 1,
            'Genap' => 2,
        ];

        $labels = $rows
            ->map(function ($row) {
                $ta = trim((string) ($row['tahun_ajaran'] ?? ''));
                $sem = trim((string) ($row['semester'] ?? ''));

                if ($ta === '' || $sem === '') {
                    return null;
                }

                return $ta . ' - ' . $sem;
            })
            ->filter()
            ->unique()
            ->sortBy(function ($label) use ($semesterOrderMap) {
                [$ta, $sem] = array_pad(explode(' - ', $label, 2), 2, '');

                return sprintf('%s-%02d', $ta, $semesterOrderMap[$sem] ?? 99);
            })
            ->values()
            ->all();

        $datasets = [];

        $grouped = $rows
            ->filter(function ($row) {
                return !empty($row['tahun_ajaran']) && !empty($row['semester']);
            })
            ->groupBy('tahun_ajaran');

        foreach ($grouped as $tahunAjaran => $items) {
            $avgBySemester = $items
                ->groupBy('semester')
                ->map(function ($semesterItems) {
                    $values = collect($semesterItems)
                        ->pluck('nilai_akhir')
                        ->filter(fn($v) => $v !== null);

                    return $values->count() > 0 ? round($values->avg(), 2) : null;
                });

            $datasets[] = [
                'label' => (string) $tahunAjaran,
                'data' => collect($labels)->map(function ($label) use ($tahunAjaran, $avgBySemester) {
                    [$ta, $sem] = array_pad(explode(' - ', $label, 2), 2, '');

                    if ($ta !== $tahunAjaran) {
                        return null;
                    }

                    return $avgBySemester->get($sem);
                })->all(),
            ];
        }

        return [$labels, $datasets];
    }

    private function makePdfFilename(?string $nis, ?string $tahunAjaran, ?string $semester): string
    {
        $nis = $this->safeFilenamePart($nis ?: 'siswa');
        $tahunAjaran = $this->safeFilenamePart($tahunAjaran ?: 'tahun-ajaran');
        $semester = $this->safeFilenamePart($semester ?: 'semester');

        return "rekap-nilai-{$nis}-{$tahunAjaran}-{$semester}.pdf";
    }

    private function safeFilenamePart(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'data';
        }

        $value = str_replace(['/', '\\'], '-', $value);
        $value = preg_replace('/[\x00-\x1F\x7F]+/', '', $value);
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value);
        $value = preg_replace('/-+/', '-', $value);
        $value = trim($value, '-._');

        return $value !== '' ? strtolower($value) : 'data';
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

    private function semesterOrder($value): int
    {
        return match (strtolower(trim((string) $value))) {
            'ganjil', 'gasal', '1' => 1,
            'genap', '2' => 2,
            default => 99,
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

    private function toFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        return is_numeric($value) ? (float) $value : null;
    }
}