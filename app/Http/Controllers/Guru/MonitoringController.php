<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\SiaClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MonitoringController extends Controller
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /**
     * MENU MONITORING SISWA
     * Langsung tampilkan rombel binaan guru pada tahun ajaran aktif.
     */
    public function index()
    {
        Carbon::setLocale('id');

        [$user, $role, $guru] = $this->resolveCurrentGuruOrAbort();

        $activePeriod = $this->resolveActiveAcademicPeriod();
        $rombel = $this->resolveMainRombelForGuru($guru, $role, $activePeriod);

        if (!$rombel) {
            return view('guru.monitoring.index', [
                'role' => $role,
                'guru' => (object) $guru,
                'rombel' => null,
                'siswaList' => collect(),
                'infoTahunAjaran' => $activePeriod['nama_tahun'] ?? null,
                'infoSemester' => $activePeriod['semester'] ?? null,
            ]);
        }

        $siswaList = collect($this->fetchRombelAnggota((int) $rombel['id'], $activePeriod));

        return view('guru.monitoring.index', [
            'role' => $role,
            'guru' => (object) $guru,
            'rombel' => (object) $rombel,
            'siswaList' => $siswaList,
            'infoTahunAjaran' => $activePeriod['nama_tahun'] ?? null,
            'infoSemester' => $activePeriod['semester'] ?? null,
        ]);
    }

    /**
     * Route lama tetap aman.
     */
    public function rombelSiswa($rombelId)
    {
        Carbon::setLocale('id');

        [$user, $role, $guru] = $this->resolveCurrentGuruOrAbort();

        $activePeriod = $this->resolveActiveAcademicPeriod();
        $rombel = $this->resolveMainRombelForGuru($guru, $role, $activePeriod);

        if (!$rombel) {
            abort(404, 'Rombel binaan tidak ditemukan pada tahun ajaran aktif.');
        }

        if ((int) $rombel['id'] !== (int) $rombelId) {
            abort(403, 'Anda tidak berhak mengakses rombel ini.');
        }

        $siswaList = collect($this->fetchRombelAnggota((int) $rombel['id'], $activePeriod));

        return view('guru.monitoring.index', [
            'role' => $role,
            'guru' => (object) $guru,
            'rombel' => (object) $rombel,
            'siswaList' => $siswaList,
            'infoTahunAjaran' => $activePeriod['nama_tahun'] ?? null,
            'infoSemester' => $activePeriod['semester'] ?? null,
        ]);
    }

    /**
     * DETAIL SISWA
     * Semua data full lewat API SIA dan tetap memakai rombel tahun ajaran aktif.
     */
    public function siswaShow($rombelId, string $nis)
    {
        Carbon::setLocale('id');

        [$user, $role, $guru] = $this->resolveCurrentGuruOrAbort();

        $activePeriod = $this->resolveActiveAcademicPeriod();
        $rombel = $this->resolveMainRombelForGuru($guru, $role, $activePeriod);

        if (!$rombel) {
            abort(404, 'Rombel binaan tidak ditemukan pada tahun ajaran aktif.');
        }

        if ((int) $rombel['id'] !== (int) $rombelId) {
            abort(403, 'Anda tidak berhak mengakses rombel ini.');
        }

        $siswaByNisResp = $this->sia->getSiswaByNis($nis);
        $siswaByNisData = $this->extractData($siswaByNisResp);

        if (empty($siswaByNisData) || empty($siswaByNisData['id'])) {
            abort(404, 'Data siswa tidak ditemukan di SIA.');
        }

        $siswaId = (int) $siswaByNisData['id'];

        $siswaDetailResp = $this->sia->masterSiswaDetail($siswaId);
        $siswaDetailData = $this->extractData($siswaDetailResp);

        $mergedRaw = array_merge(
            $this->arr($siswaByNisData),
            $this->arr($siswaDetailData)
        );

        $siswa = $this->normalizeSiswaDetail($mergedRaw);

        if (($siswa['rombel'] ?? '-') === '-' && !empty($rombel['rombel_label'])) {
            $siswa['rombel'] = $rombel['rombel_label'];
        }

        if (($siswa['tahun_ajaran'] ?? '-') === '-') {
            $siswa['tahun_ajaran'] = $rombel['tahun_ajaran'] ?? ($activePeriod['nama_tahun'] ?? '-');
        }

        /*
        |--------------------------------------------------------------------------
        | Filter data detail mengikuti tahun ajaran aktif dan rombel aktif
        |--------------------------------------------------------------------------
        */
        $filters = array_filter([
            'tahun_ajaran_id' => $activePeriod['id'] ?? null,
            'tahun_ajaran' => $activePeriod['nama_tahun'] ?? null,
            'semester' => $activePeriod['semester'] ?? null,
            'rombel_id' => $rombel['id'] ?? null,
            'rombel' => $rombel['id'] ?? null,
        ], fn($value) => $value !== null && $value !== '');

        $nilaiResp = $this->sia->getNilaiByNis($nis, $filters);
        $nilaiBlock = $this->extractData($nilaiResp);
        $nilaiApi = $this->normalizeNilaiList($nilaiBlock, $activePeriod, $rombel);

        $presensiResp = $this->sia->getPresensiByNis($nis, $filters);
        $presensiBlock = $this->extractData($presensiResp);
        $presensiApi = $this->normalizePresensiList($presensiBlock, $activePeriod, $rombel);

        $presensiRingkas = $this->buildPresensiRingkas($presensiApi);
        $presensiGrouped = $this->groupPresensiByMapel($presensiApi);

        try {
            $ekskulResp = $this->sia->getEkskulByNis($nis, $filters);
        } catch (\Throwable $e) {
            report($e);
            $ekskulResp = $this->sia->masterEkskul(['nis' => $nis] + $filters);
        }

        $ekskulBlock = $this->extractData($ekskulResp);
        $ekskulApi = $this->normalizeEkskulList($ekskulBlock, $activePeriod, $rombel);

        return view('guru.monitoring.show', [
            'role' => $role,
            'guru' => (object) $guru,
            'rombel' => (object) $rombel,
            'nis' => $nis,
            'siswa' => $siswa,
            'nilaiApi' => $nilaiApi,
            'presensiApi' => $presensiApi,
            'presensiGrouped' => $presensiGrouped,
            'presensiRingkas' => $presensiRingkas,
            'ekskulApi' => $ekskulApi,
            'infoTahunAjaran' => $activePeriod['nama_tahun'] ?? null,
            'infoSemester' => $activePeriod['semester'] ?? null,
        ]);
    }

    /* =========================================================
     * PERIODE AKADEMIK AKTIF
     * =======================================================*/
    protected function resolveActiveAcademicPeriod(): array
    {
        try {
            if (method_exists($this->sia, 'masterTahunAjaranAktif')) {
                $resp = $this->sia->masterTahunAjaranAktif();
                $data = $this->extractData($resp);

                if (!empty($data)) {
                    $namaTahun = $this->toString($data['nama_tahun'] ?? $data['nama'] ?? null, null);
                    $semester = $this->normalizeSemesterLabel($data['semester'] ?? $data['semester_aktif'] ?? null);

                    return [
                        'id' => $data['id'] ?? null,
                        'nama_tahun' => $namaTahun,
                        'semester' => $semester,
                        'status' => $data['status'] ?? null,
                        'tanggal_mulai' => $this->toString(
                            $data['tanggal_mulai']
                            ?? $data['tgl_mulai']
                            ?? $data['mulai']
                            ?? $data['start_date']
                            ?? null,
                            null
                        ),
                        'tanggal_selesai' => $this->toString(
                            $data['tanggal_selesai']
                            ?? $data['tgl_selesai']
                            ?? $data['selesai']
                            ?? $data['end_date']
                            ?? null,
                            null
                        ),
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
                    $namaTahun = $this->toString($ta['nama_tahun'] ?? $ta['nama'] ?? null, null);
                    $semester = $this->normalizeSemesterLabel($ta['semester'] ?? $ta['semester_aktif'] ?? null);

                    return [
                        'id' => $ta['id'] ?? null,
                        'nama_tahun' => $namaTahun,
                        'semester' => $semester,
                        'status' => $ta['status'] ?? null,
                        'tanggal_mulai' => $this->toString(
                            $ta['tanggal_mulai']
                            ?? $ta['tgl_mulai']
                            ?? $ta['mulai']
                            ?? $ta['start_date']
                            ?? null,
                            null
                        ),
                        'tanggal_selesai' => $this->toString(
                            $ta['tanggal_selesai']
                            ?? $ta['tgl_selesai']
                            ?? $ta['selesai']
                            ?? $ta['end_date']
                            ?? null,
                            null
                        ),
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
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
        ];
    }

    protected function normalizeSemesterLabel($value): ?string
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

    /* =========================================================
     * RESOLVER GURU LOGIN
     * =======================================================*/
    protected function resolveCurrentGuruOrAbort(): array
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Anda harus login terlebih dahulu.');
        }

        $role = (string) ($user->role ?? 'guru');
        $identifier = trim((string) ($user->sia_user_id ?? ''));
        $name = trim((string) ($user->name ?? ''));
        $email = trim((string) ($user->email ?? ''));

        $guru = null;

        if ($identifier !== '') {
            try {
                $guruResp = $this->sia->getGuruByKey($identifier);
                $guruData = $this->extractData($guruResp);

                if (!empty($guruData)) {
                    $guru = $this->normalizeGuru($guruData);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (!$guru && $name !== '') {
            try {
                $guruResp = $this->sia->masterGuru($name);
                $guruList = $this->extractListData($guruResp);

                foreach ($guruList as $row) {
                    $g = $this->normalizeGuru($row);

                    if (
                        $this->normalizeName($g['nama']) === $this->normalizeName($name) ||
                        ($identifier !== '' && ($g['nuptk'] === $identifier || $g['nip'] === $identifier)) ||
                        ($email !== '' && !empty($g['email']) && strtolower($g['email']) === strtolower($email))
                    ) {
                        $guru = $g;
                        break;
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (!$guru) {
            abort(403, 'Data guru tidak ditemukan melalui API SIA.');
        }

        return [$user, $role, $guru];
    }

    /* =========================================================
     * ROMBEL BINAAN GURU BERDASARKAN TAHUN AJARAN AKTIF
     * =======================================================*/
    protected function resolveMainRombelForGuru(array $guru, string $role, array $activePeriod = []): ?array
    {
        $guruId = $guru['id'] ?? null;

        if (!$guruId) {
            return null;
        }

        $activeTahunAjaranId = $activePeriod['id'] ?? null;
        $activeTahunAjaranName = $activePeriod['nama_tahun'] ?? null;

        $rombelRows = collect();

        try {
            $resp = $this->sia->masterRombel(null, array_filter([
                'guru_id' => $guruId,
                'aktif' => 1,
                'tahun_ajaran_id' => $activeTahunAjaranId,
                'tahun_ajaran' => $activeTahunAjaranName,
            ], fn($value) => $value !== null && $value !== ''));

            $rombelRows = $rombelRows->merge($this->extractListData($resp));
        } catch (\Throwable $e) {
            report($e);
        }

        if ($rombelRows->isEmpty()) {
            try {
                $resp = $this->sia->masterRombel(null, array_filter([
                    'guru_id' => $guruId,
                    'aktif' => 1,
                ], fn($value) => $value !== null && $value !== ''));

                $rombelRows = $rombelRows->merge($this->extractListData($resp));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($rombelRows->isEmpty()) {
            try {
                $resp = $this->sia->masterRombel();
                $rombelRows = $rombelRows->merge($this->extractListData($resp));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($rombelRows->isEmpty()) {
            return null;
        }

        $normalized = $rombelRows
            ->filter(fn($row) => is_array($row) || is_object($row))
            ->map(fn($row) => $this->normalizeRombelRow($row))
            ->filter(fn($row) => !empty($row['id']))
            ->filter(fn($row) => $this->isRombelMilikGuru($row, $guru))
            ->values();

        if ($normalized->isEmpty()) {
            return null;
        }

        $matched = $normalized->first(function ($row) use ($activeTahunAjaranId, $activeTahunAjaranName) {
            return $this->rowMatchesActiveAcademicPeriod($row, $activeTahunAjaranId, $activeTahunAjaranName);
        });

        if (!$matched) {
            $matched = $normalized->first(function ($row) {
                $aktif = strtolower(trim((string) ($row['aktif'] ?? '')));

                return $aktif === '1'
                    || $aktif === 'aktif'
                    || $aktif === 'true'
                    || ($row['aktif'] ?? null) === true;
            });
        }

        if (!$matched) {
            $matched = $normalized->first();
        }

        if (!$matched || empty($matched['id'])) {
            return null;
        }

        try {
            $detailResp = $this->sia->masterRombelDetail((int) $matched['id']);
            $detailData = $this->extractData($detailResp);

            if (!empty($detailData)) {
                $detail = $this->normalizeRombelRow($detailData);

                if (
                    $this->hasAcademicContext($detail) &&
                    !$this->rowMatchesActiveAcademicPeriod($detail, $activeTahunAjaranId, $activeTahunAjaranName)
                ) {
                    return null;
                }

                $matched = array_merge($matched, array_filter($detail, function ($value) {
                    return $value !== null && $value !== '' && $value !== '-';
                }));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if (empty($matched['tahun_ajaran']) || $matched['tahun_ajaran'] === '-') {
            $matched['tahun_ajaran'] = $activeTahunAjaranName ?: $this->resolveActiveTahunAjaranLabel();
        }

        if (empty($matched['tahun_ajaran_id']) && !empty($activeTahunAjaranId)) {
            $matched['tahun_ajaran_id'] = $activeTahunAjaranId;
        }

        if (empty($matched['rombel_label']) || $matched['rombel_label'] === '-') {
            $matched['rombel_label'] = $this->buildRombelLabel($matched);
        }

        return $matched;
    }

    protected function isRombelMilikGuru(array $row, array $guru): bool
    {
        $guruId = trim((string) ($guru['id'] ?? ''));
        $guruNip = trim((string) ($guru['nip'] ?? ''));
        $guruNuptk = trim((string) ($guru['nuptk'] ?? ''));
        $guruNama = $this->normalizeName((string) ($guru['nama'] ?? ''));

        $waliId = trim((string) ($row['wali_kelas_id'] ?? ''));
        $guruIdAlt = trim((string) ($row['guru_id'] ?? ''));
        $waliNip = trim((string) ($row['wali_kelas_nip'] ?? ''));
        $waliNuptk = trim((string) ($row['wali_kelas_nuptk'] ?? ''));
        $waliNama = $this->normalizeName((string) ($row['wali_kelas'] ?? ''));

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

    protected function hasAcademicContext(array $row): bool
    {
        return !empty($row['tahun_ajaran_id'])
            || (!empty($row['tahun_ajaran']) && $row['tahun_ajaran'] !== '-')
            || data_get($row, 'tahun_ajaran.id') !== null
            || data_get($row, 'tahun_ajaran.nama_tahun') !== null
            || data_get($row, 'tahun_ajaran.nama') !== null
            || data_get($row, 'ta.id') !== null
            || data_get($row, 'ta.nama_tahun') !== null
            || data_get($row, 'ta.nama') !== null;
    }

    protected function rowMatchesActiveAcademicPeriod(array $row, $activeTahunAjaranId = null, ?string $activeTahunAjaranName = null): bool
    {
        $rowTahunAjaranId = data_get($row, 'tahun_ajaran_id')
            ?? data_get($row, 'tahun_ajaran.id')
            ?? data_get($row, 'ta.id');

        $rowTahunAjaranName = $this->getDisplayValue(
            data_get($row, 'tahun_ajaran'),
            ['nama_tahun', 'nama', 'label']
        );

        if (!$rowTahunAjaranName) {
            $rowTahunAjaranName = $this->toString(
                data_get($row, 'nama_tahun')
                ?? data_get($row, 'tahun_ajaran_label')
                ?? data_get($row, 'ta.nama_tahun')
                ?? data_get($row, 'ta.nama')
                ?? null,
                ''
            );
        }

        if ($activeTahunAjaranId !== null && $activeTahunAjaranId !== '') {
            if ($rowTahunAjaranId !== null && $rowTahunAjaranId !== '') {
                return (string) $rowTahunAjaranId === (string) $activeTahunAjaranId;
            }
        }

        if ($activeTahunAjaranName !== null && trim($activeTahunAjaranName) !== '') {
            if ($rowTahunAjaranName !== null && trim((string) $rowTahunAjaranName) !== '') {
                return trim((string) $rowTahunAjaranName) === trim((string) $activeTahunAjaranName);
            }
        }

        return false;
    }

    protected function fetchRombelAnggota(int $rombelId, array $activePeriod = []): array
    {
        $resp = $this->sia->masterRombelAnggota($rombelId);
        $list = $this->extractListData($resp);

        $rombelDetailResp = $this->sia->masterRombelDetail($rombelId);
        $rombelDetailData = $this->extractData($rombelDetailResp);
        $rombelDetail = !empty($rombelDetailData) ? $this->normalizeRombelRow($rombelDetailData) : [];

        $tahunAjaranDefault = $this->toString(
            $rombelDetail['tahun_ajaran'] ?? ($activePeriod['nama_tahun'] ?? '-'),
            '-'
        );

        if ($tahunAjaranDefault === '-') {
            $tahunAjaranDefault = $this->resolveActiveTahunAjaranLabel();
        }

        return collect($list)
            ->map(function ($row) use ($tahunAjaranDefault) {
                $row = $this->arr($row);

                $nis = $this->toString($row['nis'] ?? data_get($row, 'siswa.nis') ?? '-', '-');
                $nisn = $this->toString($row['nisn'] ?? data_get($row, 'siswa.nisn') ?? '-', '-');

                $siswaData = [];

                if ($nis !== '-') {
                    try {
                        $siswaResp = $this->sia->getSiswaByNis($nis);
                        $siswaData = $this->extractData($siswaResp);

                        if (!empty($siswaData['nisn'])) {
                            $nisn = $this->toString($siswaData['nisn'], '-');
                        }
                    } catch (\Throwable $e) {
                        report($e);
                        $siswaData = [];
                    }
                }

                $mergedRow = array_merge($row, array_filter($this->arr($siswaData), function ($value) {
                    return $value !== null && $value !== '';
                }));

                $tahunAjaran = $this->pickFirstScalar($mergedRow, [
                    'tahun_ajaran',
                    'tahunAjaran',
                    'ta',
                    'tahun_ajaran_label',
                    'nama_tahun',
                ]);

                if ($tahunAjaran === null && isset($mergedRow['tahun_ajaran_detail'])) {
                    $tahunAjaran = $this->getDisplayValue($mergedRow['tahun_ajaran_detail'], [
                        'nama_tahun',
                        'nama',
                        'label',
                    ]);
                }

                if ($tahunAjaran === null && isset($mergedRow['tahun_ajaran_obj'])) {
                    $tahunAjaran = $this->getDisplayValue($mergedRow['tahun_ajaran_obj'], [
                        'nama_tahun',
                        'nama',
                        'label',
                    ]);
                }

                if ($tahunAjaran === null || trim((string) $tahunAjaran) === '' || $tahunAjaran === '-') {
                    $tahunAjaran = $tahunAjaranDefault;
                }

                [$fotoSrc, $previewFoto] = $this->resolveSiswaPhotoFromRow($mergedRow);

                return (object) [
                    'id' => $this->toInt($mergedRow['id'] ?? $mergedRow['siswa_id'] ?? data_get($mergedRow, 'siswa.id') ?? null),
                    'nis' => $nis,
                    'nisn' => $nisn,
                    'nama' => $this->toString($mergedRow['nama'] ?? $mergedRow['siswa_nama'] ?? data_get($mergedRow, 'siswa.nama') ?? '-', '-'),
                    'jk' => $this->normalizeJenisKelamin($mergedRow['jenis_kelamin'] ?? $mergedRow['jk'] ?? data_get($mergedRow, 'siswa.jenis_kelamin') ?? data_get($mergedRow, 'siswa.jk') ?? '-'),
                    'tahun_ajaran' => $this->toString($tahunAjaran, '-'),
                    'foto' => $this->toString($mergedRow['foto'] ?? data_get($mergedRow, 'siswa.foto') ?? '', ''),
                    'foto_url' => $this->toString($mergedRow['foto_url'] ?? data_get($mergedRow, 'siswa.foto_url') ?? '', ''),
                    'photo_url' => $this->toString($mergedRow['photo_url'] ?? data_get($mergedRow, 'siswa.photo_url') ?? '', ''),
                    'avatar' => $this->toString($mergedRow['avatar'] ?? data_get($mergedRow, 'siswa.avatar') ?? '', ''),
                    'foto_siswa' => $this->toString($mergedRow['foto_siswa'] ?? data_get($mergedRow, 'siswa.foto_siswa') ?? '', ''),
                    'foto_src' => $fotoSrc,
                    'preview_foto' => $previewFoto,
                ];
            })
            ->sortBy('nama')
            ->values()
            ->all();
    }

    /* =========================================================
     * NORMALIZER SISWA
     * =======================================================*/
    protected function normalizeSiswaDetail($data): array
    {
        $row = $this->arr($data);
        $rombelAktif = $this->arr($row['rombel_aktif'] ?? []);

        $defaultFoto = file_exists(public_path('images/default-user.png'))
            ? asset('images/default-user.png')
            : asset('images/default-siswa.png');

        [$fotoSrc, $previewFoto] = $this->resolveSiswaPhotoFromRow($row);

        $jkRaw = strtoupper(trim((string) ($row['jenis_kelamin'] ?? $row['jk'] ?? '')));
        $jkLabel = match ($jkRaw) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            'LAKI-LAKI', 'LAKI', 'LAKILAKI', 'MALE', 'M' => 'Laki-laki',
            'PEREMPUAN', 'WANITA', 'FEMALE', 'F' => 'Perempuan',
            default => '-',
        };

        $rombelNama = '-';

        if (!empty($rombelAktif)) {
            $tingkat = trim((string) ($rombelAktif['tingkat'] ?? ''));
            $nama = trim((string) ($rombelAktif['nama_rombel'] ?? '-'));

            if ($nama !== '' && $nama !== '-') {
                $upperNama = strtoupper($nama);
                $upperTingkat = strtoupper($tingkat);

                if ($tingkat !== '' && !str_starts_with($upperNama, $upperTingkat)) {
                    $rombelNama = $tingkat . $nama;
                } else {
                    $rombelNama = $nama;
                }
            }
        }

        return [
            'id' => $this->toInt($row['id'] ?? null),
            'user_id' => $this->toInt($row['user_id'] ?? null),
            'nis' => $this->toString($row['nis'] ?? '-', '-'),
            'nisn' => $this->toString($row['nisn'] ?? '-', '-'),
            'nama' => $this->toString($row['nama'] ?? '-', '-'),
            'tempat_lahir' => $this->toString($row['tempat_lahir'] ?? '-', '-'),
            'tanggal_lahir' => $this->toString($row['tanggal_lahir'] ?? '-', '-'),
            'jenis_kelamin' => $jkLabel,
            'agama' => $this->toString($row['agama'] ?? '-', '-'),
            'alamat' => $this->toString($row['alamat'] ?? '-', '-'),
            'email' => $this->toString($row['email'] ?? '-', '-'),
            'no_hp' => $this->toString($row['no_hp'] ?? '-', '-'),
            'foto' => $this->toString($row['foto'] ?? '-', '-'),
            'foto_url' => $this->toString($row['foto_url'] ?? '', ''),
            'photo_url' => $this->toString($row['photo_url'] ?? '', ''),
            'avatar' => $this->toString($row['avatar'] ?? '', ''),
            'foto_siswa' => $this->toString($row['foto_siswa'] ?? '', ''),
            'foto_src' => $fotoSrc,
            'preview_foto' => $previewFoto,
            'default_foto' => $defaultFoto,
            'status' => $this->toString($row['status'] ?? '-', '-'),
            'tahun_masuk' => $this->toString($row['tahun_masuk'] ?? '-', '-'),
            'jalur_penerimaan' => $this->toString($row['jalur_penerimaan'] ?? '-', '-'),
            'kebutuhan_khusus' => $this->toString($row['kebutuhan_khusus'] ?? '-', '-'),

            'nama_ayah' => $this->toString($row['nama_ayah'] ?? '-', '-'),
            'nik_ayah' => $this->toString($row['nik_ayah'] ?? '-', '-'),
            'status_ayah' => $this->toString($row['status_ayah'] ?? '-', '-'),
            'pekerjaan_ayah' => $this->toString($row['pekerjaan_ayah'] ?? '-', '-'),
            'pendidikan_ayah' => $this->toString($row['pendidikan_ayah'] ?? '-', '-'),
            'no_hp_ayah' => $this->toString($row['no_hp_ayah'] ?? '-', '-'),
            'alamat_ayah' => $this->toString($row['alamat_ayah'] ?? '-', '-'),

            'nama_ibu' => $this->toString($row['nama_ibu'] ?? '-', '-'),
            'nik_ibu' => $this->toString($row['nik_ibu'] ?? '-', '-'),
            'status_ibu' => $this->toString($row['status_ibu'] ?? '-', '-'),
            'pekerjaan_ibu' => $this->toString($row['pekerjaan_ibu'] ?? '-', '-'),
            'pendidikan_ibu' => $this->toString($row['pendidikan_ibu'] ?? '-', '-'),
            'no_hp_ibu' => $this->toString($row['no_hp_ibu'] ?? '-', '-'),
            'alamat_ibu' => $this->toString($row['alamat_ibu'] ?? '-', '-'),

            'rombel' => $rombelNama,
            'tahun_ajaran' => $this->toString(
                data_get($rombelAktif, 'tahun_ajaran.nama_tahun')
                ?? data_get($rombelAktif, 'tahun_ajaran.nama')
                ?? data_get($rombelAktif, 'tahun_ajaran')
                ?? $row['tahun_ajaran']
                ?? $row['nama_tahun']
                ?? '-',
                '-'
            ),
            'rombel_aktif' => [
                'id' => $this->toInt($rombelAktif['id'] ?? null),
                'nama_rombel' => $this->toString($rombelAktif['nama_rombel'] ?? '-', '-'),
                'tingkat' => $this->toString($rombelAktif['tingkat'] ?? '-', '-'),
            ],
        ];
    }

    protected function normalizeNilaiList($data, array $activePeriod = [], ?array $rombel = null): array
    {
        $block = $this->arr($data);
        $nilaiRows = [];

        if (isset($block['nilai']) && is_array($block['nilai'])) {
            $nilaiRows = $block['nilai'];
        } elseif (array_is_list($block)) {
            $nilaiRows = $block;
        }

        $hasAcademicContext = collect($nilaiRows)->contains(fn($row) => $this->hasAcademicContext($this->arr($row)));

        return collect($nilaiRows)
            ->map(function ($row) {
                $row = $this->arr($row);

                return [
                    'id' => $this->toInt($row['id'] ?? null),
                    'jadwal_id' => $this->toInt($row['jadwal_id'] ?? null),
                    'rombel' => $this->extractRombelName($row),
                    'rombel_id' => data_get($row, 'rombel_id') ?? data_get($row, 'rombel.id') ?? data_get($row, 'jadwal.rombel_id') ?? data_get($row, 'jadwal.rombel.id'),
                    'mapel' => $this->extractMapelName($row),
                    'guru' => $this->extractGuruName($row),
                    'semester' => $this->normalizeSemesterLabel($row['semester'] ?? data_get($row, 'tahun_ajaran.semester') ?? '-') ?? '-',
                    'tahun_ajaran_id' => data_get($row, 'tahun_ajaran_id') ?? data_get($row, 'tahun_ajaran.id') ?? data_get($row, 'ta.id'),
                    'tahun_ajaran' => $this->getDisplayValue(data_get($row, 'tahun_ajaran'), ['nama_tahun', 'nama', 'label'])
                        ?? $this->toString($row['nama_tahun'] ?? $row['tahun_ajaran_label'] ?? data_get($row, 'ta.nama_tahun') ?? data_get($row, 'ta.nama') ?? '-', '-'),

                    'lm1' => $this->toNumericOrNull($row['lm1_nilai'] ?? $row['lm1'] ?? null),
                    'lm2' => $this->toNumericOrNull($row['lm2_nilai'] ?? $row['lm2'] ?? null),
                    'lm3' => $this->toNumericOrNull($row['lm3_nilai'] ?? $row['lm3'] ?? null),
                    'lm4' => $this->toNumericOrNull($row['lm4_nilai'] ?? $row['lm4'] ?? null),

                    'lm1_detail' => $this->normalizeLmDetail($row, 'lm1'),
                    'lm2_detail' => $this->normalizeLmDetail($row, 'lm2'),
                    'lm3_detail' => $this->normalizeLmDetail($row, 'lm3'),
                    'lm4_detail' => $this->normalizeLmDetail($row, 'lm4'),

                    'nilai_akhir' => $this->toNumericOrNull($row['nilai_akhir'] ?? $row['rata_rata'] ?? null),
                    'status' => $this->toString($row['status'] ?? '-'),
                    'status_penilaian' => $this->toString($row['status_penilaian'] ?? '-'),
                    'finalized_at' => $this->toString($row['finalized_at'] ?? '-'),
                ];
            })
            ->filter(function ($row) use ($hasAcademicContext, $activePeriod, $rombel) {
                if (!$hasAcademicContext) {
                    return true;
                }

                return $this->rowMatchesDetailContext($row, $activePeriod, $rombel);
            })
            ->values()
            ->all();
    }

    protected function normalizeLmDetail(array $row, string $prefix): array
    {
        $detail = [];

        for ($i = 1; $i <= 4; $i++) {
            $key = $prefix . '_tp' . $i;
            $val = $row[$key] ?? null;

            $detail[] = [
                'label' => 'TP ' . $i,
                'nilai' => is_numeric($val) ? (float) $val : null,
            ];
        }

        return $detail;
    }

    protected function normalizePresensiList($data, array $activePeriod = [], ?array $rombel = null): array
    {
        $block = $this->arr($data);
        $rows = [];

        if (isset($block['detail']) && is_array($block['detail'])) {
            $rows = $block['detail'];
        } elseif (isset($block['list']) && is_array($block['list'])) {
            $rows = $block['list'];
        } elseif (isset($block['presensi']) && is_array($block['presensi'])) {
            $rows = $block['presensi'];
        } elseif (array_is_list($block)) {
            $rows = $block;
        }

        $hasAcademicContext = collect($rows)->contains(function ($row) {
            return $this->hasAcademicContext($this->arr($row))
                || data_get($this->arr($row), 'rombel_id') !== null
                || data_get($this->arr($row), 'rombel.id') !== null
                || data_get($this->arr($row), 'jadwal.rombel_id') !== null
                || data_get($this->arr($row), 'jadwal.rombel.id') !== null;
        });

        return collect($rows)
            ->map(function ($row) {
                $row = $this->arr($row);

                $tanggalRaw = $row['dipindai_pada']
                    ?? $row['tanggal']
                    ?? $row['waktu']
                    ?? $row['created_at']
                    ?? null;

                return [
                    'tanggal' => $this->toString($row['tanggal'] ?? '-', '-'),
                    'waktu_mulai' => $this->toString($row['waktu_mulai'] ?? '-', '-'),
                    'rombel' => $this->extractRombelName($row),
                    'rombel_id' => data_get($row, 'rombel_id')
                        ?? data_get($row, 'rombel.id')
                        ?? data_get($row, 'jadwal.rombel_id')
                        ?? data_get($row, 'jadwal.rombel.id'),

                    'mapel' => $this->extractMapelName($row),
                    'status' => $this->toString($row['status'] ?? '-'),
                    'dipindai_pada' => $this->toString($row['dipindai_pada'] ?? $row['created_at'] ?? $row['tanggal'] ?? '-', '-'),
                    'tanggal_raw' => $tanggalRaw,
                    'sesi_id' => $this->toInt($row['sesi_id'] ?? null),

                    'tahun_ajaran_id' => data_get($row, 'tahun_ajaran_id')
                        ?? data_get($row, 'tahun_ajaran.id')
                        ?? data_get($row, 'ta.id')
                        ?? data_get($row, 'jadwal.tahun_ajaran_id')
                        ?? data_get($row, 'jadwal.tahun_ajaran.id'),

                    'tahun_ajaran' => $this->getDisplayValue(data_get($row, 'tahun_ajaran'), ['nama_tahun', 'nama', 'label'])
                        ?? $this->getDisplayValue(data_get($row, 'jadwal.tahun_ajaran'), ['nama_tahun', 'nama', 'label'])
                        ?? $this->toString(
                            $row['nama_tahun']
                            ?? data_get($row, 'ta.nama_tahun')
                            ?? data_get($row, 'ta.nama')
                            ?? data_get($row, 'jadwal.nama_tahun')
                            ?? '-',
                            '-'
                        ),
                ];
            })
            ->filter(function ($row) use ($hasAcademicContext, $activePeriod, $rombel) {
                if ($hasAcademicContext) {
                    return $this->rowMatchesDetailContext($row, $activePeriod, $rombel);
                }

                return $this->rowMatchesActiveDatePeriod($row, $activePeriod);
            })
            ->values()
            ->all();
    }

    protected function groupPresensiByMapel(array $rows): array
    {
        return collect($rows)
            ->groupBy(function ($row) {
                $mapel = trim((string) ($row['mapel'] ?? ''));
                return $mapel !== '' && $mapel !== '-' ? $mapel : 'Mapel tidak diketahui';
            })
            ->map(function ($items, $mapel) {
                return [
                    'mapel' => $mapel,
                    'items' => array_values($items->all()),
                ];
            })
            ->values()
            ->all();
    }

    protected function normalizeEkskulList($data, array $activePeriod = [], ?array $rombel = null): array
    {
        $block = $this->arr($data);

        if (isset($block['ekskul']) && is_array($block['ekskul'])) {
            $rows = $block['ekskul'];
        } elseif (isset($block['items']) && is_array($block['items'])) {
            $rows = $block['items'];
        } elseif (isset($block['data']) && is_array($block['data'])) {
            $rows = $block['data'];
        } elseif (array_is_list($block)) {
            $rows = $block;
        } else {
            $rows = [];
        }

        $hasAcademicContext = collect($rows)->contains(fn($row) => $this->hasAcademicContext($this->arr($row)));

        return collect($rows)
            ->map(function ($row) {
                $row = $this->arr($row);

                $jamMulai = $this->toString($row['jam_mulai'] ?? '', '');
                $jamSelesai = $this->toString($row['jam_selesai'] ?? '', '');

                $jam = $this->toString($row['jam'] ?? '', '');

                if ($jam === '') {
                    if ($jamMulai !== '' && $jamSelesai !== '') {
                        $jam = substr($jamMulai, 0, 5) . ' - ' . substr($jamSelesai, 0, 5);
                    } elseif ($jamMulai !== '') {
                        $jam = substr($jamMulai, 0, 5);
                    } else {
                        $jam = '-';
                    }
                }

                return [
                    'id' => $this->toInt($row['id'] ?? null),
                    'nama' => $this->toString($row['nama'] ?? $row['nama_ekskul'] ?? data_get($row, 'ekskul.nama') ?? data_get($row, 'ekskul.nama_ekskul') ?? '-', '-'),
                    'hari' => $this->toString($row['hari'] ?? '-', '-'),
                    'jam' => $jam,
                    'pembina' => $this->extractPembinaName($row),
                    'lokasi' => $this->toString($row['lokasi'] ?? $row['tempat'] ?? '-', '-'),
                    'rombel_id' => data_get($row, 'rombel_id') ?? data_get($row, 'rombel.id'),
                    'tahun_ajaran_id' => data_get($row, 'tahun_ajaran_id') ?? data_get($row, 'tahun_ajaran.id') ?? data_get($row, 'ta.id'),
                    'tahun_ajaran' => $this->getDisplayValue(data_get($row, 'tahun_ajaran'), ['nama_tahun', 'nama', 'label'])
                        ?? $this->toString($row['nama_tahun'] ?? data_get($row, 'ta.nama_tahun') ?? data_get($row, 'ta.nama') ?? '-', '-'),
                ];
            })
            ->filter(function ($row) use ($hasAcademicContext, $activePeriod, $rombel) {
                if (!$hasAcademicContext) {
                    return true;
                }

                return $this->rowMatchesDetailContext($row, $activePeriod, $rombel);
            })
            ->values()
            ->all();
    }

    protected function rowMatchesDetailContext(array $row, array $activePeriod = [], ?array $rombel = null): bool
    {
        $activeTahunAjaranId = $activePeriod['id'] ?? null;
        $activeTahunAjaranName = $activePeriod['nama_tahun'] ?? null;
        $rombelId = $rombel['id'] ?? null;

        $rowTahunAjaranId = $row['tahun_ajaran_id'] ?? null;
        $rowTahunAjaran = $this->toString($row['tahun_ajaran'] ?? '', '');
        $rowRombelId = $row['rombel_id'] ?? null;

        if ($activeTahunAjaranId !== null && $rowTahunAjaranId !== null && $rowTahunAjaranId !== '') {
            if ((string) $rowTahunAjaranId !== (string) $activeTahunAjaranId) {
                return false;
            }
        }

        if ($activeTahunAjaranName !== null && trim($activeTahunAjaranName) !== '' && $rowTahunAjaran !== '' && $rowTahunAjaran !== '-') {
            if (trim($rowTahunAjaran) !== trim($activeTahunAjaranName)) {
                return false;
            }
        }

        if ($rombelId !== null && $rowRombelId !== null && $rowRombelId !== '') {
            if ((string) $rowRombelId !== (string) $rombelId) {
                return false;
            }
        }

        return true;
    }

    protected function buildPresensiRingkas(array $rows): array
    {
        $total = count($rows);
        $hadir = 0;

        foreach ($rows as $row) {
            $status = strtolower((string) ($row['status'] ?? ''));
            if ($status === 'hadir') {
                $hadir++;
            }
        }

        return [
            'total' => $total,
            'hadir' => $hadir,
            'persen' => $total > 0 ? round(($hadir / $total) * 100) : null,
        ];
    }

    protected function rowMatchesActiveDatePeriod(array $row, array $activePeriod = []): bool
    {
        $tanggalRaw = $row['tanggal_raw']
            ?? $row['dipindai_pada']
            ?? $row['tanggal']
            ?? null;

        if (!$tanggalRaw || $tanggalRaw === '-') {
            return false;
        }

        try {
            $tanggal = Carbon::parse($tanggalRaw, 'Asia/Jakarta')->startOfDay();
        } catch (\Throwable $e) {
            return false;
        }

        [$start, $end] = $this->resolveActiveDateRange($activePeriod);

        if (!$start || !$end) {
            return false;
        }

        return $tanggal->betweenIncluded($start, $end);
    }

    protected function resolveActiveDateRange(array $activePeriod = []): array
    {
        $tanggalMulai = $activePeriod['tanggal_mulai'] ?? null;
        $tanggalSelesai = $activePeriod['tanggal_selesai'] ?? null;

        if ($tanggalMulai && $tanggalSelesai) {
            try {
                return [
                    Carbon::parse($tanggalMulai, 'Asia/Jakarta')->startOfDay(),
                    Carbon::parse($tanggalSelesai, 'Asia/Jakarta')->endOfDay(),
                ];
            } catch (\Throwable $e) {
                // lanjut fallback dari nama tahun ajaran
            }
        }

        $namaTahun = trim((string) ($activePeriod['nama_tahun'] ?? ''));
        $semester = strtolower(trim((string) ($activePeriod['semester'] ?? '')));

        if ($namaTahun === '') {
            return [null, null];
        }

        if (!preg_match('/(20\d{2})\D+(20\d{2})/', $namaTahun, $matches)) {
            return [null, null];
        }

        $tahunAwal = (int) $matches[1];
        $tahunAkhir = (int) $matches[2];

        if (in_array($semester, ['ganjil', 'gasal', '1'], true)) {
            return [
                Carbon::create($tahunAwal, 7, 1, 0, 0, 0, 'Asia/Jakarta')->startOfDay(),
                Carbon::create($tahunAwal, 12, 31, 23, 59, 59, 'Asia/Jakarta')->endOfDay(),
            ];
        }

        if (in_array($semester, ['genap', '2'], true)) {
            return [
                Carbon::create($tahunAkhir, 1, 1, 0, 0, 0, 'Asia/Jakarta')->startOfDay(),
                Carbon::create($tahunAkhir, 6, 30, 23, 59, 59, 'Asia/Jakarta')->endOfDay(),
            ];
        }

        return [
            Carbon::create($tahunAwal, 7, 1, 0, 0, 0, 'Asia/Jakarta')->startOfDay(),
            Carbon::create($tahunAkhir, 6, 30, 23, 59, 59, 'Asia/Jakarta')->endOfDay(),
        ];
    }

    /* =========================================================
     * NORMALIZER UMUM
     * =======================================================*/
    protected function normalizeGuru($row): array
    {
        $row = $this->arr($row);

        return [
            'id' => $this->toInt($row['id'] ?? $row['guru_id'] ?? null),
            'nama' => $this->toString($row['nama'] ?? $row['name'] ?? $row['nama_guru'] ?? '-', '-'),
            'nip' => $this->toString($row['nip'] ?? '', ''),
            'nuptk' => $this->toString($row['nuptk'] ?? '', ''),
            'email' => $this->toString($row['email'] ?? '', ''),
        ];
    }

    protected function normalizeRombelRow($row): array
    {
        $row = $this->arr($row);

        $waliRaw = $row['wali_kelas']
            ?? $row['walikelas']
            ?? $row['wali']
            ?? $row['guru']
            ?? null;

        $wali = $this->arr($waliRaw);

        $ruangRaw = $row['ruang_kelas'] ?? $row['ruang'] ?? null;
        $ruang = $this->arr($ruangRaw);

        $taRaw = $row['tahun_ajaran']
            ?? $row['tahun_ajaran_detail']
            ?? $row['tahun_ajaran_obj']
            ?? $row['ta']
            ?? null;

        $ta = $this->arr($taRaw);

        $namaRombel = $this->toString(
            $row['nama_rombel'] ?? $row['nama'] ?? $row['label'] ?? $row['rombel'] ?? '-',
            '-'
        );

        $tahunAjaranLabel = $this->getDisplayValue($taRaw, ['nama_tahun', 'nama', 'label'])
            ?? $this->toString($row['nama_tahun'] ?? $row['tahun_ajaran_label'] ?? '-', '-');

        $ruangLabel = $this->getDisplayValue($ruangRaw, ['nama_ruang', 'nama', 'label'])
            ?? $this->toString($row['ruang'] ?? '-', '-');

        $waliNama = $this->toString(
            $row['nama_wali_kelas']
            ?? $row['wali_kelas_nama']
            ?? $row['walikelas_nama']
            ?? data_get($row, 'wali_kelas.nama')
            ?? data_get($row, 'walikelas.nama')
            ?? data_get($row, 'guru.nama')
            ?? (is_string($waliRaw) ? $waliRaw : null)
            ?? $wali['nama']
            ?? $wali['name']
            ?? $wali['nama_guru']
            ?? '-',
            '-'
        );

        return [
            'id' => $this->toInt($row['id'] ?? $row['rombel_id'] ?? null),
            'nama_rombel' => $namaRombel,
            'rombel_label' => $this->buildRombelLabel($row),
            'tingkat' => $this->toString($row['tingkat'] ?? $this->resolveTingkatFromRombelName($namaRombel) ?? '-', '-'),
            'kapasitas' => $this->toString($row['kapasitas'] ?? '-', '-'),
            'aktif' => $row['aktif'] ?? $row['status'] ?? null,

            'wali_kelas_id' => $this->toInt(
                $row['wali_kelas_id']
                ?? $row['walikelas_id']
                ?? $row['guru_id']
                ?? data_get($row, 'wali_kelas.id')
                ?? data_get($row, 'walikelas.id')
                ?? data_get($row, 'guru.id')
                ?? $wali['id']
                ?? $wali['guru_id']
                ?? null
            ),

            'guru_id' => $this->toInt(
                $row['guru_id']
                ?? data_get($row, 'guru.id')
                ?? data_get($row, 'wali_kelas.id')
                ?? data_get($row, 'walikelas.id')
                ?? $wali['id']
                ?? $wali['guru_id']
                ?? null
            ),

            'wali_kelas' => $waliNama,

            'wali_kelas_nip' => $this->toString(
                $row['wali_kelas_nip']
                ?? $row['nip_wali_kelas']
                ?? data_get($row, 'wali_kelas.nip')
                ?? data_get($row, 'guru.nip')
                ?? $wali['nip']
                ?? '',
                ''
            ),

            'wali_kelas_nuptk' => $this->toString(
                $row['wali_kelas_nuptk']
                ?? $row['nuptk_wali_kelas']
                ?? data_get($row, 'wali_kelas.nuptk')
                ?? data_get($row, 'guru.nuptk')
                ?? $wali['nuptk']
                ?? '',
                ''
            ),

            'ruang_kelas' => $ruangLabel,
            'ruang_kelas_id' => $this->toInt($ruang['id'] ?? $row['ruang_kelas_id'] ?? null),

            'tahun_ajaran' => $tahunAjaranLabel,
            'tahun_ajaran_id' => $this->toInt(
                $row['tahun_ajaran_id']
                ?? data_get($row, 'tahun_ajaran.id')
                ?? data_get($row, 'ta.id')
                ?? $ta['id']
                ?? null
            ),
        ];
    }

    protected function resolveTahunAjaranLabel(int $id): string
    {
        try {
            $resp = $this->sia->masterTahunAjaranDetail($id);
            $data = $this->extractData($resp);

            if (empty($data)) {
                return '-';
            }

            $nama = $this->toString($data['nama_tahun'] ?? $data['nama'] ?? '-', '-');
            $semester = $this->normalizeSemesterLabel($data['semester'] ?? '');

            return $semester ? $nama . ' (' . $semester . ')' : $nama;
        } catch (\Throwable $e) {
            report($e);
            return '-';
        }
    }

    protected function resolveActiveTahunAjaranLabel(): string
    {
        $active = $this->resolveActiveAcademicPeriod();

        $nama = $this->toString($active['nama_tahun'] ?? '-', '-');
        $semester = $this->normalizeSemesterLabel($active['semester'] ?? '');

        return $semester ? $nama . ' (' . $semester . ')' : $nama;
    }

    /* =========================================================
     * FOTO SISWA
     * =======================================================*/
    protected function resolveSiswaPhoto(string $foto): array
    {
        return $this->resolveSiswaPhotoFromRow([
            'foto' => $foto,
        ]);
    }

    protected function resolveSiswaPhotoFromRow($row): array
    {
        $defaultFoto = file_exists(public_path('images/default-user.png'))
            ? asset('images/default-user.png')
            : asset('images/default-siswa.png');

        $foto = $this->pickSiswaPhotoValue($row);

        if (!$foto) {
            return [$defaultFoto, null];
        }

        $foto = trim((string) $foto);

        if ($foto === '' || $foto === '-') {
            return [$defaultFoto, null];
        }

        if (preg_match('/^https?:\/\//i', $foto)) {
            return [$foto, $foto];
        }

        $foto = str_replace('\\', '/', $foto);
        $foto = preg_replace('#/+#', '/', $foto);
        $foto = ltrim($foto, '/');

        $basename = basename($foto);

        $candidates = [
            $foto,
            'sia/' . $foto,
            'foto_siswa/' . $basename,
            'sia/foto_siswa/' . $basename,
            'storage/' . $foto,
            'storage/foto_siswa/' . $basename,
            'storage/sia/foto_siswa/' . $basename,
        ];

        foreach (array_unique(array_filter($candidates)) as $relativePath) {
            if (is_file(public_path($relativePath))) {
                $asset = asset($relativePath);
                return [$asset, $asset];
            }
        }

        $siaPublicUrl = rtrim((string) (config('services.sia.public_url') ?: config('services.sia.base_url')), '/');

        if ($siaPublicUrl !== '') {
            if (str_starts_with($foto, 'storage/')) {
                $url = $siaPublicUrl . '/' . $foto;
                return [$url, $url];
            }

            if (str_starts_with($foto, 'foto_siswa/')) {
                $url = $siaPublicUrl . '/storage/' . $foto;
                return [$url, $url];
            }

            $url = $siaPublicUrl . '/storage/foto_siswa/' . $basename;
            return [$url, $url];
        }

        return [$defaultFoto, null];
    }

    protected function pickSiswaPhotoValue($row): ?string
    {
        $row = $this->arr($row);

        $candidates = [
            $row['foto_src'] ?? null,
            $row['preview_foto'] ?? null,
            $row['foto_url'] ?? null,
            data_get($row, 'siswa.foto_url'),
            $row['photo_url'] ?? null,
            data_get($row, 'siswa.photo_url'),
            $row['avatar'] ?? null,
            data_get($row, 'siswa.avatar'),
            $row['foto'] ?? null,
            data_get($row, 'siswa.foto'),
            $row['foto_siswa'] ?? null,
            data_get($row, 'siswa.foto_siswa'),
            $row['photo'] ?? null,
            data_get($row, 'siswa.photo'),
            $row['gambar'] ?? null,
            data_get($row, 'siswa.gambar'),
            $row['image'] ?? null,
            data_get($row, 'siswa.image'),
        ];

        foreach ($candidates as $value) {
            if (is_scalar($value) && trim((string) $value) !== '' && trim((string) $value) !== '-') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /* =========================================================
     * HELPERS
     * =======================================================*/
    protected function extractData($response): array
    {
        if (!is_array($response)) {
            return [];
        }

        $data = $response['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    protected function extractListData($response): array
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

    protected function arr($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        return [];
    }

    protected function toString($value, ?string $default = '-'): ?string
    {
        if (is_null($value)) {
            return $default;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            $text = trim((string) $value);
            return $text !== '' ? $text : $default;
        }

        return $default;
    }

    protected function toInt($value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    protected function toNumericOrNull($value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    protected function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);

        $replacements = [
            'm. pd' => 'm.pd',
            's. pd' => 's.pd',
            'm. si' => 'm.si',
            's. kom' => 's.kom',
            'm. kom' => 'm.kom',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $name);
    }

    protected function normalizeJenisKelamin($value): string
    {
        $value = strtoupper(trim((string) $value));

        return match ($value) {
            'L', 'LAKI', 'LAKI-LAKI', 'LAKILAKI', 'MALE', 'M' => 'L',
            'P', 'PEREMPUAN', 'WANITA', 'FEMALE', 'F' => 'P',
            default => '-',
        };
    }

    protected function getDisplayValue($value, array $keys = []): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_scalar($value)) {
            $v = trim((string) $value);
            return $v !== '' ? $v : null;
        }

        $arr = $this->arr($value);

        foreach ($keys as $key) {
            if (isset($arr[$key]) && is_scalar($arr[$key])) {
                $v = trim((string) $arr[$key]);
                if ($v !== '') {
                    return $v;
                }
            }
        }

        foreach ($arr as $item) {
            if (is_scalar($item)) {
                $v = trim((string) $item);
                if ($v !== '') {
                    return $v;
                }
            }
        }

        return null;
    }

    protected function pickFirstScalar(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && is_scalar($row[$key])) {
                $v = trim((string) $row[$key]);
                if ($v !== '') {
                    return $v;
                }
            }
        }

        return null;
    }

    protected function buildRombelLabel(array $row): string
    {
        $tingkat = trim((string) ($row['tingkat'] ?? ''));
        $nama = trim((string) ($row['nama_rombel'] ?? $row['nama'] ?? ''));

        if ($nama === '') {
            return $tingkat !== '' ? $tingkat : '-';
        }

        if ($tingkat === '') {
            return $nama;
        }

        $upperNama = strtoupper($nama);
        $upperTingkat = strtoupper($tingkat);

        if (str_starts_with($upperNama, $upperTingkat)) {
            return $nama;
        }

        return $tingkat . $nama;
    }

    protected function resolveTingkatFromRombelName(?string $rombel): ?string
    {
        $text = strtoupper(trim((string) $rombel));
        $text = preg_replace('/[^A-Z0-9]/', '', $text);

        if ($text === '') {
            return null;
        }

        if (str_starts_with($text, 'XII')) {
            return 'XII';
        }

        if (str_starts_with($text, 'XI')) {
            return 'XI';
        }

        if (str_starts_with($text, 'X')) {
            return 'X';
        }

        return null;
    }

    protected function extractMapelName(array $row): string
    {
        if (isset($row['mapel'])) {
            $mapel = $this->getDisplayValue($row['mapel'], ['nama_mapel', 'nama', 'label']);
            if ($mapel) {
                return $mapel;
            }
        }

        if (isset($row['mata_pelajaran'])) {
            $mapel = $this->getDisplayValue($row['mata_pelajaran'], ['nama_mapel', 'nama', 'label']);
            if ($mapel) {
                return $mapel;
            }
        }

        if (isset($row['jadwal']) && is_array($row['jadwal'])) {
            $jadwal = $this->arr($row['jadwal']);

            if (isset($jadwal['mapel'])) {
                $mapel = $this->getDisplayValue($jadwal['mapel'], ['nama_mapel', 'nama', 'label']);
                if ($mapel) {
                    return $mapel;
                }
            }

            if (isset($jadwal['mata_pelajaran'])) {
                $mapel = $this->getDisplayValue($jadwal['mata_pelajaran'], ['nama_mapel', 'nama', 'label']);
                if ($mapel) {
                    return $mapel;
                }
            }

            if (!empty($jadwal['nama_mapel'])) {
                return $this->toString($jadwal['nama_mapel'], '-');
            }
        }

        return $this->toString(
            $row['nama_mapel'] ?? $row['mapel_nama'] ?? $row['mata_pelajaran'] ?? '-',
            '-'
        );
    }

    protected function extractGuruName(array $row): string
    {
        if (isset($row['guru'])) {
            return $this->getDisplayValue($row['guru'], ['nama', 'label']) ?? '-';
        }

        if (isset($row['pengajar'])) {
            return $this->getDisplayValue($row['pengajar'], ['nama', 'label']) ?? '-';
        }

        if (isset($row['jadwal']) && is_array($row['jadwal'])) {
            $jadwal = $this->arr($row['jadwal']);

            if (isset($jadwal['guru'])) {
                return $this->getDisplayValue($jadwal['guru'], ['nama', 'label']) ?? '-';
            }
        }

        return $this->toString($row['nama_guru'] ?? '-');
    }

    protected function extractRombelName(array $row): string
    {
        if (isset($row['rombel'])) {
            return $this->getDisplayValue($row['rombel'], ['nama_rombel', 'nama', 'label']) ?? '-';
        }

        if (isset($row['jadwal']) && is_array($row['jadwal'])) {
            $jadwal = $this->arr($row['jadwal']);

            if (isset($jadwal['rombel'])) {
                return $this->getDisplayValue($jadwal['rombel'], ['nama_rombel', 'nama', 'label']) ?? '-';
            }

            if (!empty($jadwal['nama_rombel'])) {
                return $this->toString($jadwal['nama_rombel'], '-');
            }
        }

        return $this->toString($row['nama_rombel'] ?? '-');
    }

    protected function extractPembinaName(array $row): string
    {
        if (isset($row['pembina'])) {
            return $this->getDisplayValue($row['pembina'], ['nama', 'label']) ?? '-';
        }

        if (isset($row['guru'])) {
            return $this->getDisplayValue($row['guru'], ['nama', 'label']) ?? '-';
        }

        if (isset($row['pelatih'])) {
            return $this->getDisplayValue($row['pelatih'], ['nama', 'label']) ?? '-';
        }

        return $this->toString($row['pembina_nama'] ?? '-');
    }
}