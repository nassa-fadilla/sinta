<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pengumuman Resmi</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11.5px;
            line-height: 1.65;
            margin: 42px 54px;
            color: #111827;
        }

        .kop {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 22px;
            overflow: hidden;
        }

        .kop img.logo {
            float: left;
            width: 72px;
            height: auto;
            display: block;
            margin-top: 2px;
        }

        .kop .text {
            margin-left: 90px;
            text-align: center;
            padding-top: 2px;
        }

        .kop .text h4 {
            margin: 0;
            font-size: 12.5px;
            font-weight: bold;
        }

        .kop .text h2 {
            margin: 2px 0;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .kop .text h3 {
            margin: 2px 0;
            font-size: 13px;
            font-weight: bold;
        }

        .kop .text p {
            margin: 2px 0 0;
            font-size: 10.5px;
        }

        .judul-wrap {
            text-align: center;
            margin: 18px 0 20px;
        }

        .judul-wrap .judul {
            font-size: 15px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 4px;
        }

        .judul-wrap .sub {
            font-size: 10.5px;
            color: #4b5563;
        }

        .meta-box {
            border: 1px solid #d1d5db;
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 16px;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            padding: 3px 0;
            vertical-align: top;
        }

        .meta td.label {
            width: 120px;
            font-weight: bold;
        }

        .meta td.sep {
            width: 10px;
        }

        .isi-title {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 8px;
        }

        .box-isi {
            border: 1px solid #d1d5db;
            background: #ffffff;
            padding: 16px 18px;
            border-radius: 8px;
            text-align: justify;
        }

        .box-isi p {
            margin: 0 0 10px;
        }

        .ttd {
            position: relative;
            margin-top: 38px;
            text-align: right;
            height: 205px;
        }

        .ttd .tanggal {
            margin-right: 10px;
            margin-bottom: 4px;
        }

        .ttd .jabatan {
            margin-right: 10px;
            font-weight: bold;
        }

        .ttd img.cap {
            position: absolute;
            right: 82px;
            top: 32px;
            width: 112px;
            opacity: 0.22;
            z-index: 1;
        }

        .ttd img.ttd {
            position: absolute;
            right: 48px;
            top: 50px;
            width: 142px;
            z-index: 2;
            opacity: 0.92;
        }

        .ttd .nama {
            font-weight: bold;
            text-decoration: underline;
            margin-right: 8px;
            margin-top: 138px;
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
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    @php
        \Carbon\Carbon::setLocale('id');
    @endphp

    {{-- KOP SURAT --}}
    <div class="kop">
        <img src="file://{{ public_path('images/logo-sma2.png') }}" class="logo" alt="Logo SMAN 2 Temanggung">

        <div class="text">
            <h4>PEMERINTAH PROVINSI JAWA TENGAH</h4>
            <h2>SMAN 2 TEMANGGUNG</h2>
            <h3>PENGUMUMAN RESMI SEKOLAH</h3>
            <p>Jl. Jenderal Sudirman No.27, Temanggung, Jawa Tengah</p>
        </div>
    </div>

    {{-- JUDUL --}}
    <div class="judul-wrap">
        <div class="judul">PENGUMUMAN RESMI</div>
        <div class="sub">Dokumen ini diterbitkan melalui Sistem Informasi Monitoring Aktivitas Siswa (SINTA)</div>
    </div>

    {{-- META --}}
    <div class="meta-box">
        <table class="meta">
            <tr>
                <td class="label">Judul</td>
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
                <td>{{ optional($item->publish_at)->translatedFormat('d F Y') ?? '-' }}</td>
            </tr>
            @if(!empty($item->expire_at))
                <tr>
                    <td class="label">Berlaku Sampai</td>
                    <td class="sep">:</td>
                    <td>{{ optional($item->expire_at)->translatedFormat('d F Y') ?? '-' }}</td>
                </tr>
            @endif
        </table>
    </div>

    {{-- ISI --}}
    <div class="isi-title">Isi Pengumuman</div>
    <div class="box-isi">
        {!! nl2br(e($item->isi ?? '-')) !!}
    </div>

    {{-- TTD --}}
    <div class="ttd">
        <p class="tanggal">Temanggung,
            {{ \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('d F Y') }}
        </p>
        <p class="jabatan">Kepala Sekolah</p>

        <img src="file://{{ public_path('images/cap-sma2.png') }}" class="cap" alt="Cap SMAN 2 Temanggung">
        <img src="file://{{ public_path('images/ttd-kepsek.png') }}" class="ttd" alt="Tanda Tangan Kepala Sekolah">

        <p class="nama">{{ $item->approver->name ?? 'Teguh Prihantoro, S.Pd, M.Pd' }}</p>
        <p class="nip">NIP. 19781231 200501 1 002</p>
    </div>

    <div class="catatan">
        Dokumen ini merupakan pengumuman resmi sekolah yang diarsipkan melalui SINTA.
    </div>
</body>

</html>