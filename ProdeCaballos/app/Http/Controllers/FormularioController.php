<?php

namespace App\Http\Controllers;

use App\Models\ProdeCaballo;
use App\Models\Formulario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormularioController extends Controller
{
    // 1. Listado de prodes con configuraciones, carreras y caballos
 // FormularioController.php

public function index()
{
    $prodes = \App\Models\ProdeCaballo::all()->map(function($prode) {
        return [
            'id' => $prode->id,
            'nombre' => $prode->nombre,
            'precio' => $prode->precio,
            'fechafin' => $prode->fechafin,
            // Si siempre hay una sola configuración, devolvemos solo la primera (o null si no hay)
            'configuracion' => $prode->configuraciones->first(),
            'carreras' => $prode->carreras->map(function($carrera) use ($prode) {
                // Buscamos el campo obligatoria en la tabla pivote
                $pivot = $carrera->pivot ?? null;
                return [
                    'id' => $carrera->id,
                    'nombre' => $carrera->nombre,
                    'obligatoria' => $pivot ? (bool)$pivot->obligatoria : false,
                    // Caballos asociados a la carrera
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
    $id = $request->input('id'); // obtener id del POST

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
        'fechafin' => $prode->fechafin,
        'configuracion' => $prode->configuraciones->first(),
        'carreras' => $prode->carreras->map(function($carrera) {
            $pivot = $carrera->pivot ?? null;
            return [
                'id' => $carrera->id,
                'nombre' => $carrera->nombre,
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
    $id = $request->input('id'); // Obtener ID desde el cuerpo POST

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
        'fechafin' => $prode->fechafin,
        'configuracion' => $prode->configuraciones->first(),
        'carreras' => $prode->carreras->map(function($carrera) {
            $pivot = $carrera->pivot ?? null;
            return [
                'id' => $carrera->id,
                'nombre' => $carrera->nombre,
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
            'dni' => 'required|string|max:30',
            'celular' => 'required|string|max:30',
            'forma_pago' => 'required|string|max:100',
            'pronosticos' => 'required|array|min:1',
            'pronosticos.*.carrera_id' => 'required|exists:carreras,id',
            'pronosticos.*.caballo_id' => 'required|exists:caballos,id',
            'pronosticos.*.es_suplente' => 'boolean'
        ]);
        DB::beginTransaction();
        try {
            $formulario = Formulario::create([
                'prode_caballo_id' => $validated['prode_caballo_id'],
                'nombre' => $validated['nombre'],
                'dni' => $validated['dni'],
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
