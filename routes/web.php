<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\RefEmrController;
use App\Http\Controllers\RefFacilitiesController;
use App\Http\Controllers\RefFacilitytypeController;
use App\Http\Controllers\DemographicController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReferralPatientInfoController;

use App\Http\Controllers\ReferralClinicalController;

use Illuminate\Http\Request;



Route::get('/', function () {
    return Inertia::render('auth/login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::get('/users', function (Request $request) {
    $permissions = [
        'canCreate' => $request->user()->can('user create'),
        'canEdit' => $request->user()->can('user edit'),
        'canDelete' => $request->user()->can('user delete'),
        'canView' => $request->user()->can('user list'),
        'canAssign' => $request->user()->can('user assign'),
    ];
    return Inertia::render('Users/Index',$permissions);
})->middleware(['auth', 'verified'])->name('users');


Route::get('/users/create', function () {
    return Inertia::render('Users/usersForm');
})->middleware(['auth', 'verified'])->name('users.create');


Route::get('/users/assign-roles/{id}', function (Request $request,$id) {
    $permissions = [
        'user' => $request->user()->load('roles'),
        'id' => $id,
        'is_include'=>true
    ];
    
    return Inertia::render('Users/UserProfileLayout',$permissions);
})->middleware(['auth:sanctum', 'verified']);

Route::get('/users/assigned-roles/{id}', function ($id) {
    return Inertia::render('Users/UserProfileLayout', [
        'id' => $id,
        'is_include'=>true
    ]);
})->middleware(['auth:sanctum', 'verified']);

// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/users', [RegisteredUserController::class, 'index']);
    Route::put('/users/update/{role}', [RegisteredUserController::class, 'update'])->name('user.update');
    Route::delete('/users/delete/{role}', [RegisteredUserController::class, 'destroy'])->name('user.destroy');
    Route::post('/users/store', [RegisteredUserController::class, 'store'])->name('user.store');
    Route::get('/users/sample', [RegisteredUserController::class, 'sample'])->name('user.sample');
    Route::get('/users/info/{id}', [RegisteredUserController::class, 'show'])->name('user.info');
    Route::get('/user-has-role', [RegisteredUserController::class, 'role_has_user'])->name('user.has.role');
});

Route::patch('/users/assign-roles/{id}', [RegisteredUserController::class, 'assignRolesToUser'])->name('user.assign');
Route::patch('/users/revoke-roles/{id}', [RegisteredUserController::class, 'revokeRolesFromUser'])->name('user.revoke');

// Inertia Page Route (Web, uses session-based auth)
Route::get('/roles', function (Request $request) {
    $permissions = [
        'canCreateRole' => $request->user()->can('role create'),
        'canEditRole' => $request->user()->can('role edit'),
        'canDeleteRole' => $request->user()->can('role delete'),
        'canViewRole' => $request->user()->can('role list'),
        'canAssignRole' => $request->user()->can('role assign'),
    ];
    return Inertia::render('Roles/Index',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('roles');

Route::get('roles/assign/{id}', function (Request $request,$id) {
    $permissions = [
        'user' => $request->user()->load('roles'),
        'id' => $id,
        'is_include'=>true
    ];
    return Inertia::render('Roles/RolesProfileLayout', $permissions);
})->middleware(['auth:sanctum', 'verified']);

Route::get('roles/assigned/{id}', function ($id) {
    return Inertia::render('Roles/RolesProfileLayout', [
        'id' => $id,
        'is_include'=>true
    ]);
})->middleware(['auth:sanctum', 'verified']);

// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/roles', [RoleController::class, 'index']);
    Route::put('/roles/update/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/delete/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/roles/store', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/info/{id}', [RoleController::class, 'show'])->name('roles.info');
});
Route::patch('/api/assign-permissions/{id}', [RoleController::class, 'assignPermissions'])->name('roles.assign');
Route::patch('/api/revoke-permissions/{id}', [RoleController::class, 'revokePermissions'])->name('roles.revoke');


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
})->middleware(['auth:sanctum', 'verified'])->name('permission');


// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api/permission', [PermissionController::class, 'index']);
    Route::put('/permission/update/{perm}', [PermissionController::class, 'update'])->name('permission.update');
    Route::delete('/permission/delete/{perm}', [PermissionController::class, 'destroy'])->name('permission.destroy');
    Route::post('/permission/store', [PermissionController::class, 'store'])->name('permission.store');
    Route::get('/permission-has-role', [PermissionController::class, 'permission_has_role'])->name('permission.has.role');
    Route::get('/permission/info/{id}', [PermissionController::class, 'show'])->name('permission.info');
});

