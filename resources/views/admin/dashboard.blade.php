@extends('admin.layout')
@section('title', 'Dashboard Admin')

@section('content')
  @php
    $presensiTotalToday = $presensiTotalToday ?? 0;
    $presensiHadirToday = $presensiHadirToday ?? 0;
    $presensiPercent = $presensiPercent ?? 0;
    $genderL = $genderL ?? 0;
    $genderP = $genderP ?? 0;

    $topSiswaCollection = collect($topSiswa ?? []);
    $jadwalCollection = collect($jadwalHariIni ?? []);
    $rekapNilaiTingkatCollection = collect($rekapNilaiTingkat ?? []);
    $pengumumanAktif = $pengumumanAktif ?? collect();

    $hariTabs = $hariTabs ?? [
      'Senin',
      'Selasa',
      'Rabu',
      'Kamis',
      'Jumat',
    ];

    $hariAktif = $hariAktif ?? 'Senin';
    $hariSekarang = $hariSekarang ?? \Illuminate\Support\Carbon::now()->locale('id')->translatedFormat('l');

    $safePresensiPercent = min(max((float) $presensiPercent, 0), 100);
  @endphp

  <div class="space-y-6">

    {{-- HEADER --}}
    <div
      class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_12px_36px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_18px_48px_rgba(15,23,42,0.10)]">
      <div
        class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.08),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(168,85,247,0.06),transparent_24%)]">
      </div>
      <div class="absolute -top-16 -right-16 h-44 w-44 rounded-full bg-blue-300/15 blur-3xl"></div>
      <div class="absolute -bottom-14 -left-10 h-36 w-36 rounded-full bg-fuchsia-300/10 blur-3xl"></div>

      <div class="relative px-5 py-5 md:px-7 md:py-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 class="flex items-center gap-3 text-2xl font-semibold text-slate-800">
              <span
                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-sky-400 text-white shadow-lg shadow-blue-200/50 transition duration-300 hover:scale-[1.03]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor" stroke-width="1.9">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
                </svg>
              </span>
              <span>Dashboard Admin</span>
            </h1>

            <p class="mt-2 text-sm text-slate-500">
              Ringkasan data akademik dan aktivitas sistem dari integrasi SIA dan modul internal SINTA.
            </p>

            <p class="mt-1 text-xs text-slate-400">
              Login sebagai:
              <span class="font-semibold text-slate-600">{{ auth()->user()->name ?? 'Admin SINTA' }}</span>
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <div
              class="min-w-[240px] rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-[0_10px_28px_rgba(59,130,246,0.08)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_16px_34px_rgba(59,130,246,0.12)]">
              <div class="flex items-center gap-3">
                <div
                  class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-400 text-white shadow-md shadow-blue-200/50">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 3v3m8-3v3M4 9h16M5 5h14a2 2 0 012 2v11a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                  </svg>
                </div>
                <div>
                  <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    Tahun Ajaran Aktif
                  </div>
                  <div class="mt-0.5 text-sm font-semibold leading-tight text-slate-800">
                    {{ $tahunAjaranAktif ?? '—' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- QUICK STATS --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {{-- Guru --}}
      <div
        class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(59,130,246,0.16)]">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-100/70 via-white/20 to-sky-100/70"></div>
        <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-blue-300/25 blur-2xl"></div>

        <div class="relative flex items-center justify-between gap-3">
          <div>
            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-700">
              Jumlah Guru
            </div>
            <div class="mt-2 text-3xl font-bold leading-none text-slate-800">
              {{ number_format($totalGuru ?? 0) }}
            </div>
            <div class="mt-1 text-[11px] text-slate-500">
              Data guru pada SIA
            </div>
          </div>

          <div
            class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-blue-600 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
              <path d="M8 7a3 3 0 116 0 3 3 0 01-6 0z" />
              <path d="M4 19a6 6 0 1116 0v1H4v-1z" />
            </svg>
          </div>
        </div>
      </div>

      {{-- Siswa --}}
      <div
        class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(16,185,129,0.16)]">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-100/70 via-white/20 to-teal-100/70"></div>
        <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-emerald-300/25 blur-2xl"></div>

        <div class="relative flex items-center justify-between gap-3">
          <div>
            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700">
              Jumlah Siswa
            </div>
            <div class="mt-2 text-3xl font-bold leading-none text-slate-800">
              {{ number_format($totalSiswa ?? 0) }}
            </div>
            <div class="mt-1 text-[11px] text-slate-500">
              Siswa aktif terdata
            </div>
          </div>

          <div
            class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-emerald-600 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M12 12a3 3 0 100-6 3 3 0 000 6zM2 20a8 8 0 0116 0v1H2v-1zM18 8a2 2 0 114 0 2 2 0 01-4 0zM20 12c1.657 0 3 1.79 3 4v1h-2v-1c0-1.657-.895-3-1-3z" />
            </svg>
          </div>
        </div>
      </div>

      {{-- Rombel --}}
      <div
        class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(245,158,11,0.16)]">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-100/70 via-white/20 to-orange-100/70"></div>
        <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-amber-300/25 blur-2xl"></div>

        <div class="relative flex items-center justify-between gap-3">
          <div>
            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-700">
              Jumlah Rombel
            </div>
            <div class="mt-2 text-3xl font-bold leading-none text-slate-800">
              {{ number_format($totalKelas ?? 0) }}
            </div>
            <div class="mt-1 text-[11px] text-slate-500">
              Kelas/rombel aktif
            </div>
          </div>

          <div
            class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-amber-600 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
              <path d="M4 6h16v10H4z" />
              <path d="M10 20h4" />
            </svg>
          </div>
        </div>
      </div>

      {{-- Presensi --}}
      <div
        class="relative overflow-hidden rounded-[1.75rem] border border-white/50 bg-white/55 p-4 backdrop-blur-xl shadow-[0_10px_35px_rgba(30,41,59,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(14,165,233,0.16)]">
        <div class="absolute inset-0 bg-gradient-to-br from-sky-100/70 via-white/20 to-cyan-100/70"></div>
        <div class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-sky-300/25 blur-2xl"></div>

        <div class="relative flex items-start justify-between gap-3">
          <div class="flex-1">
            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-sky-700">
              Presensi Hari Ini
            </div>

            <div class="mt-2 flex flex-wrap items-baseline gap-2">
              <span class="text-2xl font-bold leading-none text-slate-800">
                {{ $presensiHadirToday }} / {{ $presensiTotalToday }}
              </span>
              <span class="text-xs font-semibold text-sky-700">
                {{ number_format($presensiPercent, 1) }}%
              </span>
            </div>

            <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/80 shadow-inner">
              <div class="h-full rounded-full bg-gradient-to-r from-sky-500 via-cyan-500 to-blue-500"
                style="width: {{ $safePresensiPercent }}%"></div>
            </div>

            <div class="mt-1 text-[11px] text-slate-500">
              Rekap presensi harian
            </div>
          </div>

          <div
            class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white/70 bg-white/80 text-sky-600 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
              stroke-width="1.9">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    {{-- CHARTS --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
      {{-- Tren Presensi --}}
      <div
        class="lg:col-span-2 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h2 class="text-sm font-semibold text-slate-800">
              Tren Presensi (12 Bulan Terakhir)
            </h2>
            <p class="mt-1 text-xs text-slate-500">
              Persentase kehadiran bulanan siswa dalam satu tahun terakhir.
            </p>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm">
          <div class="h-[290px] w-full">
            <canvas id="presensiLine"></canvas>
          </div>
        </div>
      </div>

      {{-- Gender --}}
      <div
        class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
          <div>
            <h2 class="text-sm font-semibold text-slate-800">
              Komposisi Siswa
            </h2>
            <p class="mt-1 text-xs text-slate-500">
              Perbandingan jumlah siswa laki-laki dan perempuan.
            </p>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm">
          <div class="h-[250px]">
            <canvas id="genderBar"></canvas>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
          <div class="rounded-2xl border border-blue-100 bg-blue-50/90 px-3 py-2 text-blue-700 shadow-sm">
            <div class="flex items-center gap-2">
              <span class="inline-block h-2.5 w-2.5 rounded-full bg-blue-500"></span>
              <span class="font-medium">Laki-laki</span>
            </div>
            <div class="mt-1 text-lg font-semibold">{{ $genderL }}</div>
          </div>

          <div class="rounded-2xl border border-pink-100 bg-pink-50/90 px-3 py-2 text-pink-700 shadow-sm">
            <div class="flex items-center gap-2">
              <span class="inline-block h-2.5 w-2.5 rounded-full bg-pink-500"></span>
              <span class="font-medium">Perempuan</span>
            </div>
            <div class="mt-1 text-lg font-semibold">{{ $genderP }}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- JADWAL HARI INI --}}
    <div
      class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
      <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h2 class="text-sm font-semibold text-slate-800">
            Jadwal Pembelajaran
          </h2>
          <p class="mt-1 text-xs text-slate-500">
            Ringkasan jadwal pembelajaran berdasarkan hari yang dipilih.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <span
            class="inline-flex items-center rounded-full border border-sky-100 bg-sky-50 px-3 py-1 text-[11px] font-medium text-sky-700 shadow-sm">
            Hari aktif: {{ $hariAktif }}
          </span>

          @if($hariAktif === $hariSekarang)
            <span
              class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-medium text-blue-700 shadow-sm">
              Live hari ini
            </span>
          @endif
        </div>
      </div>

      {{-- TAB HARI --}}
      <div class="mb-4">
        <div
          class="inline-flex w-fit max-w-full flex-wrap items-center gap-1 rounded-full border border-slate-200 bg-white-50 p-1 shadow-sm">
          @foreach($hariTabs as $hari)
                <a href="{{ route('admin.dashboard', ['hari' => $hari]) }}" class="rounded-full px-3 py-1.5 text-xs font-medium transition whitespace-nowrap
                                                                                {{ $hariAktif === $hari
            ? 'bg-blue-600 text-white shadow-sm'
            : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700' }}">
                  {{ $hari }}
                </a>
          @endforeach
        </div>
      </div>

      @if($jadwalCollection->isEmpty())
        <div
          class="mt-3 rounded-3xl border border-dashed border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
          Tidak ada jadwal untuk hari {{ strtolower($hariAktif) }}.
        </div>
      @else
        {{-- MOBILE --}}
        <div class="mt-3 space-y-2 md:hidden">
          @foreach($jadwalCollection as $j)
            <div class="rounded-3xl px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md
                                                              {{ !empty($j->is_active_now)
            ? 'border border-blue-200 bg-gradient-to-br from-blue-50 via-white to-sky-50 ring-1 ring-blue-100'
            : (!empty($j->is_upcoming)
              ? 'border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-yellow-50'
              : 'border border-slate-200 bg-white') }}">
              <div class="flex items-center justify-between gap-2">
                <div class="font-semibold text-slate-800">
                  {{ \Illuminate\Support\Str::of((string) ($j->jam_mulai ?? ''))->substr(0, 5) }}
                  –
                  {{ \Illuminate\Support\Str::of((string) ($j->jam_selesai ?? ''))->substr(0, 5) }}
                </div>
                <span
                  class="rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-[10px] font-semibold text-blue-700">
                  {{ is_scalar($j->rombel ?? null) ? $j->rombel : '-' }}
                </span>
              </div>

              @if(!empty($j->is_active_now))
                <div class="mt-2">
                  <span
                    class="inline-flex items-center rounded-full border border-blue-200 bg-blue-600 px-2.5 py-1 text-[10px] font-semibold text-white shadow-sm">
                    Sedang berlangsung
                  </span>
                </div>
              @elseif(!empty($j->is_upcoming))
                <div class="mt-2">
                  <span
                    class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-semibold text-amber-700">
                    Akan dimulai
                  </span>
                </div>
              @endif

              <div class="mt-2 text-sm font-medium text-slate-700">
                {{ is_scalar($j->mapel ?? null) ? $j->mapel : '-' }}
              </div>
              <div class="mt-1 text-xs text-slate-500">
                {{ is_scalar($j->guru ?? null) ? $j->guru : '-' }}
              </div>
            </div>
          @endforeach
        </div>

        {{-- DESKTOP --}}
        <div class="mt-3 hidden overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white md:block">
          <table class="w-full text-[13px]">
            <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-4 py-3 text-left">Jam</th>
                <th class="px-4 py-3 text-left">Rombel</th>
                <th class="px-4 py-3 text-left">Mata Pelajaran</th>
                <th class="px-4 py-3 text-left">Guru</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach($jadwalCollection as $j)
                    <tr class="transition duration-300
                                                                                                  {{ !empty($j->is_active_now)
                ? 'bg-blue-50/80 hover:bg-blue-100/70'
                : (!empty($j->is_upcoming)
                  ? 'bg-amber-50/60 hover:bg-amber-100/60'
                  : 'hover:bg-sky-50/40') }}">
                      <td
                        class="px-4 py-3 whitespace-nowrap font-medium {{ !empty($j->is_active_now) ? 'text-blue-700' : (!empty($j->is_upcoming) ? 'text-amber-700' : 'text-slate-700') }}">
                        <div class="flex items-center gap-2">
                          <span>
                            {{ \Illuminate\Support\Str::of((string) ($j->jam_mulai ?? ''))->substr(0, 5) }}
                            –
                            {{ \Illuminate\Support\Str::of((string) ($j->jam_selesai ?? ''))->substr(0, 5) }}
                          </span>

                          @if(!empty($j->is_active_now))
                            <span
                              class="inline-flex items-center rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-semibold text-white">
                              Live
                            </span>
                          @elseif(!empty($j->is_upcoming))
                            <span
                              class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                              Soon
                            </span>
                          @endif
                        </div>
                      </td>
                      <td class="px-4 py-3 font-semibold text-slate-800">
                        {{ is_scalar($j->rombel ?? null) ? $j->rombel : '-' }}
                      </td>
                      <td class="px-4 py-3 text-slate-700">
                        {{ is_scalar($j->mapel ?? null) ? $j->mapel : '-' }}
                      </td>
                      <td class="px-4 py-3 text-slate-600">
                        {{ is_scalar($j->guru ?? null) ? $j->guru : '-' }}
                      </td>
                    </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    {{-- REKAP NILAI & TOP SISWA --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      {{-- Rata-rata per tingkat --}}
      <div
        class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
        <div class="mb-3 flex items-center justify-between gap-3">
          <div>
            <h2 class="text-sm font-semibold text-slate-800">
              Rata-rata Nilai per Tingkat
            </h2>
            <p class="mt-1 text-xs text-slate-500">
              Rekap rata-rata nilai siswa berdasarkan tingkat kelas.
            </p>
          </div>

          <div class="rounded-2xl border border-blue-100 bg-blue-50 px-3 py-2 text-right shadow-sm">
            <div class="text-[10px] font-semibold uppercase tracking-wide text-blue-500">
              Global
            </div>
            <div class="text-sm font-bold text-blue-700">
              {{ number_format($rataNilaiGlobal ?? 0, 2) }}
            </div>
          </div>
        </div>

        <div class="overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
              <tr>
                <th class="px-4 py-3 text-left">Tingkat</th>
                <th class="px-4 py-3 text-right">Rata-rata</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @forelse($rekapNilaiTingkatCollection as $r)
                <tr class="transition duration-300 hover:bg-blue-50/30">
                  <td class="px-4 py-3 font-medium text-slate-800">
                    {{ is_scalar($r->tingkat ?? null) ? $r->tingkat : '-' }}
                  </td>
                  <td class="px-4 py-3 text-right">
                    <span
                      class="inline-flex rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                      {{ number_format((float) ($r->rata_rata ?? 0), 2) }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="py-8 text-center text-sm text-slate-500">
                    Belum ada data nilai.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- Top siswa --}}
      <div
        class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
        <div class="mb-3">
          <h2 class="text-sm font-semibold text-slate-800">
            Top 5 Siswa dengan Nilai Tertinggi
          </h2>
          <p class="mt-1 text-xs text-slate-500">
            Daftar siswa dengan capaian rata-rata nilai tertinggi dari data SIA.
          </p>
        </div>

        @if($topSiswaCollection->isEmpty())
          <div class="rounded-3xl border border-dashed border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
            Belum ada data top siswa yang ditampilkan.
          </div>
        @else
          <div class="space-y-3">
            @foreach($topSiswaCollection as $index => $s)
              @php
                $rank = $index + 1;

                $badgeClass = match ($rank) {
                  1 => 'from-amber-400 to-yellow-300 text-amber-900',
                  2 => 'from-slate-300 to-slate-200 text-slate-700',
                  3 => 'from-orange-300 to-amber-200 text-orange-900',
                  default => 'from-blue-100 to-sky-100 text-blue-700',
                };
              @endphp

              <div
                class="rounded-[1.6rem] border border-slate-200 bg-white px-4 py-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between gap-3">
                  <div class="flex min-w-0 items-center gap-3">
                    <div
                      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $badgeClass }} text-sm font-bold shadow-sm">
                      {{ $rank }}
                    </div>

                    <div class="min-w-0">
                      <div class="truncate text-sm font-semibold text-slate-800">
                        {{ is_scalar($s->nama ?? null) ? $s->nama : '-' }}
                      </div>
                      <div class="mt-1 truncate text-xs text-slate-500">
                        {{ is_scalar($s->nama_rombel ?? null) ? $s->nama_rombel : '-' }}
                      </div>
                    </div>
                  </div>

                  <div class="shrink-0 text-right">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                      Nilai
                    </div>
                    <div class="bg-gradient-to-r from-blue-600 to-sky-500 bg-clip-text text-xl font-bold text-transparent">
                      {{ number_format((float) ($s->rata_rata ?? 0), 2) }}
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    {{-- PENGUMUMAN AKTIF --}}
    <div
      class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.07)] transition duration-300 hover:shadow-[0_18px_44px_rgba(15,23,42,0.10)] lg:p-5">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
          <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
            <span
              class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-violet-500 text-white shadow-md shadow-blue-200/40">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.9">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 4l2-2 2 2m-2-2v9m-7 4a7 7 0 1114 0v3H5v-3z" />
              </svg>
            </span>
            <span>Pengumuman Aktif</span>
          </h2>
          <p class="mt-1 text-xs leading-5 text-slate-500">
            Pengumuman resmi yang sedang aktif pada modul internal SINTA.
          </p>
        </div>

        <a href="{{ route('admin.pengumuman.index') }}"
          class="inline-flex shrink-0 items-center justify-center rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition duration-300 hover:bg-blue-100">
          Lihat semua
        </a>
      </div>

      @if(collect($pengumumanAktif)->isEmpty())
        <div class="rounded-3xl border border-dashed border-slate-200 bg-white py-8 text-center text-sm text-slate-500">
          Tidak ada pengumuman aktif.
        </div>
      @else
        <div class="space-y-3">
          @foreach($pengumumanAktif as $p)
            @php
              $jenis = $p->jenis ?? 'lainnya';

              [$strip, $badge] = match ($jenis) {
                'akademik' => ['from-blue-500 to-sky-400', 'bg-blue-50 text-blue-700 border-blue-100'],
                'kegiatan' => ['from-emerald-500 to-teal-400', 'bg-emerald-50 text-emerald-700 border-emerald-100'],
                'prestasi' => ['from-amber-500 to-orange-400', 'bg-amber-50 text-amber-700 border-amber-100'],
                default => ['from-slate-400 to-slate-300', 'bg-slate-50 text-slate-700 border-slate-100'],
              };
            @endphp

            <a href="{{ route('admin.pengumuman.show', $p) }}"
              class="group block overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
              <div class="flex min-w-0">
                <div class="w-1.5 shrink-0 rounded-l-[1.6rem] bg-gradient-to-b {{ $strip }}"></div>

                <div class="min-w-0 flex-1 px-4 py-3">
                  <div class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                      <div class="truncate text-sm font-semibold leading-6 text-slate-800 sm:pr-3">
                        {{ $p->judul }}
                      </div>

                      <div class="mt-1 text-[11px] leading-5 text-slate-500">
                        {{ optional($p->publish_at)->format('d M Y H:i') ?? '-' }}
                        @if($p->expire_at)
                          <span class="hidden sm:inline"> • </span>
                          <span class="block sm:inline">Berlaku s.d. {{ $p->expire_at->format('d M Y H:i') }}</span>
                        @endif
                      </div>
                    </div>

                    <div class="shrink-0">
                      <span
                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-semibold {{ $badge }}">
                        {{ ucfirst($jenis) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          @endforeach
        </div>
      @endif
    </div>
  </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    (function () {
      const ctx = document.getElementById('genderBar');
      if (!ctx || typeof Chart === 'undefined') return;

      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Laki-laki', 'Perempuan'],
          datasets: [{
            data: [{{ $genderL }}, {{ $genderP }}],
            backgroundColor: [
              'rgba(59, 130, 246, 0.85)',
              'rgba(244, 114, 182, 0.82)'
            ],
            borderRadius: 10,
            borderSkipped: false,
            maxBarThickness: 42
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: {
            duration: 900
          },
          scales: {
            x: {
              grid: {
                display: false,
                drawBorder: false
              },
              ticks: {
                color: '#64748b',
                font: {
                  size: 11
                }
              }
            },
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(148, 163, 184, 0.14)',
                drawBorder: false
              },
              ticks: {
                precision: 0,
                color: '#64748b',
                font: {
                  size: 11
                }
              }
            }
          },
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: 'rgba(15, 23, 42, 0.88)',
              titleColor: '#fff',
              bodyColor: '#e2e8f0',
              padding: 10,
              displayColors: false
            }
          }
        }
      });
    })();

    (function () {
      const ctx = document.getElementById('presensiLine');
      if (!ctx || typeof Chart === 'undefined') return;

      const labels = {!! json_encode($trendLabelsMonthly ?? []) !!};
      const dataSeries = {!! json_encode($trendSeriesMonthly ?? []) !!};

      new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Hadir (%)',
            data: dataSeries,
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.12)',
            fill: true,
            tension: 0.38,
            pointRadius: 4,
            pointHoverRadius: 5,
            pointBackgroundColor: '#6366f1',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: {
            duration: 900
          },
          scales: {
            x: {
              grid: {
                display: false,
                drawBorder: false
              },
              ticks: {
                color: '#64748b',
                font: {
                  size: 11
                }
              }
            },
            y: {
              suggestedMin: 0,
              suggestedMax: 100,
              grid: {
                color: 'rgba(148, 163, 184, 0.14)',
                drawBorder: false
              },
              ticks: {
                color: '#64748b',
                callback: value => value + '%',
                font: {
                  size: 11
                }
              }
            }
          },
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: 'rgba(15, 23, 42, 0.88)',
              titleColor: '#fff',
              bodyColor: '#e2e8f0',
              padding: 10,
              displayColors: false,
              callbacks: {
                label: function (context) {
                  return ' ' + context.raw + '%';
                }
              }
            }
          }
        }
      });
    })();
  </script>
@endpush