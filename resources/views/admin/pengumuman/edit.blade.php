@extends('admin.layout')
@section('title', 'Ubah Pengumuman')

@section('content')
    @php
        $selectedScope = old('target_scope', $item->target_scope);
        $selectedTingkat = old('target_tingkat', $item->target_tingkat);

        if (!in_array($selectedScope, ['all', 'tingkat'])) {
            $selectedScope = 'all';
        }

        $statusBadge = match ($item->status) {
            'draft' => ['Draft', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200', 'bg-amber-500'],
            'approved' => ['Disetujui', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200', 'bg-emerald-500'],
            'rejected' => ['Ditolak', 'bg-rose-50 text-rose-700 ring-1 ring-rose-200', 'bg-rose-500'],
            default => ['-', 'bg-slate-50 text-slate-700 ring-1 ring-slate-200', 'bg-slate-400'],
        };
    @endphp

    <form method="POST" action="{{ route('admin.pengumuman.update', $item->id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.232 5.232l3.536 3.536M9 13l6.732-6.732a2 2 0 112.828 2.828L11.828 15.828H9v-2.828z" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Ubah Pengumuman
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Sesuaikan informasi pengumuman sebelum diajukan atau setelah mendapat catatan revisi.
                            </p>
                        </div>
                    </div>

                    <span
                        class="inline-flex items-center gap-2 self-start rounded-full px-3.5 py-1.5 text-xs font-semibold {{ $statusBadge[1] }}">
                        <span class="h-2 w-2 rounded-full {{ $statusBadge[2] }}"></span>
                        {{ $statusBadge[0] }}
                    </span>
                </div>
            </div>

            <div class="p-5 md:p-6 space-y-6">

                @if ($errors->any())
                    <div class="rounded-[1.25rem] border border-rose-200 bg-rose-50/80 px-4 py-4 shadow-sm">
                        <p class="text-sm font-semibold text-rose-700">Periksa kembali isian berikut:</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-6">

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Judul Pengumuman</label>
                            <input type="text" name="judul" value="{{ old('judul', $item->judul) }}" required
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                                {{ $item->status === 'approved' ? 'readonly' : '' }}>
                            @error('judul')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Jenis Pengumuman</label>
                            <select name="jenis"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                                {{ $item->status === 'approved' ? 'disabled' : '' }}>
                                @foreach(['umum', 'akademik', 'kegiatan', 'prestasi', 'lainnya'] as $jenis)
                                    <option value="{{ $jenis }}" @selected(old('jenis', $item->jenis) === $jenis)>
                                        {{ ucfirst($jenis) }}
                                    </option>
                                @endforeach
                            </select>
                            @if($item->status === 'approved')
                                <input type="hidden" name="jenis" value="{{ old('jenis', $item->jenis) }}">
                            @endif
                            @error('jenis')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Tahun Ajaran <span class="text-xs text-slate-400">(opsional)</span>
                            </label>
                            <select name="tahun_ajaran_id"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                                {{ $item->status === 'approved' ? 'disabled' : '' }}>
                                <option value="">-- Pilih --</option>
                                @foreach($tahunAjarans as $t)
                                    <option value="{{ $t->id }}"
                                        @selected(old('tahun_ajaran_id', $item->tahun_ajaran_id) == $t->id)>
                                        {{ $t->nama_tahun }} (Semester {{ $t->semester }})
                                        @if($t->status)
                                            — {{ ucfirst($t->status) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @if($item->status === 'approved')
                                <input type="hidden" name="tahun_ajaran_id"
                                    value="{{ old('tahun_ajaran_id', $item->tahun_ajaran_id) }}">
                            @endif
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
                                <label class="mb-1 block text-sm font-medium text-slate-700">Mulai Tayang</label>
                                <input type="datetime-local" name="publish_at"
                                    value="{{ old('publish_at', $item->publish_at ? \Carbon\Carbon::parse($item->publish_at)->format('Y-m-d\TH:i') : '') }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                                    {{ $item->status === 'approved' ? 'readonly' : '' }}>
                                @error('publish_at')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                    Akhir Tayang <span class="text-xs text-slate-400">(opsional)</span>
                                </label>
                                <input type="datetime-local" name="expire_at"
                                    value="{{ old('expire_at', $item->expire_at ? \Carbon\Carbon::parse($item->expire_at)->format('Y-m-d\TH:i') : '') }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                                    {{ $item->status === 'approved' ? 'readonly' : '' }}>
                                @error('expire_at')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <p class="text-xs text-slate-500">
                            Periode tayang digunakan sebagai waktu publikasi pengumuman setelah disetujui.
                        </p>
                    </div>

                    <div x-data="{ scope: '{{ $selectedScope }}' }" class="space-y-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                            Target Penerima
                        </p>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Jenis Target</label>
                                <select name="target_scope"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                                    x-on:change="scope = $event.target.value"
                                    {{ $item->status === 'approved' ? 'disabled' : '' }}>
                                    <option value="all" @selected($selectedScope === 'all')>Semua</option>
                                    <option value="tingkat" @selected($selectedScope === 'tingkat')>Per Tingkat</option>
                                </select>
                                @if($item->status === 'approved')
                                    <input type="hidden" name="target_scope" value="{{ $selectedScope }}">
                                @endif
                                @error('target_scope')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div x-show="scope === 'all'" x-cloak
                                class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-3 text-sm text-slate-500">
                                Pengumuman akan dikirim ke semua orang tua tanpa filter tingkat.
                            </div>

                            <div x-show="scope === 'tingkat'" x-cloak class="md:col-span-2">
                                <p class="text-sm font-medium text-slate-700">
                                    Tingkat <span class="text-xs font-normal text-slate-500">(pilih salah satu)</span>
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($tingkatList as $tg)
                                        <label
                                            class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 transition hover:bg-slate-50">
                                            <input type="radio" name="target_tingkat" value="{{ $tg }}"
                                                @checked($selectedTingkat == $tg)
                                                class="text-blue-600 focus:ring-blue-500"
                                                {{ $item->status === 'approved' ? 'disabled' : '' }}>
                                            <span class="text-sm text-slate-700">
                                                Tingkat {{ $tg }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @if($item->status === 'approved')
                                    <input type="hidden" name="target_tingkat" value="{{ $selectedTingkat }}">
                                @endif
                                @error('target_tingkat')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Isi Pengumuman</label>
                        <textarea name="isi" rows="7" required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                            {{ $item->status === 'approved' ? 'readonly' : '' }}>{{ old('isi', $item->isi) }}</textarea>
                        @error('isi')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        @if(in_array($item->status, ['draft', 'rejected']))
            <button type="submit"
                form="delete-pengumuman-form"
                onclick="return confirm('Yakin ingin menghapus pengumuman ini? Data yang sudah dihapus tidak dapat dikembalikan.')"
                class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-5 py-2.5 text-sm font-medium text-rose-700 shadow-sm transition hover:border-rose-300 hover:bg-rose-100">
                Hapus
            </button>
        @endif
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.pengumuman.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
            Batal
        </a>

        @if(in_array($item->status, ['draft', 'rejected']))
            <button
                class="inline-flex items-center justify-center rounded-2xl bg-blue-500 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600">
                Simpan Perubahan
            </button>
        @else
            <span class="text-sm italic text-slate-500">
                Pengumuman aktif — tidak dapat diubah.
            </span>
        @endif
    </div>
</div>

            </div>
        </section>
    </form>

    @if(in_array($item->status, ['draft', 'rejected']))
        <form id="delete-pengumuman-form"
            method="POST"
            action="{{ route('admin.pengumuman.destroy', $item->id) }}"
            class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection