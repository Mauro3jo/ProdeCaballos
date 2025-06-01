<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProdeCaballo;
use App\Models\ConfiguracionProde;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ProdeCaballoController extends Controller
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
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $prodes = ProdeCaballo::with(['configuraciones', 'carreras'])->get();

        return response()->json(['prodes' => $prodes]);
    }

    public function store(Request $request)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'fechafin' => 'required|date',
            'reglas' => 'nullable|string',
            'foto' => 'nullable|file|image|max:2048', // hasta 2 MB, ajusta si querés
            'configuracion' => 'required|array',
            'configuracion.cantidad_obligatorias' => 'required|integer',
            'configuracion.cantidad_opcionales' => 'required|integer',
            'configuracion.cantidad_suplentes' => 'required|integer',
            'carreras' => 'required|array',
            'carreras.*.id' => 'required|exists:carreras,id',
            'carreras.*.obligatoria' => 'required|boolean',
        ]);

        // Manejo de la foto
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('prodes', 'public');
        }

        $prode = ProdeCaballo::create([
            'nombre' => $data['nombre'],
            'precio' => $data['precio'],
            'fechafin' => $data['fechafin'],
            'reglas' => $data['reglas'] ?? null,
            'foto' => $fotoPath,
        ]);

        // Configuración
        $configData = $data['configuracion'];
        $configuracion = new ConfiguracionProde([
            'cantidad_obligatorias' => $configData['cantidad_obligatorias'],
            'cantidad_opcionales' => $configData['cantidad_opcionales'],
            'cantidad_suplentes' => $configData['cantidad_suplentes'],
            'prode_caballo_id' => $prode->id,
        ]);
        $configuracion->save();

        // Carreras
        $syncData = [];
        foreach ($data['carreras'] as $carrera) {
            $syncData[$carrera['id']] = ['obligatoria' => $carrera['obligatoria']];
        }
        $prode->carreras()->sync($syncData);

        return response()->json($prode->load('configuraciones', 'carreras'), 201);
    }

    public function show($id)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $prode = ProdeCaballo::with(['configuraciones', 'carreras'])->findOrFail($id);

        return response()->json($prode);
    }

    public function update(Request $request, $id)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $prode = ProdeCaballo::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'fechafin' => 'required|date',
            'reglas' => 'nullable|string',
            'foto' => 'nullable|file|image|max:2048',
            'configuracion' => 'required|array',
            'configuracion.cantidad_obligatorias' => 'required|integer',
            'configuracion.cantidad_opcionales' => 'required|integer',
            'configuracion.cantidad_suplentes' => 'required|integer',
            'carreras' => 'required|array',
            'carreras.*.id' => 'required|exists:carreras,id',
            'carreras.*.obligatoria' => 'required|boolean',
        ]);

        // Manejo de la foto: si viene nueva la actualiza, sino deja la anterior
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('prodes', 'public');
            $prode->foto = $fotoPath;
        }

        $prode->nombre = $data['nombre'];
        $prode->precio = $data['precio'];
        $prode->fechafin = $data['fechafin'];
        $prode->reglas = $data['reglas'] ?? null;
        $prode->save();

        // Configuración
        $configuracion = $prode->configuraciones()->first();
        if ($configuracion) {
            $configuracion->update([
                'cantidad_obligatorias' => $data['configuracion']['cantidad_obligatorias'],
                'cantidad_opcionales' => $data['configuracion']['cantidad_opcionales'],
                'cantidad_suplentes' => $data['configuracion']['cantidad_suplentes'],
            ]);
        } else {
            $configuracion = new ConfiguracionProde([
                'cantidad_obligatorias' => $data['configuracion']['cantidad_obligatorias'],
                'cantidad_opcionales' => $data['configuracion']['cantidad_opcionales'],
                'cantidad_suplentes' => $data['configuracion']['cantidad_suplentes'],
                'prode_caballo_id' => $prode->id,
            ]);
            $configuracion->save();
        }

        // Carreras
        $syncData = [];
        foreach ($data['carreras'] as $carrera) {
            $syncData[$carrera['id']] = ['obligatoria' => $carrera['obligatoria']];
        }
        $prode->carreras()->sync($syncData);

        return response()->json($prode->load('configuraciones', 'carreras'));
    }

    public function destroy($id)
    {
        if (!$this->validarUsuario()) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $prode = ProdeCaballo::findOrFail($id);
        $prode->delete();

        return response()->json(['message' => 'ProdeCaballo eliminado']);
    }
}
