<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Caballo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class CaballoController extends Controller
{
    private function validarUsuario()
    {
        $user = Auth::user();
        return $user &&
            !empty($user->api_token) &&
            !empty($user->token_expiration) &&
            $user->token_expiration > Carbon::now();
    }

    public function gestionCaballosPage()
    {
        return view('admin.GestionCaballosModal');
    }

    public function index()
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }
        return response()->json(Caballo::all());
    }

    public function show($id)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }
        $caballo = Caballo::findOrFail($id);
        return response()->json($caballo);
    }

    public function store(Request $request)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);
        $caballo = Caballo::create($data);
        return response()->json($caballo, 201);
    }

    public function update(Request $request, $id)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }
        $caballo = Caballo::findOrFail($id);
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);
        $caballo->update($data);
        return response()->json($caballo);
    }

    public function destroy($id)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }
        $caballo = Caballo::findOrFail($id);
        $caballo->delete();
        return response()->json(['message' => 'Caballo eliminado']);
    }

    public function historial($id)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }

        $caballo = \App\Models\Caballo::findOrFail($id);

        // Carreras donde este caballo participó con posición cargada
        $carreras = \App\Models\CaballoCarrera::with(['carrera', 'tiempos'])
            ->where('caballo_id', $caballo->id)
            ->whereNotNull('posicion') // Ignora carreras sin resultados
            ->get();

        $totalCarreras = $carreras->count();
        $victorias = $carreras->where('posicion', 1)->count();
        $porcentajeVictorias = $totalCarreras > 0
            ? round(($victorias / $totalCarreras) * 100, 2)
            : 0;

        $detalle = $carreras->map(function ($cc) {
            return [
                'carrera_id'       => $cc->carrera->id,
                'carrera_nombre'   => $cc->carrera->nombre,
                'carrera_descripcion' => $cc->carrera->descripcion, // ← agregado
                'fecha'            => $cc->carrera->fecha,
                'posicion'         => $cc->posicion,
                'tiempos' => $cc->tiempos->map(function ($t) {
                    return [
                        'metros'     => $t->metros,
                        'tiempo'     => $t->tiempo,      // TEXT
                        'diferencia' => $t->diferencia,  // ← agregado
                    ];
                }),
            ];
        });

        return response()->json([
            'caballo'              => $caballo->nombre,
            'total_carreras'       => $totalCarreras,
            'victorias'            => $victorias,
            'porcentaje_victorias' => $porcentajeVictorias,
            'carreras'             => $detalle,
        ]);
    }
}
