<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EstadisticasController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect('/login');
        }

        // 👇 nombre EXACTO según tu carpeta y archivo
        return view('Estadisticas.HomeEstadisticas', ['user' => $user]);
    }
}
