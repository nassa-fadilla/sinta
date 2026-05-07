<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Hasil Survei - {{ $survei->judul }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 25px;
        }

        h2 {
            color: #1e3a8a;
            margin-bottom: 4px;
        }

        p {
            margin: 2px 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background-color: #eff6ff;
            color: #1e3a8a;
            font-weight: 600;
        }

        tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 4px;
        }

        .summary {
            margin-bottom: 15px;
        }

        .summary span {
            display: inline-block;
            margin-right: 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo img {
            height: 45px;
            margin-right: 12px;
        }

        .divider {
            height: 2px;
            background-color: #1e3a8a;
            margin: 10px 0 15px;
        }
    </style>
</head>

<body>

    {{-- Header --}}
    <div class="logo">
        <img src="{{ public_path('images/logo-sma2.png') }}" alt="Logo Sekolah">
        <div>
            <h2>{{ $survei->judul }}</h2>
            <small>SMAN 2 Temanggung – Laporan Survei Orang Tua</small>
        </div>
    </div>
    <div class="divider"></div>

    {{-- Informasi Survei --}}
    <div class="summary">
        <p><strong>Deskripsi:</strong> {{ $survei->deskripsi ?: '-' }}</p>
        <span><strong>Periode:</strong>
            {{ $survei->mulai_at ? date('d M Y', strtotime($survei->mulai_at)) : '-' }}
            – {{ $survei->akhir_at ? date('d M Y', strtotime($survei->akhir_at)) : '-' }}
        </span>
        <span><strong>Total Respon:</strong> {{ $respon->count() }}</span>
    </div>

    {{-- Tabel Jawaban --}}
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Orang Tua</th>
                <th>Waktu</th>
                @foreach($survei->pertanyaan as $p)
                    <th>{{ $p->pertanyaan }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($respon as $i => $r)
                @php
                    $j = is_array($r->jawaban) ? $r->jawaban : json_decode($r->jawaban, true);
                    $j = $j ?? [];
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r->ortu->name ?? '-' }}</td>
                    <td>{{ date('d M Y H:i', strtotime($r->created_at)) }}</td>
                    @foreach($survei->pertanyaan as $p)
                        @php
                            $val = $j[$p->id] ?? '-';
                            if (is_array($val))
                                $val = implode(', ', $val);
                        @endphp
                        <td>{{ $val }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak otomatis dari Sistem Informasi SINTA &middot; {{ now()->format('d M Y H:i') }}
    </div>

</body>

</html>