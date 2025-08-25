<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Carrera;
use App\Models\Caballo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class CarreraController extends Controller
{
    private function validarUsuario()
    {
        $user = Auth::user();
        return $user &&
            !empty($user->api_token) &&
            !empty($user->token_expiration) &&
            $user->token_expiration > Carbon::now();
    }

 public function index()
{
    if (!$this->validarUsuario()) {
        return response()->json(['message' => 'No autorizado.'], 401);
    }

    // Todas las carreras con sus caballos
    $carreras = Carrera::with(['caballos'])->get();

    // Todos los caballos disponibles
    $caballos = Caballo::all();

    // 🔥 Si los hípicos están en la misma tabla Carrera, sacamos los distintos
    $hipicos = Carrera::select('hipico')
        ->whereNotNull('hipico')
        ->distinct()
        ->pluck('hipico');

    return response()->json([
        'carreras' => $carreras,
        'caballos' => $caballos,
        'hipicos'  => $hipicos, // se agrega este array
    ]);
}


    public function show($id)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }
        $carrera = Carrera::with('caballos')->findOrFail($id);
        return response()->json($carrera);
    }

    public function store(Request $request)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'hipico' => 'nullable|string|max:255',
            'fecha' => 'nullable|date',
            'caballos' => 'required|array',
            'caballos.*' => 'exists:caballos,id',
        ]);

        // Forzamos estado ACTIVA en creación
        $data['estado'] = 'ACTIVA';

        $carrera = Carrera::create([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'hipico' => $data['hipico'] ?? null,
            'fecha' => $data['fecha'] ?? null,
            'estado' => $data['estado'],
        ]);

        $pivotData = [];
        foreach ($data['caballos'] as $index => $caballoId) {
            $pivotData[$caballoId] = ['numero' => $index + 1];
        }
        $carrera->caballos()->sync($pivotData);

        return response()->json($carrera->load('caballos'), 201);
    }

    // Nuevo método POST para actualizar sin romper PUT
public function actualizarPost(Request $request, $id)
{
    if (!$this->validarUsuario()) {
        return response()->json(['message' => 'No autorizado.'], 401);
    }

    $carrera = Carrera::findOrFail($id);

    $data = $request->validate([
        'nombre' => 'required|string|max:255',
        'descripcion' => 'nullable|string',
        'hipico' => 'nullable|string|max:255',
        'fecha' => 'nullable|date',
        'estado' => 'required|string|max:50',
        'caballos' => 'required|array',
        'caballos.*' => 'exists:caballos,id',
        'resultados' => 'nullable|array',
        'resultados.*.caballo_id' => 'required|exists:caballos,id',
        'resultados.*.posicion' => 'nullable|integer',
        'resultados.*.tiempos' => 'nullable|array',
        'resultados.*.tiempos.*.metros' => 'required_with:resultados.*.tiempos|integer',
        'resultados.*.tiempos.*.tiempo' => 'required_with:resultados.*.tiempos|numeric',
    ]);

    // ✅ Actualizar datos básicos de la carrera
    $carrera->update([
        'nombre' => $data['nombre'],
        'descripcion' => $data['descripcion'] ?? null,
        'hipico' => $data['hipico'] ?? null,
        'fecha' => $data['fecha'] ?? null,
        'estado' => !empty($data['resultados']) ? 'FINALIZADA' : $data['estado'],
    ]);

    // ✅ Actualizar caballos en la relación
    $pivotData = [];
    foreach ($data['caballos'] as $index => $caballoId) {
        $pivotData[$caballoId] = ['numero' => $index + 1];
    }
    $carrera->caballos()->sync($pivotData);

    // ✅ Guardar resultados si vienen
    if (!empty($data['resultados'])) {
        foreach ($data['resultados'] as $res) {
            // Buscar o crear la relación caballo-carrera
            $cc = \App\Models\CaballoCarrera::firstOrNew([
                'carrera_id' => $carrera->id,
                'caballo_id' => $res['caballo_id'],
            ]);

            $ganador = ($res['posicion'] ?? null) === 1 ? 1 : 0;
            $perdedor = $ganador ? 0 : 1;

            $cc->posicion = $res['posicion'] ?? null;
            $cc->ganador = $ganador;
            $cc->perdedor = $perdedor;
            $cc->save();

            // Guardar tiempos (se agregan, no se borran)
            if (!empty($res['tiempos'])) {
                foreach ($res['tiempos'] as $t) {
                    \App\Models\CaballoCarreraTiempo::create([
                        'caballo_carrera_id' => $cc->id,
                        'metros' => $t['metros'],
                        'tiempo' => $t['tiempo'],
                    ]);
                }
            }
        }
    }

    return response()->json(['success' => true, 'message' => '✅ Carrera actualizada correctamente']);
}




public function update(Request $request, $id)
{
    if (!$this->validarUsuario()) {
        return response()->json(['message' => 'No autorizado.'], 401);
    }
    dd($request);
    $carrera = Carrera::findOrFail($id);

    $data = $request->validate([
        'nombre' => 'required|string|max:255',
        'descripcion' => 'nullable|string',
        'hipico' => 'nullable|string|max:255',
        'fecha' => 'nullable|date',
        'estado' => 'required|string|max:50',
        'caballos' => 'required|array',
        'caballos.*' => 'exists:caballos,id',
        'resultados' => 'nullable|array',
        'resultados.*.caballo_carrera_id' => 'required|exists:caballo_carrera,id',
        'resultados.*.posicion' => 'nullable|integer',
        'resultados.*.tiempos' => 'nullable|array',
        'resultados.*.tiempos.*.metros' => 'required_with:resultados.*.tiempos|integer',
        'resultados.*.tiempos.*.tiempo' => 'required_with:resultados.*.tiempos|numeric',
    ]);

    // actualizar datos de la carrera
    $carrera->update([
        'nombre' => $data['nombre'],
        'descripcion' => $data['descripcion'] ?? null,
        'hipico' => $data['hipico'] ?? null,
        'fecha' => $data['fecha'] ?? null,
        'estado' => !empty($data['resultados']) ? 'FINALIZADA' : $data['estado'],
    ]);

    // actualizar caballos de la carrera
    $pivotData = [];
    foreach ($data['caballos'] as $index => $caballoId) {
        $pivotData[$caballoId] = ['numero' => $index + 1];
    }
    $carrera->caballos()->sync($pivotData);

    // procesar resultados (posición + tiempos)
    if (!empty($data['resultados'])) {
        foreach ($data['resultados'] as $res) {
            $cc = \App\Models\CaballoCarrera::findOrFail($res['caballo_carrera_id']);

            $ganador = ($res['posicion'] ?? null) === 1 ? 1 : 0;
            $perdedor = $ganador ? 0 : 1;

            $cc->update([
                'posicion' => $res['posicion'] ?? null,
                'ganador' => $ganador,
                'perdedor' => $perdedor,
            ]);

            // 👉 en vez de borrar, agregamos los tiempos nuevos
            if (!empty($res['tiempos'])) {
                foreach ($res['tiempos'] as $t) {
                    \App\Models\CaballoCarreraTiempo::create([
                        'caballo_carrera_id' => $cc->id,
                        'metros' => $t['metros'],
                        'tiempo' => $t['tiempo'],
                    ]);
                }
            }
        }
    }

    return response()->json(['success' => true, 'message' => 'Carrera actualizada']);
}


    public function destroy($id)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }
        $carrera = Carrera::findOrFail($id);
        $carrera->delete();
        return response()->json(['message' => 'Carrera eliminada']);
    }
}
