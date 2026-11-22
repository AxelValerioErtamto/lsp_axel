<?php

use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RentalController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::view('/', 'home')->name('home');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/garages', [GarageController::class, 'index'])->name('garages.index');
    Route::post('/garages', [GarageController::class, 'store'])->name('garages.store');
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::patch('/tenants/{user}/approve', [TenantController::class, 'approve'])->name('tenants.approve');
    Route::patch('/tenants/{user}/reject', [TenantController::class, 'reject'])->name('tenants.reject');
});

Route::middleware(['auth', 'role:tenant'])->group(function () {
    Route::get('/rentals/create', [RentalController::class, 'create'])->name('rentals.create');
    Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');
    Route::get('/rentals/{rental}', [RentalController::class, 'show'])->name('rentals.show');
});