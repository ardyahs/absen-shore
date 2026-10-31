<?php

namespace App\Models;

use App\Support\AturanAbsen;
use Illuminate\Database\Eloquent\Model;

/** Pengaturan kantor (satu baris saja). */
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $fillable = ['jam_masuk', 'radius', 'lokasi_nama', 'lat', 'lng', 'izinkan_luar_radius', 'aturan'];

    protected function casts(): array
    {
        return [
            'radius' => 'integer',
            'lat' => 'float',
            'lng' => 'float',
            'izinkan_luar_radius' => 'boolean',
            'aturan' => 'array',
        ];
    }

    public static function aktif(): self
    {
        return static::query()->first() ?? static::create([
            'jam_masuk' => '16:00',
            'radius' => 100,
            'lokasi_nama' => 'Kantor Pusat',
            'lat' => -5.1477,
            'lng' => 119.4327,
            'izinkan_luar_radius' => true,
            'aturan' => AturanAbsen::BAWAAN,
        ]);
    }

    /** Batas jam untuk dapat reward, atau null jika reward dimatikan (nominal 0). */
    public function batasReward(): ?string
    {
        $r = $this->aturan[0] ?? null;

        return $r && $r['nominal'] > 0
            ? AturanAbsen::menitKeJam(AturanAbsen::jamKeMenit($this->jam_masuk) + $r['sampai'])
            : null;
    }

    public function nominalReward(): int
    {
        return (int) ($this->aturan[0]['nominal'] ?? 0);
    }
}
