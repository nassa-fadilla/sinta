<?php

namespace App\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use App\Services\SiaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SiaMasterController extends Controller
{
    protected SiaClient $sia;

    protected string $viewBase = 'kepsek.sia-master';

    public function __construct(SiaClient $sia)
    {
        $this->sia = $sia;
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER SISWA
    |--------------------------------------------------------------------------
    */

    public function siswaIndex(Request $request)
    {
        $q = trim((string) $request->q);
        $status = strtolower(trim((string) $request->status));

        $data = $this->resolveSiswaIndexData($q, $status);

        return view($this->view('siswa.index'), [
            'data' => $data,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function siswaShow($id)
    {
        $res = $this->sia->masterSiswaDetail($id);

        if (!$this->hasData($res)) {
            abort(404);
        }

        $siswa = $this->toObject($res['data']);

        return view($this->view('siswa.show'), compact('siswa'));
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER GURU
    |--------------------------------------------------------------------------
    */

    public function guruIndex(Request $request)
    {
        $q = trim((string) $request->q);
        $res = $this->sia->masterGuru($q);

        $data = $this->asArray($res['data'] ?? []);

        return view($this->view('guru.index'), compact('data', 'q'));
    }

    public function guruShow($id)
    {
        $guruRes = $this->sia->masterGuruDetail($id);

        if (!$this->hasData($guruRes)) {
            abort(404);
        }

        $guru = $this->toObject($guruRes['data']);

        $jadwalRes = $this->sia->masterJadwal([
            'guru' => $id,
        ]);

        $jadwalList = collect($this->asArray($jadwalRes['data'] ?? []));

        $rombel = $jadwalList
            ->map(function ($j) {
                $r = $this->extractRombelData($j);
                if (!$r || empty($r['id'])) {
                    return null;
                }

                return [
                    'id' => $r['id'] ?? null,
                    'nama_rombel' => $r['nama_rombel'] ?? '-',
                    'tingkat' => $r['tingkat'] ?? '-',
                ];
            })
            ->filter()
            ->unique('id')
            ->values()
            ->all();

        $mapel = $jadwalList
            ->map(function ($j) {
                $m = $this->extractMapelData($j);
                if (!$m || empty($m['id'])) {
                    return null;
                }

                return [
                    'id' => $m['id'] ?? null,
                    'nama_mapel' => $m['nama_mapel'] ?? '-',
                    'kelompok' => $m['kelompok'] ?? null,
                    'kkm' => $m['kkm'] ?? null,
                    'status' => $m['status'] ?? null,
                ];
            })
            ->filter()
            ->unique('id')
            ->values()
            ->all();

        return view($this->view('guru.show'), [
            'guru' => $guru,
            'mapel' => $mapel,
            'rombel' => $rombel,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER TAHUN AJARAN
    |--------------------------------------------------------------------------
    */

    public function tahunAjaranIndex()
    {
        $res = $this->sia->masterTahunAjaran();

        $tahunAjaran = collect($this->asArray($res['data'] ?? []))
            ->map(fn($r) => $this->toObject([
                'id' => $this->dataGet($r, 'id'),
                'nama_tahun' => $this->dataGet($r, 'nama_tahun', $this->dataGet($r, 'nama')),
                'semester' => $this->dataGet($r, 'semester'),
                'status' => $this->dataGet($r, 'status'),
                'tanggal_mulai' => $this->dataGet($r, 'tanggal_mulai'),
                'tanggal_selesai' => $this->dataGet($r, 'tanggal_selesai'),
            ]));

        return view($this->view('ta.index'), compact('tahunAjaran'));
    }

    public function tahunAjaranShow($id)
    {
        $res = $this->sia->masterTahunAjaranDetail($id);

        if (!$this->hasData($res)) {
            abort(404);
        }

        $ta = $this->toObject([
            'id' => $this->dataGet($res['data'], 'id'),
            'nama_tahun' => $this->dataGet($res['data'], 'nama_tahun', $this->dataGet($res['data'], 'nama')),
            'semester' => $this->dataGet($res['data'], 'semester'),
            'status' => $this->dataGet($res['data'], 'status'),
            'tanggal_mulai' => $this->dataGet($res['data'], 'tanggal_mulai'),
            'tanggal_selesai' => $this->dataGet($res['data'], 'tanggal_selesai'),
        ]);

        return view($this->view('ta.show'), compact('ta'));
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER MAPEL
    |--------------------------------------------------------------------------
    */

    public function mapelIndex(Request $request)
    {
        $q = trim((string) $request->q);
        $tingkat = trim((string) $request->tingkat);

        $res = $this->sia->masterMapel([
            'q' => $q,
            'tingkat' => $tingkat,
        ]);

        $data = collect($this->asArray($res['data'] ?? []))
            ->map(function ($row) {
                return [
                    'id' => $this->dataGet($row, 'id'),
                    'kode' => $this->dataGet($row, 'kode'),
                    'nama_mapel' => $this->dataGet($row, 'nama_mapel'),
                    'tingkat' => $this->dataGet($row, 'tingkat'),
                    'kelompok' => $this->dataGet($row, 'kelompok'),
                    'kkm' => $this->dataGet($row, 'kkm'),
                    'status' => $this->dataGet($row, 'status'),
                ];
            })
            ->values()
            ->all();

        return view($this->view('mapel.index'), compact('data', 'q', 'tingkat'));
    }

    public function mapelShow($id)
    {
        $res = $this->sia->masterMapelDetail($id);

        if (!$this->hasData($res)) {
            abort(404);
        }

        $raw = $res['data'];

        $mapel = $this->toObject([
            'id' => $this->dataGet($raw, 'id'),
            'kode' => $this->dataGet($raw, 'kode'),
            'nama_mapel' => $this->dataGet($raw, 'nama_mapel', '-'),
            'tingkat' => $this->dataGet($raw, 'tingkat', '-'),
            'kelompok' => $this->dataGet($raw, 'kelompok', '-'),
            'kkm' => $this->dataGet($raw, 'kkm', '-'),
            'status' => $this->dataGet($raw, 'status', '-'),
            'guru_pengajar' => collect($this->asArray($this->dataGet($raw, 'guru_pengajar', [])))
                ->map(fn($g) => [
                    'id' => $this->dataGet($g, 'id'),
                    'nama' => $this->dataGet($g, 'nama', '-'),
                    'nip' => $this->dataGet($g, 'nip'),
                    'nuptk' => $this->dataGet($g, 'nuptk'),
                ])
                ->values()
                ->all(),
        ]);

        return view($this->view('mapel.show'), compact('mapel'));
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER JADWAL
    |--------------------------------------------------------------------------
    */

    public function jadwalIndex(Request $request)
    {
        $res = $this->sia->masterJadwal([
            'q' => $request->q,
            'rombel' => $request->rombel,
            'guru' => $request->guru,
            'hari' => $request->hari,
        ]);

        $data = collect($this->asArray($res['data'] ?? []))
            ->map(fn($row) => $this->toFlatJadwalRow($row))
            ->values()
            ->all();

        return view($this->view('jadwal.index'), [
            'data' => $data,
            'q' => $request->q,
            'rombel' => $request->rombel,
            'guru' => $request->guru,
            'hari' => $request->hari,
        ]);
    }

    public function jadwalShow($id)
    {
        $res = $this->sia->masterJadwalDetail($id);

        if (!$this->hasData($res)) {
            abort(404);
        }

        $raw = $res['data'];

        $rombel = $this->extractRombelData($raw);
        $mapel = $this->extractMapelData($raw);
        $guru = $this->extractGuruData($raw);

        $jamMulai = $this->scalarOrNull($this->dataGet($raw, 'jam_mulai'));
        $jamSelesai = $this->scalarOrNull($this->dataGet($raw, 'jam_selesai'));

        $durasiJp = $this->dataGet($raw, 'durasi_jp');
        if (is_array($durasiJp) || is_object($durasiJp)) {
            $durasiJp = null;
        }

        if (is_null($durasiJp) && $jamMulai && $jamSelesai) {
            try {
                $start = \Illuminate\Support\Carbon::createFromFormat(
                    'H:i:s',
                    strlen($jamMulai) === 5 ? $jamMulai . ':00' : $jamMulai
                );
                $end = \Illuminate\Support\Carbon::createFromFormat(
                    'H:i:s',
                    strlen($jamSelesai) === 5 ? $jamSelesai . ':00' : $jamSelesai
                );
                $minutes = $start->diffInMinutes($end);
                $durasiJp = (string) max(1, (int) round($minutes / 45));
            } catch (\Throwable $e) {
                $durasiJp = '-';
            }
        }

        $ruangKelas = $this->extractRuangLabel($this->dataGet($raw, 'rombel', [])) ?: '-';
        $tahunAjaran = $this->extractTahunAjaranLabel($this->dataGet($raw, 'rombel', [])) ?: '-';

        $jadwal = $this->toObject([
            'id' => $this->dataGet($raw, 'id'),
            'hari' => $this->scalarOrDash($this->dataGet($raw, 'hari')),
            'jam_mulai' => $jamMulai ?: '-',
            'jam_selesai' => $jamSelesai ?: '-',
            'durasi_jp' => $this->scalarOrDash($durasiJp),
            'rombel' => $this->scalarOrDash(
                $rombel['nama_rombel'] ?? $this->dataGet($raw, 'rombel_nama', '-')
            ),
            'mapel' => $this->scalarOrDash(
                $mapel['nama_mapel'] ?? $this->dataGet($raw, 'nama_mapel', '-')
            ),
            'guru' => $this->scalarOrDash(
                $guru['nama'] ?? $this->dataGet($raw, 'nama_guru', '-')
            ),
            'tingkat' => $this->scalarOrDash(
                $rombel['tingkat'] ?? $this->dataGet($raw, 'tingkat', '-')
            ),
            'kelompok' => $this->scalarOrDash(
                $mapel['kelompok'] ?? $this->dataGet($raw, 'kelompok', '-')
            ),
            'kkm' => $this->scalarOrDash(
                $mapel['kkm'] ?? $this->dataGet($raw, 'kkm', '-')
            ),
            'status' => $this->scalarOrDash(
                $this->dataGet($raw, 'status', $mapel['status'] ?? '-')
            ),
            'nip' => $this->scalarOrDash(
                $guru['nip'] ?? $this->dataGet($raw, 'nip', '-')
            ),
            'nuptk' => $this->scalarOrDash(
                $guru['nuptk'] ?? $this->dataGet($raw, 'nuptk', '-')
            ),
            'ruang_kelas' => $ruangKelas,
            'tahun_ajaran' => $tahunAjaran,
            'rombel_id' => $rombel['id'] ?? $this->dataGet($raw, 'rombel_id'),
            'mapel_id' => $mapel['id'] ?? $this->dataGet($raw, 'mapel_id'),
            'guru_id' => $guru['id'] ?? $this->dataGet($raw, 'guru_id'),
        ]);

        return view($this->view('jadwal.show'), compact('jadwal'));
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER ROMBEL
    |--------------------------------------------------------------------------
    */

    public function rombelIndex(Request $request)
    {
        $q = trim((string) $request->q);

        $res = $this->sia->masterRombel($q, [
            'guru_id' => $request->guru_id,
            'aktif' => $request->aktif,
            'tahun_ajaran_id' => $request->tahun_ajaran_id,
            'tingkat' => $request->tingkat,
        ]);

        $rows = collect($this->asArray($res['data'] ?? []))
            ->map(function ($row) {
                $wali = $this->extractGuruData(
                    $this->dataGet($row, 'wali_kelas', $this->dataGet($row, 'guru'))
                );

                $ruang = $this->extractRuangLabel($row);
                $tahunAjaran = $this->extractTahunAjaranLabel($row);

                return [
                    'id' => $this->dataGet($row, 'id'),
                    'nama_rombel' => $this->dataGet($row, 'nama_rombel', '-'),
                    'tingkat' => $this->dataGet($row, 'tingkat', '-'),
                    'wali_kelas' => $wali['nama'] ?? '-',
                    'kapasitas' => $this->dataGet($row, 'kapasitas', '-'),
                    'ruang_kelas' => $ruang ?: '-',
                    'tahun_ajaran' => $tahunAjaran ?: '-',
                    'aktif' => (int) $this->dataGet($row, 'aktif', 0),
                ];
            })
            ->values()
            ->all();

        return view($this->view('rombel.index'), [
            'data' => $rows,
            'q' => $q,
        ]);
    }

    public function rombelShow($id)
    {
        $res = $this->sia->masterRombelDetail($id);

        if (!$this->hasData($res)) {
            abort(404);
        }

        $raw = $res['data'];

        $wali = $this->extractGuruData(
            $this->dataGet($raw, 'wali_kelas', $this->dataGet($raw, 'guru'))
        );

        $namaRombel = $this->dataGet($raw, 'nama_rombel', '-');

        $siswa = collect(
            $this->asArray(
                $this->dataGet($raw, 'siswa', $this->dataGet($raw, 'anggota', []))
            )
        )
            ->map(function ($s) use ($namaRombel) {
                $jkSource = $this->dataGet(
                    $s,
                    'jenis_kelamin',
                    $this->dataGet($s, 'jk', '-')
                );

                $jkRaw = is_scalar($jkSource)
                    ? strtoupper(trim((string) $jkSource))
                    : null;

                $jenisKelamin = match ($jkRaw) {
                    'L' => 'Laki-laki',
                    'P' => 'Perempuan',
                    default => $this->scalarOrDash($jkSource),
                };

                $statusAktif = $this->dataGet($s, 'aktif');
                $statusText = ((string) $statusAktif === '1') ? 'aktif' : 'aktif';

                return $this->toObject([
                    'id' => $this->dataGet($s, 'id'),
                    'nis' => $this->dataGet($s, 'nis', '-'),
                    'nama' => $this->dataGet($s, 'nama', '-'),
                    'jenis_kelamin' => $jenisKelamin,
                    'status' => strtolower((string) $this->dataGet($s, 'status', $statusText)),
                    'rombel' => $this->dataGet(
                        $s,
                        'rombel_nama',
                        $this->dataGet($s, 'nama_rombel', $namaRombel)
                    ),
                    'foto' => $this->dataGet($s, 'foto'),
                    'foto_url' => $this->dataGet($s, 'foto_url'),
                    'photo_url' => $this->dataGet($s, 'photo_url'),
                    'avatar' => $this->dataGet($s, 'avatar'),
                    'foto_src' => $this->resolveSiswaPhotoUrl($s),
                ]);
            })
            ->values();

        $jadwal = collect($this->asArray($this->dataGet($raw, 'jadwal', [])))
            ->map(fn($j) => $this->toObject($this->toFlatJadwalRow($j)))
            ->values();

        $rombel = $this->toObject([
            'id' => $this->dataGet($raw, 'id'),
            'nama_rombel' => $namaRombel,
            'tingkat' => $this->dataGet($raw, 'tingkat', '-'),
            'wali_kelas' => $wali['nama'] ?? '-',
            'kapasitas' => $this->dataGet($raw, 'kapasitas', '-'),
            'ruang_kelas' => $this->extractRuangLabel($raw) ?: '-',
            'tahun_ajaran' => $this->extractTahunAjaranLabel($raw) ?: '-',
            'aktif' => (int) $this->dataGet($raw, 'aktif', 0),
            'siswa' => $siswa,
            'jadwal' => $jadwal,
        ]);

        return view($this->view('rombel.show'), [
            'rombel' => $rombel,
        ]);
    }

    public function rombelAnggota($id)
    {
        $res = $this->sia->masterRombelAnggota($id);
        $data = $this->asArray($res['data'] ?? []);

        return view($this->view('rombel.anggota'), compact('data'));
    }

    public function rombelJadwal($id)
    {
        $res = $this->sia->masterRombelJadwal($id);
        $data = collect($this->asArray($res['data'] ?? []))
            ->map(fn($j) => $this->toFlatJadwalRow($j))
            ->values()
            ->all();

        return view($this->view('rombel.jadwal'), compact('data'));
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER PRESENSI INDEX
    |--------------------------------------------------------------------------
    */

    public function presensiIndex(Request $request)
    {
        $q = trim((string) $request->q);
        $statusFilter = strtolower(trim((string) $request->status));
        $mapelFilter = trim((string) $request->mapel);

        $data = [];
        $siswa = null;
        $mapelOptions = [];

        if ($q !== '') {
            [$nis, $siswa] = $this->resolveStudentFromQuery($q);

            if ($nis) {
                $res = $this->sia->getPresensiByNis($nis);

                if (($res['status'] ?? false) === true) {
                    $rows = collect($this->asArray($this->dataGet($res['data'], 'presensi', [])))
                        ->map(function ($row) {
                            return [
                                'tanggal' => $this->dataGet($row, 'tanggal', $this->dataGet($row, 'tgl', '-')),
                                'waktu_mulai' => $this->dataGet($row, 'waktu_mulai', $this->dataGet($row, 'jam_mulai')),
                                'rombel' => $this->extractRombelName($row),
                                'mapel' => $this->extractMapelName($row),
                                'status' => strtolower((string) $this->dataGet($row, 'status', '-')),
                                'dipindai_pada' => $this->dataGet($row, 'dipindai_pada', $this->dataGet($row, 'scanned_at', '-')),
                                'sesi_id' => $this->dataGet($row, 'sesi_id', $this->dataGet($row, 'id')),
                            ];
                        })
                        ->filter(function ($row) {
                            return !empty($row['mapel']) && trim((string) $row['mapel']) !== '-';
                        })
                        ->values();

                    $mapelOptions = $rows
                        ->pluck('mapel')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();

                    $grouped = $rows
                        ->groupBy(fn($row) => strtolower(trim((string) ($row['mapel'] ?? '-'))))
                        ->map(function ($group) {
                            $group = collect($group)
                                ->sortByDesc(function ($item) {
                                    return strtotime((string) ($item['tanggal'] ?? '1970-01-01'));
                                })
                                ->values();

                            $latest = $group->first();

                            return [
                                'mapel' => $latest['mapel'] ?? '-',
                                'rombel' => $latest['rombel'] ?? '-',
                                'tanggal_terakhir' => $latest['tanggal'] ?? '-',
                                'waktu_mulai' => $latest['waktu_mulai'] ?? '-',
                                'status_terakhir' => strtolower((string) ($latest['status'] ?? '-')),
                                'total_pertemuan' => $group->count(),
                                'hadir_count' => $group->where('status', 'hadir')->count(),
                                'izin_count' => $group->where('status', 'izin')->count(),
                                'sakit_count' => $group->where('status', 'sakit')->count(),
                                'alfa_count' => $group->where('status', 'alfa')->count(),
                                'sesi_id' => $latest['sesi_id'] ?? null,
                            ];
                        });

                    if ($statusFilter !== '') {
                        $grouped = $grouped->filter(function ($row) use ($statusFilter) {
                            return strtolower((string) ($row['status_terakhir'] ?? '')) === $statusFilter;
                        });
                    }

                    if ($mapelFilter !== '') {
                        $grouped = $grouped->filter(function ($row) use ($mapelFilter) {
                            return strtolower(trim((string) ($row['mapel'] ?? ''))) === strtolower(trim($mapelFilter));
                        });
                    }

                    $data = $grouped
                        ->sortBy('mapel')
                        ->values()
                        ->all();

                    $siswaData = $this->dataGet($res['data'], 'siswa');
                    if ($siswaData) {
                        $siswa = $this->normalizeStudentObject($siswaData);
                    }
                }

                if (!$siswa) {
                    $sRes = $this->sia->getSiswaByNis($nis);
                    if (($sRes['status'] ?? false) === true) {
                        $siswa = $this->normalizeStudentObject($sRes['data'] ?? []);
                    }
                }
            }
        }

        return view($this->view('presensi.index'), [
            'data' => $data,
            'q' => $q,
            'siswa' => $siswa,
            'statusFilter' => $statusFilter,
            'mapelFilter' => $mapelFilter,
            'mapelOptions' => $mapelOptions,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER PRESENSI DETAIL
    |--------------------------------------------------------------------------
    */

    public function presensiShow(Request $request, $sesiId)
    {
        $q = trim((string) $request->query('q', ''));
        $mapelFilter = trim((string) $request->query('mapel', ''));

        if ($q !== '' && $mapelFilter !== '') {
            [$nis, $siswa] = $this->resolveStudentFromQuery($q);

            if (!$nis) {
                abort(404);
            }

            $res = $this->sia->getPresensiByNis($nis);

            if (($res['status'] ?? false) !== true) {
                abort(404);
            }

            $rows = collect($this->asArray($this->dataGet($res['data'], 'presensi', [])))
                ->map(function ($row) {
                    $guru = $this->extractGuruData($row);

                    $waktuTutup = $this->dataGet($row, 'waktu_tutup', $this->dataGet($row, 'ditutup_pada'));
                    if (is_string($waktuTutup) && strlen($waktuTutup) >= 16) {
                        try {
                            $waktuTutup = \Illuminate\Support\Carbon::parse($waktuTutup)->format('H:i');
                        } catch (\Throwable $e) {
                            $waktuTutup = $this->dataGet($row, 'waktu_tutup', '-');
                        }
                    }

                    return $this->toObject([
                        'id' => $this->dataGet($row, 'id'),
                        'sesi_id' => $this->dataGet($row, 'sesi_id', $this->dataGet($row, 'id')),
                        'tanggal' => $this->dataGet($row, 'tanggal', $this->dataGet($row, 'tgl', '-')),
                        'waktu_mulai' => $this->dataGet($row, 'waktu_mulai', $this->dataGet($row, 'jam_mulai', '-')),
                        'waktu_tutup' => $this->scalarOrDash($waktuTutup),
                        'rombel' => $this->extractRombelName($row),
                        'mapel' => $this->extractMapelName($row),
                        'guru' => $guru['nama'] ?? '-',
                        'status' => strtolower((string) $this->dataGet($row, 'status', '-')),
                        'dipindai_pada' => $this->dataGet($row, 'dipindai_pada', $this->dataGet($row, 'scanned_at')),
                    ]);
                })
                ->filter(function ($row) use ($mapelFilter) {
                    return strtolower(trim((string) ($row->mapel ?? ''))) === strtolower(trim($mapelFilter));
                })
                ->sortByDesc(function ($row) {
                    return strtotime((string) (($row->tanggal ?? '1970-01-01') . ' ' . ($row->waktu_mulai ?? '00:00')));
                })
                ->values();

            if ($rows->isEmpty()) {
                abort(404);
            }

            $hadir = $rows->where('status', 'hadir')->count();
            $izin = $rows->where('status', 'izin')->count();
            $sakit = $rows->where('status', 'sakit')->count();
            $alfa = $rows->where('status', 'alfa')->count();

            $latest = $rows->first();

            $sesi = $this->toObject([
                'id' => $latest->sesi_id ?? $sesiId,
                'status' => $latest->status ?? '-',
                'mulai_pada' => null,
                'ditutup_pada' => null,
                'rombel' => $this->toObject([
                    'id' => null,
                    'nama_rombel' => $latest->rombel ?? '-',
                ]),
                'mapel' => $this->toObject([
                    'id' => null,
                    'nama_mapel' => $latest->mapel ?? $mapelFilter,
                ]),
                'guru' => $this->toObject([
                    'id' => null,
                    'nama' => $latest->guru ?? '-',
                ]),
                'rekap' => $this->toObject([
                    'hadir' => $hadir,
                    'alfa' => $alfa,
                    'sakit' => $sakit,
                    'izin' => $izin,
                ]),
                'siswa' => $siswa,
            ]);

            return view($this->view('presensi.show'), [
                'sesi' => $sesi,
                'presensi' => $rows,
                'isMapelDetail' => true,
                'q' => $q,
            ]);
        }

        $res = $this->sia->getPresensiSesi($sesiId);

        if (!$this->hasData($res)) {
            abort(404);
        }

        $d = $res['data'];

        $rombel = $this->extractRombelData($d);
        $mapel = $this->extractMapelData($d);
        $guru = $this->extractGuruData($d);
        $rekap = $this->asArray($this->dataGet($d, 'rekap', []));

        $sesi = $this->toObject([
            'id' => $this->dataGet($d, 'id'),
            'mulai_pada' => $this->dataGet($d, 'mulai_pada'),
            'ditutup_pada' => $this->dataGet($d, 'ditutup_pada'),
            'status' => $this->dataGet($d, 'status'),
            'rombel' => $this->toObject([
                'id' => $rombel['id'] ?? null,
                'nama_rombel' => $rombel['nama_rombel'] ?? '-',
            ]),
            'mapel' => $this->toObject([
                'id' => $mapel['id'] ?? null,
                'nama_mapel' => $mapel['nama_mapel'] ?? '-',
            ]),
            'guru' => $this->toObject([
                'id' => $guru['id'] ?? null,
                'nama' => $guru['nama'] ?? '-',
            ]),
            'rekap' => $this->toObject([
                'hadir' => (int) ($rekap['hadir'] ?? 0),
                'alfa' => (int) ($rekap['alfa'] ?? 0),
                'sakit' => (int) ($rekap['sakit'] ?? 0),
                'izin' => (int) ($rekap['izin'] ?? 0),
            ]),
        ]);

        $presensi = collect($this->asArray($this->dataGet($d, 'presensi', [])))
            ->map(function ($p) {
                $siswa = $this->extractSiswaData($p);

                return $this->toObject([
                    'id' => $this->dataGet($p, 'id'),
                    'nis' => $this->dataGet($p, 'nis', $siswa['nis'] ?? '-'),
                    'nama' => $this->dataGet($p, 'nama', $siswa['nama'] ?? '-'),
                    'status' => strtolower((string) $this->dataGet($p, 'status', '-')),
                    'dipindai_pada' => $this->dataGet($p, 'dipindai_pada', $this->dataGet($p, 'scanned_at')),
                ]);
            })
            ->values();

        return view($this->view('presensi.show'), [
            'sesi' => $sesi,
            'presensi' => $presensi,
            'isMapelDetail' => false,
            'q' => $q,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER NILAI INDEX
    |--------------------------------------------------------------------------
    */

    public function nilaiIndex(Request $request)
    {
        $q = trim((string) $request->q);
        $data = [];
        $siswa = null;

        $tahunAjaranRes = $this->sia->masterTahunAjaran();
        $tahunAjaranMap = collect($this->asArray($tahunAjaranRes['data'] ?? []))
            ->mapWithKeys(function ($ta) {
                $id = $this->dataGet($ta, 'id');
                $nama = $this->dataGet($ta, 'nama_tahun', $this->dataGet($ta, 'nama'));

                return [$id => $nama ?: '-'];
            });

        if ($q !== '') {
            [$nis, $siswa] = $this->resolveStudentFromQuery($q);

            if ($nis) {
                $res = $this->sia->getNilaiByNis($nis);

                if (($res['status'] ?? false) === true) {
                    $data = collect($this->asArray($this->dataGet($res['data'], 'nilai', [])))
                        ->map(function ($row) use ($tahunAjaranMap) {
                            $rombel = $this->extractRombelData($row);
                            $mapel = $this->extractMapelData($row);
                            $guru = $this->extractGuruData($row);

                            $tahunAjaranId = $this->dataGet($row, 'tahun_ajaran_id');
                            $tahunAjaranLabel = $tahunAjaranMap->get($tahunAjaranId, '-');

                            return [
                                'id' => $this->dataGet($row, 'id'),
                                'jadwal_id' => $this->dataGet($row, 'jadwal_id'),
                                'siswa_id' => $this->dataGet($row, 'siswa_id'),
                                'tahun_ajaran_id' => $tahunAjaranId,
                                'tahun_ajaran' => $tahunAjaranLabel,
                                'tahun_ajaran_label' => $tahunAjaranLabel,
                                'semester' => $this->dataGet($row, 'semester', '-'),
                                'rombel' => $rombel['nama_rombel'] ?? '-',
                                'mapel' => $mapel['nama_mapel'] ?? '-',
                                'guru' => $guru['nama'] ?? '-',
                                'lm1_tp1' => $this->dataGet($row, 'lm1_tp1'),
                                'lm1_tp2' => $this->dataGet($row, 'lm1_tp2'),
                                'lm1_tp3' => $this->dataGet($row, 'lm1_tp3'),
                                'lm1_tp4' => $this->dataGet($row, 'lm1_tp4'),
                                'lm1_nilai' => $this->dataGet($row, 'lm1_nilai'),
                                'lm2_tp1' => $this->dataGet($row, 'lm2_tp1'),
                                'lm2_tp2' => $this->dataGet($row, 'lm2_tp2'),
                                'lm2_tp3' => $this->dataGet($row, 'lm2_tp3'),
                                'lm2_tp4' => $this->dataGet($row, 'lm2_tp4'),
                                'lm2_nilai' => $this->dataGet($row, 'lm2_nilai'),
                                'lm3_tp1' => $this->dataGet($row, 'lm3_tp1'),
                                'lm3_tp2' => $this->dataGet($row, 'lm3_tp2'),
                                'lm3_tp3' => $this->dataGet($row, 'lm3_tp3'),
                                'lm3_tp4' => $this->dataGet($row, 'lm3_tp4'),
                                'lm3_nilai' => $this->dataGet($row, 'lm3_nilai'),
                                'lm4_tp1' => $this->dataGet($row, 'lm4_tp1'),
                                'lm4_tp2' => $this->dataGet($row, 'lm4_tp2'),
                                'lm4_tp3' => $this->dataGet($row, 'lm4_tp3'),
                                'lm4_tp4' => $this->dataGet($row, 'lm4_tp4'),
                                'lm4_nilai' => $this->dataGet($row, 'lm4_nilai'),
                                'nilai_akhir' => $this->dataGet($row, 'nilai_akhir'),
                                'status' => strtolower((string) $this->dataGet($row, 'status', 'tidak_tuntas')),
                                'status_penilaian' => strtolower((string) $this->dataGet($row, 'status_penilaian', 'draft')),
                            ];
                        })
                        ->values()
                        ->all();

                    $siswaData = $this->dataGet($res['data'], 'siswa');

                    if ($siswaData) {
                        $siswa = $this->normalizeStudentObject($siswaData);
                    }
                }

                if (!$siswa) {
                    $sRes = $this->sia->getSiswaByNis($nis);
                    if (($sRes['status'] ?? false) === true) {
                        $siswa = $this->normalizeStudentObject($sRes['data'] ?? []);
                    }
                }
            }
        }

        return view($this->view('nilai.index'), [
            'data' => $data,
            'q' => $q,
            'siswa' => $siswa,
        ]);
    }

    public function nilaiShow(Request $request, $nilaiId)
    {
        $q = trim((string) $request->query('q', ''));
        $siswa = null;
        $nilai = null;

        $tahunAjaranRes = $this->sia->masterTahunAjaran();
        $tahunAjaranMap = collect($this->asArray($tahunAjaranRes['data'] ?? []))
            ->mapWithKeys(function ($ta) {
                $id = $this->dataGet($ta, 'id');
                $nama = $this->dataGet($ta, 'nama_tahun', $this->dataGet($ta, 'nama'));

                return [$id => $nama ?: '-'];
            });

        if ($q !== '') {
            [$nis, $siswa] = $this->resolveStudentFromQuery($q);

            if ($nis) {
                $res = $this->sia->getNilaiByNis($nis);

                if (($res['status'] ?? false) === true) {
                    $rows = collect($this->asArray($this->dataGet($res['data'], 'nilai', [])));

                    $nilaiRow = $rows->first(function ($row) use ($nilaiId) {
                        return (string) $this->dataGet($row, 'id') === (string) $nilaiId;
                    });

                    $siswaData = $this->dataGet($res['data'], 'siswa');
                    if ($siswaData) {
                        $siswa = $this->normalizeStudentObject($siswaData);
                    }

                    if ($nilaiRow) {
                        $rombel = $this->extractRombelData($nilaiRow);
                        $mapel = $this->extractMapelData($nilaiRow);
                        $guru = $this->extractGuruData($nilaiRow);

                        $tahunAjaranId = $this->dataGet($nilaiRow, 'tahun_ajaran_id');
                        $tahunAjaranLabel = $tahunAjaranMap->get($tahunAjaranId, '-');

                        $nilai = $this->toObject([
                            'id' => $this->dataGet($nilaiRow, 'id'),
                            'tahun_ajaran_id' => $tahunAjaranId,
                            'tahun_ajaran' => $tahunAjaranLabel,
                            'semester' => $this->dataGet($nilaiRow, 'semester', '-'),
                            'rombel' => $rombel['nama_rombel'] ?? '-',
                            'mapel' => $mapel['nama_mapel'] ?? '-',
                            'guru' => $guru['nama'] ?? '-',
                            'lm1_tp1' => $this->dataGet($nilaiRow, 'lm1_tp1'),
                            'lm1_tp2' => $this->dataGet($nilaiRow, 'lm1_tp2'),
                            'lm1_tp3' => $this->dataGet($nilaiRow, 'lm1_tp3'),
                            'lm1_tp4' => $this->dataGet($nilaiRow, 'lm1_tp4'),
                            'lm1_nilai' => $this->dataGet($nilaiRow, 'lm1_nilai'),
                            'lm2_tp1' => $this->dataGet($nilaiRow, 'lm2_tp1'),
                            'lm2_tp2' => $this->dataGet($nilaiRow, 'lm2_tp2'),
                            'lm2_tp3' => $this->dataGet($nilaiRow, 'lm2_tp3'),
                            'lm2_tp4' => $this->dataGet($nilaiRow, 'lm2_tp4'),
                            'lm2_nilai' => $this->dataGet($nilaiRow, 'lm2_nilai'),
                            'lm3_tp1' => $this->dataGet($nilaiRow, 'lm3_tp1'),
                            'lm3_tp2' => $this->dataGet($nilaiRow, 'lm3_tp2'),
                            'lm3_tp3' => $this->dataGet($nilaiRow, 'lm3_tp3'),
                            'lm3_tp4' => $this->dataGet($nilaiRow, 'lm3_tp4'),
                            'lm3_nilai' => $this->dataGet($nilaiRow, 'lm3_nilai'),
                            'lm4_tp1' => $this->dataGet($nilaiRow, 'lm4_tp1'),
                            'lm4_tp2' => $this->dataGet($nilaiRow, 'lm4_tp2'),
                            'lm4_tp3' => $this->dataGet($nilaiRow, 'lm4_tp3'),
                            'lm4_tp4' => $this->dataGet($nilaiRow, 'lm4_tp4'),
                            'lm4_nilai' => $this->dataGet($nilaiRow, 'lm4_nilai'),
                            'nilai_akhir' => $this->dataGet($nilaiRow, 'nilai_akhir'),
                            'status' => strtolower((string) $this->dataGet($nilaiRow, 'status', 'tidak_tuntas')),
                            'status_penilaian' => strtolower((string) $this->dataGet($nilaiRow, 'status_penilaian', 'draft')),
                        ]);
                    }
                }
            }
        }

        if (!$nilai) {
            abort(404);
        }

        return view($this->view('nilai.show'), [
            'nilai' => $nilai,
            'siswa' => $siswa,
            'q' => $q,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MASTER EKSKUL
    |--------------------------------------------------------------------------
    */

    public function ekskulIndex(Request $request)
    {
        $res = $this->sia->masterEkskul([
            'q' => $request->q,
            'pembina' => $request->pembina,
            'hari' => $request->hari,
        ]);

        $data = collect($this->asArray($res['data'] ?? []))
            ->map(function ($row) {
                $pembina = $this->extractGuruData($this->dataGet($row, 'pembina'));

                return [
                    'id' => $this->dataGet($row, 'id'),
                    'nama' => $this->dataGet($row, 'nama', '-'),
                    'pembina' => $pembina['nama'] ?? '-',
                    'hari' => $this->scalarOrDash($this->dataGet($row, 'hari')),
                    'jam_mulai' => $this->scalarOrNull($this->dataGet($row, 'jam_mulai')),
                    'jam_selesai' => $this->scalarOrNull($this->dataGet($row, 'jam_selesai')),
                    'lokasi' => $this->scalarOrDash($this->dataGet($row, 'lokasi')),
                    'jumlah_anggota' => (int) $this->dataGet($row, 'jumlah_anggota', 0),
                ];
            })
            ->values()
            ->all();

        return view($this->view('ekskul.index'), [
            'data' => $data,
            'q' => $request->q,
            'pembina' => $request->pembina,
            'hari' => $request->hari,
        ]);
    }

    public function ekskulShow($id)
    {
        $res = $this->sia->masterEkskulDetail($id);

        if (!$this->hasData($res)) {
            abort(404);
        }

        $raw = $res['data'];

        $pembina = $this->extractGuruData($this->dataGet($raw, 'pembina'));

        $anggota = collect($this->asArray($this->dataGet($raw, 'anggota', [])))
            ->map(function ($row) {
                $siswa = $this->extractSiswaData($row);

                return [
                    'id' => $this->dataGet($row, 'id'),
                    'siswa_nis' => $this->scalarOrDash($this->dataGet($row, 'siswa_nis', $siswa['nis'] ?? '-')),
                    'siswa_nama' => $this->scalarOrDash($this->dataGet($row, 'siswa_nama', $siswa['nama'] ?? '-')),
                    'tahun_ajaran' => $this->extractTahunAjaranLabel($row) ?: '-',
                    'status' => strtolower((string) $this->scalarOrDash($this->dataGet($row, 'status', 'aktif'))),
                ];
            })
            ->values()
            ->all();

        $presensi = collect($this->asArray($this->dataGet($raw, 'presensi', [])))
            ->map(function ($row) {
                $siswa = $this->extractSiswaData($row);

                return [
                    'id' => $this->dataGet($row, 'id'),
                    'tanggal' => $this->scalarOrDash($this->dataGet($row, 'tanggal', '-')),
                    'siswa_nis' => $this->scalarOrDash($this->dataGet($row, 'siswa_nis', $siswa['nis'] ?? '-')),
                    'siswa_nama' => $this->scalarOrDash($this->dataGet($row, 'siswa_nama', $siswa['nama'] ?? '-')),
                    'status' => strtoupper((string) $this->scalarOrDash($this->dataGet($row, 'status', 'H'))),
                    'keterangan' => $this->normalizeKeteranganEkskul($row),
                ];
            })
            ->values()
            ->all();

        $ekskul = $this->toObject([
            'id' => $this->dataGet($raw, 'id'),
            'nama' => $this->scalarOrDash($this->dataGet($raw, 'nama', '-')),
            'hari' => $this->scalarOrDash($this->dataGet($raw, 'hari', '-')),
            'jam_mulai' => $this->scalarOrNull($this->dataGet($raw, 'jam_mulai')),
            'jam_selesai' => $this->scalarOrNull($this->dataGet($raw, 'jam_selesai')),
            'lokasi' => $this->scalarOrDash($this->dataGet($raw, 'lokasi', '-')),
            'pembina' => [
                'id' => $pembina['id'] ?? null,
                'nama' => $pembina['nama'] ?? '-',
            ],
            'anggota' => $anggota,
            'presensi' => $presensi,
        ]);

        return view($this->view('ekskul.show'), compact('ekskul'));
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    protected function view(string $path): string
    {
        return "{$this->viewBase}.{$path}";
    }

    protected function hasData(array $res): bool
    {
        return array_key_exists('data', $res) && !is_null($res['data']) && $res['data'] !== [];
    }

    protected function toObject($data): object
    {
        return (object) $this->asArray($data);
    }

    protected function asArray($data): array
    {
        if ($data instanceof Collection) {
            return $data->toArray();
        }

        if (is_array($data)) {
            return $data;
        }

        if (is_object($data)) {
            return (array) $data;
        }

        return [];
    }

    protected function dataGet($target, string $key, $default = null)
    {
        if (is_array($target)) {
            return data_get($target, $key, $default);
        }

        if (is_object($target)) {
            return data_get((array) $target, $key, $default);
        }

        return $default;
    }

    protected function normalizeStudentObject($data): ?object
    {
        if (!$data) {
            return null;
        }

        $data = $this->asArray($data);

        return $this->toObject([
            'id' => $this->dataGet($data, 'id'),
            'nis' => $this->dataGet($data, 'nis'),
            'nisn' => $this->dataGet($data, 'nisn'),
            'nama' => $this->dataGet($data, 'nama'),
            'rombel_nama' => $this->extractRombelName($data),
            'foto' => $this->dataGet($data, 'foto'),
            'foto_url' => $this->dataGet($data, 'foto_url'),
            'photo_url' => $this->dataGet($data, 'photo_url'),
            'avatar' => $this->dataGet($data, 'avatar'),
            'foto_src' => $this->resolveSiswaPhotoUrl($data),
        ]);
    }

    protected function extractRombelData($row): ?array
    {
        $rombel = $this->dataGet($row, 'rombel');

        if (is_array($rombel)) {
            return $rombel;
        }

        if (is_object($rombel)) {
            return (array) $rombel;
        }

        return [
            'id' => $this->dataGet($row, 'rombel_id'),
            'nama_rombel' => $this->dataGet($row, 'nama_rombel', $this->dataGet($row, 'rombel')),
            'tingkat' => $this->dataGet($row, 'tingkat'),
        ];
    }

    protected function extractMapelData($row): ?array
    {
        $mapel = $this->dataGet($row, 'mapel', $this->dataGet($row, 'mata_pelajaran'));

        if (is_array($mapel)) {
            return $mapel;
        }

        if (is_object($mapel)) {
            return (array) $mapel;
        }

        return [
            'id' => $this->dataGet($row, 'mapel_id', $this->dataGet($row, 'mata_pelajaran_id')),
            'nama_mapel' => $this->dataGet($row, 'nama_mapel', is_string($mapel) ? $mapel : '-'),
            'kelompok' => $this->dataGet($row, 'kelompok'),
            'kkm' => $this->dataGet($row, 'kkm'),
            'status' => $this->dataGet($row, 'status'),
        ];
    }

    protected function extractGuruData($row): ?array
    {
        if (is_array($row)) {
            if (array_key_exists('nama', $row) || array_key_exists('nip', $row) || array_key_exists('nuptk', $row)) {
                return [
                    'id' => $row['id'] ?? null,
                    'nama' => $this->scalarOrDash($row['nama'] ?? '-'),
                    'nip' => $this->scalarOrNull($row['nip'] ?? null),
                    'nuptk' => $this->scalarOrNull($row['nuptk'] ?? null),
                ];
            }

            $guru = $row['guru'] ?? null;

            if (is_array($guru)) {
                return [
                    'id' => $guru['id'] ?? ($row['guru_id'] ?? null),
                    'nama' => $this->scalarOrDash($guru['nama'] ?? $row['nama_guru'] ?? $row['wali_kelas'] ?? '-'),
                    'nip' => $this->scalarOrNull($guru['nip'] ?? $row['nip'] ?? null),
                    'nuptk' => $this->scalarOrNull($guru['nuptk'] ?? $row['nuptk'] ?? null),
                ];
            }

            if (is_object($guru)) {
                $guru = (array) $guru;

                return [
                    'id' => $guru['id'] ?? ($row['guru_id'] ?? null),
                    'nama' => $this->scalarOrDash($guru['nama'] ?? $row['nama_guru'] ?? $row['wali_kelas'] ?? '-'),
                    'nip' => $this->scalarOrNull($guru['nip'] ?? $row['nip'] ?? null),
                    'nuptk' => $this->scalarOrNull($guru['nuptk'] ?? $row['nuptk'] ?? null),
                ];
            }

            return [
                'id' => $row['guru_id'] ?? null,
                'nama' => $this->scalarOrDash($row['nama_guru'] ?? $row['wali_kelas'] ?? '-'),
                'nip' => $this->scalarOrNull($row['nip'] ?? null),
                'nuptk' => $this->scalarOrNull($row['nuptk'] ?? null),
            ];
        }

        if (is_object($row)) {
            return $this->extractGuruData((array) $row);
        }

        if (is_string($row)) {
            return [
                'id' => null,
                'nama' => $row,
                'nip' => null,
                'nuptk' => null,
            ];
        }

        return [
            'id' => null,
            'nama' => '-',
            'nip' => null,
            'nuptk' => null,
        ];
    }

    protected function extractSiswaData($row): ?array
    {
        $siswa = $this->dataGet($row, 'siswa');

        if (is_array($siswa)) {
            return $siswa;
        }

        if (is_object($siswa)) {
            return (array) $siswa;
        }

        return [
            'id' => $this->dataGet($row, 'siswa_id'),
            'nis' => $this->dataGet($row, 'nis', $this->dataGet($row, 'siswa_nis')),
            'nama' => $this->dataGet($row, 'nama', $this->dataGet($row, 'siswa_nama')),
            'jk' => $this->dataGet($row, 'jk', $this->dataGet($row, 'jenis_kelamin')),
            'aktif' => $this->dataGet($row, 'aktif'),
            'status_pilihan' => $this->dataGet($row, 'status_pilihan'),
            'foto' => $this->dataGet($row, 'foto'),
            'foto_url' => $this->dataGet($row, 'foto_url'),
            'photo_url' => $this->dataGet($row, 'photo_url'),
            'avatar' => $this->dataGet($row, 'avatar'),
        ];
    }

    protected function extractRombelName($row): string
    {
        $rombel = $this->extractRombelData($row);

        return $this->scalarOrDash($rombel['nama_rombel'] ?? $this->dataGet($row, 'rombel_nama', '-'));
    }

    protected function extractMapelName($row): string
    {
        $mapel = $this->extractMapelData($row);

        return $this->scalarOrDash($mapel['nama_mapel'] ?? '-');
    }

    protected function extractGuruName($row): string
    {
        $guru = $this->extractGuruData($row);

        return $this->scalarOrDash($guru['nama'] ?? '-');
    }

    protected function extractTahunAjaranLabel($row): ?string
    {
        $ta = $this->dataGet($row, 'tahun_ajaran');

        if (is_string($ta)) {
            return $ta;
        }

        if (is_array($ta) || is_object($ta)) {
            $nama = $this->dataGet($ta, 'nama_tahun', $this->dataGet($ta, 'nama'));
            $semester = $this->dataGet($ta, 'semester');

            if ($nama && $semester) {
                return $nama . ' (' . $semester . ')';
            }

            return is_string($nama) ? $nama : null;
        }

        $nama = $this->dataGet($row, 'nama_tahun', $this->dataGet($row, 'tahun_ajaran_nama'));
        $semester = $this->dataGet($row, 'semester');

        if ($nama && $semester) {
            return $nama . ' (' . $semester . ')';
        }

        return is_string($nama) ? $nama : null;
    }

    protected function extractRuangLabel($row): ?string
    {
        $ruang = $this->dataGet($row, 'ruang_kelas', $this->dataGet($row, 'ruang'));

        if (is_string($ruang) && trim($ruang) !== '') {
            return trim($ruang);
        }

        if (is_array($ruang) || is_object($ruang)) {
            $label = $this->dataGet(
                $ruang,
                'nama',
                $this->dataGet(
                    $ruang,
                    'nama_ruang',
                    $this->dataGet($ruang, 'kode', $this->dataGet($ruang, 'label'))
                )
            );

            if (is_scalar($label) && trim((string) $label) !== '') {
                return trim((string) $label);
            }
        }

        $fallback = $this->dataGet(
            $row,
            'ruang_kelas_nama',
            $this->dataGet(
                $row,
                'ruang_nama',
                $this->dataGet(
                    $row,
                    'nama_ruang',
                    $this->dataGet(
                        $row,
                        'ruang_label',
                        $this->dataGet($row, 'ruang_kelas_label')
                    )
                )
            )
        );

        if (is_scalar($fallback) && trim((string) $fallback) !== '') {
            return trim((string) $fallback);
        }

        return null;
    }

    protected function normalizeKeteranganEkskul($row): string
    {
        $ket = $this->dataGet($row, 'keterangan');

        if (is_string($ket) && trim($ket) !== '') {
            return $ket;
        }

        if (is_array($ket)) {
            $parts = array_filter(array_map(function ($item) {
                if (is_scalar($item)) {
                    return (string) $item;
                }
                return null;
            }, $ket));

            return !empty($parts) ? implode(', ', $parts) : '-';
        }

        if (is_object($ket)) {
            $ket = (array) $ket;
            $parts = array_filter(array_map(function ($item) {
                if (is_scalar($item)) {
                    return (string) $item;
                }
                return null;
            }, $ket));

            return !empty($parts) ? implode(', ', $parts) : '-';
        }

        return '-';
    }

    protected function scalarOrDash($value): string
    {
        if (is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }

        return '-';
    }

    protected function scalarOrNull($value): ?string
    {
        if (is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }

        return null;
    }

    protected function toFlatJadwalRow($j): array
    {
        $rombel = $this->extractRombelData($j);
        $mapel = $this->extractMapelData($j);
        $guru = $this->extractGuruData($j);

        return [
            'id' => $this->dataGet($j, 'id'),
            'hari' => $this->scalarOrDash($this->dataGet($j, 'hari')),
            'jam_mulai' => $this->scalarOrNull($this->dataGet($j, 'jam_mulai')),
            'jam_selesai' => $this->scalarOrNull($this->dataGet($j, 'jam_selesai')),
            'rombel' => $this->scalarOrDash($rombel['nama_rombel'] ?? '-'),
            'mapel' => $this->scalarOrDash($mapel['nama_mapel'] ?? '-'),
            'guru' => $this->scalarOrDash($guru['nama'] ?? '-'),
        ];
    }

    protected function normalizeTanggalPresensi($row): string
    {
        return (string) (
            $this->dataGet($row, 'tanggal')
            ?? $this->dataGet($row, 'tgl')
            ?? ($this->dataGet($row, 'mulai_pada')
                ? date('Y-m-d', strtotime($this->dataGet($row, 'mulai_pada')))
                : '-')
        );
    }

    /**
     * @return array{0: string|null, 1: object|null}
     */
    protected function resolveStudentFromQuery(string $q): array
    {
        $q = trim($q);

        if ($q === '') {
            return [null, null];
        }

        if (ctype_digit($q)) {
            try {
                $byNisRes = $this->sia->getSiswaByNis($q);
                $byNisData = $this->asArray($byNisRes['data'] ?? []);

                if (!empty($byNisData)) {
                    $nis = $this->dataGet($byNisData, 'nis', $q);

                    return [
                        $nis,
                        $this->normalizeStudentObject($byNisData),
                    ];
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        try {
            $listRes = $this->sia->masterSiswa($q, [
                'per_page' => 10,
            ]);

            $list = collect($this->extractListFromResponse($listRes));

            if ($list->isNotEmpty()) {
                $matched = $list->first(function ($row) use ($q) {
                    return $this->studentRowMatchesQuery($row, $q, true);
                });

                $picked = $matched ?: $list->first();

                if ($picked) {
                    $nis = $this->dataGet($picked, 'nis');

                    return [
                        $nis,
                        $nis ? $this->normalizeStudentObject($picked) : null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $allRes = $this->sia->masterSiswa('', [
                'per_page' => 1000,
            ]);

            $allRows = collect($this->extractListFromResponse($allRes));

            $matched = $allRows->first(function ($row) use ($q) {
                return $this->studentRowMatchesQuery($row, $q, true);
            });

            if ($matched) {
                $nis = $this->dataGet($matched, 'nis');

                return [
                    $nis,
                    $nis ? $this->normalizeStudentObject($matched) : null,
                ];
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return [null, null];
    }

    protected function resolveSiswaIndexData(string $q = '', string $status = ''): array
    {
        $rows = collect();

        if ($q !== '' && ctype_digit($q)) {
            try {
                $byNisRes = $this->sia->getSiswaByNis($q);
                $byNisData = $this->asArray($byNisRes['data'] ?? []);

                if (!empty($byNisData)) {
                    $rows->push($byNisData);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        try {
            $res = $this->sia->masterSiswa($q, [
                'per_page' => 100,
            ]);

            $rows = $rows->merge($this->extractListFromResponse($res));
        } catch (\Throwable $e) {
            report($e);
        }

        if ($q !== '') {
            $hasMatch = $rows->contains(function ($row) use ($q) {
                return $this->studentRowMatchesQuery($row, $q, false);
            });

            if (!$hasMatch) {
                try {
                    $allRes = $this->sia->masterSiswa('', [
                        'per_page' => 1000,
                    ]);

                    $rows = $rows->merge($this->extractListFromResponse($allRes));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return $rows
            ->filter(fn($row) => is_array($row) || is_object($row))
            ->map(fn($row) => $this->normalizeSiswaIndexRow($row))
            ->filter(function ($row) use ($q) {
                if ($q === '') {
                    return true;
                }

                return $this->studentRowMatchesQuery($row, $q, false);
            })
            ->filter(function ($row) use ($status) {
                if ($status === '' || $status === 'semua' || $status === 'all') {
                    return true;
                }

                return strtolower((string) ($row['status'] ?? '')) === $status;
            })
            ->unique(function ($row) {
                return (string) ($row['id'] ?? '') . '|'
                    . (string) ($row['nis'] ?? '') . '|'
                    . (string) ($row['nisn'] ?? '');
            })
            ->sortBy('nama')
            ->values()
            ->all();
    }

    protected function normalizeSiswaIndexRow($row): array
    {
        $row = $this->asArray($row);

        $jkRaw = strtoupper(trim((string) (
            $this->dataGet($row, 'jk')
            ?? $this->dataGet($row, 'jenis_kelamin')
            ?? '-'
        )));

        $jk = match ($jkRaw) {
            'L', 'LAKI', 'LAKI-LAKI', 'LAKILAKI', 'MALE', 'M' => 'L',
            'P', 'PEREMPUAN', 'WANITA', 'FEMALE', 'F' => 'P',
            default => $this->scalarOrDash($jkRaw),
        };

        $statusRaw = strtolower(trim((string) (
            $this->dataGet($row, 'status')
            ?? $this->dataGet($row, 'aktif')
            ?? 'aktif'
        )));

        $status = match ($statusRaw) {
            '1', 'aktif', 'active' => 'aktif',
            '0', 'nonaktif', 'tidak aktif', 'inactive' => 'nonaktif',
            default => $statusRaw !== '' ? $statusRaw : 'aktif',
        };

        $fotoRaw = $this->pickPhotoValue($row);
        $fotoSrc = $this->resolveSiswaPhotoUrl($row);

        return [
            'id' => $this->dataGet($row, 'id'),
            'nama' => $this->scalarOrDash(
                $this->dataGet($row, 'nama', $this->dataGet($row, 'nama_siswa', '-'))
            ),
            'nis' => $this->scalarOrDash($this->dataGet($row, 'nis', '-')),
            'nisn' => $this->scalarOrDash($this->dataGet($row, 'nisn', '-')),
            'jk' => $jk,
            'jenis_kelamin' => $jk,
            'status' => $status,
            'rombel_nama' => $this->extractRombelName($row),

            /*
            |--------------------------------------------------------------------------
            | Field foto dikirim lengkap agar blade index tetap kompatibel.
            |--------------------------------------------------------------------------
            | Beberapa blade biasanya membaca $s->foto, $s->foto_url, $s->photo_url,
            | atau $s->foto_src. Semua disediakan supaya thumbnail tidak hilang.
            */
            'foto' => $fotoRaw,
            'foto_url' => $this->dataGet($row, 'foto_url'),
            'photo_url' => $this->dataGet($row, 'photo_url'),
            'avatar' => $this->dataGet($row, 'avatar'),
            'foto_siswa' => $this->dataGet($row, 'foto_siswa'),
            'foto_src' => $fotoSrc,
        ];
    }

    protected function studentRowMatchesQuery($row, string $q, bool $exactForNumeric = false): bool
    {
        $row = $this->asArray($row);
        $q = trim($q);

        if ($q === '') {
            return true;
        }

        $qLower = mb_strtolower($q);

        $nis = trim((string) $this->dataGet($row, 'nis', ''));
        $nisn = trim((string) $this->dataGet($row, 'nisn', ''));
        $nama = trim((string) $this->dataGet($row, 'nama', $this->dataGet($row, 'nama_siswa', '')));

        if ($exactForNumeric && ctype_digit($q)) {
            return $nis === $q || $nisn === $q;
        }

        return str_contains(mb_strtolower($nis), $qLower)
            || str_contains(mb_strtolower($nisn), $qLower)
            || str_contains(mb_strtolower($nama), $qLower);
    }

    protected function extractListFromResponse($response): array
    {
        if (!is_array($response)) {
            return [];
        }

        $data = $response['data'] ?? [];

        if ($data instanceof Collection) {
            return $data->toArray();
        }

        if (!is_array($data)) {
            return [];
        }

        if (array_is_list($data)) {
            return $data;
        }

        if (isset($data['data']) && is_array($data['data'])) {
            return array_is_list($data['data'])
                ? $data['data']
                : array_values($data['data']);
        }

        if (isset($data['items']) && is_array($data['items'])) {
            return array_is_list($data['items'])
                ? $data['items']
                : array_values($data['items']);
        }

        if (isset($data['rows']) && is_array($data['rows'])) {
            return array_is_list($data['rows'])
                ? $data['rows']
                : array_values($data['rows']);
        }

        return [$data];
    }

    protected function pickPhotoValue($row): ?string
    {
        $row = $this->asArray($row);

        $candidates = [
            $this->dataGet($row, 'foto_url'),
            $this->dataGet($row, 'photo_url'),
            $this->dataGet($row, 'avatar'),
            $this->dataGet($row, 'foto'),
            $this->dataGet($row, 'foto_siswa'),
            $this->dataGet($row, 'photo'),
            $this->dataGet($row, 'gambar'),
            $this->dataGet($row, 'image'),
        ];

        foreach ($candidates as $value) {
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    protected function resolveSiswaPhotoUrl($row): ?string
    {
        $foto = $this->pickPhotoValue($row);

        if (!$foto) {
            return null;
        }

        $foto = trim((string) $foto);

        if ($foto === '' || $foto === '-') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $foto)) {
            return $foto;
        }

        $foto = str_replace('\\', '/', $foto);
        $foto = preg_replace('#/+#', '/', $foto);
        $foto = ltrim($foto, '/');

        $basename = basename($foto);

        $localCandidates = [
            $foto,
            'sia/' . $foto,
            'foto_siswa/' . $basename,
            'sia/foto_siswa/' . $basename,
            'storage/' . $foto,
            'storage/foto_siswa/' . $basename,
            'storage/sia/foto_siswa/' . $basename,
        ];

        foreach (array_unique($localCandidates) as $relativePath) {
            if (is_file(public_path($relativePath))) {
                return asset($relativePath);
            }
        }

        $fotoForStorage = preg_replace('#^storage/#', '', $foto);

        $baseUrl = rtrim((string) config('services.sia.base_url'), '/');

        if ($baseUrl !== '') {
            return $baseUrl . '/storage/' . $fotoForStorage;
        }

        return null;
    }
}