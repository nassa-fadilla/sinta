@extends('layouts.auth')
@section('title', 'Login SINTA')

@section('content')
    <div
        class="min-h-screen bg-[radial-gradient(circle_at_top_left,rgba(59,130,246,0.20),transparent_26%),radial-gradient(circle_at_bottom_right,rgba(14,165,233,0.14),transparent_24%),linear-gradient(180deg,#eef6ff_0%,#dfeeff_100%)] flex items-center justify-center px-4 py-6">

        <div
            class="relative w-full max-w-[1000px] overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-[0_26px_70px_rgba(15,23,42,0.12)] transition duration-300">

            {{-- aksen blur luar --}}
            <div
                class="pointer-events-none absolute -left-16 -top-16 h-44 w-44 rounded-full bg-blue-300/25 blur-3xl">
            </div>
            <div
                class="pointer-events-none absolute -bottom-20 -right-16 h-52 w-52 rounded-full bg-sky-300/20 blur-3xl">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-[0.95fr_auto_1fr]">

                {{-- ================= KIRI ================= --}}
                <div class="hidden md:flex flex-col justify-between px-8 py-8 bg-white">
                    <div>
                        <div>
                            <span
                                class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-3.5 py-1 text-[12px] font-semibold text-blue-700 shadow-sm transition duration-300 hover:scale-[1.02]">
                                Selamat datang
                            </span>
                        </div>

                        <div class="mt-7 max-w-[360px]">
                            <h1 class="text-[2rem] leading-[1.16] font-bold tracking-tight text-slate-900">
                                Sistem Informasi
                                <span class="block text-blue-600">Monitoring Aktivitas Siswa</span>
                            </h1>

                            <p class="mt-4 text-[14px] leading-7 text-slate-600">
                                SINTA membantu sekolah dan orang tua memantau informasi aktivitas siswa secara lebih mudah
                                dan terpusat.
                            </p>

                            <div
                                class="mt-5 max-w-[400px] rounded-[22px] border border-slate-200 bg-white px-4 py-4 shadow-[0_16px_34px_rgba(15,23,42,0.07)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_20px_42px_rgba(37,99,235,0.10)]">
                                <div class="text-[10px] font-semibold uppercase tracking-wide text-blue-600">
                                    Panduan Login Pengguna
                                </div>

                                <div class="mt-1 text-[14px] font-semibold text-slate-900">
                                    Cara Masuk Ke Portal SINTA
                                </div>

                                <div class="mt-3 space-y-2 text-[12px] text-slate-600 leading-relaxed">
                                    <div class="flex gap-2">
                                        <span class="mt-[5px] h-1.5 w-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                        <p><span class="font-medium text-slate-800">Admin</span> menggunakan email & kata
                                            sandi.</p>
                                    </div>

                                    <div class="flex gap-2">
                                        <span class="mt-[5px] h-1.5 w-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                        <p><span class="font-medium text-slate-800">Kepala Sekolah</span>
                                            menggunakan NUPTK & tanggal lahir.</p>
                                    </div>

                                    <div class="flex gap-2">
                                        <span class="mt-[5px] h-1.5 w-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                        <p><span class="font-medium text-slate-800">Wali Kelas</span>
                                            menggunakan NUPTK & tanggal lahir.</p>
                                    </div>

                                    <div class="flex gap-2">
                                        <span class="mt-[5px] h-1.5 w-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                        <p><span class="font-medium text-slate-800">Orang Tua</span> menggunakan NIS &
                                            tanggal lahir siswa.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 text-[12px] text-slate-400">
                        © {{ now()->format('Y') }} SMA Negeri 2 Temanggung
                    </div>
                </div>

                {{-- ================= PEMBATAS ================= --}}
                <div class="hidden md:block w-px bg-slate-200 my-8"></div>

                {{-- ================= KANAN / FORM LOGIN ================= --}}
                <div class="flex items-center justify-center px-6 py-8 sm:px-8 md:px-8 bg-white">
                    <div class="w-full max-w-[380px]">

                        {{-- Mobile welcome --}}
                        <div class="md:hidden mb-6">
                            <span
                                class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[12px] font-medium text-slate-600 shadow-sm">
                                Selamat datang
                            </span>

                            <h1 class="mt-4 text-[1.7rem] font-bold tracking-tight text-slate-900 leading-tight">
                                Sistem Informasi
                                <span class="block text-blue-600">Monitoring Aktivitas Siswa</span>
                            </h1>

                            <p class="mt-3 text-sm text-slate-500 leading-6">
                                Gunakan identitas akun sesuai peran Anda untuk masuk ke sistem.
                            </p>
                        </div>

                        {{-- Header form --}}
                        <div class="text-center mb-5">
                            <div
                                class="mx-auto h-16 w-16 rounded-[22px] border border-slate-200 bg-white shadow-[0_14px_32px_rgba(15,23,42,0.08)] flex items-center justify-center overflow-hidden transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_18px_38px_rgba(37,99,235,0.12)]">
                                <img src="{{ asset('images/logo-sma2.png') }}" alt="Logo SMAN 2 Temanggung"
                                    class="h-10 w-10 object-contain">
                            </div>

                            <p class="mt-3 text-[13px] text-slate-500">SMA Negeri 2 Temanggung</p>
                            <h2 class="mt-0.5 text-[1.5rem] font-semibold tracking-tight text-slate-900">Portal SINTA</h2>
                        </div>

                        {{-- Error --}}
                        @if ($errors->any())
                            <div
                                class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Success --}}
                        @if (session('ok'))
                            <div
                                class="mb-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 shadow-sm">
                                {{ session('ok') }}
                            </div>
                        @endif

                        {{-- Form --}}
                        <form method="POST" action="{{ route('login.store') }}" class="space-y-3.5" autocomplete="off">
                            @csrf

                            {{-- Identity --}}
                            <div>
                                <label for="identity" class="block text-[13px] font-semibold text-slate-800 mb-1.5">
                                    Email / NUPTK / NIS
                                </label>

                                <input type="text" name="identity" id="identity" value="{{ old('identity', '') }}" required
                                    autofocus autocomplete="off" autocapitalize="off" autocorrect="off"
                                    spellcheck="false"
                                    class="sinta-input block w-full rounded-[18px] border border-slate-200 bg-white px-4 py-3 text-[14px] text-slate-700 placeholder-slate-400 shadow-[0_10px_24px_rgba(15,23,42,0.05)] transition duration-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 hover:border-blue-200 hover:shadow-[0_14px_28px_rgba(37,99,235,0.08)]"
                                    placeholder="Masukkan identitas akun">
                            </div>

                            {{-- Credential --}}
                            <div>
                                <label for="credential" id="credential-label"
                                    class="block text-[13px] font-semibold text-slate-800 mb-1.5">
                                    Kata Sandi
                                </label>

                                <div class="relative">
                                    <input type="password" name="credential" id="credential" required value=""
                                        autocomplete="new-password"
                                        class="sinta-input block w-full rounded-[18px] border border-slate-200 bg-white px-4 py-3 pr-11 text-[14px] text-slate-700 placeholder-slate-400 shadow-[0_10px_24px_rgba(15,23,42,0.05)] transition duration-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 hover:border-blue-200 hover:shadow-[0_14px_28px_rgba(37,99,235,0.08)]"
                                        placeholder="Masukkan kata sandi">

                                    <button type="button" id="toggle-password"
                                        class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-blue-600 transition"
                                        aria-label="Tampilkan atau sembunyikan password">
                                        <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        <svg id="eye-close" xmlns="http://www.w3.org/2000/svg"
                                            class="h-[18px] w-[18px] hidden" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 3l18 18M10.584 10.587A2 2 0 0 0 13.414 13.4M9.88 5.09A9.77 9.77 0 0 1 12 4.875c4.478 0 8.268 2.943 9.542 7a9.783 9.783 0 0 1-4.205 5.146M6.228 6.228A9.776 9.776 0 0 0 2.458 12c1.274 4.057 5.064 7 9.542 7 1.61 0 3.13-.38 4.478-1.055" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- CAPTCHA --}}
                            <div>
                                <p class="mb-1.5 text-[13px] font-medium text-slate-700">
                                    Berapakah {{ $captchaA ?? 0 }} ditambah {{ $captchaB ?? 0 }}?
                                </p>

                                <input type="number" name="captcha_answer" id="captcha_answer" value="" required
                                    autocomplete="off"
                                    class="sinta-input block w-full rounded-[18px] border border-blue-200 bg-white px-4 py-3 text-[14px] text-slate-700 placeholder-slate-400 shadow-[0_10px_24px_rgba(37,99,235,0.06)] transition duration-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 hover:border-blue-300 hover:shadow-[0_14px_28px_rgba(37,99,235,0.10)]"
                                    placeholder="Jawaban anda">
                            </div>

                            {{-- Remember --}}
                            <div class="flex items-center pt-1">
                                <label class="inline-flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" name="remember"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-200"
                                        {{ old('remember') ? 'checked' : '' }}>
                                    <span class="text-[14px] text-slate-600">Ingat saya</span>
                                </label>
                            </div>

                            {{-- Button --}}
                            <button type="submit"
                                class="w-full rounded-[18px] border border-blue-700/80 bg-[linear-gradient(135deg,rgba(37,99,235,1),rgba(59,130,246,0.96))] py-3 text-white text-[14px] font-semibold shadow-[0_18px_34px_rgba(37,99,235,0.22)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_22px_42px_rgba(37,99,235,0.28)] hover:brightness-[1.03] active:translate-y-0">
                                MASUK
                            </button>

                            {{-- Help --}}
                            <div class="pt-1 text-center text-[11px] text-slate-500 leading-5">
                                Tidak bisa masuk?
                                <a href="https://api.whatsapp.com/send?phone=6285601820651&text=Halo%20Admin%20SINTA%2C%20saya%20mengalami%20kendala%20saat%20login"
                                    target="_blank" rel="noopener noreferrer"
                                    class="font-medium text-blue-600 hover:text-blue-700 hover:underline">
                                    Hubungi admin
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .sinta-input:-webkit-autofill,
        .sinta-input:-webkit-autofill:hover,
        .sinta-input:-webkit-autofill:focus,
        .sinta-input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
            box-shadow: 0 0 0 1000px #ffffff inset !important;
            -webkit-text-fill-color: #334155 !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const identityInput = document.getElementById('identity');
            const credentialInput = document.getElementById('credential');
            const credentialLabel = document.getElementById('credential-label');
            const togglePasswordBtn = document.getElementById('toggle-password');
            const eyeOpen = document.getElementById('eye-open');
            const eyeClose = document.getElementById('eye-close');

            function setPasswordMode() {
                credentialInput.type = 'password';
                credentialInput.value = '';
                credentialInput.placeholder = 'Masukkan kata sandi';
                credentialInput.setAttribute('autocomplete', 'new-password');
                credentialLabel.textContent = 'Kata Sandi';
                togglePasswordBtn.classList.remove('hidden');
                eyeOpen.classList.remove('hidden');
                eyeClose.classList.add('hidden');
            }

            function setDateMode() {
                credentialInput.type = 'date';
                credentialInput.value = '';
                credentialInput.placeholder = '';
                credentialInput.setAttribute('autocomplete', 'off');
                credentialLabel.textContent = 'Tanggal Lahir';
                togglePasswordBtn.classList.add('hidden');
            }

            function updateCredentialMode() {
                const value = identityInput.value.trim();

                if (value.includes('@')) {
                    setPasswordMode();
                } else if (/^\d+$/.test(value)) {
                    setDateMode();
                } else if (value === '') {
                    setPasswordMode();
                } else {
                    setPasswordMode();
                }
            }

            togglePasswordBtn.addEventListener('click', function () {
                if (credentialInput.type === 'password') {
                    credentialInput.type = 'text';
                    eyeOpen.classList.add('hidden');
                    eyeClose.classList.remove('hidden');
                } else if (credentialInput.type === 'text') {
                    credentialInput.type = 'password';
                    eyeOpen.classList.remove('hidden');
                    eyeClose.classList.add('hidden');
                }
            });

            identityInput.addEventListener('input', updateCredentialMode);

            updateCredentialMode();
        });
    </script>
@endsection