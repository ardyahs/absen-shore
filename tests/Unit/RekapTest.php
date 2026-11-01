<?php

namespace Tests\Unit;

use App\Support\Rekap;
use PHPUnit\Framework\TestCase;

class RekapTest extends TestCase
{
    public function test_total_gaji_dari_gaji_harian_ditambah_reward_dikurangi_pinalti(): void
    {
        $rows = collect([
            ['status' => 'valid', 'kategori' => 'reward', 'nominal' => 5000],       // 55.000
            ['status' => 'valid', 'kategori' => 'tepat', 'nominal' => 0],           // 50.000
            ['status' => 'valid', 'kategori' => 'terlambat', 'nominal' => -10000],  // 40.000
            ['status' => 'perlu_ditinjau', 'kategori' => 'reward', 'nominal' => 5000],
            ['status' => 'ditolak', 'kategori' => 'tepat', 'nominal' => 0],
        ]);

        $r = Rekap::ringkas($rows, 50000);

        $this->assertSame(3, $r['hadir']);
        $this->assertSame(150000, $r['gaji_pokok']);
        $this->assertSame(145000, $r['total_gaji']);
    }

    public function test_tanpa_absen_gaji_nol(): void
    {
        $this->assertSame(0, Rekap::ringkas(collect(), 75000)['total_gaji']);
    }
}
