<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user);
            $request->session()->regenerate();

            // Generar nuevo token y tiempo de expiración
            $user->api_token = Str::random(60);
            $user->token_expiration = Carbon::now()->addHour();
            $user->save();

            // Redirección según tipo
            if ($user->tipo == 1) {
                return redirect()->intended('/admin');
            } elseif ($user->tipo == 2) {
                return redirect()->intended('/estadisticas');
            } else {
                // Si no tiene tipo definido, lo mando al login
                Auth::logout();
                return redirect('/login')->withErrors([
                    'email' => 'Tipo de usuario no válido.',
                ]);
            }
        }

        return back()->withErrors([
            'email' => 'Credenciales incorrectas.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user) {
            $user->api_token = null;
            $user->token_expiration = null;
            $user->save();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
