@extends($user->isAdmin() ? 'layouts.admin' : 'layouts.karyawan')
@section('judul', 'Akun')

@section('isi')
    <h1>{{ $user->isAdmin() ? 'Akun' : 'Profil' }}</h1>

    <div class="info-baris">
        <span><b>{{ $user->nama }}</b></span>
        <span class="mut">{{ $user->username }}</span>
    </div>

    <form method="post" action="{{ route('akun.sandi') }}" class="sempit">
        @csrf @method('put')
        <h2>Ubah kata sandi</h2>
        <label for="lama">Kata sandi lama</label>
        <input id="lama" name="lama" type="password" autocomplete="current-password" required>
        <label for="baru">Kata sandi baru</label>
        <input id="baru" name="baru" type="password" minlength="6" autocomplete="new-password" required>
        <label for="baru_confirmation">Ulangi kata sandi baru</label>
        <input id="baru_confirmation" name="baru_confirmation" type="password" minlength="6" autocomplete="new-password" required>
        <button class="btn">Simpan kata sandi</button>
    </form>

    @unless ($user->isAdmin())
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-garis btn-penuh buruk">Keluar</button>
        </form>
    @endunless
@endsection
