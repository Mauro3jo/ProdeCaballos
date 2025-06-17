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

    // Nuevo método para listar los Prodes (ahora incluye foto_url, premio, tipo)
    public function listarProdes()
    {
        $prodes = ProdeCaballo::select('id', 'nombre', 'premio', 'precio', 'fechafin', 'foto', 'tipo')
            ->orderBy('fechafin', 'desc')
            ->get()
            ->map(function ($prode) {
                return [
                    'id' => $prode->id,
                    'nombre' => $prode->nombre,
                    'premio' => $prode->premio,
                    'precio' => $prode->precio,
                    'fechafin' => $prode->fechafin,
                    'tipo' => $prode->tipo,
                    'foto_url' => $prode->foto
                        ? asset('img/' . $prode->foto)
                        : null,
                ];
            });

        return response()->json($prodes);
    }

    // Método modificado para filtrar por prode_caballo_id y traer TODO (incluye preciopagado)
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

        // LISTA de carreras para este prode
        $prode = ProdeCaballo::with(['carreras'])->find($prodeId);
        $carreras = $prode
            ? $prode->carreras->map(function ($c) {
                return [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                ];
            })->values()
            : [];

        $data = $formularios->map(function ($form) {
            return [
                'id' => $form->id,
                'nombre' => $form->nombre,
                'alias' => $form->alias,
                'alias_admin' => $form->alias_admin,
                'celular' => $form->celular,
                'forma_pago' => $form->forma_pago,
                'preciopagado' => $form->preciopagado, // <-- NUEVO
                'created_at' => $form->created_at
                    ->timezone('America/Argentina/Buenos_Aires')
                    ->format('Y-m-d H:i:s'),
                'pronosticos' => $form->pronosticos->map(function ($p) {
                    return [
                        'carrera_id' => $p->carrera->id ?? null,
                        'carrera_nombre' => $p->carrera->nombre ?? 'N/A',
                        'caballo_nombre' => $p->caballo->nombre ?? '',
                        'es_suplente' => (bool)$p->es_suplente,
                    ];
                }),
            ];
        });

        return response()->json([
            'formularios' => $data,
            'carreras' => $carreras,
        ]);
    }
}
