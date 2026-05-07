<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login') - SINTA</title>
    <link rel="icon" href="{{ asset('images/logo-sma2.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-blue-100">

    {{-- Kontainer Utama --}}
    <main class="flex flex-col items-center justify-center w-full px-4 py-6">
        <div
            class="bg-white shadow-xl rounded-2xl border border-gray-100 px-10 py-8 w-full max-w-lg mx-auto text-center">
            @yield('content')
        </div>
    </main>

    <footer class="absolute bottom-4 text-center w-full text-xs text-gray-400">
        © {{ date('Y') }} SMAN 2 Temanggung — Sistem Informasi SINTA
    </footer>

</body>

</html>