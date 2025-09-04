<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\RefEmrController;

Route::get('/emr', function (Request $request) {
    $permissions = [
        'canCreate' => $request->user()->can('provider create'),
        'canEdit' => $request->user()->can('provider edit'),
        'canDelete' => $request->user()->can('provider delete'),
        'canView' => $request->user()->can('provider list'),
        'canAssign' => $request->user()->can('provider assign'),
    ];
    return Inertia::render('Emr/Index',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('/emr');


Route::get('emr/profile/{id}', function (Request $request,$id) {
    $permissions = [
        'user' => $request->user()->load('roles'),
        'id' => $id,
        'is_include'=>true
    ];
    return Inertia::render('Emr/ProfileLayout',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('emr.profile');

// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/emr/list', [RefEmrController::class, 'index'])->name('emr.list');
    Route::put('/emr/update/{id}', [RefEmrController::class, 'update'])->name('emr.update');
/*     Route::delete('/emr/delete/{id}', [RefEmrController::class, 'destroy'])->name('emr.destroy'); */
    Route::delete('/emr/{id}', [RefEmrController::class, 'destroy'])->name('emr.destroy');
    Route::post('/emr/{id}/restore', [RefEmrController::class, 'restore'])->name('emr.restore');
    Route::delete('/emr/{id}/force', [RefEmrController::class, 'forceDelete'])->name('emr.forceDelete');

    Route::post('/emr/store', [RefEmrController::class, 'store'])->name('emr.store');
    Route::get('/emr/info/{id}', [RefEmrController::class, 'show'])->name('emr.info');

    Route::post('/emr/assign', [RefEmrController::class, 'assign'])->name('emr.assign-facility');
    Route::post('/emr/revoke', [RefEmrController::class, 'revoke'])->name('emr.revoke-facility');

    Route::get('/emr/profile_form', function (Request $request) {
        return Inertia::render('Emr/ProfileForm');
    })->name('emr/profile_form');

});

