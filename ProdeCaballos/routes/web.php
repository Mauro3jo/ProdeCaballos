<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CaballoController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\ProdeCaballoController;
use App\Http\Controllers\FormularioController;

Route::middleware('web')->group(function () {
    // HOME principal
    Route::get('/', function () {
        return view('welcome');
    });

    // LOGIN FORM
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login.form');
    Route::post('/auth/login', [LoginController::class, 'login'])->name('login.process');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // ADMIN (requiere sesión activa)
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');

    // Endpoints para Vue/JS frontend (sin prefijo)
    Route::apiResource('caballos', CaballoController::class);
    Route::apiResource('carreras', CarreraController::class);

    // Ruta para crear ProdeCaballo
Route::apiResource('prode-caballos', ProdeCaballoController::class);
// Para la parte pública (sin login)
Route::apiResource('formularios', FormularioController::class); // <-- Así, como los otros
    Route::post('/formularios/detalle', [FormularioController::class, 'detalleProde']);
    Route::post('/api/guardar-formulario', [FormularioController::class, 'store']);

Route::post('/admin/prodes/listar', [AdminController::class, 'listarProdes']);
Route::post('/admin/formularios/listar', [AdminController::class, 'listarFormulariosConDetalle']);
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');



});
