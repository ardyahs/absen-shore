<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu absen masuk. Kolom "tanggal" sengaja tidak di-cast ke date supaya tetap
 * string Y-m-d dan perbandingan tanggal konsisten di MySQL maupun SQLite.
 *
 * status: valid | perlu_ditinjau | ditolak
 */
class Attendance extends Model
{
    protected $fillable = [
        'user_id', 'tanggal', 'jam', 'selisih_menit', 'kategori', 'nominal',
        'lat', 'lng', 'akurasi', 'jarak', 'foto', 'status', 'catatan', 'ditinjau_oleh', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'selisih_menit' => 'integer',
            'nominal' => 'integer',
            'lat' => 'float',
            'lng' => 'float',
            'akurasi' => 'integer',
            'jarak' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Dicatat admin tanpa foto/GPS. */
    public function manual(): bool
    {
        return $this->dibuat_oleh !== null;
    }

    public function keterangan(): string
    {
        return match (true) {
            $this->status === 'perlu_ditinjau' => 'Anda di luar radius kantor. Absen menunggu tinjauan admin.',
            $this->status === 'ditolak' => 'Absen ditolak admin.',
            $this->manual() => 'Dicatat manual oleh admin.',
            $this->kategori === 'reward' => 'Absen '.abs($this->selisih_menit).' menit lebih awal.',
            $this->kategori === 'terlambat' => 'Terlambat '.$this->selisih_menit.' menit.',
            default => 'Absen sesuai waktu.',
        };
    }
}
