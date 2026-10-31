<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Akun contoh. Segera ganti kata sandinya setelah login pertama. */
    public function run(): void
    {
        User::firstOrCreate(['username' => 'admin'], [
            'nama' => 'Admin', 'password' => 'admin123', 'role' => 'admin',
        ]);
        User::firstOrCreate(['username' => 'karyawan1'], [
            'nama' => 'Karyawan Contoh', 'password' => 'karyawan123', 'role' => 'karyawan',
        ]);
        Pengaturan::aktif();
    }
}
