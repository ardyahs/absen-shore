@php
    [$kelas, $teks] = match (true) {
        $a->status === 'perlu_ditinjau' => ['info', 'Menunggu tinjauan'],
        $a->status === 'ditolak' => ['buruk', 'Ditolak'],
        $a->kategori === 'reward' => ['baik', 'Reward '.rupiah($a->nominal, true)],
        $a->kategori === 'terlambat' => ['waspada', 'Pinalti '.rupiah($a->nominal, true)],
        default => ['baik', 'Tepat waktu'],
    };
@endphp
<span class="status {{ $kelas }}">{{ $teks }}</span>
