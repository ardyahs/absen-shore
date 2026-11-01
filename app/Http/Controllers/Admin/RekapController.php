<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Rekap;
use App\Support\Waktu;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    public function index(Request $request)
    {
        $hari = $this->hari($request);
        $cari = trim((string) $request->query('cari'));
        $rows = Rekap::semua($hari, $cari);

        return view('admin.rekap', [
            'hari' => $hari,
            'dari' => Waktu::mulai($hari),
            'cari' => $cari,
            'rows' => $rows,
            'totalReward' => $rows->sum('total_reward'),
            'totalPinalti' => $rows->sum('total_pinalti'),
            'totalTerlambat' => $rows->sum('terlambat'),
            'totalGaji' => $rows->sum('total_gaji'),
        ]);
    }

    /** CSV dengan BOM UTF-8 agar langsung terbaca rapi di Excel. */
    public function csv(Request $request)
    {
        $hari = $this->hari($request);
        $rows = Rekap::semua($hari);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nama', 'Username', 'Hadir', 'Reward', 'Tepat waktu', 'Terlambat', 'Total reward', 'Total pinalti', 'Saldo', '% Tepat', 'Gaji harian', 'Gaji pokok', 'Total gaji'], ',', '"', '');
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['user']->nama, $r['user']->username, $r['hadir'], $r['reward'], $r['tepat'],
                    $r['terlambat'], $r['total_reward'], $r['total_pinalti'], $r['saldo'], $r['persen_tepat'],
                    $r['gaji_harian'], $r['gaji_pokok'], $r['total_gaji'],
                ], ',', '"', '');
            }
            fclose($out);
        }, "rekap-{$hari}-hari.csv", ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    private function hari(Request $request): int
    {
        return (int) $request->query('hari') === 30 ? 30 : 15;
    }
}
