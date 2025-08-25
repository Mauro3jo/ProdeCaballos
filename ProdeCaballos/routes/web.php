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
// Vista de gestión de formularios
Route::get('/GestionFormulariosModal', [AdminController::class, 'gestionFormulariosPage']);

    // LOGIN FORM
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login.form');
    Route::post('/auth/login', [LoginController::class, 'login'])->name('login.process');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // ADMIN (requiere sesión activa)
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');

    // Endpoint POST para actualizar carrera (sin usar PUT)
    Route::post('/carreras/actualizar/{id}', [CarreraController::class, 'actualizarPost']);

    // Endpoints para Vue/JS frontend (sin prefijo)
    Route::apiResource('caballos', CaballoController::class);
    Route::apiResource('carreras', CarreraController::class);
    Route::apiResource('prode-caballos', ProdeCaballoController::class);
    Route::apiResource('formularios', FormularioController::class);

    // Extra endpoints específicos
    Route::post('/formularios/detalle', [FormularioController::class, 'detalleProde']);
    Route::post('/api/guardar-formulario', [FormularioController::class, 'store']);
    Route::post('/admin/prodes/listar', [AdminController::class, 'listarProdes']);
    Route::post('/admin/formularios/listar', [AdminController::class, 'listarFormulariosConDetalle']);
Route::post('/admin/formularios/borrar', [AdminController::class, 'borrarFormulario']);

    // ¡ESTE SIEMPRE AL FINAL!
    Route::get('/{any}', function () {
        return view('welcome');
    })->where('any', '.*');
});
