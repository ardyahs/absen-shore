<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Support\Rekap;
use App\Support\Waktu;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /** Absen per tanggal; bawaan hari ini, ?tanggal=Y-m-d untuk tanggal sebelumnya. */
    public function index(Request $request)
    {
        $hariIni = Waktu::tanggal();
        $tanggal = min(Waktu::bacaTanggal($request->query('tanggal')) ?? $hariIni, $hariIni);
        $geser = fn (int $n) => Carbon::parse($tanggal)->addDays($n)->toDateString();
        $totalKaryawan = User::where('role', 'karyawan')->where('aktif', true)->count();

        $hariIni = Attendance::with('user')
            ->where('tanggal', $tanggal)
            ->where('status', '!=', 'ditolak')
            ->orderByDesc('id')
            ->get();

        $antrean = Attendance::with('user')
            ->where('status', 'perlu_ditinjau')
            ->orderByDesc('id')
            ->get();

        return view('admin.dashboard', [
            'tanggal' => $tanggal,
            'hariIniTanggal' => $hariIni,
            'sebelum' => $geser(-1),
            'sesudah' => $tanggal < $hariIni ? $geser(1) : null,
            'totalKaryawan' => $totalKaryawan,
            'belum' => max(0, $totalKaryawan - $hariIni->pluck('user_id')->unique()->count()),
            'r' => Rekap::ringkas($hariIni),
            'hariIni' => $hariIni,
            'antrean' => $antrean,
        ]);
    }

    /** Setujui atau tolak absen di luar radius. */
    public function tinjau(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'keputusan' => ['required', 'in:setuju,tolak'],
            'catatan' => ['nullable', 'string', 'max:200'],
        ]);

        abort_unless($attendance->status === 'perlu_ditinjau', 404);

        $setuju = $data['keputusan'] === 'setuju';
        $attendance->update([
            'status' => $setuju ? 'valid' : 'ditolak',
            'catatan' => $data['catatan'] ?? null,
            'ditinjau_oleh' => $request->user()->id,
        ]);

        return back()->with('status', 'Absen '.$attendance->user->nama.($setuju ? ' disetujui.' : ' ditolak.'));
    }
}
