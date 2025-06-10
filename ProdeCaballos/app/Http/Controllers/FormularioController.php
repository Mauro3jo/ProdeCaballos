<?php

namespace App\Http\Controllers;

use App\Models\ProdeCaballo;
use App\Models\Formulario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormularioController extends Controller
{
    // 1. Listado de prodes con configuraciones, carreras y caballos
    public function index()
    {
        $prodes = ProdeCaballo::all()->map(function($prode) {
            return [
                'id' => $prode->id,
                'nombre' => $prode->nombre,
                'precio' => $prode->precio,
                'premio' => $prode->premio,
                'fechafin' => $prode->fechafin,
                'foto_url' => $prode->foto
                    ? asset('img/' . $prode->foto)
                    : null,
                'configuracion' => $prode->configuraciones->first(),
                'carreras' => $prode->carreras->map(function($carrera) use ($prode) {
                    $pivot = $carrera->pivot ?? null;
                    return [
                        'id' => $carrera->id,
                        'nombre' => $carrera->nombre,
                        'hipico' => $carrera->hipico, // <-- AGREGADO
                        'obligatoria' => $pivot ? (bool)$pivot->obligatoria : false,
                        'caballos' => $carrera->caballos->map(function($caballo) {
                            return [
                                'id' => $caballo->id,
                                'nombre' => $caballo->nombre,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json($prodes);
    }

    public function detalleProde(Request $request)
    {
        $id = $request->input('id');

        if (!$id) {
            return response()->json(['error' => 'ID requerido'], 400);
        }

        $prode = ProdeCaballo::find($id);

        if (!$prode) {
            return response()->json(['error' => 'Prode no encontrado'], 404);
        }

        $data = [
            'id' => $prode->id,
            'nombre' => $prode->nombre,
            'precio' => $prode->precio,
            'premio' => $prode->premio,
            'fechafin' => $prode->fechafin,
            'foto_url' => $prode->foto ? asset('img/' . $prode->foto) : null,
            'reglas' => $prode->reglas,
            'configuracion' => $prode->configuraciones->first(),
            'carreras' => $prode->carreras->map(function($carrera) {
                $pivot = $carrera->pivot ?? null;
                return [
                    'id' => $carrera->id,
                    'nombre' => $carrera->nombre,
                    'hipico' => $carrera->hipico, // <-- AGREGADO
                    'obligatoria' => $pivot ? (bool)$pivot->obligatoria : false,
                    'caballos' => $carrera->caballos->map(function($caballo) {
                        return [
                            'id' => $caballo->id,
                            'nombre' => $caballo->nombre,
                        ];
                    }),
                ];
            }),
        ];

        return response()->json($data);
    }

    // 2. Detalle de un solo prode (por ID) con todo incluido
    public function show(Request $request)
    {
        $id = $request->input('id');

        if (!$id) {
            return response()->json(['error' => 'ID requerido'], 400);
        }

        $prode = ProdeCaballo::find($id);
        if (!$prode) {
            return response()->json(['error' => 'Prode no encontrado'], 404);
        }

        $data = [
            'id' => $prode->id,
            'nombre' => $prode->nombre,
            'precio' => $prode->precio,
            'premio' => $prode->premio,
            'fechafin' => $prode->fechafin,
            'configuracion' => $prode->configuraciones->first(),
            'carreras' => $prode->carreras->map(function($carrera) {
                $pivot = $carrera->pivot ?? null;
                return [
                    'id' => $carrera->id,
                    'nombre' => $carrera->nombre,
                    'hipico' => $carrera->hipico, // <-- AGREGADO
                    'obligatoria' => $pivot ? (bool)$pivot->obligatoria : false,
                    'caballos' => $carrera->caballos->map(function($caballo) {
                        return [
                            'id' => $caballo->id,
                            'nombre' => $caballo->nombre,
                        ];
                    }),
                ];
            }),
        ];

        return response()->json($data);
    }

    // 3. Guardar formulario + pronósticos
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prode_caballo_id' => 'required|exists:prode_caballos,id',
            'nombre' => 'required|string|max:255',
            'alias' => 'required|string|max:255',
            'alias_admin' => 'nullable|string|in:lafijacuadrera2025,Studvecinaslindas',
            'celular' => 'required|string|max:30',
            'forma_pago' => 'required|in:Efectivo,Transferencia',
            'pronosticos' => 'required|array|min:1',
            'pronosticos.*.carrera_id' => 'required|exists:carreras,id',
            'pronosticos.*.caballo_id' => 'required|exists:caballos,id',
            'pronosticos.*.es_suplente' => 'boolean'
        ]);

        // Si es transferencia, alias_admin es obligatorio
        if (
            $validated['forma_pago'] === 'Transferencia' &&
            (!isset($validated['alias_admin']) || !$validated['alias_admin'])
        ) {
            return response()->json(['success' => false, 'error' => 'Debe seleccionar un alias de transferencia.'], 422);
        }

        DB::beginTransaction();
        try {
            $formulario = Formulario::create([
                'prode_caballo_id' => $validated['prode_caballo_id'],
                'nombre' => $validated['nombre'],
                'alias' => $validated['alias'],
                'alias_admin' => $validated['alias_admin'] ?? null,
                'celular' => $validated['celular'],
                'forma_pago' => $validated['forma_pago'],
            ]);

            foreach ($validated['pronosticos'] as $pronostico) {
                $formulario->pronosticos()->create([
                    'carrera_id' => $pronostico['carrera_id'],
                    'caballo_id' => $pronostico['caballo_id'],
                    'es_suplente' => $pronostico['es_suplente'] ?? false
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'formulario_id' => $formulario->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
