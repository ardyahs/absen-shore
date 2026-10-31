<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Semua waktu absen berasal dari jam server (WITA), tidak pernah dari HP karyawan. */
final class Waktu
{
    public static function sekarang(): Carbon
    {
        return Carbon::now(config('absensi.zona', 'Asia/Makassar'));
    }

    public static function tanggal(): string
    {
        return self::sekarang()->toDateString();
    }

    /** Jam server JJ:MM (detik dibuang). SIMULASI_JAM diabaikan di production. */
    public static function jam(): string
    {
        $simulasi = (string) config('absensi.simulasi_jam');
        if ($simulasi !== '' && ! app()->isProduction() && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $simulasi)) {
            return $simulasi;
        }

        return self::sekarang()->format('H:i');
    }

    /** Tanggal awal periode N hari terakhir (termasuk hari ini). */
    public static function mulai(int $hari): string
    {
        return self::sekarang()->subDays($hari - 1)->toDateString();
    }
}
