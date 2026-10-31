<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

final class Rekap
{
    /** Ringkasan dari kumpulan absen. Hanya status "valid" yang dihitung. */
    public static function ringkas(Collection $rows): array
    {
        $valid = $rows->where('status', 'valid');
        $hitung = fn (string $k) => $valid->where('kategori', $k)->count();
        $reward = (int) $valid->where('nominal', '>', 0)->sum('nominal');
        $pinalti = (int) -$valid->where('nominal', '<', 0)->sum('nominal');
        $hadir = $valid->count();

        return [
            'hadir' => $hadir,
            'reward' => $hitung('reward'),
            'tepat' => $hitung('tepat'),
            'terlambat' => $hitung('terlambat'),
            'total_reward' => $reward,
            'total_pinalti' => $pinalti,
            'saldo' => $reward - $pinalti,
            'persen_tepat' => $hadir ? (int) round(($hitung('reward') + $hitung('tepat')) * 100 / $hadir) : 0,
            'ditinjau' => $rows->where('status', 'perlu_ditinjau')->count(),
        ];
    }

    /** Rekap per karyawan aktif untuk N hari terakhir. */
    public static function semua(int $hari, ?string $cari = null): Collection
    {
        $dari = Waktu::mulai($hari);

        return User::query()
            ->where('role', 'karyawan')
            ->where('aktif', true)
            ->when($cari, fn ($q) => $q->where('nama', 'like', '%'.$cari.'%'))
            ->with(['attendances' => fn ($q) => $q->where('tanggal', '>=', $dari)])
            ->orderBy('nama')
            ->get()
            ->map(fn (User $u) => ['user' => $u] + self::ringkas($u->attendances));
    }
}
