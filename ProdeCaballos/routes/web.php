<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CaballoController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\ProdeCaballoController;
use App\Http\Controllers\FormularioController;

Route::middleware('web')->group(function () {
    // ==============================
    // HOME
    // ==============================
    Route::get('/', function () {
        return view('welcome');
    });

    // ==============================
    // LOGIN
    // ==============================
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login.form');
    Route::post('/auth/login', [LoginController::class, 'login'])->name('login.process');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // ==============================
    // ADMIN
    // ==============================
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/GestionFormulariosModal', [AdminController::class, 'gestionFormulariosPage']);
    Route::get('/GestionFormulariosUsuariosModal/{id}/{nombre?}', [AdminController::class, 'gestionFormulariosUsuariosPage']);
    Route::post('/admin/prodes/listar', [AdminController::class, 'listarProdes']);
    Route::post('/admin/formularios/listar', [AdminController::class, 'listarFormulariosConDetalle']);
    Route::post('/admin/formularios/borrar', [AdminController::class, 'borrarFormulario']);

    // ==============================
    // CARRERAS
    // ==============================
    Route::get('/GestionCarrerasModal', [CarreraController::class, 'gestionCarrerasPage']);
    Route::post('/carreras/actualizar/{id}', [CarreraController::class, 'actualizarPost']);
    Route::apiResource('carreras', CarreraController::class);

    // ==============================
    // CABALLOS
    // ==============================
    Route::get('/GestionCaballosModal', [CaballoController::class, 'gestionCaballosPage']);
    Route::get('/caballos/{id}/historial', [CaballoController::class, 'historial']);
    Route::apiResource('caballos', CaballoController::class);

    // ==============================
    // PRODE CABALLOS
    // ==============================
    Route::apiResource('prode-caballos', ProdeCaballoController::class);

    // ==============================
    // FORMULARIOS
    // ==============================
    Route::apiResource('formularios', FormularioController::class);
    Route::post('/formularios/detalle', [FormularioController::class, 'detalleProde']);
    Route::post('/api/guardar-formulario', [FormularioController::class, 'store']);

    // ==============================
    // CATCH-ALL
    // ==============================
    Route::get('/{any}', function () {
        return view('welcome');
    })->where('any', '.*');
});
