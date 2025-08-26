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
            $tipo = (int) $user->tipo;

            Auth::login($user);
            $request->session()->regenerate();

            $user->api_token = Str::random(60);
            $user->token_expiration = Carbon::now()->addHour();
            $user->save();

            if ($tipo === 1) {
                return $this->successResponse($request, '/admin');
            }

            if ($tipo === 2) {
                return $this->successResponse($request, '/estadisticas');
            }

            return $this->failResponse($request, 'Tipo de usuario no válido.');
        }

        return $this->failResponse($request, 'Credenciales incorrectas.');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->api_token = null;
            $user->token_expiration = null;
            $user->save();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * 🔹 Helper: respuesta exitosa
     */
    private function successResponse(Request $request, string $redirect)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => $redirect
            ]);
        }
        return redirect($redirect);
    }

    /**
     * 🔹 Helper: respuesta de error
     */
    private function failResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'errors' => ['email' => [$message]]
            ], 422);
        }
        return redirect('/login')->withErrors(['email' => $message]);
    }
}
