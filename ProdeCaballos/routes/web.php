<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// HOME principal
Route::get('/', function () {
    return view('welcome');
});

// LOGIN
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// LOGOUT
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ADMIN (protegido por login y token válido)
Route::middleware(['auth', 'valid.token'])->group(function () {
    Route::get('/admin', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});
