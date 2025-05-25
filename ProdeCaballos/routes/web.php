<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminController; // ✅ CORREGIDO

Route::middleware('web')->group(function () {
    // HOME principal
    Route::get('/', function () {
        return view('welcome');
    });

    // LOGIN FORM
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login.form');

    // LOGIN POST (API SEPARADA)
    Route::post('/auth/login', [LoginController::class, 'login'])->name('login.process');

    // LOGOUT
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // ADMIN (requiere sesión activa)
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
});
