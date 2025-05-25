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
}
