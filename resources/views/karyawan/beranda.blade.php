@extends('layouts.karyawan')
@section('judul', 'Absensi')

@section('isi')
    <p class="mut kecil">{{ $sekarang->locale('id')->translatedFormat('l, j F Y') }}</p>
    <h1>Halo, {{ $user->nama }}</h1>

    <div class="info-baris">
        <span>Jam masuk <b>{{ $p->jam_masuk }}</b></span>
        <span class="mut">{{ $p->lokasi_nama }}</span>
    </div>

    <div class="jam" id="jam">{{ $sekarang->format('H:i') }}</div>
    <p class="jam-zona">WITA</p>

    @if ($perluAbsen && ! $dibuka)
        <p class="petunjuk">Absen dibuka pukul {{ $jamBuka }} sampai 23:59 WITA.</p>
    @elseif ($perluAbsen)
        <div id="absen"
             data-kirim="{{ route('absen.kirim') }}"
             data-lat="{{ $p->lat }}" data-lng="{{ $p->lng }}" data-radius="{{ $p->radius }}">
            <p id="lokasi" class="lokasi mut">Memeriksa lokasi…</p>
            @if ($p->batasReward())
                <p class="petunjuk">Absen sampai {{ $p->batasReward() }} untuk reward {{ rupiah($p->nominalReward()) }}</p>
            @endif
            <button id="tombol-absen" class="btn btn-besar" type="button">Absen masuk</button>
            @if ($hariIni?->status === 'ditolak')
                <p class="lokasi waspada">Absen sebelumnya ditolak admin. Silakan absen ulang.</p>
            @endif
        </div>

        <dialog id="kamera" aria-labelledby="kamera-judul">
            <h2 id="kamera-judul">Foto absen masuk</h2>
            <video id="video" playsinline autoplay muted></video>
            <p class="mut kecil">Posisikan wajah di kamera. Lokasi diambil saat foto dikirim.</p>
            <p id="kamera-pesan" class="pesan buruk" role="alert"></p>
            <div class="aksi">
                <button id="kirim" class="btn" type="button">Ambil foto dan kirim</button>
                <button id="batal" class="btn btn-garis" type="button">Batal</button>
            </div>
        </dialog>
    @else
        <section class="hasil">
            <h2>Absen masuk tercatat</h2>
            <p class="hasil-jam">{{ $hariIni->jam }} WITA</p>
            @include('partials.status', ['a' => $hariIni])
            <p class="mut kecil">{{ $hariIni->keterangan() }}<br>Jarak dari kantor {{ $hariIni->jarak }} m</p>
        </section>
    @endif
@endsection

@push('skrip')
    <script src="{{ asset('js/absen.js') }}?v={{ filemtime(public_path('js/absen.js')) }}" defer></script>
@endpush
