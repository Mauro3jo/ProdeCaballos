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

        // Si no hay usuario, redirige al home
        if (!$user) {
            return redirect('/');
        }

        // Si no hay token o expiró, también al home
        if (
            empty($user->api_token) ||
            empty($user->token_expiration) ||
            $user->token_expiration < Carbon::now()
        ) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect('/');
        }

        return view('admin.home', ['user' => $user]);
    }
}
