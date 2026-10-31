<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Pengaturan;
use App\Models\User;
use App\Support\AturanAbsen;
use App\Support\Rekap;
use App\Support\Waktu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AbsenController extends Controller
{
    public function beranda(Request $request)
    {
        $hariIni = $request->user()->attendances()
            ->where('tanggal', Waktu::tanggal())
            ->latest('id')
            ->first();

        return view('karyawan.beranda', [
            'user' => $request->user(),
            'p' => Pengaturan::aktif(),
            'hariIni' => $hariIni,
            'perluAbsen' => ! $hariIni || $hariIni->status === 'ditolak',
            'sekarang' => Waktu::sekarang(),
        ]);
    }

    /** Terima absen masuk: foto kamera (JPEG base64) + lokasi GPS. Jam dari server. */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $p = Pengaturan::aktif();

        $lat = filter_var($request->input('lat'), FILTER_VALIDATE_FLOAT);
        $lng = filter_var($request->input('lng'), FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lng === false || abs($lat) > 90 || abs($lng) > 180) {
            return $this->gagal('Lokasi GPS tidak valid.');
        }

        $awalan = 'data:image/jpeg;base64,';
        $data = (string) $request->input('foto');
        $foto = str_starts_with($data, $awalan) ? base64_decode(substr($data, strlen($awalan)), true) : false;
        if ($foto === false) {
            return $this->gagal('Foto selfie wajib diambil dari kamera.');
        }
        if (strlen($foto) < 500 || strlen($foto) > 3_000_000 || ! str_starts_with($foto, "\xFF\xD8")) {
            return $this->gagal('Ukuran atau format foto tidak valid.');
        }

        $jarak = (int) round(AturanAbsen::jarakMeter($lat, $lng, $p->lat, $p->lng));
        $luarRadius = $jarak > $p->radius;
        if ($luarRadius && ! $p->izinkan_luar_radius) {
            return $this->gagal("Anda di luar radius kantor ({$jarak} m, batas {$p->radius} m).");
        }

        $tanggal = Waktu::tanggal();
        $jam = Waktu::jam();
        $selisih = AturanAbsen::selisih($jam, $p->jam_masuk);
        $hasil = AturanAbsen::klasifikasi($selisih, $p->aturan);

        return DB::transaction(function () use ($user, $request, $tanggal, $jam, $selisih, $hasil, $lat, $lng, $jarak, $luarRadius, $foto) {
            // Kunci baris user agar dua kiriman bersamaan tidak sama-sama lolos.
            User::whereKey($user->id)->lockForUpdate()->first();

            $sudah = $user->attendances()->where('tanggal', $tanggal)->where('status', '!=', 'ditolak')->exists();
            if ($sudah) {
                return $this->gagal('Anda sudah absen hari ini.', 409);
            }

            $file = 'absen/'.$user->id.'-'.$tanggal.'-'.Str::lower(Str::random(12)).'.jpg';
            Storage::disk('local')->put($file, $foto);

            $absen = $user->attendances()->create([
                'tanggal' => $tanggal,
                'jam' => $jam,
                'selisih_menit' => $selisih,
                'kategori' => $hasil['kategori'],
                'nominal' => $hasil['nominal'],
                'lat' => $lat,
                'lng' => $lng,
                'akurasi' => (int) round((float) $request->input('akurasi', 0)),
                'jarak' => $jarak,
                'foto' => $file,
                'status' => $luarRadius ? 'perlu_ditinjau' : 'valid',
            ]);

            return response()->json(['ok' => true, 'id' => $absen->id]);
        });
    }

    public function riwayat(Request $request)
    {
        $hari = (int) $request->query('hari') === 30 ? 30 : 15;
        $dari = Waktu::mulai($hari);
        $rows = $request->user()->attendances()->where('tanggal', '>=', $dari)->orderByDesc('id')->get();

        return view('karyawan.riwayat', [
            'hari' => $hari,
            'dari' => $dari,
            'rows' => $rows,
            'r' => Rekap::ringkas($rows),
        ]);
    }

    /** Foto absen: admin boleh melihat semua, karyawan hanya miliknya. */
    public function foto(Request $request, Attendance $attendance)
    {
        abort_unless($request->user()->isAdmin() || $attendance->user_id === $request->user()->id, 403);
        abort_unless(Storage::disk('local')->exists($attendance->foto), 404);

        return Storage::disk('local')->response($attendance->foto, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function gagal(string $pesan, int $kode = 422): JsonResponse
    {
        return response()->json(['error' => $pesan], $kode);
    }
}
