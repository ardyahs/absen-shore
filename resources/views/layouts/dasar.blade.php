<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>@yield('judul', 'Absensi')</title>
    <link rel="stylesheet" href="{{ asset('css/absen.css') }}?v={{ filemtime(public_path('css/absen.css')) }}">
</head>
<body class="@yield('kelas-body')">
    @yield('badan')
    @stack('skrip')
</body>
</html>
