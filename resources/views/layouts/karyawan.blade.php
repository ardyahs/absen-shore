@extends('layouts.dasar')
@section('kelas-body', 'hp')

@section('badan')
    <main class="app">
        @include('partials.pesan')
        @yield('isi')
    </main>
    <nav class="tab" aria-label="Menu">
        <a href="{{ route('absen') }}" @class(['on' => request()->routeIs('absen')])>Beranda</a>
        <a href="{{ route('riwayat') }}" @class(['on' => request()->routeIs('riwayat')])>Riwayat</a>
        <a href="{{ route('akun') }}" @class(['on' => request()->routeIs('akun')])>Profil</a>
    </nav>
@endsection
