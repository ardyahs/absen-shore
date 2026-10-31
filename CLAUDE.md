# Panduan Proyek: Absensi Karyawan (Laravel)

Aplikasi web absensi foto + GPS dengan reward/pinalti. Laravel + Blade + CSS/JS biasa (tanpa build step). Bahasa antarmuka: Indonesia.

## Aturan bisnis (jangan diubah tanpa persetujuan)
- Jam masuk 16:00 WITA. Aturan disimpan di tabel `pengaturan` (kolom `aturan`, JSON); bawaan di `AturanAbsen::BAWAAN`. Admin bisa mengubahnya.
- `<=15:45` reward +5.000 | `15:46-16:05` tepat waktu 0 | `16:06-16:15` -5.000 | `16:16-16:30` -10.000 | `>=16:31` -15.000.
- Waktu absen SELALU dari jam server (`Waktu::jam()`), tidak pernah dari klien. Detik diabaikan.
- Foto hanya dari kamera langsung (bukan unggah galeri). Satu absen valid per hari per karyawan.
- Absen di luar radius -> status `perlu_ditinjau`, tidak dihitung sampai admin menyetujui.

## Konvensi
- Setiap perubahan pada `app/Support/AturanAbsen.php` WAJIB disertai pembaruan `tests/Unit/AturanAbsenTest.php`; jalankan `php artisan test`.
- Jangan menambah paket Composer/npm tanpa alasan kuat; jelaskan dulu alasannya.
- Teks pengguna di Blade selalu lewat `{{ }}` (ter-escape). Di JS, pakai `textContent`, bukan `innerHTML`.
- Jangan menampilkan kata sandi/hash/token di respons atau log.
- Kerjakan per fitur kecil, jalankan tes, dan jelaskan apa yang diubah.
