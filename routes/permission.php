
<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\PermissionController;

// Inertia Page Route (Web, uses session-based auth)
Route::get('/permission', function (Request $request) {
     // Check permissions for the authenticated user
     $permissions = [
        'canCreatePermission' => $request->user()->can('permission create'),
        'canEditPermission' => $request->user()->can('permission edit'),
        'canDeletePermission' => $request->user()->can('permission delete'),
        'canViewPermission' => $request->user()->can('permission list'),
    ];
    return Inertia::render('Permission/Index',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('/permission');


// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/permission/lists', [PermissionController::class, 'index']);
    Route::put('/permission/update/{perm}', [PermissionController::class, 'update'])->name('permission.update');
    Route::delete('/permission/delete/{perm}', [PermissionController::class, 'destroy'])->name('permission.destroy');
    Route::post('/permission/store', [PermissionController::class, 'store'])->name('permission.store');
    Route::get('/permission-has-role', [PermissionController::class, 'permission_has_role'])->name('permission.has.role');
    Route::get('/permission/info/{id}', [PermissionController::class, 'show'])->name('permission.info');
});

?>