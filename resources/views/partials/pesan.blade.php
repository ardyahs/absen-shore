@if (session('status'))
    <p class="pesan baik" role="status">{{ session('status') }}</p>
@endif
@if ($errors->any())
    <p class="pesan buruk" role="alert">{{ $errors->first() }}</p>
@endif
