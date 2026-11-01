<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class KaryawanController extends Controller
{
    private const ATURAN_GAJI = ['required', 'integer', 'min:0', 'max:10000000'];

    public function index()
    {
        return view('admin.karyawan', [
            'users' => User::where('role', 'karyawan')->orderByDesc('aktif')->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'gaji_harian' => self::ATURAN_GAJI,
        ]);

        User::create($data + ['role' => 'karyawan', 'aktif' => true]);

        return back()->with('status', $data['nama'].' ditambahkan.');
    }

    public function status(User $user)
    {
        abort_unless($user->role === 'karyawan', 404);
        $user->update(['aktif' => ! $user->aktif]);

        return back()->with('status', $user->nama.($user->aktif ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    /** Gaji per hari hadir; dipakai rekap untuk menghitung total gaji periode. */
    public function gaji(Request $request, User $user)
    {
        abort_unless($user->role === 'karyawan', 404);
        $data = $request->validate(['gaji_harian' => self::ATURAN_GAJI]);
        $user->update($data);

        return back()->with('status', 'Gaji harian '.$user->nama.' menjadi '.rupiah($user->gaji_harian).'.');
    }

    /** Admin mengatur ulang kata sandi karyawan yang lupa. */
    public function sandi(Request $request, User $user)
    {
        abort_unless($user->role === 'karyawan', 404);
        $data = $request->validate(['password' => ['required', 'string', 'min:6']]);
        $user->update(['password' => $data['password']]);

        return back()->with('status', 'Kata sandi '.$user->nama.' diperbarui.');
    }
}
