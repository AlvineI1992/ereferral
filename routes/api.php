<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;

Route::prefix('api')->middleware('auth:sanctum')->group(function () {
    Route::get('/roles', [RoleController::class, 'apiIndex'])->name('api.roles.index');
    Route::post('/roles', [RoleController::class, 'apiStore'])->name('api.roles.store');
    Route::delete('/roles/{role}', [RoleController::class, 'apiDestroy'])->name('api.roles.destroy');
});
