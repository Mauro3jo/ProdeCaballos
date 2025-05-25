<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\ProdeCaballo;
use App\Models\Formulario;

class AdminController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/');
        }

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

    // Nuevo método para listar los Prodes (solo datos básicos)
    public function listarProdes()
    {
        $prodes = ProdeCaballo::select('id', 'nombre', 'precio', 'fechafin')->orderBy('fechafin', 'desc')->get();
        return response()->json($prodes);
    }

    // Método modificado para filtrar por prode_caballo_id
    public function listarFormulariosConDetalle(Request $request)
    {
        $prodeId = $request->input('prode_caballo_id');
        if (!$prodeId) {
            return response()->json(['error' => 'ID de Prode requerido'], 400);
        }

        $formularios = Formulario::with(['pronosticos.carrera', 'pronosticos.caballo'])
            ->where('prode_caballo_id', $prodeId)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $formularios->map(function ($form) {
            return [
                'id' => $form->id,
                'nombre' => $form->nombre,
                'dni' => $form->dni,
                'celular' => $form->celular,
                'forma_pago' => $form->forma_pago,
                'created_at' => $form->created_at->format('Y-m-d H:i:s'),
                'pronosticos' => $form->pronosticos->map(function ($p) {
                    return [
                        'carrera_nombre' => $p->carrera->nombre ?? 'N/A',
                        'caballo_nombre' => $p->caballo->nombre ?? 'N/A',
                        'es_suplente' => (bool)$p->es_suplente,
                    ];
                }),
            ];
        });

        return response()->json($data);
    }
}
