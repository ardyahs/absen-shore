<?php

namespace Tests\Unit;

use App\Support\AturanAbsen;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AturanAbsenTest extends TestCase
{
    public static function batasWaktu(): array
    {
        return [
            ['14:30', 'reward', 5000],
            ['15:45', 'reward', 5000],   // tepat 15 menit sebelum
            ['15:46', 'tepat', 0],
            ['16:00', 'tepat', 0],
            ['16:05', 'tepat', 0],
            ['16:06', 'terlambat', -5000],
            ['16:15', 'terlambat', -5000],
            ['16:16', 'terlambat', -10000],
            ['16:30', 'terlambat', -10000],
            ['16:31', 'terlambat', -15000],
            ['18:00', 'terlambat', -15000],
        ];
    }

    #[DataProvider('batasWaktu')]
    public function test_klasifikasi_jam_masuk_16(string $jam, string $kategori, int $nominal): void
    {
        $selisih = AturanAbsen::selisih($jam, '16:00');

        $this->assertSame(['kategori' => $kategori, 'nominal' => $nominal], AturanAbsen::klasifikasi($selisih));
    }

    public static function jamBuka(): array
    {
        return [
            ['00:00', false],
            ['03:37', false],
            ['14:59', false],
            ['15:00', true],
            ['16:00', true],
            ['23:59', true],
        ];
    }

    #[DataProvider('jamBuka')]
    public function test_absen_hanya_dibuka_jam_15_sampai_23_59(string $jam, bool $dibuka): void
    {
        $this->assertSame($dibuka, AturanAbsen::dibuka($jam));
    }

    public function test_jarak_gps(): void
    {
        $this->assertSame(0, (int) round(AturanAbsen::jarakMeter(-5.1, 119.4, -5.1, 119.4)));

        $d = AturanAbsen::jarakMeter(-5.1, 119.4, -5.101, 119.4);
        $this->assertGreaterThan(105, $d); // ±111 m per 0,001 derajat lintang
        $this->assertLessThan(117, $d);
    }

    public function test_form_bawaan_menghasilkan_aturan_bawaan(): void
    {
        $aturan = AturanAbsen::dariForm('16:00', '15:45', 5000, '16:05', [
            ['sampai' => '16:15', 'nominal' => 5000],
            ['sampai' => '16:30', 'nominal' => 10000],
            ['sampai' => null, 'nominal' => 15000],
        ]);

        $this->assertSame(AturanAbsen::BAWAAN, $aturan);
    }

    public function test_kembali_ke_form_lalu_ke_aturan_hasilnya_sama(): void
    {
        $f = AturanAbsen::keForm(AturanAbsen::BAWAAN, '16:00');

        $this->assertSame('15:45', $f['reward_sampai']);
        $this->assertSame('16:05', $f['tepat_sampai']);
        $this->assertNull(end($f['pinalti'])['sampai']);
        $this->assertSame(
            AturanAbsen::BAWAAN,
            AturanAbsen::dariForm('16:00', $f['reward_sampai'], $f['reward_nominal'], $f['tepat_sampai'], $f['pinalti']),
        );
    }

    public function test_jam_masuk_digeser_aturan_ikut_bergeser(): void
    {
        $aturan = AturanAbsen::dariForm('08:00', '07:45', 5000, '08:05', [['nominal' => 20000]]);

        $this->assertSame(-15, $aturan[0]['sampai']);
        $this->assertSame(5, $aturan[1]['sampai']);
        $this->assertSame(['kategori' => 'terlambat', 'nominal' => -20000], AturanAbsen::klasifikasi(6, $aturan));
    }

    public function test_batas_jam_tidak_berurutan_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AturanAbsen::dariForm('16:00', '16:10', 5000, '16:05', [['nominal' => 5000]]);
    }

    public function test_reward_nol_dihitung_tepat_waktu(): void
    {
        $aturan = AturanAbsen::dariForm('16:00', '15:45', 0, '16:05', [['nominal' => 5000]]);

        $this->assertSame(['kategori' => 'tepat', 'nominal' => 0], AturanAbsen::klasifikasi(-30, $aturan));
    }
}
