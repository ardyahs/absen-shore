<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function form()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $kunci = 'login:'.$request->ip();
        if (RateLimiter::tooManyAttempts($kunci, 10)) {
            $menit = (int) ceil(RateLimiter::availableIn($kunci) / 60);

            return back()->withErrors(['username' => "Terlalu banyak percobaan. Coba lagi dalam {$menit} menit."])->onlyInput('username');
        }
        RateLimiter::hit($kunci, 300);

        $kredensial = ['username' => trim($data['username']), 'password' => $data['password'], 'aktif' => true];
        if (! Auth::attempt($kredensial)) {
            return back()->withErrors(['username' => 'Username atau kata sandi salah.'])->onlyInput('username');
        }

        RateLimiter::clear($kunci);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Arahkan ke halaman awal sesuai peran. */
    public function home(Request $request)
    {
        return redirect()->route($request->user()->isAdmin() ? 'admin.dashboard' : 'absen');
    }
}
