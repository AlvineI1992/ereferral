<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\RegisteredUserController;


Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});



Route::get('/roles', function () {
    return Inertia::render('roles/Index');
})->middleware(['auth', 'verified'])->name('roles');

Route::middleware('auth')->get('/api/roles', [RoleController::class, 'index']);

Route::get('/users', function () {
    return Inertia::render('users/Index');
})->middleware(['auth', 'verified'])->name('users');


Route::get('/users/create', function () {
    return Inertia::render('users/usersForm');
})->middleware(['auth', 'verified'])->name('users.create');

Route::middleware('auth')->get('/api/users', [RegisteredUserController::class, 'index']);




require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
