<?php

namespace Tests\Unit;

use App\Support\Waktu;
use PHPUnit\Framework\TestCase;

class WaktuTest extends TestCase
{
    public function test_baca_tanggal_hanya_menerima_y_m_d_yang_valid(): void
    {
        $this->assertSame('2026-11-04', Waktu::bacaTanggal('2026-11-04'));
        $this->assertNull(Waktu::bacaTanggal('2026-02-30'));
        $this->assertNull(Waktu::bacaTanggal('04-11-2026'));
        $this->assertNull(Waktu::bacaTanggal(''));
        $this->assertNull(Waktu::bacaTanggal(null));
    }
}
