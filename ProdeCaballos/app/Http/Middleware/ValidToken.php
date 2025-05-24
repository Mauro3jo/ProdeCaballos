<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ValidToken
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (
            !$user ||
            !$user->api_token ||
            !$user->token_expiration ||
            $user->token_expiration < Carbon::now()
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login')->withErrors([
                'token' => 'Tu sesión ha expirado. Iniciá sesión nuevamente.'
            ]);
        }

        return $next($request);
    }
}
