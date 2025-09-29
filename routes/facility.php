<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\RefFacilitiesController;
use App\Http\Controllers\RefFacilitytypeController;

// API Routes (Sanctum-protected)

Route::get('/facilities', function (Request $request) {
    // Check permissions for the authenticated user
    $permissions = [
        'canCreateFacilities' => $request->user()->can('facility create'),
        'canEditFacilities' => $request->user()->can('facility edit'),
        'canDeleteFacilities' => $request->user()->can('facility delete'),
        'canViewFacilities' => $request->user()->can('facility list'),
    ];

    // Return the Inertia view with the permissions data
    return Inertia::render('Ref_Facilities/Index', $permissions);
})
    ->middleware(['auth:sanctum', 'verified']) // Apply permission middleware here
    ->name('facilities');

// Authenticated and permission-guarded API routes
Route::middleware(['auth:sanctum'])->group(function () {

    Route::put('/facilities/update/{id}', [RefFacilitiesController::class, 'update'])
        ->middleware('can:facility edit')
        ->name('facility.update');

    Route::delete('/facilities/delete/{id}', [RefFacilitiesController::class, 'destroy'])
        ->middleware('can:facility delete')
        ->name('facility.destroy');

    Route::get('/facilities-list/{type}', [RefFacilitiesController::class, 'facility_list'])
        ->name('facility.facility_list');

        Route::post('/facilities/store', [RefFacilitiesController::class, 'store'])
        ->middleware('can:facility create')
        ->name('facility.store');

    Route::get('/facilities/info/{id}', [RefFacilitiesController::class, 'show'])
        //->middleware('can:facility view')
        ->name('facility.info');

    Route::get('/facilities/profile_form', function () {
        return Inertia::render('Emr/ProfileForm');
    })->middleware('can:facility list')->name('facilities/profile_form');

});
/* Route::get('/api/facilities', [RefFacilitiesController::class, 'index']); */
Route::middleware(['auth:sanctum', 'can:facility list'])->get('/facility/list', [RefFacilitiesController::class, 'index'])->name('facility.list');
// API Routes (Sanctum-protected)

/* Route::get('/facility_type', function () {
    return Inertia::render('Ref_Facilities/Index');
})->middleware(['auth:sanctum', 'verified'])->name('facilities'); */
Route::middleware('auth:sanctum')->group(function () {

    Route::put('/facility_type/update/{id}', [RefFacilitytypeController::class, 'update'])
       
        ->name('facility_type.update');

    Route::delete('/facility_type/delete/{id}', [RefFacilitytypeController::class, 'destroy'])
      
        ->name('facility_type.destroy');

    Route::post('/facility_type/store', [RefFacilitytypeController::class, 'store'])
        
        ->name('facility_type.store');

    Route::get('/facility_type/info/{id}', [RefFacilitytypeController::class, 'show'])
        ->middleware('can:view facility_type')
        ->name('facility_type.info');
});

// If you want this to be publicly accessible, you can leave it as is.
// Otherwise, wrap in auth and add permission middleware too.
Route::get('/facility_type/list', [RefFacilitytypeController::class, 'list'])
    
    ->name('facility_type.list');

