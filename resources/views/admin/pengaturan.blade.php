@extends('layouts.admin')
@section('judul', 'Pengaturan · Absensi')

@php
    $pinalti = old('pinalti', $form['pinalti']);
    $jamMasuk = old('jam_masuk', $p->jam_masuk);
@endphp

@section('isi')
    <form method="post" action="{{ route('admin.pengaturan.simpan') }}" id="form-pengaturan" class="pengaturan">
        @csrf @method('put')

        <div class="kepala">
            <h1>Pengaturan</h1>
            <button class="btn">Simpan perubahan</button>
        </div>

        <section>
            <h2>Jam masuk</h2>
            <input id="jam_masuk" name="jam_masuk" type="time" value="{{ $jamMasuk }}" data-sebelum="{{ $jamMasuk }}" class="input-jam-masuk" aria-label="Jam masuk" required>
            <p class="mut kecil">Jika jam masuk diubah, semua batas jam di bawah ikut bergeser.</p>
        </section>

        <section>
            <h2>Reward dan pinalti</h2>
            <p class="mut kecil">Batas jam harus berurutan dari yang paling awal.</p>

            <div class="daftar-aturan">
            <div class="aturan">
                <span class="tingkat baik">Reward</span>
                <span>Absen sampai</span>
                <input type="time" name="reward_sampai" value="{{ old('reward_sampai', $form['reward_sampai']) }}" aria-label="Batas jam reward" required>
                <span>dapat Rp</span>
                <input type="number" name="reward_nominal" value="{{ old('reward_nominal', $form['reward_nominal']) }}" min="0" step="1000" aria-label="Nominal reward" required>
            </div>
            <div class="aturan">
                <span class="tingkat">Tepat waktu</span>
                <span>Absen sampai</span>
                <input type="time" name="tepat_sampai" value="{{ old('tepat_sampai', $form['tepat_sampai']) }}" aria-label="Batas jam tepat waktu" required>
                <span class="mut">tanpa reward dan pinalti</span>
            </div>

            <div id="pinalti">
                @foreach (array_values($pinalti) as $i => $pn)
                    @php $akhir = $loop->last; @endphp
                    <div class="aturan" data-pinalti>
                        <span class="tingkat waspada">Pinalti</span>
                        <span data-awal>{{ $akhir ? 'Absen setelahnya' : 'Absen sampai' }}</span>
                        <input type="time" name="pinalti[{{ $i }}][sampai]" value="{{ $pn['sampai'] ?? '' }}" aria-label="Batas jam pinalti" @if ($akhir) hidden disabled @else required @endif>
                        <span>denda Rp</span>
                        <input type="number" name="pinalti[{{ $i }}][nominal]" value="{{ $pn['nominal'] }}" min="0" step="1000" aria-label="Nominal pinalti" required>
                        <button type="button" class="btn-teks" data-hapus @if (count($pinalti) === 1) hidden @endif>Hapus</button>
                    </div>
                @endforeach
            </div>
            </div>
            <button type="button" id="tambah-pinalti" class="btn btn-garis btn-kecil tambah">Tambah tingkat pinalti</button>

            <p id="aturan-galat" class="pesan buruk" role="alert">@error('aturan'){{ $message }}@enderror</p>
            <div class="ringkasan" aria-live="polite">
                <b>Ringkasan</b>
                <div id="ringkasan"></div>
            </div>
        </section>

        <section>
            <h2>Lokasi kantor</h2>
            <div class="baris-form">
                <div><label for="lokasi_nama">Nama lokasi</label><input id="lokasi_nama" name="lokasi_nama" value="{{ old('lokasi_nama', $p->lokasi_nama) }}" required></div>
                <div><label for="radius">Radius (meter)</label><input id="radius" name="radius" type="number" min="10" value="{{ old('radius', $p->radius) }}" required></div>
                <div><label for="lat">Latitude</label><input id="lat" name="lat" type="number" step="any" value="{{ old('lat', $p->lat) }}" required></div>
                <div><label for="lng">Longitude</label><input id="lng" name="lng" type="number" step="any" value="{{ old('lng', $p->lng) }}" required></div>
            </div>
            <p><button type="button" id="pakai-lokasi" class="btn-teks">Pakai lokasi saya sekarang</button> <span id="lokasi-info" class="mut kecil"></span></p>
            <label class="centang">
                <input type="checkbox" name="izinkan_luar_radius" value="1" @checked(old('izinkan_luar_radius', $p->izinkan_luar_radius))>
                Izinkan absen di luar radius (masuk antrean tinjau admin)
            </label>
        </section>
    </form>
@endsection

@push('skrip')
    <script src="{{ asset('js/pengaturan.js') }}?v={{ filemtime(public_path('js/pengaturan.js')) }}" defer></script>
@endpush
