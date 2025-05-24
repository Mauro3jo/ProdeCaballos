<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

// Página principal redirige al login (opcional)
Route::get('/', function () {
    return redirect('/login');
});

// Login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// Logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rutas protegidas por login + token válido
Route::middleware(['auth', 'valid.token'])->group(function () {

    // Ruta del panel admin
    Route::get('/admin', function () {
        return view('admin.dashboard'); // creá esta vista en resources/views/admin/dashboard.blade.php
    })->name('admin.dashboard');

    // Acá podés agregar más rutas protegidas
});
