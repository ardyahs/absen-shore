@extends('layouts.admin')
@section('judul', 'Hari ini · Absensi')

@section('isi')
    <div class="kepala">
        <h1>{{ tgl($tanggal, 'l, j F Y') }}</h1>
        <span class="mut">{{ $totalKaryawan }} karyawan aktif</span>
    </div>

    <div class="angka">
        <div><b>{{ $r['hadir'] }}</b><span>Hadir, {{ $belum }} belum absen</span></div>
        <div><b class="baik">{{ $r['reward'] }}</b><span>Reward {{ rupiah($r['total_reward']) }}</span></div>
        <div><b>{{ $r['tepat'] }}</b><span>Tepat waktu</span></div>
        <div><b class="waspada">{{ $r['terlambat'] }}</b><span>Terlambat, pinalti {{ rupiah($r['total_pinalti']) }}</span></div>
        <div><b class="info">{{ $antrean->count() }}</b><span>Perlu ditinjau</span></div>
    </div>

    @if ($antrean->isNotEmpty())
        <section>
            <h2>Perlu ditinjau</h2>
            <p class="mut kecil">Absen dari luar radius kantor. Belum dihitung sampai disetujui.</p>
            <div class="gulir">
                <table>
                    <thead><tr><th>Foto</th><th>Karyawan</th><th>Tanggal</th><th>Jam</th><th>Jarak</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($antrean as $a)
                        <tr>
                            <td>@include('admin.foto', ['a' => $a])</td>
                            <td><b>{{ $a->user->nama }}</b></td>
                            <td>{{ tgl($a->tanggal) }}</td>
                            <td>{{ $a->jam }}</td>
                            <td>{{ number_format($a->jarak, 0, ',', '.') }} m</td>
                            <td>
                                <div class="aksi">
                                    <form method="post" action="{{ route('admin.tinjau', $a) }}">
                                        @csrf<input type="hidden" name="keputusan" value="setuju">
                                        <button class="btn btn-kecil">Setujui</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.tinjau', $a) }}">
                                        @csrf<input type="hidden" name="keputusan" value="tolak">
                                        <button class="btn btn-kecil btn-bahaya">Tolak</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section>
        <h2>Absen hari ini</h2>
        <div class="gulir">
            <table>
                <thead><tr><th>Foto</th><th>Karyawan</th><th>Jam masuk</th><th>Jarak dari kantor</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($hariIni as $a)
                    <tr>
                        <td>@include('admin.foto', ['a' => $a])</td>
                        <td><b>{{ $a->user->nama }}</b></td>
                        <td>{{ $a->jam }}</td>
                        <td>{{ number_format($a->jarak, 0, ',', '.') }} m <span class="mut kecil">(GPS ±{{ $a->akurasi }} m)</span></td>
                        <td>@include('partials.status', ['a' => $a])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="kosong mut">Belum ada yang absen hari ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
