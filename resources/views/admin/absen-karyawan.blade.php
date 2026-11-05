@extends('layouts.admin')
@section('judul', 'Absen '.$karyawan->nama.' · Absensi')

@section('isi')
    <div class="kepala">
        <h1>{{ $karyawan->nama }}</h1>
        <a class="btn-teks" href="{{ route('admin.rekap') }}">Kembali ke rekap</a>
    </div>

    <form method="get" class="alat">
        <div><label for="dari">Dari</label><input id="dari" name="dari" type="date" value="{{ $dari }}" max="{{ $hariIni }}"></div>
        <div><label for="sampai">Sampai</label><input id="sampai" name="sampai" type="date" value="{{ $sampai }}" max="{{ $hariIni }}"></div>
        <button class="btn btn-garis">Tampilkan</button>
    </form>
    <p class="mut kecil">{{ tgl($dari, 'j M Y') }} sampai {{ tgl($sampai, 'j M Y') }}</p>

    <div class="angka">
        <div><b>{{ $r['hadir'] }}</b><span>Hadir valid</span></div>
        <div><b>{{ rupiah($r['gaji_pokok']) }}</b><span>Gaji pokok ({{ $r['hadir'] }} × {{ rupiah($r['gaji_harian']) }})</span></div>
        <div><b @class(['baik' => $r['saldo'] >= 0, 'waspada' => $r['saldo'] < 0])>{{ rupiah($r['saldo'], true) }}</b><span>Reward − pinalti</span></div>
        <div><b>{{ rupiah($r['total_gaji']) }}</b><span>Total gaji</span></div>
    </div>

    <section>
        <h2>Tambah absen manual</h2>
        <p class="mut kecil">Untuk karyawan yang lupa absen atau HP-nya bermasalah. Tanpa foto dan GPS, langsung dihitung valid. Isi nominal positif untuk reward, negatif untuk pinalti, 0 untuk tepat waktu.</p>
        <form method="post" action="{{ route('admin.karyawan.absen.simpan', $karyawan) }}" class="baris-form">
            @csrf
            <div><label for="tanggal">Tanggal</label><input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', $hariIni) }}" max="{{ $hariIni }}" required></div>
            <div><label for="jam">Jam masuk</label><input id="jam" name="jam" type="time" value="{{ old('jam') }}" required></div>
            <div><label for="nominal">Reward / pinalti (Rp)</label><input id="nominal" name="nominal" type="number" step="1000" value="{{ old('nominal', 0) }}" required></div>
            <div><label for="catatan">Catatan</label><input id="catatan" name="catatan" maxlength="200" value="{{ old('catatan') }}" placeholder="mis. HP rusak"></div>
            <button class="btn">Tambah absen</button>
        </form>
    </section>

    <section>
        <h2>Daftar absen</h2>
        <div class="gulir">
            <table>
                <thead><tr><th>Foto</th><th>Tanggal</th><th>Jam</th><th>Hasil</th><th>Keterangan</th><th>Ubah status</th><th></th></tr></thead>
                <tbody>
                @forelse ($rows as $a)
                    <tr @class(['redup' => $a->status === 'ditolak'])>
                        <td>@include('admin.foto', ['a' => $a])</td>
                        <td>{{ tgl($a->tanggal) }}</td>
                        <td>{{ $a->jam }}</td>
                        <td>@include('partials.status', ['a' => $a])</td>
                        <td class="mut kecil">
                            {{ $a->manual() ? 'Manual oleh admin' : number_format($a->jarak, 0, ',', '.').' m dari kantor' }}
                            @if ($a->catatan)<br>{{ $a->catatan }}@endif
                        </td>
                        <td>
                            <form method="post" action="{{ route('admin.absen.status', $a) }}" class="aksi">
                                @csrf @method('patch')
                                <select name="status" aria-label="Status absen {{ tgl($a->tanggal) }}" class="input-kecil">
                                    <option value="valid" @selected($a->status === 'valid')>Valid</option>
                                    <option value="ditolak" @selected($a->status === 'ditolak')>Ditolak</option>
                                    @if ($a->status === 'perlu_ditinjau')<option value="" selected disabled>Menunggu tinjauan</option>@endif
                                </select>
                                <button class="btn btn-garis btn-kecil">Simpan</button>
                            </form>
                        </td>
                        <td class="kanan">
                            <form method="post" action="{{ route('admin.absen.hapus', $a) }}" data-konfirmasi="Hapus absen {{ $karyawan->nama }} pada {{ tgl($a->tanggal) }}? Data dan foto tidak bisa dikembalikan.">
                                @csrf @method('delete')
                                <button class="btn btn-kecil btn-bahaya">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kosong mut">Tidak ada absen pada rentang tanggal ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('skrip')
    <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}" defer></script>
@endpush
