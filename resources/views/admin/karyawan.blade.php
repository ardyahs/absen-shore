@extends('layouts.admin')
@section('judul', 'Karyawan · Absensi')

@section('isi')
    <h1>Karyawan</h1>

    <form method="post" action="{{ route('admin.karyawan.simpan') }}" class="baris-form">
        @csrf
        <div><label for="nama">Nama</label><input id="nama" name="nama" value="{{ old('nama') }}" required></div>
        <div><label for="username">Username</label><input id="username" name="username" value="{{ old('username') }}" autocapitalize="none" required></div>
        <div><label for="password">Kata sandi awal</label><input id="password" name="password" type="password" minlength="6" autocomplete="new-password" required></div>
        <div><label for="gaji_harian">Gaji harian (Rp)</label><input id="gaji_harian" name="gaji_harian" type="number" min="0" step="1000" value="{{ old('gaji_harian', \App\Models\User::GAJI_HARIAN) }}" required></div>
        <button class="btn">Tambah karyawan</button>
    </form>

    <section>
        <div class="gulir">
            <table>
                <thead><tr><th>Nama</th><th>Username</th><th>Status</th><th>Gaji harian (Rp)</th><th>Atur ulang kata sandi</th><th></th></tr></thead>
                <tbody>
                @forelse ($users as $u)
                    <tr @class(['redup' => ! $u->aktif])>
                        <td><b>{{ $u->nama }}</b></td>
                        <td>{{ $u->username }}</td>
                        <td><span @class(['status', 'baik' => $u->aktif, 'mut' => ! $u->aktif])>{{ $u->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>
                            <form method="post" action="{{ route('admin.karyawan.gaji', $u) }}" class="aksi">
                                @csrf @method('put')
                                <input name="gaji_harian" type="number" min="0" step="1000" value="{{ $u->gaji_harian }}" aria-label="Gaji harian {{ $u->nama }}" required class="input-kecil">
                                <button class="btn btn-garis btn-kecil">Simpan</button>
                            </form>
                        </td>
                        <td>
                            <form method="post" action="{{ route('admin.karyawan.sandi', $u) }}" class="aksi">
                                @csrf @method('put')
                                <input name="password" type="password" minlength="6" placeholder="Kata sandi baru" aria-label="Kata sandi baru untuk {{ $u->nama }}" autocomplete="new-password" required class="input-kecil">
                                <button class="btn btn-garis btn-kecil">Simpan</button>
                            </form>
                        </td>
                        <td class="kanan">
                            <form method="post" action="{{ route('admin.karyawan.status', $u) }}">
                                @csrf @method('patch')
                                <button class="btn-teks">{{ $u->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="kosong mut">Belum ada karyawan. Tambahkan lewat form di atas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
