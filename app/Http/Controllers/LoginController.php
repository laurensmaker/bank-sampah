<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Nasabah;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('nasabah.dasbor');
        }

        return view('frontend.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Jika role nasabah
            if ($user->role == 'nasabah') {

                $nasabah = Nasabah::where('email', $user->email)->first();

                if (!$nasabah) {
                    Auth::logout();

                    return back()->withErrors([
                        'email' => 'Data nasabah tidak ditemukan.',
                    ])->withInput($request->only('email'));
                }

                $request->session()->put('id_nasabah', $nasabah->id);

                return redirect()->route('nasabah.dasbor');
            }

            // Jika role armada
            if ($user->role == 'armada') {
                return redirect()->route('armada.dasbor');
            }

            // Jika role tidak dikenali
            Auth::logout();

            return back()->withErrors([
                'email' => 'Role pengguna tidak valid.',
            ]);
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
