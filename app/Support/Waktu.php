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

    /** Tanggal Y-m-d dari input pengguna; null jika kosong/tidak valid. */
    public static function bacaTanggal(mixed $nilai): ?string
    {
        $nilai = (string) $nilai;
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $nilai));

        return checkdate($m, $d, $y) ? $nilai : null;
    }

    /** Tanggal awal periode N hari terakhir (termasuk hari ini). */
    public static function mulai(int $hari): string
    {
        return self::sekarang()->subDays($hari - 1)->toDateString();
    }
}
