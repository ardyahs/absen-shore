<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Logika inti absensi: klasifikasi reward/pinalti, konversi jam, dan jarak GPS.
 * Sengaja tanpa ketergantungan framework supaya mudah diuji.
 *
 * "selisih" = menit absen − menit jam masuk (negatif = lebih awal). Detik diabaikan.
 * Setiap perubahan di sini WAJIB disertai pembaruan tests/Unit/AturanAbsenTest.php.
 */
final class AturanAbsen
{
    /** Jam masuk 16:00: ≤15:45 reward | 15:46–16:05 tepat | 16:06–16:15 | 16:16–16:30 | ≥16:31 */
    public const BAWAAN = [
        ['dari' => -1440, 'sampai' => -15, 'kategori' => 'reward', 'nominal' => 5000],
        ['dari' => -14, 'sampai' => 5, 'kategori' => 'tepat', 'nominal' => 0],
        ['dari' => 6, 'sampai' => 15, 'kategori' => 'terlambat', 'nominal' => -5000],
        ['dari' => 16, 'sampai' => 30, 'kategori' => 'terlambat', 'nominal' => -10000],
        ['dari' => 31, 'sampai' => 1440, 'kategori' => 'terlambat', 'nominal' => -15000],
    ];

    private const POLA_JAM = '/^([01]\d|2[0-3]):[0-5]\d$/';

    /** @return array{kategori: string, nominal: int} */
    public static function klasifikasi(int $selisih, array $aturan = self::BAWAAN): array
    {
        foreach ($aturan as $r) {
            if ($selisih >= $r['dari'] && $selisih <= $r['sampai']) {
                return ['kategori' => $r['kategori'], 'nominal' => (int) $r['nominal']];
            }
        }

        return ['kategori' => 'tepat', 'nominal' => 0];
    }

    public static function jamKeMenit(string $jam): int
    {
        [$h, $m] = array_map('intval', explode(':', $jam) + [0, 0]);

        return $h * 60 + $m;
    }

    public static function menitKeJam(int $menit): string
    {
        $m = (($menit % 1440) + 1440) % 1440;

        return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }

    public static function selisih(string $jamAbsen, string $jamMasuk): int
    {
        return self::jamKeMenit($jamAbsen) - self::jamKeMenit($jamMasuk);
    }

    /** Jarak dua titik dalam meter (rumus Haversine). */
    public static function jarakMeter(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371000;
        $t = M_PI / 180;
        $dLat = ($lat2 - $lat1) * $t;
        $dLon = ($lon2 - $lon1) * $t;
        $a = sin($dLat / 2) ** 2 + cos($lat1 * $t) * cos($lat2 * $t) * sin($dLon / 2) ** 2;

        return 2 * $r * asin(sqrt($a));
    }

    public static function kategori(int $nominal): string
    {
        return $nominal > 0 ? 'reward' : ($nominal < 0 ? 'terlambat' : 'tepat');
    }

    /**
     * Ubah isian form (jam biasa) menjadi aturan berbasis menit terhadap jam masuk.
     * Tingkat pinalti terakhir tidak punya batas ("dan seterusnya").
     *
     * @param  array<int, array{sampai?: ?string, nominal: int|string}>  $pinalti
     */
    public static function dariForm(string $jamMasuk, string $rewardSampai, int $rewardNominal, string $tepatSampai, array $pinalti): array
    {
        $pinalti = array_values($pinalti);
        if ($pinalti === []) {
            throw new InvalidArgumentException('Minimal harus ada satu tingkat pinalti.');
        }

        $jamBatas = [$rewardSampai, $tepatSampai];
        foreach (array_slice($pinalti, 0, -1) as $p) {
            $jamBatas[] = (string) ($p['sampai'] ?? '');
        }
        foreach ([$jamMasuk, ...$jamBatas] as $jam) {
            if (! preg_match(self::POLA_JAM, $jam)) {
                throw new InvalidArgumentException('Setiap batas jam wajib diisi dengan format JJ:MM.');
            }
        }

        $batas = array_map(fn (string $j) => self::selisih($j, $jamMasuk), $jamBatas);
        foreach ($batas as $i => $b) {
            if ($b <= -1440 || $b >= 1440 || ($i > 0 && $b <= $batas[$i - 1])) {
                throw new InvalidArgumentException('Batas jam harus berurutan dari yang paling awal (periksa '.$jamBatas[$i].').');
            }
        }

        $aturan = [
            ['dari' => -1440, 'sampai' => $batas[0], 'nominal' => max(0, $rewardNominal)],
            ['dari' => $batas[0] + 1, 'sampai' => $batas[1], 'nominal' => 0],
        ];
        $sebelum = $batas[1];
        foreach ($pinalti as $i => $p) {
            $sampai = $i === count($pinalti) - 1 ? 1440 : $batas[$i + 2];
            $aturan[] = ['dari' => $sebelum + 1, 'sampai' => $sampai, 'nominal' => -abs((int) $p['nominal'])];
            $sebelum = $sampai;
        }

        return array_map(fn (array $r) => [
            'dari' => $r['dari'],
            'sampai' => $r['sampai'],
            'kategori' => self::kategori($r['nominal']),
            'nominal' => $r['nominal'],
        ], $aturan);
    }

    /** Kebalikan dariForm(): aturan → nilai awal form. */
    public static function keForm(array $aturan, string $jamMasuk): array
    {
        usort($aturan, fn ($a, $b) => $a['dari'] <=> $b['dari']);
        if (count($aturan) < 3) {
            $aturan = self::BAWAAN;
        }
        $dasar = self::jamKeMenit($jamMasuk);
        $jam = fn (array $r) => self::menitKeJam($dasar + $r['sampai']);
        $pinalti = array_slice($aturan, 2);

        return [
            'reward_sampai' => $jam($aturan[0]),
            'reward_nominal' => max(0, (int) $aturan[0]['nominal']),
            'tepat_sampai' => $jam($aturan[1]),
            'pinalti' => array_map(fn (array $r, int $i) => [
                'sampai' => $i === count($pinalti) - 1 ? null : $jam($r),
                'nominal' => abs((int) $r['nominal']),
            ], $pinalti, array_keys($pinalti)),
        ];
    }
}
