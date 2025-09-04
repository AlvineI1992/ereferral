<?php

use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/roles/list', [RoleController::class, 'index']);
    Route::put('/roles/update/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/delete/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/roles/store', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/info/{id}', [RoleController::class, 'show'])->name('roles.info');
});

Route::get('/roles', function (Request $request) {
    $permissions = [
        'canCreateRole' => $request->user()->can('role create'),
        'canEditRole' => $request->user()->can('role edit'),
        'canDeleteRole' => $request->user()->can('role delete'),
        'canViewRole' => $request->user()->can('role list'),
        'canAssignRole' => $request->user()->can('role assign'),
    ];
    return Inertia::render('Roles/Index',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('/roles');
?>