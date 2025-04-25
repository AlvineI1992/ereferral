<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\RefEmrController;



Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::get('/users', function () {
    return Inertia::render('Users/Index');
})->middleware(['auth', 'verified'])->name('users');


Route::get('/users/create', function () {
    return Inertia::render('Users/usersForm');
})->middleware(['auth', 'verified'])->name('users.create');

// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/users', [RegisteredUserController::class, 'index']);
    Route::put('/users/update/{role}', [RegisteredUserController::class, 'update'])->name('user.update');
    Route::delete('/users/delete/{role}', [RegisteredUserController::class, 'destroy'])->name('user.destroy');
    Route::post('/users/store', [RegisteredUserController::class, 'store'])->name('user.store');
    Route::get('/users/sample', [RegisteredUserController::class, 'sample'])->name('user.sample');
    
});

// Inertia Page Route (Web, uses session-based auth)
Route::get('/roles', function () {
    return Inertia::render('Roles/Index');
})->middleware(['auth:sanctum', 'verified'])->name('roles');

// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/roles', [RoleController::class, 'index']);
    Route::put('/roles/update/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/delete/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/roles/store', [RoleController::class, 'store'])->name('roles.store');
});

// Inertia Page Route (Web, uses session-based auth)
Route::get('/permission', function () {
    return Inertia::render('Permission/Index');
})->middleware(['auth:sanctum', 'verified'])->name('permission');

// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/permission', [PermissionController::class, 'index']);
    Route::put('/permission/update/{role}', [PermissionController::class, 'update'])->name('permission.update');
    Route::delete('/permission/delete/{role}', [PermissionController::class, 'destroy'])->name('permission.destroy');
    Route::post('/permission/store', [PermissionController::class, 'store'])->name('permission.store');
});

Route::get('/emr', function () {
    return Inertia::render('Emr/Index');
})->middleware(['auth:sanctum', 'verified'])->name('emr');


Route::get('emr/profile/{id}', function ($id) {
    return Inertia::render('Emr/ProfileLayout', [
        'id' => $id
    ]);
})->middleware(['auth:sanctum', 'verified'])->name('emr.profile');

// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/emr', [RefEmrController::class, 'index']);
    Route::put('/emr/update/{id}', [RefEmrController::class, 'update'])->name('emr.update');
    Route::delete('/emr/delete/{id}', [RefEmrController::class, 'destroy'])->name('emr.destroy');
    Route::post('/emr/store', [RefEmrController::class, 'store'])->name('emr.store');
    Route::get('/api/emr/info/{id}', [RefEmrController::class, 'show'])->name('emr.info');

    Route::get('/emr/profile_form', function () {
        return Inertia::render('Emr/ProfileForm');
    })->name('emr/profile_form');

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
