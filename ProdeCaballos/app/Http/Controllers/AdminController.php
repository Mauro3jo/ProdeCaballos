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
    
    
   public function borrarFormulario(Request $request)
{
    try {
        $id = $request->input('formulario_id');
        if (!$id) {
            return response()->json(['error' => 'ID requerido'], 400);
        }

        $form = \App\Models\Formulario::find($id);

        if (!$form) {
            return response()->json(['error' => 'Formulario no encontrado'], 404);
        }

        // Primero borra los pronósticos asociados a este formulario
        \App\Models\Pronostico::where('formulario_id', $id)->delete();

        // Ahora sí, borra el formulario
        $form->delete();

        return response()->json(['success' => true]);
    } catch (\Throwable $e) {
        \Log::error('Error al borrar formulario: ' . $e->getMessage());
        return response()->json([
            'error' => 'Error interno del servidor: ' . $e->getMessage()
        ], 500);
    }
}




}
