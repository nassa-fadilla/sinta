@extends('admin.layout')
@section('title', 'Kelola Survei')

@section('content')
    <div class="space-y-6">
        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5h6m-3-2.25A1.75 1.75 0 0 1 13.75 4.5h.75A2.75 2.75 0 0 1 17.25 7.25v9.5A2.75 2.75 0 0 1 14.5 19.5h-5A2.75 2.75 0 0 1 6.75 16.75v-9.5A2.75 2.75 0 0 1 9.5 4.5h.75A1.75 1.75 0 0 1 12 2.75z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                                Kelola Survei
                            </h1>
                            <p class="mt-1 text-sm text-slate-500">
                                Ubah informasi survei dan kelola daftar pertanyaan.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        @if($survei->is_active)
                            <span
                                class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                Aktif
                            </span>
                        @else
                            <span
                                class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                Nonaktif
                            </span>
                        @endif

                        <a href="{{ route('admin.survei.index') }}"
                            class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Kembali</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-5 md:p-6 space-y-6">
                @if(session('ok'))
                    <div
                        class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                        {{ session('ok') }}
                    </div>
                @endif

                {{-- INFORMASI SURVEI --}}
                <div class="rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-semibold text-slate-800">Informasi Survei</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            Atur judul, deskripsi, periode, dan status keaktifan survei.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.survei.update', $survei->id) }}"
                        class="p-5 md:p-6 space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Judul Survei
                            </label>
                            <input type="text" name="judul" value="{{ $survei->judul }}"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100"
                                required>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Deskripsi
                            </label>
                            <textarea name="deskripsi" rows="3"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">{{ $survei->deskripsi }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Mulai</label>
                                <input type="datetime-local" name="mulai_at"
                                    value="{{ $survei->mulai_at ? date('Y-m-d\TH:i', strtotime($survei->mulai_at)) : '' }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Akhir</label>
                                <input type="datetime-local" name="akhir_at"
                                    value="{{ $survei->akhir_at ? date('Y-m-d\TH:i', strtotime($survei->akhir_at)) : '' }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100">
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" name="is_active" {{ $survei->is_active ? 'checked' : '' }}
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <label class="text-sm font-medium text-slate-700">
                                Aktifkan survei
                            </label>
                        </div>

                        <div class="flex justify-end border-t border-slate-200 pt-5">
                            <button
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-500 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md sm:w-auto">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>

                {{-- BUILDER PERTANYAAN --}}
                <div class="rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-800">Daftar Pertanyaan</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    Tambah, ubah teks, atur urutan, dan kelola opsi jawaban.
                                </p>
                            </div>

                            <span
                                class="inline-flex w-fit items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                {{ $pertanyaan->count() }} pertanyaan
                            </span>
                        </div>
                    </div>

                    <div class="p-5 md:p-6 space-y-5">
                        {{-- Tambah pertanyaan --}}
                        <form method="POST" action="{{ route('admin.survei.storePertanyaan', $survei->id) }}"
                            class="grid grid-cols-1 gap-4 rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-4 md:grid-cols-[1fr_220px_auto] md:items-end">
                            @csrf

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Pertanyaan</label>
                                <input type="text" name="pertanyaan"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    required>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Tipe</label>
                                <select name="tipe"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    <option value="text">Teks</option>
                                    <option value="textarea">Teks Panjang</option>
                                    <option value="radio">Pilihan Ganda</option>
                                    <option value="checkbox">Checkbox</option>
                                    <option value="dropdown">Dropdown</option>
                                    <option value="skala">Skala (1–5)</option>
                                </select>
                            </div>

                            <button
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-blue-500 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-600 md:w-auto">
                                Tambah
                            </button>
                        </form>

                        {{-- List pertanyaan --}}
                        @forelse($pertanyaan as $i => $p)
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-4 shadow-sm">
                                <div class="flex flex-col gap-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex min-w-0 flex-1 items-start gap-3">
                                            <span
                                                class="inline-flex h-8 min-w-[32px] items-center justify-center rounded-full bg-blue-50 px-2 text-xs font-semibold text-blue-700 ring-1 ring-blue-200">
                                                {{ $i + 1 }}
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                <form method="POST"
                                                    action="{{ route('admin.survei.updatePertanyaan', $p->id) }}">
                                                    @csrf
                                                    @method('PUT')

                                                    <input type="hidden" name="tipe" value="{{ $p->tipe }}">

                                                    <input type="text" name="pertanyaan" value="{{ $p->pertanyaan }}"
                                                        class="autosave-input w-full border-0 border-b border-slate-300 bg-transparent px-0 py-1 text-sm font-medium text-slate-800 focus:border-blue-500 focus:ring-0">
                                                    <span class="status-msg mt-1 block text-[11px] text-slate-400"></span>
                                                </form>

                                                <div class="mt-2">
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600 ring-1 ring-slate-200">
                                                        {{ strtoupper($p->tipe) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex shrink-0 items-center justify-end gap-1">
                                            <form method="POST"
                                                action="{{ route('admin.survei.reorderPertanyaan', $survei->id) }}">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $p->id }}">
                                                <button name="direction" value="up"
                                                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-blue-50 hover:text-blue-600"
                                                    title="Naik">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M5 15l7-7 7 7" />
                                                    </svg>
                                                </button>
                                            </form>

                                            <form method="POST"
                                                action="{{ route('admin.survei.reorderPertanyaan', $survei->id) }}">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $p->id }}">
                                                <button name="direction" value="down"
                                                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-blue-50 hover:text-blue-600"
                                                    title="Turun">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.survei.destroyPertanyaan', $p->id) }}"
                                                onsubmit="return confirm('Hapus pertanyaan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-lg p-1.5 text-rose-500 transition hover:bg-rose-50 hover:text-rose-700"
                                                    title="Hapus">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    @if(in_array($p->tipe, ['radio', 'checkbox', 'dropdown', 'skala']))
                                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                Opsi Jawaban
                                            </h4>

                                            <div class="space-y-3">
                                                @foreach($p->opsi as $o)
                                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                        <form method="POST" action="{{ route('admin.survei.updateOpsi', $o->id) }}"
                                                            class="flex min-w-0 flex-1 flex-col gap-1 sm:flex-row sm:items-center sm:gap-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="text" name="opsi" value="{{ $o->opsi }}"
                                                                class="autosave-input w-full border-0 border-b border-slate-300 bg-transparent px-0 py-1.5 text-sm text-slate-700 focus:border-blue-500 focus:ring-0">
                                                            <span class="status-msg shrink-0 text-[11px] text-slate-400"></span>
                                                        </form>

                                                        <form method="POST" action="{{ route('admin.survei.destroyOpsi', $o->id) }}"
                                                            onsubmit="return confirm('Hapus opsi ini?')" class="shrink-0">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="inline-flex items-center justify-center rounded-xl px-2.5 py-1.5 text-[11px] font-medium text-rose-500 transition hover:bg-rose-50 hover:text-rose-700">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <form method="POST" action="{{ route('admin.survei.storeOpsi', $p->id) }}"
                                                class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                                                @csrf

                                                <input type="text" name="opsi" placeholder="Tambah opsi baru..."
                                                    class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                                    required>

                                                <button
                                                    class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-blue-700 sm:w-auto">
                                                    Tambah
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-sm text-slate-500">
                                Belum ada pertanyaan.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const delay = (ms) => new Promise(res => setTimeout(res, ms));
            let timers = {};

            document.querySelectorAll('.autosave-input').forEach(input => {
                const form = input.closest('form');
                const status = form.querySelector('.status-msg');

                input.addEventListener('input', () => {
                    const id = form.action;
                    if (timers[id]) clearTimeout(timers[id]);
                    if (status) status.textContent = 'Menyimpan...';

                    timers[id] = setTimeout(async () => {
                        try {
                            const formData = new FormData(form);
                            const res = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'X-HTTP-Method-Override': 'PUT'
                                },
                                body: formData
                            });

                            if (res.ok) {
                                if (status) {
                                    status.textContent = 'Tersimpan';
                                    await delay(1500);
                                    status.textContent = '';
                                }
                            } else {
                                throw new Error();
                            }
                        } catch {
                            if (status) status.textContent = 'Gagal menyimpan';
                        }
                    }, 1000);
                });
            });
        });
    </script>
@endsection