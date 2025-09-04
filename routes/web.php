<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;



use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\RefEmrController;
use App\Http\Controllers\RefFacilitiesController;
use App\Http\Controllers\RefFacilitytypeController;
use App\Http\Controllers\DemographicController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReferralPatientInfoController;
use App\Http\Controllers\RoleController;
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
})->middleware(['auth', 'verified'])->name('/users');


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
    Route::get('/users/list', [RegisteredUserController::class, 'index'])->name('user.list');
    Route::put('/users/update/{role}', [RegisteredUserController::class, 'update'])->name('user.update');
    Route::delete('/users/delete/{role}', [RegisteredUserController::class, 'destroy'])->name('user.destroy');
    Route::post('/users/store', [RegisteredUserController::class, 'store'])->name('user.store');
    Route::get('/users/sample', [RegisteredUserController::class, 'sample'])->name('user.sample');
    Route::get('/users/info/{id}', [RegisteredUserController::class, 'show'])->name('user.info');
    Route::get('/user-has-role', [RegisteredUserController::class, 'role_has_user'])->name('user.has.role');
});

Route::patch('/users/assign-roles/{id}', [RegisteredUserController::class, 'assignRolesToUser'])->name('user.assign');
Route::patch('/users/revoke-roles/{id}', [RegisteredUserController::class, 'revokeRolesFromUser'])->name('user.revoke');

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

Route::patch('/assign-permissions/{id}', [RoleController::class, 'assignPermissions'])->name('roles.assign');
Route::patch('/revoke-permissions/{id}', [RoleController::class, 'revokePermissions'])->name('roles.revoke');



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

    Route::get('/emr/info/{id}', [RefFacilitiesController::class, 'show'])
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

    Route::get('/facility_type/info/{id}', [RefFacilitytypeController::class, 'show'])
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
        'canView' => $request->user()->can('incoming list'),
    ];

    return Inertia::render('Incoming/Index',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('incoming'); 


Route::get('/referrals/create', function (Request $request) {

    $permissions = [
        'canCreate' => $request->user()->can('incoming create'),
        'canEdit' => $request->user()->can('incoming edit'),
        'canDelete' => $request->user()->can('incoming delete'),
        'canVie' => $request->user()->can('incoming list'),
    ];

    return Inertia::render('Incoming/Form',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('create.referral'); 

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/incoming/list', [ReferralController::class, 'index'])->name('incoming.list');
    Route::post('store', [ReferralController::class, 'store'])->name('referral.store');

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
    Route::get('/referral-information/{LogID}', [ReferralController::class, 'show'])->name('incoming.show');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/generate-code/{hfhudcode}', [ReferralController::class, 'generate_hfhudcode'])->name('generate.hfhudcode');
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
        'canView' => $request->user()->can('patient list'),
    ];

    return Inertia::render('Patient/Index',$permissions);
})->middleware(['auth:sanctum', 'verified'])->name('patient_list');


Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/patient-profile/{LogID}', [ReferralPatientInfoController::class, 'show'])->name('patient_profile.show');
    Route::get('/patient-list', [ReferralPatientInfoController::class, 'index'])->name('patient_profile.list');
    
});



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/role.php';
require __DIR__.'/permission.php';
require __DIR__.'/provider.php';
