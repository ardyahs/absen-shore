<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Support\AturanAbsen;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PengaturanController extends Controller
{
    public function edit()
    {
        $p = Pengaturan::aktif();

        return view('admin.pengaturan', [
            'p' => $p,
            'form' => AturanAbsen::keForm($p->aturan, $p->jam_masuk),
        ]);
    }

    public function update(Request $request)
    {
        $jam = 'regex:/^([01]\d|2[0-3]):[0-5]\d$/';
        $d = $request->validate([
            'jam_masuk' => ['required', $jam],
            'lokasi_nama' => ['required', 'string', 'max:60'],
            'radius' => ['required', 'integer', 'min:10', 'max:100000'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'reward_sampai' => ['required', $jam],
            'reward_nominal' => ['required', 'integer', 'min:0'],
            'tepat_sampai' => ['required', $jam],
            'pinalti' => ['required', 'array', 'min:1', 'max:10'],
            'pinalti.*.sampai' => ['nullable', $jam],
            'pinalti.*.nominal' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $aturan = AturanAbsen::dariForm(
                $d['jam_masuk'], $d['reward_sampai'], (int) $d['reward_nominal'], $d['tepat_sampai'], $d['pinalti'],
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['aturan' => $e->getMessage()])->withInput();
        }

        Pengaturan::aktif()->update([
            'jam_masuk' => $d['jam_masuk'],
            'lokasi_nama' => $d['lokasi_nama'],
            'radius' => (int) $d['radius'],
            'lat' => (float) $d['lat'],
            'lng' => (float) $d['lng'],
            'izinkan_luar_radius' => $request->boolean('izinkan_luar_radius'),
            'aturan' => $aturan,
        ]);

        return back()->with('status', 'Pengaturan disimpan. Berlaku untuk absen berikutnya.');
    }
}
