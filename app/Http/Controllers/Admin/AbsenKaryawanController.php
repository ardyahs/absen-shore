<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Pengaturan;
use App\Models\User;
use App\Support\AturanAbsen;
use App\Support\Rekap;
use App\Support\Waktu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Admin mengelola absen satu karyawan: lihat per rentang tanggal, tambah manual, ubah status, hapus. */
class AbsenKaryawanController extends Controller
{
    public function index(Request $request, User $user)
    {
        abort_unless($user->role === 'karyawan', 404);

        $hariIni = Waktu::tanggal();
        $dari = Waktu::bacaTanggal($request->query('dari')) ?? Waktu::mulai(15);
        $sampai = Waktu::bacaTanggal($request->query('sampai')) ?? $hariIni;
        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $rows = $user->attendances()
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderByDesc('tanggal')->orderByDesc('id')
            ->get();

        return view('admin.absen-karyawan', [
            'karyawan' => $user,
            'dari' => $dari,
            'sampai' => $sampai,
            'hariIni' => $hariIni,
            'rows' => $rows,
            'r' => Rekap::ringkas($rows, $user->gaji_harian),
        ]);
    }

    /** Absen manual untuk karyawan yang lupa absen / HP bermasalah. Nominal diisi admin. */
    public function store(Request $request, User $user)
    {
        abort_unless($user->role === 'karyawan', 404);

        $d = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.Waktu::tanggal()],
            'jam' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'nominal' => ['required', 'integer', 'between:-10000000,10000000'],
            'catatan' => ['nullable', 'string', 'max:200'],
        ]);

        return DB::transaction(function () use ($request, $user, $d) {
            User::whereKey($user->id)->lockForUpdate()->first();

            $sudah = $user->attendances()->where('tanggal', $d['tanggal'])->where('status', '!=', 'ditolak')->exists();
            if ($sudah) {
                return back()->withErrors(['tanggal' => $user->nama.' sudah punya absen pada '.tgl($d['tanggal']).'. Tolak atau hapus dulu absen itu.'])->withInput();
            }

            $nominal = (int) $d['nominal'];
            $user->attendances()->create([
                'tanggal' => $d['tanggal'],
                'jam' => $d['jam'],
                'selisih_menit' => AturanAbsen::selisih($d['jam'], Pengaturan::aktif()->jam_masuk),
                'kategori' => AturanAbsen::kategori($nominal),
                'nominal' => $nominal,
                'status' => 'valid',
                'catatan' => $d['catatan'] ?? null,
                'dibuat_oleh' => $request->user()->id,
            ]);

            return back()->with('status', 'Absen manual '.$user->nama.' pada '.tgl($d['tanggal']).' ditambahkan.');
        });
    }

    public function status(Request $request, Attendance $attendance)
    {
        $d = $request->validate(['status' => ['required', 'in:valid,ditolak']]);

        if ($d['status'] === 'valid' && $attendance->status !== 'valid') {
            $bentrok = Attendance::where('user_id', $attendance->user_id)
                ->where('tanggal', $attendance->tanggal)
                ->where('status', '!=', 'ditolak')
                ->whereKeyNot($attendance->id)
                ->exists();
            if ($bentrok) {
                return back()->withErrors(['status' => 'Sudah ada absen lain yang berlaku pada '.tgl($attendance->tanggal).'.']);
            }
        }

        $attendance->update(['status' => $d['status'], 'ditinjau_oleh' => $request->user()->id]);

        return back()->with('status', 'Absen '.tgl($attendance->tanggal).' menjadi '.($d['status'] === 'valid' ? 'valid.' : 'ditolak.'));
    }

    public function destroy(Attendance $attendance)
    {
        if ($attendance->foto) {
            Storage::disk('local')->delete($attendance->foto);
        }
        $attendance->delete();

        return back()->with('status', 'Absen '.tgl($attendance->tanggal).' dihapus.');
    }
}
