@extends('layouts.dasar')
@section('judul', 'Masuk · Absensi')

@section('badan')
    <main class="login">
        <h1>Absensi</h1>
        <p class="mut">Masuk dengan username dan kata sandi Anda.</p>

        <form method="post" action="{{ url('/login') }}">
            @csrf
            <label for="username">Username</label>
            <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" autocapitalize="none" required autofocus>

            <label for="password">Kata sandi</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>

            @error('username')<p class="pesan buruk" role="alert">{{ $message }}</p>@enderror
            <button class="btn btn-penuh">Masuk</button>
        </form>
    </main>
@endsection
