<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (
            !$user->api_token ||
            !$user->token_expiration ||
            $user->token_expiration < Carbon::now()
        ) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect('/login')->withErrors([
                'token' => 'Tu sesión ha expirado. Iniciá sesión nuevamente.',
            ]);
        }

        return view('admin.home', ['user' => $user]);
    }
}
