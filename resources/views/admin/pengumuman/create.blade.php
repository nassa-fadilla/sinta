@extends('admin.layout')
@section('title', 'Buat Pengumuman')

@section('content')
  <form method="POST" action="{{ route('admin.pengumuman.store') }}" class="space-y-6">
    @csrf

    <section
      class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

      {{-- HEADER --}}
      <div class="border-b border-slate-200 px-5 py-5 md:px-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div class="flex items-start gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 8.25L19.5 4.5m-3.75 3.75L19.5 12m-3.75-3.75H13.5L9 4.5H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5A2.25 2.25 0 0 0 6.75 19.5H9l4.5-3.75h2.25a2.25 2.25 0 0 0 2.25-2.25v-3A2.25 2.25 0 0 0 15.75 8.25z" />
              </svg>
            </div>

            <div>
              <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                Buat Pengumuman
              </h1>
              <p class="mt-1 text-sm text-slate-500">
                Atur informasi, target penerima, isi, dan periode tayang sebelum diajukan.
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="p-5 md:p-6 space-y-6">

        @if ($errors->any())
          <div class="rounded-[1.5rem] border border-rose-200 bg-rose-50/80 px-4 py-4 shadow-sm">
            <p class="text-sm font-semibold text-rose-700">Terdapat data yang perlu diperiksa:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">
              Judul Pengumuman
            </label>
            <input type="text" name="judul" value="{{ old('judul') }}" required
              class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
            @error('judul')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
              Jenis Pengumuman
            </label>
            <select name="jenis"
              class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
              <option value="umum" @selected(old('jenis') === 'umum')>Umum</option>
              <option value="akademik" @selected(old('jenis') === 'akademik')>Akademik</option>
              <option value="kegiatan" @selected(old('jenis') === 'kegiatan')>Kegiatan</option>
              <option value="prestasi" @selected(old('jenis') === 'prestasi')>Prestasi</option>
              <option value="lainnya" @selected(old('jenis') === 'lainnya')>Lainnya</option>
            </select>
            @error('jenis')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
              Tahun Ajaran <span class="text-xs text-slate-400">(opsional)</span>
            </label>
            <select name="tahun_ajaran_id"
              class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
              <option value="">-- Pilih --</option>
              @foreach($tahunAjarans as $t)
                @php
                  $isAktif = strtolower((string) ($t->status ?? '')) === 'aktif';
                  $isSelected = old('tahun_ajaran_id')
                    ? old('tahun_ajaran_id') == $t->id
                    : $isAktif;
                @endphp
                <option value="{{ $t->id }}" @selected($isSelected)>
                  {{ $t->nama_tahun }} (Semester {{ $t->semester }}){{ $isAktif ? ' — Aktif' : '' }}
                </option>
              @endforeach
            </select>
            @error('tahun_ajaran_id')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="space-y-3">
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
            Periode Tayang
          </p>

          <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">
                Mulai Tayang
              </label>
              <input type="datetime-local" name="publish_at"
                value="{{ old('publish_at') ? \Carbon\Carbon::parse(old('publish_at'))->format('Y-m-d\TH:i') : \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i') }}"
                required
                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
              @error('publish_at')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">
                Akhir Tayang <span class="text-xs text-slate-400">(opsional)</span>
              </label>
              <input type="datetime-local" name="expire_at"
                value="{{ old('expire_at') ? \Carbon\Carbon::parse(old('expire_at'))->format('Y-m-d\TH:i') : '' }}"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
              @error('expire_at')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <p class="text-xs text-slate-500">
            Periode tayang diisi saat pengumuman dibuat agar dapat ditinjau bersama isi pengumuman.
          </p>
        </div>

        <div x-data="{ scope: '{{ old('target_scope', 'all') }}' }" class="space-y-3">
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
            Target Penerima
          </p>

          <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">
                Jenis Target
              </label>
              <select name="target_scope" x-on:change="scope = $event.target.value"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
                <option value="all" @selected(old('target_scope', 'all') === 'all')>Semua</option>
                <option value="tingkat" @selected(old('target_scope') === 'tingkat')>Per Tingkat</option>
              </select>
              @error('target_scope')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <template x-if="scope === 'tingkat'">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">
                  Pilih Tingkat
                </label>
                <select name="target_tingkat"
                  class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
                  <option value="">-- Pilih --</option>
                  @foreach($tingkatList as $tg)
                    <option value="{{ $tg }}" @selected(old('target_tingkat') == $tg)>
                      Tingkat {{ $tg }}
                    </option>
                  @endforeach
                </select>
                @error('target_tingkat')
                  <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
              </div>
            </template>

            <template x-if="scope === 'all'">
              <div class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-3 text-sm text-slate-500">
                Pengumuman akan dikirim ke semua orang tua tanpa filter tingkat.
              </div>
            </template>
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">
            Isi Pengumuman
          </label>
          <textarea name="isi" rows="7" required
            class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">{{ old('isi') }}</textarea>
          @error('isi')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <a href="{{ route('admin.pengumuman.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
            Batal
          </a>
          <button
            class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600">
            Ajukan
          </button>
        </div>

      </div>
    </section>
  </form>
@endsection