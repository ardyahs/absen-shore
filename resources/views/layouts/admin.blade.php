@extends('layouts.dasar')
@section('kelas-body', 'admin')

@section('badan')
    <header class="atas">
        <a href="{{ route('admin.dashboard') }}" class="merek">Absensi</a>
        <nav aria-label="Menu admin">
            <a href="{{ route('admin.dashboard') }}" @class(['on' => request()->routeIs('admin.dashboard')])>Hari ini</a>
            <a href="{{ route('admin.rekap') }}" @class(['on' => request()->routeIs('admin.rekap')])>Rekap</a>
            <a href="{{ route('admin.karyawan') }}" @class(['on' => request()->routeIs('admin.karyawan')])>Karyawan</a>
            <a href="{{ route('admin.pengaturan') }}" @class(['on' => request()->routeIs('admin.pengaturan')])>Pengaturan</a>
            <a href="{{ route('akun') }}" @class(['on' => request()->routeIs('akun')])>Akun</a>
        </nav>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn-teks">Keluar</button></form>
    </header>
    <main class="halaman">
        @include('partials.pesan')
        @yield('isi')
    </main>
@endsection
