@extends('ortu.layout')
@section('title', 'Mulai Chat Baru')

@section('content')
    <div class="space-y-6">
        <section
            class="overflow-hidden rounded-[1.5rem] border border-slate-200/70 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.08)] transition duration-300 hover:shadow-[0_24px_70px_rgba(15,23,42,0.10)]">

            {{-- HEADER --}}
            <div class="border-b border-slate-200 px-5 py-5 md:px-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-500 text-white shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16h3m0 0h3m-3 0v3m0-3v-3m8-3a2 2 0 0 0-2-2H6A2 2 0 0 0 4 8v8l2 2h9" />
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-slate-800">
                            Mulai Chat Baru
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Untuk saat ini percakapan hanya dapat dikirim ke Admin atau Wali Kelas sesuai device yang
                            terdaftar.
                        </p>
                        @if($siswa)
                            <p class="mt-1 text-xs text-slate-400">
                                Siswa:
                                <span class="font-medium text-slate-700">{{ $siswa->nama }}</span>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-6 p-5 md:p-6">
                @if($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <p class="mb-1 font-semibold">Periksa kembali isian berikut:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('ortu.chat.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Pilih Tujuan
                        </label>
                        <select name="assigned_to_user_id"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100"
                            required>
                            <option value="">— Pilih —</option>
                            @foreach($receivers as $r)
                                <option value="{{ $r->id }}" {{ old('assigned_to_user_id', old('receiver_id')) == $r->id ? 'selected' : '' }}>
                                    {{ $r->display_name ?? $r->name }} ({{ $r->chat_role_detail ?? $r->chat_role_label }}) -
                                    {{ $r->contact_phone }}
                                </option>
                            @endforeach
                        </select>

                        @error('assigned_to_user_id')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                        @error('receiver_id')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror

                        <p class="mt-2 text-[11px] text-slate-400">
                            Device 1: Admin SMADA (085601820651) • Device 2: Wali Kelas SMADA (083190007144)
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Pesan Pertama
                        </label>
                        <textarea name="message" rows="5" required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100"
                            placeholder="Tulis pesan Anda...">{{ old('message') }}</textarea>

                        @error('message')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror

                        <p class="mt-1 text-[11px] text-slate-400">
                            Pesan ini akan tersimpan sebagai pesan pertama dalam thread percakapan.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('ortu.chat.index') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                            Batal
                        </a>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-2xl bg-blue-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:bg-blue-600 hover:shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Kirim</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection