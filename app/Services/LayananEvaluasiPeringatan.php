<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LayananEvaluasiPeringatan
{
    protected SiaClient $sia;

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /**
     * Ambil semua peringatan yang valid:
     * - nilai di bawah KKM
     * - alpa lebih dari 5
     */
    public function ambilSemuaPeringatan(): array
    {
        $periodeAktif = $this->ambilPeriodeAkademikAktif();

        return [
            'peringatan_nilai' => $this->ambilPeringatanNilai($periodeAktif),
            'peringatan_alpa' => $this->ambilPeringatanAlpa($periodeAktif),
        ];
    }

    /**
     * Evaluasi peringatan nilai di bawah KKM.
     */
    public function ambilPeringatanNilai(?array $periodeAktif = null): array
    {
        $hasil = [];
        $periodeAktif = $periodeAktif ?: $this->ambilPeriodeAkademikAktif();

        $daftarOrtu = $this->ambilDaftarUserOrtu();

        foreach ($daftarOrtu as $ortu) {
            $nis = trim((string) ($ortu->sia_user_id ?? ''));

            if ($nis === '') {
                continue;
            }

            $resNilai = $this->sia->getNilaiByNis($nis, $this->bangunFilterPeriode($periodeAktif));

            if (($resNilai['status'] ?? false) !== true) {
                Log::warning('[PERINGATAN][NILAI] Gagal mengambil nilai siswa', [
                    'nis' => $nis,
                    'ortu_id' => $ortu->id,
                    'periode_aktif' => $periodeAktif,
                    'response' => $resNilai,
                ]);
                continue;
            }

            $data = $this->toArray($resNilai['data'] ?? []);
            $dataSiswa = $this->toArray($data['siswa'] ?? []);
            $daftarNilai = collect($this->toList($data['nilai'] ?? []));

            if ($daftarNilai->isEmpty()) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Filter lokal tetap dilakukan sebagai pengaman.
            | Jika API SIA sudah memfilter berdasarkan periode, bagian ini tidak merusak.
            | Jika API mengabaikan filter, bagian ini mencegah nilai lama ikut diproses.
            |--------------------------------------------------------------------------
            */
            $daftarNilai = $this->filterNilaiBerdasarkanPeriode($daftarNilai, $periodeAktif);

            if ($daftarNilai->isEmpty()) {
                continue;
            }

            foreach ($daftarNilai as $itemNilai) {
                $itemNilai = $this->toArray($itemNilai);
                $peringatan = $this->bangunPeringatanNilai($ortu, $dataSiswa, $itemNilai, $periodeAktif);

                if ($peringatan) {
                    $hasil[] = $peringatan;
                }
            }
        }

        return $hasil;
    }

    /**
     * Evaluasi peringatan alpa lebih dari 5.
     */
    public function ambilPeringatanAlpa(?array $periodeAktif = null): array
    {
        $hasil = [];
        $periodeAktif = $periodeAktif ?: $this->ambilPeriodeAkademikAktif();

        $daftarOrtu = $this->ambilDaftarUserOrtu();

        foreach ($daftarOrtu as $ortu) {
            $nis = trim((string) ($ortu->sia_user_id ?? ''));

            if ($nis === '') {
                continue;
            }

            $resPresensi = $this->sia->getPresensiByNis($nis, $this->bangunFilterPeriode($periodeAktif));

            if (($resPresensi['status'] ?? false) !== true) {
                Log::warning('[PERINGATAN][ALPA] Gagal mengambil presensi siswa', [
                    'nis' => $nis,
                    'ortu_id' => $ortu->id,
                    'periode_aktif' => $periodeAktif,
                    'response' => $resPresensi,
                ]);
                continue;
            }

            $data = $this->toArray($resPresensi['data'] ?? []);
            $dataSiswa = $this->toArray($data['siswa'] ?? []);
            $daftarPresensi = collect($this->toList($data['presensi'] ?? []));

            if ($daftarPresensi->isEmpty()) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Filter lokal berdasarkan tahun ajaran aktif dan semester aktif.
            |--------------------------------------------------------------------------
            */
            $daftarPresensi = $this->filterPresensiBerdasarkanPeriode($daftarPresensi, $periodeAktif);

            if ($daftarPresensi->isEmpty()) {
                continue;
            }

            $totalAlpa = $daftarPresensi
                ->filter(function ($item) {
                    $item = $this->toArray($item);

                    $status = strtolower(trim((string) (
                        $item['status']
                        ?? $item['status_kehadiran']
                        ?? $item['keterangan']
                        ?? ''
                    )));

                    return in_array($status, ['alpa', 'alpha', 'a'], true);
                })
                ->count();

            if ($totalAlpa <= 5) {
                continue;
            }

            $hasil[] = $this->bangunPeringatanAlpa($ortu, $dataSiswa, $daftarPresensi, $totalAlpa, $periodeAktif);
        }

        return $hasil;
    }

    /**
     * Ambil user ortu aktif yang terhubung ke SIA.
     */
    protected function ambilDaftarUserOrtu(): Collection
    {
        return User::query()
            ->where('role', 'ortu')
            ->whereNotNull('sia_user_id')
            ->select('id', 'name', 'role', 'sia_user_id', 'siswa_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * Ambil tahun ajaran dan semester aktif dari API SIA.
     */
    protected function ambilPeriodeAkademikAktif(): array
    {
        $response = $this->sia->masterTahunAjaranAktif();

        if (($response['status'] ?? false) === true && !empty($response['data'])) {
            $row = $this->toArray($response['data']);

            return [
                'tahun_ajaran_id' => $this->ambilTahunAjaranId($row),
                'nama_tahun' => trim((string) ($row['nama_tahun'] ?? $row['nama'] ?? $row['tahun_ajaran'] ?? '')),
                'semester' => trim((string) ($row['semester'] ?? '')),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback: ambil dari daftar tahun ajaran dan cari status aktif.
        |--------------------------------------------------------------------------
        */
        $response = $this->sia->masterTahunAjaran();
        $rows = collect($this->toList($response['data'] ?? []));

        $aktif = $rows->first(function ($row) {
            $row = $this->toArray($row);
            return strtolower(trim((string) ($row['status'] ?? ''))) === 'aktif';
        });

        if ($aktif) {
            $aktif = $this->toArray($aktif);

            return [
                'tahun_ajaran_id' => $this->ambilTahunAjaranId($aktif),
                'nama_tahun' => trim((string) ($aktif['nama_tahun'] ?? $aktif['nama'] ?? $aktif['tahun_ajaran'] ?? '')),
                'semester' => trim((string) ($aktif['semester'] ?? '')),
            ];
        }

        Log::warning('[PERINGATAN] Tahun ajaran aktif tidak ditemukan dari API SIA.', [
            'response_aktif' => $response,
        ]);

        return [
            'tahun_ajaran_id' => null,
            'nama_tahun' => '',
            'semester' => '',
        ];
    }

    /**
     * Bangun filter periode untuk request API SIA.
     */
    protected function bangunFilterPeriode(array $periodeAktif): array
    {
        $filters = [];

        if (!empty($periodeAktif['tahun_ajaran_id'])) {
            $filters['tahun_ajaran_id'] = $periodeAktif['tahun_ajaran_id'];
        }

        if (!empty($periodeAktif['nama_tahun'])) {
            $filters['tahun_ajaran'] = $periodeAktif['nama_tahun'];
        }

        if (!empty($periodeAktif['semester'])) {
            $filters['semester'] = $periodeAktif['semester'];
        }

        return $filters;
    }

    /**
     * Bangun data peringatan nilai.
     */
    protected function bangunPeringatanNilai(object $ortu, array $dataSiswa, array $itemNilai, array $periodeAktif): ?array
    {
        $mapel = $this->toArray($itemNilai['mapel'] ?? []);
        $rombel = $this->toArray($itemNilai['rombel'] ?? ($dataSiswa['rombel'] ?? []));

        $mapelId = $mapel['id'] ?? $itemNilai['mapel_id'] ?? null;
        $namaMapel = trim((string) ($mapel['nama_mapel'] ?? $itemNilai['nama_mapel'] ?? '-'));
        $kkm = $this->ubahKeAngka($mapel['kkm'] ?? $itemNilai['kkm'] ?? null);
        $nilaiAkhir = $this->ambilNilaiAkhir($itemNilai);
        $nilaiId = $itemNilai['id'] ?? null;

        $tahunAjaranId = $this->ambilTahunAjaranId($itemNilai) ?: ($periodeAktif['tahun_ajaran_id'] ?? null);
        $namaTahun = $this->ambilNamaTahunAjaran($itemNilai) ?: ($periodeAktif['nama_tahun'] ?? '');
        $semester = $this->ambilSemester($itemNilai) ?: ($periodeAktif['semester'] ?? '');

        if ($mapelId === null || $nilaiId === null) {
            return null;
        }

        if ($kkm === null || $nilaiAkhir === null) {
            return null;
        }

        if ($nilaiAkhir >= $kkm) {
            return null;
        }

        $nis = trim((string) ($dataSiswa['nis'] ?? $ortu->sia_user_id ?? ''));
        $namaSiswa = trim((string) ($dataSiswa['nama'] ?? $ortu->name ?? 'Siswa'));
        $rombelNama = trim((string) ($rombel['nama_rombel'] ?? $dataSiswa['rombel_nama'] ?? '-'));

        $tahunKey = $tahunAjaranId ?: ($namaTahun ?: '-');
        $semesterKey = $semester ?: '-';

        return [
            'jenis_peringatan' => 'nilai_di_bawah_kkm',
            'kunci_peringatan' => 'nilai:' . $nis . ':' . $mapelId . ':' . $tahunKey . ':' . $semesterKey . ':' . $nilaiId,

            'nis' => $nis,
            'siswa_id' => $dataSiswa['id'] ?? $ortu->siswa_id ?? null,
            'ortu_id' => $ortu->id,

            'nama_siswa' => $namaSiswa,
            'rombel_nama' => $rombelNama,
            'mapel_id' => $mapelId,
            'nama_mapel' => $namaMapel,
            'semester' => $semesterKey,
            'tahun_ajaran_id' => $tahunAjaranId,
            'tahun_ajaran' => $namaTahun,
            'nilai_akhir' => $nilaiAkhir,
            'kkm' => $kkm,

            'snapshot_data' => [
                'nama_siswa' => $namaSiswa,
                'rombel' => $rombelNama,
                'nama_mapel' => $namaMapel,
                'nilai_akhir' => $nilaiAkhir,
                'kkm' => $kkm,
                'semester' => $semesterKey,
                'tahun_ajaran_id' => $tahunAjaranId,
                'tahun_ajaran' => $namaTahun,
                'nilai_id' => $nilaiId,
            ],
        ];
    }

    /**
     * Bangun data peringatan alpa.
     */
    protected function bangunPeringatanAlpa(
        object $ortu,
        array $dataSiswa,
        Collection $daftarPresensi,
        int $totalAlpa,
        array $periodeAktif
    ): array {
        $nis = trim((string) ($dataSiswa['nis'] ?? $ortu->sia_user_id ?? ''));
        $namaSiswa = trim((string) ($dataSiswa['nama'] ?? $ortu->name ?? 'Siswa'));

        $rombel = $this->toArray($dataSiswa['rombel'] ?? $dataSiswa['rombel_aktif'] ?? []);
        $rombelNama = trim((string) ($rombel['nama_rombel'] ?? $dataSiswa['rombel_nama'] ?? '-'));

        $tahunAjaranId = $this->ambilTahunAjaranIdDariPresensi($daftarPresensi) ?: ($periodeAktif['tahun_ajaran_id'] ?? null);
        $namaTahun = $this->ambilNamaTahunAjaranDariPresensi($daftarPresensi) ?: ($periodeAktif['nama_tahun'] ?? '');
        $semester = $this->ambilSemesterDariPresensi($daftarPresensi) ?: ($periodeAktif['semester'] ?? '');

        $tahunKey = $tahunAjaranId ?: ($namaTahun ?: '-');
        $semesterKey = $semester ?: '-';

        return [
            'jenis_peringatan' => 'alpa_lebih_dari_5',
            'kunci_peringatan' => 'alpa:' . $nis . ':lebih_dari_5:' . $tahunKey . ':' . $semesterKey,

            'nis' => $nis,
            'siswa_id' => $dataSiswa['id'] ?? $ortu->siswa_id ?? null,
            'ortu_id' => $ortu->id,

            'nama_siswa' => $namaSiswa,
            'rombel_nama' => $rombelNama,
            'semester' => $semesterKey,
            'tahun_ajaran_id' => $tahunAjaranId,
            'tahun_ajaran' => $namaTahun,
            'total_alpa' => $totalAlpa,

            'snapshot_data' => [
                'nama_siswa' => $namaSiswa,
                'rombel' => $rombelNama,
                'semester' => $semesterKey,
                'tahun_ajaran_id' => $tahunAjaranId,
                'tahun_ajaran' => $namaTahun,
                'total_alpa' => $totalAlpa,
            ],
        ];
    }

    /**
     * Filter nilai berdasarkan periode aktif.
     */
    protected function filterNilaiBerdasarkanPeriode(Collection $daftarNilai, array $periodeAktif): Collection
    {
        $tahunAktifId = trim((string) ($periodeAktif['tahun_ajaran_id'] ?? ''));
        $namaTahunAktif = trim((string) ($periodeAktif['nama_tahun'] ?? ''));
        $semesterAktif = trim((string) ($periodeAktif['semester'] ?? ''));

        return $daftarNilai
            ->filter(function ($item) use ($tahunAktifId, $namaTahunAktif, $semesterAktif) {
                $item = $this->toArray($item);

                $tahunItemId = trim((string) ($this->ambilTahunAjaranId($item) ?? ''));
                $namaTahunItem = trim((string) $this->ambilNamaTahunAjaran($item));
                $semesterItem = trim((string) $this->ambilSemester($item));

                $cocokTahun = true;
                $cocokSemester = true;

                if ($tahunAktifId !== '' && $tahunItemId !== '') {
                    $cocokTahun = $tahunAktifId === $tahunItemId;
                } elseif ($namaTahunAktif !== '' && $namaTahunItem !== '') {
                    $cocokTahun = $namaTahunAktif === $namaTahunItem;
                }

                if ($semesterAktif !== '' && $semesterItem !== '') {
                    $cocokSemester = $semesterAktif === $semesterItem;
                }

                return $cocokTahun && $cocokSemester;
            })
            ->values();
    }

    /**
     * Filter presensi berdasarkan periode aktif.
     */
    protected function filterPresensiBerdasarkanPeriode(Collection $daftarPresensi, array $periodeAktif): Collection
    {
        $tahunAktifId = trim((string) ($periodeAktif['tahun_ajaran_id'] ?? ''));
        $namaTahunAktif = trim((string) ($periodeAktif['nama_tahun'] ?? ''));
        $semesterAktif = trim((string) ($periodeAktif['semester'] ?? ''));

        return $daftarPresensi
            ->filter(function ($item) use ($tahunAktifId, $namaTahunAktif, $semesterAktif) {
                $item = $this->toArray($item);

                $tahunItemId = trim((string) ($this->ambilTahunAjaranId($item) ?? ''));
                $namaTahunItem = trim((string) $this->ambilNamaTahunAjaran($item));
                $semesterItem = trim((string) $this->ambilSemester($item));

                $cocokTahun = true;
                $cocokSemester = true;

                if ($tahunAktifId !== '' && $tahunItemId !== '') {
                    $cocokTahun = $tahunAktifId === $tahunItemId;
                } elseif ($namaTahunAktif !== '' && $namaTahunItem !== '') {
                    $cocokTahun = $namaTahunAktif === $namaTahunItem;
                }

                if ($semesterAktif !== '' && $semesterItem !== '') {
                    $cocokSemester = $semesterAktif === $semesterItem;
                }

                return $cocokTahun && $cocokSemester;
            })
            ->values();
    }

    /**
     * Ambil nilai akhir dari payload nilai.
     */
    protected function ambilNilaiAkhir(array $itemNilai): ?float
    {
        $kandidat = [
            $itemNilai['nilai_akhir'] ?? null,
            $itemNilai['nilai'] ?? null,
            $itemNilai['lm4_nilai'] ?? null,
            $itemNilai['lm3_nilai'] ?? null,
            $itemNilai['lm2_nilai'] ?? null,
            $itemNilai['lm1_nilai'] ?? null,
        ];

        foreach ($kandidat as $nilai) {
            $angka = $this->ubahKeAngka($nilai);
            if ($angka !== null) {
                return $angka;
            }
        }

        return null;
    }

    /**
     * Ambil tahun ajaran dari kumpulan presensi.
     */
    protected function ambilTahunAjaranIdDariPresensi(Collection $daftarPresensi): ?string
    {
        foreach ($daftarPresensi as $item) {
            $id = $this->ambilTahunAjaranId($this->toArray($item));

            if ($id !== null && trim((string) $id) !== '') {
                return (string) $id;
            }
        }

        return null;
    }

    protected function ambilNamaTahunAjaranDariPresensi(Collection $daftarPresensi): string
    {
        foreach ($daftarPresensi as $item) {
            $nama = $this->ambilNamaTahunAjaran($this->toArray($item));

            if (trim($nama) !== '') {
                return $nama;
            }
        }

        return '';
    }

    /**
     * Ambil semester dari kumpulan presensi.
     */
    protected function ambilSemesterDariPresensi(Collection $daftarPresensi): string
    {
        foreach ($daftarPresensi as $item) {
            $semester = $this->ambilSemester($this->toArray($item));

            if (trim($semester) !== '') {
                return $semester;
            }
        }

        return '';
    }

    /**
     * Ambil tahun ajaran ID dari payload nilai/presensi.
     */
    protected function ambilTahunAjaranId(array $item): ?string
    {
        $tahunAjaran = $this->toArray($item['tahun_ajaran'] ?? $item['tahunAjaran'] ?? []);

        $id = $item['tahun_ajaran_id']
            ?? $item['tahun_ajarans_id']
            ?? $item['ta_id']
            ?? $tahunAjaran['id']
            ?? null;

        if ($id === null || $id === '') {
            return null;
        }

        return (string) $id;
    }

    /**
     * Ambil nama tahun ajaran dari payload nilai/presensi.
     */
    protected function ambilNamaTahunAjaran(array $item): string
    {
        $tahunAjaran = $this->toArray($item['tahun_ajaran'] ?? $item['tahunAjaran'] ?? []);

        return trim((string) (
            $item['nama_tahun']
            ?? $item['tahun_ajaran_nama']
            ?? $item['tahun_ajaran_label']
            ?? $item['tahun']
            ?? $tahunAjaran['nama_tahun']
            ?? $tahunAjaran['nama']
            ?? ''
        ));
    }

    /**
     * Ambil semester dari payload nilai/presensi.
     */
    protected function ambilSemester(array $item): string
    {
        $tahunAjaran = $this->toArray($item['tahun_ajaran'] ?? $item['tahunAjaran'] ?? []);

        return trim((string) (
            $item['semester']
            ?? $item['semester_id']
            ?? $tahunAjaran['semester']
            ?? ''
        ));
    }

    /**
     * Ubah nilai campuran menjadi angka float.
     */
    protected function ubahKeAngka($nilai): ?float
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        if (is_numeric($nilai)) {
            return (float) $nilai;
        }

        $nilai = str_replace(',', '.', (string) $nilai);

        return is_numeric($nilai) ? (float) $nilai : null;
    }

    /**
     * Normalisasi array/list.
     */
    protected function toArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        return [];
    }

    protected function toList($value): array
    {
        if ($value instanceof Collection) {
            return $value->map(fn($item) => $this->toArray($item))->all();
        }

        if (is_array($value)) {
            return array_map(fn($item) => $this->toArray($item), $value);
        }

        if (is_object($value)) {
            return array_map(fn($item) => $this->toArray($item), (array) $value);
        }

        return [];
    }
}