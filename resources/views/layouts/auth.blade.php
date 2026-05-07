<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'SINTA')</title>
  <link rel="icon" href="{{ asset('images/logo-sma2.png') }}">
  <script src="https://cdn.tailwindcss.com"></script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased text-gray-900">
  @yield('content')
</body>

</html>