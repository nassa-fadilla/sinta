<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pengumuman Resmi</title>

    <style>
        @page {
            margin: 35px 48px 38px 48px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11.5px;
            line-height: 1.55;
            color: #111827;
            margin: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | KOP SURAT
        |--------------------------------------------------------------------------
        */
        .kop {
            width: 100%;
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kop-logo {
            width: 92px;
            vertical-align: top;
            text-align: center;
        }

        .kop-logo img {
            width: 72px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .kop-text {
            text-align: center;
            vertical-align: top;
            padding-right: 92px;
        }

        .kop-text .provinsi {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .kop-text .sekolah {
            margin: 3px 0 0;
            font-size: 19px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .kop-text .jenis {
            margin: 4px 0 0;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .kop-text .alamat {
            margin: 4px 0 0;
            font-size: 10.5px;
        }

        /*
        |--------------------------------------------------------------------------
        | JUDUL DOKUMEN
        |--------------------------------------------------------------------------
        */
        .judul-surat {
            text-align: center;
            margin: 18px 0 18px;
        }

        .judul-surat .judul {
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            letter-spacing: 0.6px;
            margin: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | META INFORMASI
        |--------------------------------------------------------------------------
        */
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 18px;
        }

        .meta td {
            vertical-align: top;
            padding: 2px 0;
            font-size: 11.5px;
        }

        .meta .label {
            width: 105px;
        }

        .meta .sep {
            width: 12px;
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | ISI PENGUMUMAN
        |--------------------------------------------------------------------------
        */
        .isi {
            text-align: justify;
            font-size: 11.5px;
            line-height: 1.75;
            margin-top: 8px;
        }

        .isi p {
            margin: 0 0 10px;
            text-align: justify;
        }

        .isi .paragraf {
            text-align: justify;
            white-space: pre-line;
        }

        /*
        |--------------------------------------------------------------------------
        | TANDA TANGAN
        |--------------------------------------------------------------------------
        */
        .ttd-wrap {
            width: 100%;
            margin-top: 34px;
        }

        .ttd-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ttd-left {
            width: 55%;
            vertical-align: top;
        }

        .ttd-right {
            width: 45%;
            vertical-align: top;
            text-align: left;
            position: relative;
        }

        .ttd-block {
            width: 250px;
            margin-left: auto;
            text-align: left;
            position: relative;
            min-height: 180px;
        }

        .ttd-tempat {
            margin: 0 0 4px;
        }

        .ttd-jabatan {
            margin: 0;
            font-weight: bold;
        }

        .signature-area {
            position: relative;
            height: 105px;
            margin-top: 4px;
        }

        .signature-area img.cap {
            position: absolute;
            left: 10px;
            top: 2px;
            width: 110px;
            opacity: 0.34;
            z-index: 1;
        }

        .signature-area img.ttd-img {
            position: absolute;
            left: 35px;
            top: 28px;
            width: 145px;
            z-index: 2;
            opacity: 0.96;
        }

        .ttd-nama {
            margin: 6px 0 0;
            font-weight: bold;
            text-decoration: underline;
        }

        .ttd-nip {
            margin: 2px 0 0;
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */
        .catatan {
            position: fixed;
            bottom: -18px;
            left: 0;
            right: 0;
            font-size: 9.5px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        \Carbon\Carbon::setLocale('id');

        $tanggalTerbit = $item->publish_at
            ? $item->publish_at->locale('id')->translatedFormat('d F Y')
            : '-';

        $tanggalBerlaku = $item->expire_at
            ? $item->expire_at->locale('id')->translatedFormat('d F Y')
            : null;

        $tanggalTtd = \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('d F Y');

        $namaKepsek = $item->approver->name ?? 'Teguh Prihantoro, S.Pd, M.Pd';
        $nipKepsek = '19781231 200501 1 002';

        $isiPengumuman = trim((string) ($item->isi ?? '-'));

        $logoPath = public_path('images/logo-sma2.png');
        $capPath = $capPath ?? public_path('images/cap-sma2.png');
        $ttdPath = $ttdPath ?? public_path('images/ttd-kepsek.png');
    @endphp

    {{-- KOP SURAT --}}
    <div class="kop">
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    @if(is_file($logoPath))
                        <img src="file://{{ $logoPath }}" alt="Logo SMAN 2 Temanggung">
                    @endif
                </td>
                <td class="kop-text">
                    <p class="provinsi">PEMERINTAH PROVINSI JAWA TENGAH</p>
                    <p class="sekolah">SMA NEGERI 2 TEMANGGUNG</p>
                    <p class="jenis">PENGUMUMAN RESMI SEKOLAH</p>
                    <p class="alamat">Jl. Jenderal Sudirman No. 27, Temanggung, Jawa Tengah</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- JUDUL --}}
    <div class="judul-surat">
        <p class="judul">PENGUMUMAN</p>
    </div>

    {{-- META --}}
    <table class="meta">
        <tr>
            <td class="label">Perihal</td>
            <td class="sep">:</td>
            <td>{{ $item->judul ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jenis</td>
            <td class="sep">:</td>
            <td>{{ ucfirst($item->jenis ?? 'Umum') }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Terbit</td>
            <td class="sep">:</td>
            <td>{{ $tanggalTerbit }}</td>
        </tr>
        @if($tanggalBerlaku)
            <tr>
                <td class="label">Berlaku Sampai</td>
                <td class="sep">:</td>
                <td>{{ $tanggalBerlaku }}</td>
            </tr>
        @endif
    </table>

    {{-- ISI PENGUMUMAN --}}
    <div class="isi">
        <p>Dengan hormat,</p>

        <div class="paragraf">{!! nl2br(e(preg_replace('/\n{2,}/', "\n", $isiPengumuman))) !!}
        </div>

        <p>
            Demikian pengumuman ini disampaikan untuk menjadi perhatian dan dilaksanakan sebagaimana mestinya.
        </p>
    </div>

    {{-- TANDA TANGAN --}}
    <div class="ttd-wrap">
        <table class="ttd-table">
            <tr>
                <td class="ttd-left"></td>
                <td class="ttd-right">
                    <div class="ttd-block">
                        <p class="ttd-tempat">Temanggung, {{ $tanggalTtd }}</p>
                        <p class="ttd-jabatan">Kepala Sekolah</p>

                        <div class="signature-area">
                            @if(!empty($capPath) && is_file($capPath))
                                <img src="file://{{ $capPath }}" class="cap" alt="Cap SMAN 2 Temanggung">
                            @endif

                            @if(!empty($ttdPath) && is_file($ttdPath))
                                <img src="file://{{ $ttdPath }}" class="ttd-img" alt="Tanda Tangan Kepala Sekolah">
                            @endif
                        </div>

                        <p class="ttd-nama">{{ $namaKepsek }}</p>
                        <p class="ttd-nip">NIP. {{ $nipKepsek }}</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="catatan">
        Dokumen ini merupakan pengumuman resmi sekolah yang diarsipkan melalui Sistem Informasi Monitoring Aktivitas
        Siswa (SINTA).
    </div>
</body>

</html>