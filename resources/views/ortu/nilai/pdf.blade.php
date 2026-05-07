<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai Siswa</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            margin: 34px 40px;
            color: #000;
        }

        .kop {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 18px;
            overflow: hidden;
        }

        .kop img.logo {
            float: left;
            width: 68px;
            height: auto;
            display: block;
            margin-top: 2px;
        }

        .kop .text {
            margin-left: 84px;
            text-align: center;
            padding-top: 1px;
        }

        .kop .text h4 {
            margin: 0;
            font-size: 11px;
            font-weight: bold;
        }

        .kop .text h3 {
            margin: 2px 0;
            font-size: 13px;
            font-weight: bold;
        }

        .kop .text h2 {
            margin: 2px 0;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.4px;
        }

        .kop .text p {
            margin: 2px 0 0;
            font-size: 10px;
        }

        .judul {
            text-align: center;
            margin-bottom: 16px;
        }

        .judul .nama-dokumen {
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 3px;
        }

        .judul .sub {
            font-size: 11px;
        }

        .pengantar {
            margin-bottom: 12px;
            text-align: justify;
        }

        .pengantar p {
            margin: 0;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .meta td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .meta td.label {
            width: 120px;
        }

        .meta td.titik {
            width: 10px;
        }

        table.nilai {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 6px;
        }

        table.nilai th,
        table.nilai td {
            border: 1px solid #000;
            padding: 4px 4px;
            font-size: 9px;
            word-wrap: break-word;
        }

        table.nilai th {
            text-align: center;
            font-weight: bold;
            background: #f2f2f2;
        }

        table.nilai td.center {
            text-align: center;
        }

        table.nilai td.right {
            text-align: right;
        }

        table.nilai td.mapel {
            font-size: 9px;
        }

        .ringkasan {
            margin-top: 12px;
            width: 100%;
            border-collapse: collapse;
        }

        .ringkasan td {
            padding: 2px 4px;
            font-size: 10px;
        }

        .ringkasan td.label {
            width: 130px;
        }

        .ringkasan td.titik {
            width: 10px;
        }

        .ttd {
            position: relative;
            margin-top: 32px;
            text-align: right;
            height: 185px;
        }

        .ttd .tanggal {
            margin-right: 14px;
            margin-bottom: 4px;
        }

        .ttd .jabatan {
            margin-right: 14px;
            font-weight: bold;
        }

        .ttd img.cap {
            position: absolute;
            right: 76px;
            top: 32px;
            width: 105px;
            opacity: 0.22;
            z-index: 1;
        }

        .ttd img.ttd {
            position: absolute;
            right: 45px;
            top: 48px;
            width: 135px;
            z-index: 2;
            opacity: 0.9;
        }

        .ttd .nama {
            font-weight: bold;
            text-decoration: underline;
            margin-right: 8px;
            margin-top: 128px;
            position: relative;
            z-index: 3;
        }

        .ttd .nip {
            margin-right: 8px;
            position: relative;
            z-index: 3;
        }

        .catatan {
            margin-top: 8px;
            font-size: 9px;
            color: #333;
            text-align: left;
        }
    </style>
</head>

<body>
    {{-- KOP SURAT --}}
    <div class="kop">
        <img src="file://{{ public_path('images/logo-sma2.png') }}" class="logo" alt="Logo SMAN 2 Temanggung">

        <div class="text">
            <h4>PEMERINTAH PROVINSI JAWA TENGAH</h4>
            <h2>SMAN 2 TEMANGGUNG</h2>
            <h3>SISTEM INFORMASI MONITORING AKTIVITAS SISWA (SINTA)</h3>
            <p>Jl. Jenderal Sudirman No.27, Temanggung, Jawa Tengah</p>
        </div>
    </div>

    {{-- JUDUL --}}
    <div class="judul">
        <div class="nama-dokumen">REKAP NILAI SISWA</div>
        <div class="sub">Dokumen Monitoring Akademik Siswa</div>
    </div>

    {{-- PENGANTAR --}}
    <div class="pengantar">
        <p>
            Dokumen ini merupakan rekap nilai siswa yang dihasilkan melalui Sistem Informasi Monitoring Aktivitas
            Siswa (SINTA) berdasarkan data akademik yang terintegrasi dengan Sistem Informasi Akademik (SIA)
            SMA Negeri 2 Temanggung.
        </p>
    </div>

    {{-- IDENTITAS --}}
    <table class="meta">
        <tr>
            <td class="label">Nama Siswa</td>
            <td class="titik">:</td>
            <td>{{ $siswa->nama ?? $siswa->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIS</td>
            <td class="titik">:</td>
            <td>{{ $siswa->nis ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kelas / Rombel</td>
            <td class="titik">:</td>
            <td>{{ $rombelName ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Wali Kelas</td>
            <td class="titik">:</td>
            <td>{{ $waliKelasName ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tahun Ajaran</td>
            <td class="titik">:</td>
            <td>{{ $infoTahunAjaran ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Semester</td>
            <td class="titik">:</td>
            <td>{{ $semesterAktif ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Cetak</td>
            <td class="titik">:</td>
            <td>{{ $today->translatedFormat('d F Y H:i') }} WIB</td>
        </tr>
    </table>

    {{-- TABEL NILAI --}}
    <table class="nilai">
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">No</th>
                <th rowspan="2" style="width: 18%;">Mata Pelajaran</th>
                <th rowspan="2" style="width: 4%;">KKM</th>

                <th colspan="4" style="width: 12%;">LM 1</th>
                <th colspan="4" style="width: 12%;">LM 2</th>
                <th colspan="4" style="width: 12%;">LM 3</th>
                <th colspan="4" style="width: 12%;">LM 4</th>

                <th rowspan="2" style="width: 6%;">Nilai Akhir</th>
                <th rowspan="2" style="width: 7%;">Status</th>
            </tr>
            <tr>
                <th>TP1</th>
                <th>TP2</th>
                <th>TP3</th>
                <th>TP4</th>

                <th>TP1</th>
                <th>TP2</th>
                <th>TP3</th>
                <th>TP4</th>

                <th>TP1</th>
                <th>TP2</th>
                <th>TP3</th>
                <th>TP4</th>

                <th>TP1</th>
                <th>TP2</th>
                <th>TP3</th>
                <th>TP4</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalNilaiAkhir = 0;
                $jumlahNilaiAkhir = 0;
                $jumlahTuntas = 0;
                $jumlahBelumTuntas = 0;
            @endphp

            @forelse($nilaiList as $i => $row)
                @php
                    $nilaiAkhir = $row['nilai_akhir'] ?? null;
                    $isTuntas = $row['is_tuntas'] ?? null;

                    if ($nilaiAkhir !== null && is_numeric($nilaiAkhir)) {
                        $totalNilaiAkhir += (float) $nilaiAkhir;
                        $jumlahNilaiAkhir++;
                    }

                    if ($isTuntas === true) {
                        $jumlahTuntas++;
                        $statusLabel = 'Tuntas';
                    } elseif ($isTuntas === false) {
                        $jumlahBelumTuntas++;
                        $statusLabel = 'Belum Tuntas';
                    } else {
                        $statusLabel = '-';
                    }
                @endphp
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td class="mapel">{{ $row['mapel'] ?? '-' }}</td>
                    <td class="right">{{ $row['kkm'] ?? '-' }}</td>

                    <td class="right">{{ $row['lm1_tp1'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm1_tp2'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm1_tp3'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm1_tp4'] ?? '-' }}</td>

                    <td class="right">{{ $row['lm2_tp1'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm2_tp2'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm2_tp3'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm2_tp4'] ?? '-' }}</td>

                    <td class="right">{{ $row['lm3_tp1'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm3_tp2'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm3_tp3'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm3_tp4'] ?? '-' }}</td>

                    <td class="right">{{ $row['lm4_tp1'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm4_tp2'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm4_tp3'] ?? '-' }}</td>
                    <td class="right">{{ $row['lm4_tp4'] ?? '-' }}</td>

                    <td class="right">{{ $row['nilai_akhir'] ?? '-' }}</td>
                    <td class="center">{{ $statusLabel }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="23" class="center">Data nilai tidak tersedia.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- RINGKASAN --}}
    @php
        $rataGlobal = $jumlahNilaiAkhir > 0 ? round($totalNilaiAkhir / $jumlahNilaiAkhir, 2) : null;
    @endphp

    <table class="ringkasan">
        <tr>
            <td class="label">Jumlah Mata Pelajaran</td>
            <td class="titik">:</td>
            <td>{{ count($nilaiList) }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Tuntas</td>
            <td class="titik">:</td>
            <td>{{ $jumlahTuntas }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Belum Tuntas</td>
            <td class="titik">:</td>
            <td>{{ $jumlahBelumTuntas }}</td>
        </tr>
        <tr>
            <td class="label">Rata-rata Global</td>
            <td class="titik">:</td>
            <td>{{ $rataGlobal !== null ? $rataGlobal : '-' }}</td>
        </tr>
    </table>

    {{-- TTD --}}
    <div class="ttd">
        <p class="tanggal">Temanggung, {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y') }}</p>
        <p class="jabatan">Kepala Sekolah</p>

        <img src="file://{{ public_path('images/cap-sma2.png') }}" class="cap" alt="Cap SMAN 2 Temanggung">
        <img src="file://{{ public_path('images/ttd-kepsek.png') }}" class="ttd" alt="Tanda Tangan Kepala Sekolah">

        <p class="nama">Teguh Wibowo, M.M.</p>
        <p class="nip">NIP. 19681231 199603 1 010</p>
    </div>

    <div class="catatan">
        Dokumen ini diterbitkan secara otomatis oleh SINTA dan digunakan sebagai media informasi akademik orang tua.
    </div>
</body>

</html>