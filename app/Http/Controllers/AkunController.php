<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AkunController extends Controller
{
    public function show(Request $request)
    {
        return view('akun', ['user' => $request->user()]);
    }

    public function kataSandi(Request $request)
    {
        $data = $request->validate([
            'lama' => ['required', 'string'],
            'baru' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! Hash::check($data['lama'], $request->user()->password)) {
            return back()->withErrors(['lama' => 'Kata sandi lama salah.']);
        }

        $request->user()->update(['password' => $data['baru']]);

        return back()->with('status', 'Kata sandi diperbarui.');
    }
}
