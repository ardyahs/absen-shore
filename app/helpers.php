<?php

use Illuminate\Support\Carbon;

if (! function_exists('rupiah')) {
    /** rupiah(5000) → "Rp5.000"; rupiah(-5000, true) → "−Rp5.000" */
    function rupiah(int|float|string|null $nilai, bool $tanda = false): string
    {
        $n = (int) $nilai;
        $teks = 'Rp'.number_format(abs($n), 0, ',', '.');

        return $tanda && $n !== 0 ? ($n < 0 ? '−' : '+').$teks : $teks;
    }
}

if (! function_exists('tgl')) {
    /** tgl('2026-11-01') → "Min, 1 Nov" */
    function tgl(Carbon|string $tanggal, string $pola = 'D, j M'): string
    {
        return Carbon::parse($tanggal)->locale('id')->translatedFormat($pola);
    }
}
