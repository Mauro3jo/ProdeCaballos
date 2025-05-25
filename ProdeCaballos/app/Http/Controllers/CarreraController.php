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

        $carreras = Carrera::with('caballos')->get();
        $caballos = Caballo::all();

        return response()->json([
            'carreras' => $carreras,
            'caballos' => $caballos,
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
            'fecha' => 'nullable|date',
            'caballos' => 'required|array',
            'caballos.*' => 'exists:caballos,id',
        ]);

        // Forzamos estado ACTIVA en creación
        $data['estado'] = 'ACTIVA';

        $carrera = Carrera::create([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
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

    public function update(Request $request, $id)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado.'], 401);
        }
        $carrera = Carrera::findOrFail($id);
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha' => 'nullable|date',
            'estado' => 'required|string|max:50',
            'caballos' => 'required|array',
            'caballos.*' => 'exists:caballos,id',
        ]);

        $carrera->update([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha' => $data['fecha'] ?? null,
            'estado' => $data['estado'],
        ]);

        $pivotData = [];
        foreach ($data['caballos'] as $index => $caballoId) {
            $pivotData[$caballoId] = ['numero' => $index + 1];
        }
        $carrera->caballos()->sync($pivotData);

        return response()->json($carrera->load('caballos'));
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
