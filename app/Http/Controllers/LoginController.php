<?php

namespace App\Http\Controllers;

use App\Models\Armada;
use App\Models\Nasabah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // Jika user sudah login, arahkan ke dasbor yang sesuai
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user->role === 'armada') {
                return redirect()->route('armada.dasbor');
            } elseif ($user->role === 'nasabah') {
                return redirect()->route('nasabah.dasbor');
            }
            
            // Jika role tidak dikenali, logout
            Auth::logout();
        }

        return view('frontend.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->withInput($request->only('email'));
        }

        $request->session()->regenerate();
        $user = Auth::user();


        // ✅ Pakai switch agar jelas & tidak ada urutan yang "menang" duluan
        switch ($user->role) {
            case 'armada':
                $armada = Armada::where('email', $user->email)->first();
                if (!$armada) {
                    Auth::logout();
                    return back()->withErrors([
                        'email' => 'Data armada tidak ditemukan. Hubungi admin.',
                    ])->withInput($request->only('email'));
                }
                $request->session()->put('id_armada', $armada->id);
                return redirect()->route('armada.dasbor');

            case 'nasabah':
                $nasabah = Nasabah::where('email', $user->email)->first();
                if (!$nasabah) {
                    Auth::logout();
                    return back()->withErrors([
                        'email' => 'Data nasabah tidak ditemukan.',
                    ])->withInput($request->only('email'));
                }
                $request->session()->put('id_nasabah', $nasabah->id);
                return redirect()->route('nasabah.dasbor');

            default:
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Role pengguna tidak valid: ' . ($user->role ?? 'NULL'),
                ]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
