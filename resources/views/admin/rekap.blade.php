@extends('layouts.admin')
@section('judul', 'Rekap · Absensi')

@section('isi')
    <div class="kepala">
        <h1>Rekap absensi</h1>
        <a class="btn btn-garis" href="{{ route('admin.rekap.csv', ['hari' => $hari]) }}">Unduh CSV</a>
    </div>

    <div class="alat">
        <nav class="pilih" aria-label="Periode">
            <a href="{{ route('admin.rekap', ['hari' => 15, 'cari' => $cari ?: null]) }}" @class(['on' => $hari === 15])>15 hari</a>
            <a href="{{ route('admin.rekap', ['hari' => 30, 'cari' => $cari ?: null]) }}" @class(['on' => $hari === 30])>30 hari</a>
        </nav>
        <span class="mut kecil">{{ tgl($dari) }} sampai hari ini</span>
        <form method="get" class="cari" role="search">
            <input type="hidden" name="hari" value="{{ $hari }}">
            <input type="search" name="cari" value="{{ $cari }}" placeholder="Cari nama karyawan" aria-label="Cari nama karyawan">
        </form>
    </div>

    <div class="angka">
        <div><b>{{ rupiah($totalGaji) }}</b><span>Total gaji {{ $hari }} hari</span></div>
        <div><b class="baik">{{ rupiah($totalReward) }}</b><span>Total reward</span></div>
        <div><b class="waspada">{{ rupiah($totalPinalti) }}</b><span>Total pinalti dari {{ $totalTerlambat }} keterlambatan</span></div>
    </div>

    <div class="gulir">
        <table>
            <thead>
            <tr>
                <th>Karyawan</th><th class="kanan">Hadir</th><th class="kanan">Reward</th><th class="kanan">Tepat</th>
                <th class="kanan">Terlambat</th><th class="kanan">Total reward</th><th class="kanan">Total pinalti</th>
                <th class="kanan">Saldo</th><th class="kanan">Tepat waktu</th>
                <th class="kanan">Gaji pokok</th><th class="kanan">Total gaji</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($rows as $x)
                <tr>
                    <td><b>{{ $x['user']->nama }}</b></td>
                    <td class="kanan">{{ $x['hadir'] }}</td>
                    <td class="kanan baik">{{ $x['reward'] }}</td>
                    <td class="kanan">{{ $x['tepat'] }}</td>
                    <td class="kanan waspada">{{ $x['terlambat'] }}</td>
                    <td class="kanan">{{ rupiah($x['total_reward']) }}</td>
                    <td class="kanan">{{ rupiah($x['total_pinalti']) }}</td>
                    <td class="kanan"><b @class(['baik' => $x['saldo'] >= 0, 'waspada' => $x['saldo'] < 0])>{{ rupiah($x['saldo'], true) }}</b></td>
                    <td class="kanan">{{ $x['persen_tepat'] }}%</td>
                    <td class="kanan">{{ rupiah($x['gaji_pokok']) }}<div class="mut kecil">{{ $x['hadir'] }} × {{ rupiah($x['gaji_harian']) }}</div></td>
                    <td class="kanan"><b>{{ rupiah($x['total_gaji']) }}</b></td>
                </tr>
            @empty
                <tr><td colspan="11" class="kosong mut">{{ $cari ? 'Tidak ada karyawan dengan nama itu.' : 'Belum ada karyawan aktif.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
