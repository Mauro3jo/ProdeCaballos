<?php

namespace App\Http\Controllers;

use App\Models\ProdeCaballo;
use App\Models\Formulario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormularioController extends Controller
{
    // 1. Trae todos los prodes con config, carreras y caballos por carrera
    public function prodes()
    {
        $prodes = ProdeCaballo::with([
            'configuracion',
            'carreras.caballos'
        ])->get();

        return response()->json($prodes);
    }

    // 2. Detalle de un prode por id (con carreras y caballos)
    public function prodeDetalle($id)
    {
        $prode = ProdeCaballo::with([
            'configuracion',
            'carreras.caballos'
        ])->findOrFail($id);

        return response()->json($prode);
    }

    // 3. Guarda formulario + pronósticos (datos personales + array de pronósticos)
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
