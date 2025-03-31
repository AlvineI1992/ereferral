<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\RoleController;



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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
