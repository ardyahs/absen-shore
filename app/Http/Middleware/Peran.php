<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * "peran"        → pastikan akun masih aktif (akun yang dinonaktifkan langsung keluar).
 * "peran:admin"  → khusus admin; peran lain diarahkan ke halamannya sendiri.
 */
class Peran
{
    public function handle(Request $request, Closure $next, ?string $peran = null): Response
    {
        $user = $request->user();

        if (! $user || ! $user->aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['error' => 'Sesi berakhir, silakan masuk kembali.'], 401)
                : redirect()->route('login');
        }

        if ($peran && $user->role !== $peran) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Halaman ini bukan untuk akun Anda.'], 403)
                : redirect()->route('home');
        }

        return $next($request);
    }
}
