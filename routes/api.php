<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Referral;
use App\Http\Controllers\Auth\AuthenticatedSessionController;



Route::get('generate_code/{hfhudcode}', [Referral::class, 'generate_reference'])->middleware('auth:sanctum')->name('referral.reference');
Route::get('demographics', [Referral::class, 'demographic_reference'])->middleware('auth:sanctum')->name('referral.demographics');
/* Route::get('demographics', [Referral::class, 'demographic_reference'])->name('referral.demographics'); */
Route::post('login', [Referral::class, 'login']);


/* Route::prefix('api')->middleware('auth:sanctum')->group(function () {
    Route::get('/roles', [RoleController::class, 'apiIndex'])->name('api.roles.index');
    Route::post('/roles', [RoleController::class, 'apiStore'])->name('api.roles.store');
    Route::delete('/roles/{role}', [RoleController::class, 'apiDestroy'])->name('api.roles.destroy');
}); */


