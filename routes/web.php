<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\RoleController;
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
    return Inertia::render('Roles/Index');
})->middleware(['auth', 'verified'])->name('roles');



Route::get('/users', function () {
    return Inertia::render('users/Index');
})->middleware(['auth', 'verified'])->name('users');


Route::get('/users/create', function () {
    return Inertia::render('users/usersForm');
})->middleware(['auth', 'verified'])->name('users.create');

Route::middleware('auth')->get('/api/roles', [RoleController::class, 'index']);

Route::middleware('auth')->get('/api/users', [RegisteredUserController::class, 'index']);

Route::middleware('auth')->delete('/roles/dele{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

Route::middleware('auth')->post('/roles/store', [RoleController::class, 'store'])->name('roles.store');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
