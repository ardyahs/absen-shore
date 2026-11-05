@if ($a->foto)
    <a href="{{ route('foto', $a) }}" target="_blank" rel="noopener">
        <img class="foto" src="{{ route('foto', $a) }}" alt="Foto absen {{ $a->user->nama }}" loading="lazy" width="40" height="40">
    </a>
@else
    <span class="mut kecil">Manual</span>
@endif
