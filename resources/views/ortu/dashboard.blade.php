@extends('ortu.layout')
@section('title', 'Dashboard')

@section('content')
  @php
    $siswaApi = is_array($siswaApi ?? null) ? $siswaApi : null;

    $namaSiswa = $siswaApi['nama'] ?? auth()->user()->name ?? '-';
    $nisSiswa = $siswaApi['nis'] ?? auth()->user()->sia_user_id ?? '-';
    $nisnSiswa = $siswaApi['nisn'] ?? '-';

    $rombelLabel = $rombelNama
      ?? data_get($siswaApi, 'rombel_aktif.nama_rombel')
      ?? data_get($siswaApi, 'rombel.nama_rombel')
      ?? data_get($sidebarSiswa ?? [], 'kelas')
      ?? '-';

    $periodeAktifLabel = trim(
      ($tahunAjaranAktif ? $tahunAjaranAktif : '') .
      ($tahunAjaranAktif && $semesterAktif ? ' • ' : '') .
      ($semesterAktif ? 'Semester ' . $semesterAktif : '')
    );

    $periodeAktifLabel = $periodeAktifLabel !== '' ? $periodeAktifLabel : 'Tahun ajaran aktif belum tersedia';

    $jadwalApi = is_iterable($jadwalApi ?? null) ? collect($jadwalApi)->map(fn($v) => (array) $v)->all() : [];
    $nilaiApi = is_iterable($nilaiApi ?? null) ? collect($nilaiApi)->map(fn($v) => (array) $v)->all() : [];
    $presensiApi = is_iterable($presensiApi ?? null) ? collect($presensiApi)->map(fn($v) => (array) $v)->all() : [];

    $presensiByHari = is_iterable($presensiByHari ?? null)
      ? collect($presensiByHari)
        ->map(fn($rows) => collect($rows)->map(fn($v) => (array) $v)->all())
        ->all()
      : [];

    $ekskulApi = is_iterable($ekskulApi ?? null) ? collect($ekskulApi)->map(fn($v) => (array) $v)->all() : [];

    /*
    |--------------------------------------------------------------------------
    | Hari untuk jadwal
    |--------------------------------------------------------------------------
    | Jadwal tetap memakai 7 hari agar jika suatu saat sekolah punya jadwal Sabtu,
    | bagian jadwal harian tetap aman. Namun presensi dashboard dikunci hanya
    | Senin sampai Jumat.
    */
    $mapHari = [
      1 => 'Senin',
      2 => 'Selasa',
      3 => 'Rabu',
      4 => 'Kamis',
      5 => 'Jumat',
      6 => 'Sabtu',
      7 => 'Minggu',
    ];

    $urutanHariJadwal = [
      'Senin' => 1,
      'Selasa' => 2,
      'Rabu' => 3,
      'Kamis' => 4,
      'Jumat' => 5,
      'Sabtu' => 6,
      'Minggu' => 7,
    ];

    /*
    |--------------------------------------------------------------------------
    | Hari presensi dashboard
    |--------------------------------------------------------------------------
    | Bagian kehadiran dashboard hanya menampilkan hari kerja Senin sampai Jumat.
    */
    $hariPresensiUtama = [
      'Senin',
      'Selasa',
      'Rabu',
      'Kamis',
      'Jumat',
    ];

    $urutanHariPresensi = [
      'Senin' => 1,
      'Selasa' => 2,
      'Rabu' => 3,
      'Kamis' => 4,
      'Jumat' => 5,
    ];

    $hariIni = $mapHari[\Carbon\Carbon::now('Asia/Jakarta')->dayOfWeekIso] ?? null;
    $todayLabel = \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y');

    $jadwalHariIni = [];
    foreach ($jadwalApi as $row) {
      $hariRow = strtolower(trim((string) ($row['hari'] ?? '')));

      if ($hariIni && $hariRow === strtolower($hariIni)) {
        $jadwalHariIni[] = $row;
      }
    }

    usort($jadwalHariIni, function ($a, $b) {
      return strcmp((string) ($a['jam_mulai'] ?? ''), (string) ($b['jam_mulai'] ?? ''));
    });

    /*
    |--------------------------------------------------------------------------
    | Tab presensi
    |--------------------------------------------------------------------------
    | Tidak lagi mengambil dari jadwalApi, supaya Sabtu/Minggu tidak ikut muncul.
    | Semua hari kerja tetap ditampilkan walaupun jumlah presensi 0.
    */
    $hariPresensiTabs = $hariPresensiUtama;

    $defaultPresensiDay = in_array($hariIni, $hariPresensiTabs, true)
      ? $hariIni
      : 'Senin';

    $rataGlobal = null;

    if (!empty($nilaiApi)) {
      $nilaiAkhirList = collect($nilaiApi)
        ->map(function ($n) {
          $r = $n['nilai_akhir'] ?? null;

          if ($r === '-' || $r === null || $r === '') {
            return null;
          }

          return is_numeric($r) ? (float) $r : null;
        })
        ->filter(fn($v) => $v !== null)
        ->values();

      if ($nilaiAkhirList->isNotEmpty()) {
        $rataGlobal = round($nilaiAkhirList->avg(), 2);
      }
    }

    $latestPengumuman = ($pengumuman ?? collect())->sortByDesc('publish_at')->take(3);
    $persenPresensi = $presensiRingkas['persen'] ?? null;

    $ekskulPalettes = [
      [
        'outer' => 'border-blue-100 bg-white hover:border-blue-200 hover:shadow-[0_20px_48px_rgba(59,130,246,0.14)]',
        'iconWrap' => 'border-blue-200 bg-blue-50 text-blue-600',
        'chip' => 'border-blue-200 bg-blue-50 text-blue-700',
        'line' => 'bg-blue-500',
      ],
      [
        'outer' => 'border-emerald-100 bg-white hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]',
        'iconWrap' => 'border-emerald-200 bg-emerald-50 text-emerald-600',
        'chip' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'line' => 'bg-emerald-500',
      ],
      [
        'outer' => 'border-fuchsia-100 bg-white hover:border-fuchsia-200 hover:shadow-[0_20px_48px_rgba(217,70,239,0.14)]',
        'iconWrap' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-600',
        'chip' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-700',
        'line' => 'bg-fuchsia-500',
      ],
      [
        'outer' => 'border-amber-100 bg-white hover:border-amber-200 hover:shadow-[0_20px_48px_rgba(245,158,11,0.14)]',
        'iconWrap' => 'border-amber-200 bg-amber-50 text-amber-600',
        'chip' => 'border-amber-200 bg-amber-50 text-amber-700',
        'line' => 'bg-amber-500',
      ],
    ];

    $getEkskulIcon = function ($nama) {
      $nama = strtolower(trim((string) $nama));

      if (str_contains($nama, 'voli') || str_contains($nama, 'volley')) {
        return 'voli';
      }

      if (str_contains($nama, 'batik')) {
        return 'batik';
      }

      if (str_contains($nama, 'bulu tangkis') || str_contains($nama, 'badminton')) {
        return 'badminton';
      }

      return 'default';
    };
  @endphp

  <div class="space-y-6" x-data="{
          tab: localStorage.getItem('ortuDashboardTab') || 'jadwal',
          presensiDay: localStorage.getItem('ortuPresensiDay') || @js($defaultPresensiDay),
          allowedPresensiDays: @js($hariPresensiTabs)
        }" x-init="
          if (!allowedPresensiDays.includes(presensiDay)) {
            presensiDay = @js($defaultPresensiDay);
            localStorage.setItem('ortuPresensiDay', presensiDay);
          }

          $watch('tab', value => localStorage.setItem('ortuDashboardTab', value));

          $watch('presensiDay', value => {
            if (!allowedPresensiDays.includes(value)) {
              presensiDay = @js($defaultPresensiDay);
              localStorage.setItem('ortuPresensiDay', presensiDay);
              return;
            }

            localStorage.setItem('ortuPresensiDay', value);
          });
        ">

    {{-- HEADER --}}
    <section
      class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_30px_80px_rgba(15,23,42,0.10)]">

      <div class="border-b border-slate-200/80 px-5 py-6 md:px-6 lg:px-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex min-w-0 items-start gap-4">
            <div
              class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-[0_14px_30px_rgba(59,130,246,0.28)]">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 4l9 6.5M5 10.5v9h14v-9" />
              </svg>
            </div>

            <div class="min-w-0">
              <div
                class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-blue-700">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                Portal Orang Tua
              </div>

              <h1 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">
                Dashboard Orang Tua
              </h1>

              <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Pantau jadwal, nilai, kehadiran, ekstrakurikuler, dan informasi sekolah secara ringkas berdasarkan tahun
                ajaran aktif.
              </p>

              <div class="mt-4 flex flex-wrap items-center gap-2">
                <span
                  class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
                  <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                  {{ $todayLabel }}
                </span>

                <span
                  class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600">
                  Login sebagai: {{ auth()->user()->name ?? 'Orang Tua' }}
                </span>

                <span
                  class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
                  {{ $periodeAktifLabel }}
                </span>
              </div>
            </div>
          </div>

          @if ($siswaApi)
            <div class="shrink-0 lg:pt-1">
              <div
                class="min-w-[270px] rounded-[1.7rem] border border-slate-200 bg-white px-5 py-4 shadow-[0_16px_38px_rgba(15,23,42,0.07)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_22px_50px_rgba(59,130,246,0.13)]">
                <div class="flex items-center gap-3">
                  <div
                    class="grid h-12 w-12 place-items-center rounded-2xl bg-blue-500 text-white shadow-[0_12px_26px_rgba(59,130,246,0.28)]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6a3 3 0 1 1 0 6 3 3 0 0 1 0-6zm0 6c-3.314 0-6 1.79-6 4v1h12v-1c0-2.21-2.686-4-6-4z" />
                    </svg>
                  </div>

                  <div class="min-w-0">
                    <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                      Data Siswa Aktif
                    </div>
                    <div class="mt-0.5 text-sm font-semibold leading-tight text-slate-900">
                      {{ $namaSiswa }}
                    </div>
                    <div class="mt-1 text-[11px] leading-5 text-slate-500">
                      NIS {{ $nisSiswa }}
                      @if ($nisnSiswa && $nisnSiswa !== '-')
                        • NISN {{ $nisnSiswa }}
                      @endif
                    </div>
                    <div class="mt-1 text-[11px] leading-5 text-slate-500">
                      Kelas {{ $rombelLabel }}
                    </div>
                    <div class="mt-1 text-[11px] leading-5 text-slate-500">
                      {{ $periodeAktifLabel }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endif
        </div>
      </div>

      {{-- QUICK STATS --}}
      <div class="px-5 py-5 md:px-6 lg:px-7">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <div
            class="group h-full rounded-[1.6rem] border border-sky-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(14,165,233,0.08)] transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_20px_48px_rgba(14,165,233,0.14)]">
            <div class="flex h-full items-start justify-between gap-3">
              <div>
                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-sky-700">
                  Jadwal Hari Ini
                </div>
                <div class="mt-3 text-3xl font-bold leading-none text-slate-900">
                  {{ count($jadwalHariIni) }}
                </div>
                <div class="mt-2 text-xs leading-5 text-slate-500">
                  Mata pelajaran pada rombel aktif.
                </div>
              </div>

              <div
                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-600 shadow-sm transition duration-300 group-hover:scale-110">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M8 3v3m8-3v3M4 9h16M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" />
                </svg>
              </div>
            </div>
          </div>

          <div
            class="group h-full rounded-[1.6rem] border border-blue-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(59,130,246,0.08)] transition duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_20px_48px_rgba(59,130,246,0.14)]">
            <div class="flex h-full items-start justify-between gap-3">
              <div>
                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-blue-700">
                  Rata-rata Nilai
                </div>
                <div class="mt-3 text-3xl font-bold leading-none text-slate-900">
                  {{ $rataGlobal !== null ? $rataGlobal : '-' }}
                </div>
                <div class="mt-2 text-xs leading-5 text-slate-500">
                  Tahun ajaran dan semester aktif.
                </div>
              </div>

              <div
                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-blue-200 bg-blue-50 text-blue-600 shadow-sm transition duration-300 group-hover:scale-110">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M8 17V9m4 8V5m4 12v-6" />
                </svg>
              </div>
            </div>
          </div>

          <div
            class="group h-full rounded-[1.6rem] border border-emerald-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(16,185,129,0.08)] transition duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_20px_48px_rgba(16,185,129,0.14)]">
            <div class="flex h-full items-start justify-between gap-3">
              <div>
                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">
                  Kehadiran
                </div>
                <div class="mt-3 text-3xl font-bold leading-none text-slate-900">
                  {{ $persenPresensi !== null ? $persenPresensi . '%' : '—' }}
                </div>
                <div class="mt-2 text-xs leading-5 text-slate-500">
                  Rekap Senin sampai Jumat.
                </div>
              </div>

              <div
                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm transition duration-300 group-hover:scale-110">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                </svg>
              </div>
            </div>
          </div>

          <div
            class="group h-full rounded-[1.6rem] border border-rose-100 bg-white px-4 py-4 shadow-[0_14px_34px_rgba(244,63,94,0.08)] transition duration-300 hover:-translate-y-1 hover:border-rose-200 hover:shadow-[0_20px_48px_rgba(244,63,94,0.14)]">
            <div class="flex h-full items-start justify-between gap-3">
              <div>
                <div class="text-[11px] font-bold uppercase tracking-[0.16em] text-rose-700">
                  Ekskul
                </div>
                <div class="mt-3 text-3xl font-bold leading-none text-slate-900">
                  {{ count($ekskulApi) }}
                </div>
                <div class="mt-2 text-xs leading-5 text-slate-500">
                  Ekstrakurikuler pada periode aktif.
                </div>
              </div>

              <div
                class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-600 shadow-sm transition duration-300 group-hover:scale-110">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6l3 7H9l3-7zm0 0V3m0 10v8" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- PANEL AKADEMIK --}}
    <section
      class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_30px_80px_rgba(15,23,42,0.10)]">

      <div class="border-b border-slate-200/80 px-5 py-5 md:px-6 lg:px-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h2 class="text-lg font-semibold tracking-tight text-slate-900">
              Data Akademik
            </h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">
              Data di bawah ini mengikuti tahun ajaran aktif dari SIA: {{ $periodeAktifLabel }}.
            </p>
          </div>

          <div
            class="inline-flex w-fit max-w-full flex-wrap items-center gap-1 rounded-full border border-slate-200 bg-slate-50 p-1 shadow-sm">
            <button type="button" @click="tab = 'jadwal'" :class="tab === 'jadwal'
                            ? 'bg-blue-600 text-white shadow-sm'
                            : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700'"
              class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium whitespace-nowrap transition">
              Jadwal
            </button>

            <button type="button" @click="tab = 'nilai'" :class="tab === 'nilai'
                            ? 'bg-blue-600 text-white shadow-sm'
                            : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700'"
              class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium whitespace-nowrap transition">
              Nilai
            </button>

            <button type="button" @click="tab = 'presensi'" :class="tab === 'presensi'
                            ? 'bg-blue-600 text-white shadow-sm'
                            : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700'"
              class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium whitespace-nowrap transition">
              Kehadiran
            </button>

            <button type="button" @click="tab = 'ekskul'" :class="tab === 'ekskul'
                            ? 'bg-blue-600 text-white shadow-sm'
                            : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700'"
              class="flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium whitespace-nowrap transition">
              Ekskul
            </button>
          </div>
        </div>
      </div>

      <div class="p-5 md:p-6 lg:p-7">

        {{-- TAB: JADWAL --}}
        <div x-show="tab === 'jadwal'" x-cloak>
          @if (empty($jadwalHariIni))
            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50/70 px-5 py-12 text-center shadow-inner">
              <p class="text-sm font-semibold text-slate-700">
                Tidak ada jadwal pelajaran untuk hari ini.
              </p>
              <p class="mt-1 text-xs text-slate-500">
                Jadwal akan tampil sesuai rombel aktif siswa pada tahun ajaran aktif.
              </p>
            </div>
          @else
            <div
              class="overflow-hidden rounded-[1.9rem] border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.06)]">
              <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-sm">
                  <thead>
                    <tr
                      class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                      <th class="px-5 py-4">Hari</th>
                      <th class="px-5 py-4">Jam</th>
                      <th class="px-5 py-4">Mata Pelajaran</th>
                      <th class="px-5 py-4">Guru</th>
                      <th class="px-5 py-4">Status</th>
                    </tr>
                  </thead>

                  <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach ($jadwalHariIni as $j)
                      @php
                        $rowClass = 'transition duration-300 hover:bg-blue-50/40';
                        if (!empty($j['is_active'])) {
                          $rowClass = 'bg-blue-50/70 transition duration-300 hover:bg-blue-50/80';
                        } elseif (!empty($j['is_passed'])) {
                          $rowClass = 'bg-slate-50/70 text-slate-400 transition duration-300 hover:bg-slate-50';
                        }
                      @endphp

                      <tr class="{{ $rowClass }}">
                        <td class="px-5 py-4 {{ !empty($j['is_passed']) ? 'text-slate-400' : 'text-slate-700' }}">
                          {{ $j['hari'] ?? '-' }}
                        </td>

                        <td class="px-5 py-4 {{ !empty($j['is_passed']) ? 'text-slate-400' : 'text-slate-700' }}">
                          {{ $j['jam_mulai'] ?? '' }}
                          @if (!empty($j['jam_selesai']))
                            – {{ $j['jam_selesai'] }}
                          @endif
                        </td>

                        <td
                          class="px-5 py-4 font-semibold {{ !empty($j['is_passed']) ? 'text-slate-500' : 'text-slate-900' }}">
                          {{ $j['mapel'] ?? '-' }}
                        </td>

                        <td class="px-5 py-4 {{ !empty($j['is_passed']) ? 'text-slate-400' : 'text-slate-700' }}">
                          {{ $j['guru'] ?? '-' }}
                        </td>

                        <td class="px-5 py-4">
                          @if (!empty($j['is_active']))
                            <span
                              class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[11px] font-medium text-blue-700">
                              Sedang Berlangsung
                            </span>
                          @elseif (!empty($j['is_upcoming']))
                            <span
                              class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[11px] font-medium text-amber-700">
                              Akan Datang
                            </span>
                          @elseif (!empty($j['is_passed']))
                            <span
                              class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-500">
                              Selesai
                            </span>
                          @else
                            <span
                              class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-medium text-slate-600">
                              Terjadwal
                            </span>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          @endif
        </div>

        {{-- TAB: NILAI --}}
        <div x-show="tab === 'nilai'" x-cloak>
          @if (empty($nilaiApi))
            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50/70 px-5 py-12 text-center shadow-inner">
              <p class="text-sm font-semibold text-slate-700">
                Belum ada data nilai pada tahun ajaran dan semester aktif.
              </p>
              <p class="mt-1 text-xs text-slate-500">
                Data nilai akan tampil setelah tersedia pada SIA untuk {{ $periodeAktifLabel }}.
              </p>
            </div>
          @else
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
              <div
                class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-[0_14px_38px_rgba(15,23,42,0.06)]">
                <div class="border-b border-slate-200 px-5 py-4">
                  <h3 class="text-base font-semibold text-slate-900">
                    Ringkasan Nilai
                  </h3>
                  <p class="mt-1 text-xs leading-5 text-slate-500">
                    {{ $periodeAktifLabel }}.
                  </p>
                </div>

                <div class="max-h-[330px] overflow-x-auto overflow-y-auto">
                  <table class="min-w-[680px] w-full border-collapse text-sm">
                    <thead>
                      <tr
                        class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                        <th class="px-5 py-4 text-left">Mapel</th>
                        <th class="px-4 py-4 text-right">LM 1</th>
                        <th class="px-4 py-4 text-right">LM 2</th>
                        <th class="px-4 py-4 text-right">LM 3</th>
                        <th class="px-4 py-4 text-right">LM 4</th>
                        <th class="px-5 py-4 text-right">Akhir</th>
                      </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                      @foreach ($nilaiApi as $n)
                        <tr class="transition duration-300 hover:bg-blue-50/40">
                          <td class="px-5 py-4 font-semibold text-slate-900">
                            {{ $n['mapel'] ?? '-' }}
                          </td>
                          <td class="px-4 py-4 text-right text-slate-700">{{ $n['lm1'] ?? '-' }}</td>
                          <td class="px-4 py-4 text-right text-slate-700">{{ $n['lm2'] ?? '-' }}</td>
                          <td class="px-4 py-4 text-right text-slate-700">{{ $n['lm3'] ?? '-' }}</td>
                          <td class="px-4 py-4 text-right text-slate-700">{{ $n['lm4'] ?? '-' }}</td>
                          <td class="px-5 py-4 text-right">
                            <span
                              class="inline-flex min-w-[54px] justify-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                              {{ $n['nilai_akhir'] ?? '-' }}
                            </span>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>

              <div
                class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-[0_14px_38px_rgba(15,23,42,0.06)]">
                <div class="border-b border-slate-200 px-5 py-4">
                  <h3 class="text-base font-semibold text-slate-900">
                    Komposisi Nilai Akhir
                  </h3>
                  <p class="mt-1 text-xs leading-5 text-slate-500">
                    Visualisasi ringkas nilai akhir pada periode aktif.
                  </p>
                </div>

                <div class="flex h-[330px] items-center justify-center p-5">
                  <canvas id="chartNilaiPie" class="max-h-[280px]"></canvas>
                </div>
              </div>
            </div>
          @endif
        </div>

        {{-- TAB: PRESENSI --}}
        <div x-show="tab === 'presensi'" x-cloak>
          <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <h3 class="text-base font-semibold text-slate-900">
                Rekap Kehadiran Mingguan
              </h3>
              <p class="mt-1 text-xs leading-5 text-slate-500">
                Periode:
                {{ optional($presensiWeekStart)->translatedFormat('d M') }}
                -
                {{ optional($presensiWeekEnd)->translatedFormat('d M Y') }}
              </p>
            </div>

            <div class="flex max-w-full flex-wrap items-center gap-2">
              @foreach($hariPresensiTabs as $hariTab)
                @php
                  $rowsHari = $presensiByHari[$hariTab] ?? [];
                  $jumlahHari = count($rowsHari);
                @endphp

                <button type="button" @click="presensiDay = @js($hariTab)"
                  :class="presensiDay === @js($hariTab)
                            ? 'border-emerald-200 bg-emerald-500 text-white shadow-sm'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700'"
                  class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium transition">
                  <span>{{ $hariTab }}</span>
                  <span :class="presensiDay === @js($hariTab)
                            ? 'bg-white/20 text-white'
                            : 'bg-slate-100 text-slate-500'"
                    class="inline-flex min-w-[22px] items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold">
                    {{ $jumlahHari }}
                  </span>
                </button>
              @endforeach
            </div>
          </div>

          @foreach($hariPresensiTabs as $hariTab)
            @php
              $rowsHari = $presensiByHari[$hariTab] ?? [];

              $jadwalHari = collect($jadwalApi)
                ->filter(fn($j) => strtolower(trim((string) ($j['hari'] ?? ''))) === strtolower($hariTab))
                ->sortBy(fn($j) => $j['jam_mulai'] ?? '')
                ->values()
                ->all();
            @endphp

            <div x-show="presensiDay === @js($hariTab)" x-cloak>
              <div class="mb-4 rounded-[1.7rem] border border-emerald-100 bg-emerald-50/70 px-4 py-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                  <div>
                    <div class="text-sm font-semibold text-slate-900">
                      Presensi Hari {{ $hariTab }}
                    </div>
                    <p class="mt-1 text-xs leading-5 text-slate-500">
                      Data presensi ditampilkan sesuai jadwal minggu berjalan.
                    </p>
                  </div>

                  <span
                    class="inline-flex w-fit items-center rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-medium text-emerald-700">
                    {{ count($rowsHari) }} presensi
                  </span>
                </div>

                @if(!empty($jadwalHari))
                  <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($jadwalHari as $j)
                      <span
                        class="inline-flex items-center rounded-full border border-white bg-white/90 px-3 py-1 text-[11px] font-medium text-slate-600 shadow-sm">
                        {{ $j['jam_mulai'] ?? '-' }}
                        @if(!empty($j['jam_selesai']))
                          - {{ $j['jam_selesai'] }}
                        @endif
                        • {{ $j['mapel'] ?? '-' }}
                      </span>
                    @endforeach
                  </div>
                @endif
              </div>

              @if(empty($rowsHari))
                <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50/70 px-5 py-12 text-center shadow-inner">
                  <p class="text-sm font-semibold text-slate-700">
                    Belum ada data presensi untuk hari {{ $hariTab }}.
                  </p>
                  <p class="mt-1 text-xs text-slate-500">
                    Dashboard hanya menampilkan data presensi Senin sampai Jumat pada minggu berjalan.
                  </p>
                </div>
              @else
                <div
                  class="overflow-hidden rounded-[1.9rem] border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.06)]">
                  <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm">
                      <thead>
                        <tr
                          class="border-b border-slate-200 bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                          <th class="px-5 py-4">Tanggal / Waktu</th>
                          <th class="px-5 py-4">Hari</th>
                          <th class="px-5 py-4">Mapel</th>
                          <th class="px-5 py-4">Status</th>
                        </tr>
                      </thead>

                      <tbody class="divide-y divide-slate-100">
                        @foreach ($rowsHari as $p)
                          @php
                            $status = strtolower($p['status'] ?? '');
                            $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';

                            if ($status === 'hadir') {
                              $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            } elseif (in_array($status, ['izin', 'sakit'])) {
                              $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                            } elseif (in_array($status, ['alpa', 'alfa'])) {
                              $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                            }
                          @endphp

                          <tr class="transition duration-300 hover:bg-emerald-50/35">
                            <td class="px-5 py-4 text-slate-700">
                              <div class="font-semibold text-slate-900">
                                {{ $p['tanggal_label'] ?? '-' }}
                              </div>
                              <div class="mt-0.5 text-xs text-slate-400">
                                {{ $p['dipindai_pada'] ?? '-' }}
                              </div>
                            </td>

                            <td class="px-5 py-4 text-slate-700">
                              {{ $p['hari'] ?? $hariTab }}
                            </td>

                            <td class="px-5 py-4 font-semibold text-slate-900">
                              {{ $p['mapel'] ?? '-' }}
                            </td>

                            <td class="px-5 py-4">
                              <span
                                class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-medium {{ $badgeClass }}">
                                {{ strtoupper($p['status'] ?? '-') }}
                              </span>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              @endif
            </div>
          @endforeach
        </div>

        {{-- TAB: EKSKUL --}}
        <div x-show="tab === 'ekskul'" x-cloak>
          @if (empty($ekskulApi))
            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50/70 px-5 py-12 text-center shadow-inner">
              <p class="text-sm font-semibold text-slate-700">
                Tidak ada ekskul aktif yang tercatat pada periode aktif.
              </p>
              <p class="mt-1 text-xs text-slate-500">
                Data ekstrakurikuler akan tampil setelah tersedia dari SIA.
              </p>
            </div>
          @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
              @foreach ($ekskulApi as $index => $e)
                @php
                  $palette = $ekskulPalettes[$index % count($ekskulPalettes)];
                  $iconType = $getEkskulIcon($e['nama'] ?? '');
                @endphp

                <article
                  class="group relative overflow-hidden rounded-[1.7rem] border px-4 py-4 shadow-[0_12px_32px_rgba(15,23,42,0.06)] transition-all duration-300 hover:-translate-y-1 {{ $palette['outer'] }}">
                  <div class="absolute inset-x-0 top-0 h-1 {{ $palette['line'] }}"></div>

                  <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                      <h3 class="truncate text-base font-semibold text-slate-900">
                        {{ $e['nama'] ?? '-' }}
                      </h3>
                      <p class="mt-2 text-sm leading-6 text-slate-500">
                        {{ $e['hari'] ?? '-' }}
                        @if (!empty($e['jam']) && $e['jam'] !== '-')
                          • {{ $e['jam'] }}
                        @endif
                      </p>
                    </div>

                    <div
                      class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border shadow-sm transition duration-300 group-hover:scale-110 {{ $palette['iconWrap'] }}">
                      @if($iconType === 'voli')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor" stroke-width="1.9">
                          <circle cx="12" cy="12" r="7.5"></circle>
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 8.5c2 1 3.5 2.5 4.5 4.5M16.5 7c-.7 2.3-2.2 4.2-4.5 5.5M9 18c1.8-1.1 4-1.6 6.5-1.5" />
                        </svg>
                      @elseif($iconType === 'batik')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor" stroke-width="1.9">
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 16c1.5-1.5 3.5-2 5-1 1.5 1 3.5.5 5-1M6 8c1.2 1.2 2.8 1.8 4.2 1.5M11 6c.8 1.4 2.1 2.5 3.8 3" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M5 19l5-12 9 9" />
                        </svg>
                      @elseif($iconType === 'badminton')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor" stroke-width="1.9">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l5 5M12 7l5 5M5 19l6-6" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4 20l3-1 10-10-2-2L5 17l-1 3z" />
                        </svg>
                      @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                          stroke="currentColor" stroke-width="1.9">
                          <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 17a1 1 0 0 0 2 0v-5a1 1 0 0 0-.553-.894l-4-2A1 1 0 0 0 7 10v3.382a1 1 0 0 0 .553.894l4 2z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M17 9.5V14a2 2 0 0 1-.553 1.382L13 19" />
                        </svg>
                      @endif
                    </div>
                  </div>

                  <div class="mt-4 flex flex-wrap gap-2">
                    @if (!empty($e['pembina']))
                      <span
                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium {{ $palette['chip'] }}">
                        Pembina: {{ $e['pembina'] }}
                      </span>
                    @endif

                    @if (!empty($e['lokasi']))
                      <span
                        class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                        Lokasi: {{ $e['lokasi'] }}
                      </span>
                    @endif
                  </div>
                </article>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </section>

    {{-- PENGUMUMAN --}}
    <section
      class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_30px_80px_rgba(15,23,42,0.10)]">

      <div class="border-b border-slate-200/80 px-5 py-5 md:px-6 lg:px-7">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div class="flex min-w-0 items-start gap-4">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-[0_12px_26px_rgba(59,130,246,0.25)]">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10v4m-2 0h6l4 3V7l-4 3H6a2 2 0 0 0 0 4z" />
              </svg>
            </div>

            <div class="min-w-0">
              <div
                class="mb-2 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.16em] text-blue-700">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                Informasi Sekolah
              </div>

              <h2 class="text-lg font-semibold tracking-tight text-slate-900">
                Pengumuman Sekolah
              </h2>
              <p class="mt-1 text-xs leading-5 text-slate-500">
                Informasi penting terbaru yang sesuai dengan target siswa.
              </p>
            </div>
          </div>

          <a href="{{ route('ortu.pengumuman.index') }}"
            class="inline-flex w-fit shrink-0 items-center justify-center rounded-2xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-xs font-medium text-blue-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-100 hover:shadow-md">
            Lihat semua
          </a>
        </div>
      </div>

      <div class="p-5 md:p-6 lg:p-7">
        @if ($latestPengumuman->isEmpty())
          <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50/70 px-5 py-12 text-center shadow-inner">
            <p class="text-sm font-semibold text-slate-700">
              Belum ada pengumuman aktif.
            </p>
            <p class="mt-1 text-xs text-slate-500">
              Pengumuman terbaru akan tampil di sini.
            </p>
          </div>
        @else
          <div
            class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-[0_16px_46px_rgba(15,23,42,0.06)]">
            <div class="divide-y divide-slate-100">
              @foreach ($latestPengumuman as $p)
                @php
                  $jenis = $p->jenis ?? 'lainnya';

                  $config = match ($jenis) {
                    'akademik' => [
                      'line' => 'bg-blue-500',
                      'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
                      'hover' => 'group-hover:text-blue-700',
                    ],
                    'kegiatan' => [
                      'line' => 'bg-emerald-500',
                      'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                      'hover' => 'group-hover:text-emerald-700',
                    ],
                    'prestasi' => [
                      'line' => 'bg-amber-500',
                      'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                      'hover' => 'group-hover:text-amber-700',
                    ],
                    default => [
                      'line' => 'bg-slate-400',
                      'badge' => 'bg-slate-50 text-slate-700 border-slate-200',
                      'hover' => 'group-hover:text-slate-900',
                    ],
                  };
                @endphp

                <a href="{{ route('ortu.pengumuman.show', $p->id) }}"
                  class="group block transition duration-300 hover:bg-blue-50/35">
                  <div class="flex min-w-0">
                    <div class="w-1.5 shrink-0 {{ $config['line'] }}"></div>

                    <div class="min-w-0 flex-1 px-5 py-4">
                      <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="min-w-0 flex-1">
                          <div class="flex flex-wrap items-center gap-2">
                            <span
                              class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium {{ $config['badge'] }}">
                              {{ ucfirst($jenis) }}
                            </span>

                            <span class="text-[11px] text-slate-400">
                              {{ $p->publish_at ? \Carbon\Carbon::parse($p->publish_at)->translatedFormat('d M Y') : '-' }}
                            </span>
                          </div>

                          <h3
                            class="mt-2 line-clamp-1 text-sm font-semibold leading-6 text-slate-900 transition {{ $config['hover'] }}">
                            {{ $p->judul }}
                          </h3>

                          <p class="mt-1 line-clamp-2 text-xs leading-6 text-slate-500">
                            {{ \Illuminate\Support\Str::limit(strip_tags($p->isi), 170) }}
                          </p>
                        </div>

                        <div class="shrink-0 md:pt-2">
                          <span class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600">
                            Baca detail
                            <svg xmlns="http://www.w3.org/2000/svg"
                              class="h-3.5 w-3.5 transition duration-300 group-hover:translate-x-1" fill="none"
                              viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </a>
              @endforeach
            </div>
          </div>
        @endif
      </div>
    </section>
  </div>

  {{-- CHART.JS --}}
  @if (!empty($nilaiApi))
    @php
      $labels = collect($nilaiApi)
        ->map(fn($n) => $n['mapel'] ?? 'Mapel')
        ->values();

      $values = collect($nilaiApi)
        ->map(function ($n) {
          $v = $n['nilai_akhir'] ?? 0;
          return is_numeric($v) ? (float) $v : 0;
        })
        ->values();
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('chartNilaiPie');
        if (!ctx) return;

        const labels = @json($labels->toArray());
        const values = @json($values->toArray());

        const validIndexes = values
          .map((value, index) => ({ value, index }))
          .filter(item => Number(item.value) > 0);

        if (validIndexes.length === 0) return;

        const filteredLabels = validIndexes.map(item => labels[item.index]);
        const filteredValues = validIndexes.map(item => values[item.index]);

        new Chart(ctx, {
          type: 'pie',
          data: {
            labels: filteredLabels,
            datasets: [{
              data: filteredValues,
              backgroundColor: [
                '#3B82F6',
                '#06B6D4',
                '#10B981',
                '#F59E0B',
                '#F97316',
                '#EF4444',
                '#8B5CF6',
                '#EC4899',
                '#14B8A6',
                '#6366F1'
              ],
              borderColor: '#ffffff',
              borderWidth: 3,
              hoverOffset: 10,
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  boxWidth: 12,
                  boxHeight: 12,
                  padding: 14,
                  color: '#475569',
                  font: {
                    size: 11
                  }
                }
              }
            }
          }
        });
      });
    </script>
  @endif

  {{-- AUTO RELOAD RINGAN --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      let reloadScheduled = false;

      function scheduleReload() {
        if (reloadScheduled) return;
        reloadScheduled = true;

        setTimeout(() => {
          if (document.visibilityState === 'visible' && navigator.onLine) {
            window.location.reload();
          } else {
            reloadScheduled = false;
          }
        }, 60000);
      }

      scheduleReload();

      document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && navigator.onLine) {
          setTimeout(() => {
            window.location.reload();
          }, 1200);
        }
      });

      window.addEventListener('online', function () {
        setTimeout(() => {
          window.location.reload();
        }, 1200);
      });
    });
  </script>
@endsection