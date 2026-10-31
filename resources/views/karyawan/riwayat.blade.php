@extends('layouts.karyawan')
@section('judul', 'Riwayat absensi')

@section('isi')
    <h1>Riwayat</h1>

    <nav class="pilih" aria-label="Periode">
        <a href="{{ route('riwayat', ['hari' => 15]) }}" @class(['on' => $hari === 15])>15 hari</a>
        <a href="{{ route('riwayat', ['hari' => 30]) }}" @class(['on' => $hari === 30])>30 hari</a>
    </nav>
    <p class="mut kecil">{{ tgl($dari) }} sampai hari ini</p>

    <div class="angka">
        <div><b class="baik">{{ $r['reward'] + $r['tepat'] }}</b><span>Tepat waktu</span></div>
        <div><b class="waspada">{{ $r['terlambat'] }}</b><span>Terlambat</span></div>
        <div><b>{{ $r['hadir'] }}</b><span>Hadir</span></div>
    </div>

    <div class="kv"><span>Ketepatan waktu</span><b>{{ $r['persen_tepat'] }}%</b></div>
    <div class="bar" role="img" aria-label="Ketepatan waktu {{ $r['persen_tepat'] }} persen"><i style="width: {{ $r['persen_tepat'] }}%"></i></div>
    <div class="kv"><span>Reward ({{ $r['reward'] }} hari)</span><span class="baik">{{ rupiah($r['total_reward'], true) }}</span></div>
    <div class="kv"><span>Pinalti ({{ $r['terlambat'] }} hari)</span><span class="waspada">{{ rupiah(-$r['total_pinalti'], true) }}</span></div>
    <div class="kv total"><span>Saldo bersih</span><span @class(['baik' => $r['saldo'] >= 0, 'waspada' => $r['saldo'] < 0])>{{ rupiah($r['saldo'], true) }}</span></div>

    <ul class="daftar">
        @forelse ($rows as $a)
            <li>
                <div>
                    <b>{{ tgl($a->tanggal) }}</b>
                    <div class="mut kecil">Masuk {{ $a->jam }}</div>
                </div>
                @include('partials.status', ['a' => $a])
            </li>
        @empty
            <li class="kosong mut">Belum ada absen di periode ini.</li>
        @endforelse
    </ul>
@endsection
