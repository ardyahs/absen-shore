<?php

return [
    // Zona waktu absen. Semua jam absen dihitung dari jam server di zona ini.
    'zona' => 'Asia/Makassar',

    // Uji coba di luar jam kerja, mis. SIMULASI_JAM=15:42. Diabaikan saat APP_ENV=production.
    'simulasi_jam' => env('SIMULASI_JAM'),
];