Route::get('/emr', function (Request $request) {
    $permissions = [
        'canCreate' => $request->user()->can('provider create'),
        'canEdit' => $request->user()->can('provider edit'),
        'canDelete' => $request->user()->can('provider delete'),
        'canView' => $request->user()->can('provider list'),
        'canAssign' => $request->user()->can('provider assign'),
    ];
    return Inertia::render('Emr/Index',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('emr');


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
    Route::get('/api/emr', [RefEmrController::class, 'index']);
    Route::put('/emr/update/{id}', [RefEmrController::class, 'update'])->name('emr.update');
    Route::delete('/emr/delete/{id}', [RefEmrController::class, 'destroy'])->name('emr.destroy');
    Route::post('/emr/store', [RefEmrController::class, 'store'])->name('emr.store');
    Route::get('/emr/info/{id}', [RefEmrController::class, 'show'])->name('emr.info');

    Route::post('/emr/assign', [RefEmrController::class, 'assign'])->name('emr.assign-facility');
    Route::post('/emr/revoke', [RefEmrController::class, 'revoke'])->name('emr.revoke-facility');

    Route::get('/emr/profile_form', function (Request $request) {
      
        return Inertia::render('Emr/ProfileForm');
    })->name('emr/profile_form');

});
Route::get('/emr/list', [RefEmrController::class, 'list'])->name('emr.list');



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

    Route::get('/facilities-list', [RefFacilitiesController::class, 'facility_list'])
        ->name('facility.facility_list');

        Route::post('/facilities/store', [RefFacilitiesController::class, 'store'])
        ->middleware('can:facility create')
        ->name('facility.store');

    Route::get('/api/emr/info/{id}', [RefFacilitiesController::class, 'show'])
        ->middleware('can:facility view')
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

    Route::get('/api/facility_type/info/{id}', [RefFacilitytypeController::class, 'show'])
        ->middleware('can:view facility_type')
        ->name('facility_type.info');
});

// If you want this to be publicly accessible, you can leave it as is.
// Otherwise, wrap in auth and add permission middleware too.
Route::get('/facility_type/list', [RefFacilitytypeController::class, 'list'])
    
    ->name('facility_type.list');



Route::get('/demographic/list', [DemographicController::class, 'list'])->name('demographic.list');
Route::get('/region/list', [DemographicController::class, 'region_list'])->name('demographic.region_list');



Route::get('/incoming', function (Request $request) {

    $permissions = [
        'canCreate' => $request->user()->can('incoming create'),
        'canEdit' => $request->user()->can('incoming edit'),
        'canDelete' => $request->user()->can('incoming delete'),
        'canVie' => $request->user()->can('incoming list'),
    ];

    return Inertia::render('Incoming/Index',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('incoming'); 

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/incoming/list', [ReferralController::class, 'index'])->name('incoming.list');

    Route::get('incoming/profile/{id}', function (Request $request,$id) {
        $permissions = [
            'user' => $request->user()->load('roles'),
            'id' => $id,
            'is_include'=>true
        ];
        return Inertia::render('Incoming/IncomingProfile', $permissions);
    })->middleware(['auth:sanctum', 'verified']);
    
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/referral-information/{LogID}', [ReferralController::class, 'show'])->name('incoming.list');
});

//Patient profile
Route::get('/patient', function (Request $request) {
    $permissions = [
        'canCreate' => $request->user()->can('incoming create'),
        'canEdit' => $request->user()->can('incoming edit'),
        'canDelete' => $request->user()->can('incoming delete'),
        'canVie' => $request->user()->can('incoming list'),
    ];
    return Inertia::render('Incoming/Index',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('patient'); 

Route::middleware([])->group(function () {
    Route::get('/test', [ReferralController::class, 'test'])->name('referral.test');
});

//Clinical
Route::middleware(['auth:sanctum', 'verified'])
    ->get('/referral-clinical/{LogID}', [ReferralClinicalController::class, 'show']);


//Patient


 Route::get('/patient_registry', function (Request $request) {

    $permissions = [
        'canCreate' => $request->user()->can('patient create'),
        'canEdit' => $request->user()->can('patient edit'),
        'canDelete' => $request->user()->can('patient delete'),
        'canVie' => $request->user()->can('patient list'),
    ];

    return Inertia::render('Patient/Index',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('patient_list');

/* Route::get('/patient-list', [ReferralPatientInfoController::class, 'index'])->name('patient_profile.list'); */

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/patient-profile/{LogID}', [ReferralPatientInfoController::class, 'show'])->name('patient_profile.show');
    Route::get('/patient-list', [ReferralPatientInfoController::class, 'index'])->name('patient_profile.list');
    
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
