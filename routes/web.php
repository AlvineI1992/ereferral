<?php

use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BedTrackerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataEncryptionController;
use App\Http\Controllers\DemographicController;
use App\Http\Controllers\FacilityHierarchyController;
use App\Http\Controllers\PatientMasterController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RefEmrController;
use App\Http\Controllers\ReferralClinicalController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReferralFacilityReportController;
use App\Http\Controllers\ReferralPatientInfoController;
use App\Http\Controllers\RefFacilitiesController;
use App\Http\Controllers\RefFacilitytypeController;
use App\Http\Controllers\RefReligionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('auth/login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::get('/users', function (Request $request) {
    $permissions = [
        'canCreate' => $request->user()->can('user create'),
        'canEdit' => $request->user()->can('user edit'),
        'canDelete' => $request->user()->can('user delete'),
        'canView' => $request->user()->can('user list'),
        'canAssign' => $request->user()->can('user assign'),
    ];

    return Inertia::render('users/Index', $permissions);
})->middleware(['auth', 'verified', 'can:user list'])->name('user.index');

Route::get('/users/create', function () {
    return Inertia::render('users/usersForm');
})->middleware(['auth', 'verified'])->name('users.create');

Route::get('/users/assign-roles/{id}', function (Request $request, $id) {
    $permissions = [
        'user' => $request->user()->load('roles'),
        'id' => $id,
        'is_include' => true,
    ];

    return Inertia::render('users/UserProfileLayout', $permissions);
})->middleware(['auth:sanctum', 'verified', 'can:user assign']);

Route::get('/users/assigned-roles/{id}', function ($id) {
    return Inertia::render('users/UserProfileLayout', [
        'id' => $id,
        'is_include' => false,
    ]);
})->middleware(['auth:sanctum', 'verified', 'can:user assign']);

// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/{user}/emr-credential', [\App\Http\Controllers\EmrCredentialController::class, 'store'])
        ->middleware(['can:user edit', 'throttle:10,1'])->name('users.emr-credential.store');
    Route::get('/users/{user}/emr-credential', [\App\Http\Controllers\EmrCredentialController::class, 'show'])
        ->middleware('can:user edit')->name('users.emr-credential.show');
    Route::delete('/users/{user}/emr-credential', [\App\Http\Controllers\EmrCredentialController::class, 'destroy'])
        ->middleware('can:user edit')->name('users.emr-credential.destroy');
    Route::get('/users/list', [RegisteredUserController::class, 'index'])
        ->middleware('can:user list')
        ->name('user.list');
    Route::put('/users/update/{user}', [RegisteredUserController::class, 'update'])
        ->middleware('can:user edit')
        ->name('user.update');
    Route::delete('/users/delete/{user}', [RegisteredUserController::class, 'destroy'])
        ->middleware('can:user delete')
        ->name('user.destroy');
    Route::post('/users/store', [RegisteredUserController::class, 'store'])
        ->middleware('can:user create')
        ->name('user.store');
    Route::get('/users/sample', [RegisteredUserController::class, 'sample'])->name('user.sample');
    Route::get('/users/info/{id}', [RegisteredUserController::class, 'show'])->name('user.info');
    Route::get('/user-has-role', [RegisteredUserController::class, 'role_has_user'])
        ->middleware('can:user assign')
        ->name('user.has.role');
});

Route::patch('/users/assign-roles/{id}', [RegisteredUserController::class, 'assignRolesToUser'])
    ->middleware(['auth:sanctum', 'can:user assign'])
    ->name('user.assign');
Route::patch('/users/revoke-roles/{id}', [RegisteredUserController::class, 'revokeRolesFromUser'])
    ->middleware(['auth:sanctum', 'can:user assign'])
    ->name('user.revoke');

// Inertia Page Route (Web, uses session-based auth)
Route::get('/roles', function (Request $request) {
    $permissions = [
        'canCreateRole' => $request->user()->can('role create'),
        'canEditRole' => $request->user()->can('role edit'),
        'canDeleteRole' => $request->user()->can('role delete'),
        'canViewRole' => $request->user()->can('role list'),
        'canAssignRole' => $request->user()->can('role assign'),
    ];

    return Inertia::render('roles/Index', $permissions);
})->middleware(['auth:sanctum', 'verified', 'can:role list'])->name('roles.index');

Route::get('roles/assign/{id}', function (Request $request, $id) {
    $permissions = [
        'user' => $request->user()->load('roles'),
        'id' => $id,
        'is_include' => true,
    ];

    return Inertia::render('roles/RolesProfileLayout', $permissions);
})->middleware(['auth:sanctum', 'verified', 'can:role assign']);

Route::get('roles/assigned/{id}', function ($id) {
    return Inertia::render('roles/RolesProfileLayout', [
        'id' => $id,
        'is_include' => true,
    ]);
})->middleware(['auth:sanctum', 'verified', 'can:role assign']);

// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/roles/list', [RoleController::class, 'index'])->middleware('can:role list');
    Route::put('/roles/update/{role}', [RoleController::class, 'update'])->middleware('can:role edit')->name('roles.update');
    Route::delete('/roles/delete/{role}', [RoleController::class, 'destroy'])->middleware('can:role delete')->name('roles.destroy');
    Route::post('/roles/store', [RoleController::class, 'store'])->middleware('can:role create')->name('roles.store');
    Route::get('/roles/info/{id}', [RoleController::class, 'show'])->middleware('can:role list')->name('roles.info');
});

Route::patch('/assign-permissions/{id}', [RoleController::class, 'assignPermissions'])
    ->middleware(['auth:sanctum', 'can:role assign'])
    ->name('roles.assign');
Route::patch('/revoke-permissions/{id}', [RoleController::class, 'revokePermissions'])
    ->middleware(['auth:sanctum', 'can:role assign'])
    ->name('roles.revoke');

// Inertia Page Route (Web, uses session-based auth)
Route::get('/permission', function (Request $request) {
    // Check permissions for the authenticated user
    $permissions = [
        'canCreatePermission' => $request->user()->can('permission create'),
        'canEditPermission' => $request->user()->can('permission edit'),
        'canDeletePermission' => $request->user()->can('permission delete'),
        'canViewPermission' => $request->user()->can('permission list'),
    ];

    return Inertia::render('Permission/Index', $permissions);
})->middleware(['auth:sanctum', 'verified', 'can:permission list'])->name('permission.index');

// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/permission/list', [PermissionController::class, 'index'])->middleware('can:permission list');
    Route::put('/permission/update/{perm}', [PermissionController::class, 'update'])->middleware('can:permission edit')->name('permission.update');
    // Route::delete('/permission/delete/{permission}', [PermissionController::class, 'destroy'])->name('permission.destroy');
    Route::delete('/permission/delete/{perm}', [PermissionController::class, 'destroy'])->middleware('can:permission delete')->name('permission.destroy');
    Route::post('/permission/store', [PermissionController::class, 'store'])->middleware('can:permission create')->name('permission.store');
    Route::get('/permission-has-role', [RolePermissionController::class, 'index'])
        ->middleware('can:role assign')
        ->name('permission.has.role');
    Route::get('/permission/info/{id}', [PermissionController::class, 'show'])->middleware('can:permission list')->name('permission.info');
});

Route::get('/provider-setup', fn () => Inertia::render('Emr/SetupWizard'))
    ->middleware(['auth:sanctum', 'verified', 'can:provider create', 'can:user create', 'can:user assign', 'can:user edit'])
    ->name('provider.setup');

Route::get('emr', function (Request $request) {
    $permissions = [
        'canSetup' => $request->user()->can('provider create') && $request->user()->can('user create') && $request->user()->can('user assign') && $request->user()->can('user edit'),
        'canCreate' => $request->user()->can('provider create'),
        'canEdit' => $request->user()->can('provider edit'),
        'canDelete' => $request->user()->can('provider delete'),
        'canView' => $request->user()->can('provider list'),
        'canAssign' => $request->user()->can('provider assign')];

    return Inertia::render('Emr/Index', $permissions);
})->middleware(['auth:sanctum', 'verified'])->name('emr.index');

Route::get('emr/profile/{id}', function (Request $request, $id) {
    $permissions = [
        'user' => $request->user()->load('roles'),
        'id' => $id,
        'is_include' => true,
        'canAssign' => $request->user()->can('provider assign'),
        'canView' => $request->user()->can('provider list'),
    ];

    return Inertia::render('Emr/ProfileLayout', $permissions);
})->middleware(['auth:sanctum', 'verified'])->name('emr.profile');

// API Routes (Sanctum-protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/emr/list', [RefEmrController::class, 'index'])
        ->middleware('can:provider list')
        ->name('emr.list');
    Route::post('/emr/store', [RefEmrController::class, 'store'])
        ->middleware('can:provider create')
        ->name('emr.store');
    Route::put('/emr/update/{id}', [RefEmrController::class, 'update'])
        ->middleware('can:provider edit')
        ->name('emr.update');
    Route::delete('/emr/delete/{id}', [RefEmrController::class, 'destroy'])
        ->middleware('can:provider delete')
        ->name('emr.destroy');
    Route::get('/emr/info/{id}', [RefEmrController::class, 'show'])
        ->middleware('can:provider list')
        ->name('emr.info');

    Route::post('/emr/assign', [RefEmrController::class, 'assign'])
        ->middleware('can:provider assign')
        ->name('emr.assign-facility');
    Route::post('/emr/revoke', [RefEmrController::class, 'revoke'])
        ->middleware('can:provider assign')
        ->name('emr.revoke-facility');

    Route::get('/emr/profile_form', function (Request $request) {

        return Inertia::render('Emr/ProfileForm');
    })->name('emr/profile_form');

});
/* Route::get('/emr/list', [RefEmrController::class, 'list'])->name('emr.list');
 */

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
    return Inertia::render('Ref_Facilities/Index', [...$permissions, 'googleMaps' => config('services.google_maps')]);
})
    ->middleware(['auth:sanctum', 'verified', 'can:facility list'])
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

    Route::get('/facilities/info/{id}', [RefFacilitiesController::class, 'show'])
/*         ->middleware('can:facility view') */
        ->name('facility.info');

    Route::get('/facilities/profile_form', function () {
        return Inertia::render('Emr/ProfileForm');
    })->middleware('can:facility list')->name('facilities/profile_form');

});

Route::middleware(['auth:sanctum', 'verified', 'can:referral report list'])
    ->prefix('reports/referrals-by-facility')
    ->group(function () {
        Route::get('/', [ReferralFacilityReportController::class, 'index'])->name('reports.referrals-by-facility.index');
        Route::get('/data', [ReferralFacilityReportController::class, 'data'])->name('reports.referrals-by-facility.data');
        Route::get('/patients', [ReferralFacilityReportController::class, 'patients'])->name('reports.referrals-by-facility.patients');
        Route::get('/csv', [ReferralFacilityReportController::class, 'csv'])->name('reports.referrals-by-facility.csv');
    });
Route::middleware(['auth:sanctum', 'verified', 'can:diagnosis heatmap list'])->prefix('reports/diagnosis-heatmap')->group(function () {
    Route::get('/', [\App\Http\Controllers\DiagnosisHeatmapController::class, 'index'])->name('reports.diagnosis-heatmap.index');
    Route::get('/data', [\App\Http\Controllers\DiagnosisHeatmapController::class, 'data'])->name('reports.diagnosis-heatmap.data');
});
/* Route::get('/api/facilities', [RefFacilitiesController::class, 'index']); */
Route::middleware(['auth:sanctum', 'can:facility list'])->get('/facility/list', [RefFacilitiesController::class, 'index'])->name('facility.list');

Route::middleware(['auth:sanctum', 'verified', 'can:facility hierarchy list'])
    ->prefix('facility-hierarchy')
    ->group(function () {
        Route::get('/', [FacilityHierarchyController::class, 'index'])->name('facility-hierarchy.index');
        Route::get('/data', [FacilityHierarchyController::class, 'data'])->name('facility-hierarchy.data');
        Route::get('/options', [FacilityHierarchyController::class, 'options'])->name('facility-hierarchy.options');
        Route::post('/bulk', [FacilityHierarchyController::class, 'bulkStore'])->middleware('can:facility hierarchy create')->name('facility-hierarchy.bulk-store');
        Route::post('/', [FacilityHierarchyController::class, 'store'])->middleware('can:facility hierarchy create')->name('facility-hierarchy.store');
        Route::put('/{facilityHierarchy}', [FacilityHierarchyController::class, 'update'])->middleware('can:facility hierarchy edit')->name('facility-hierarchy.update');
        Route::delete('/{facilityHierarchy}', [FacilityHierarchyController::class, 'destroy'])->middleware('can:facility hierarchy delete')->name('facility-hierarchy.destroy');
    });
// API Routes (Sanctum-protected)

/* Route::get('/facility_type', function () {
    return Inertia::render('Ref_Facilities/Index');
})->middleware(['auth:sanctum', 'verified'])->name('facilities'); */
Route::middleware('auth:sanctum')->group(function () {

    Route::put('/facility_type/update/{id}', [RefFacilitytypeController::class, 'update'])
        ->middleware('can:facility edit')
        ->name('facility_type.update');

    Route::delete('/facility_type/delete/{id}', [RefFacilitytypeController::class, 'destroy'])
        ->middleware('can:facility delete')
        ->name('facility_type.destroy');

    Route::post('/facility_type/store', [RefFacilitytypeController::class, 'store'])
        ->middleware('can:facility create')
        ->name('facility_type.store');

    Route::get('/facility_type/info/{id}', [RefFacilitytypeController::class, 'show'])
        ->middleware('can:view facility_type')
        ->name('facility_type.info');
});

// If you want this to be publicly accessible, you can leave it as is.
// Otherwise, wrap in auth and add permission middleware too.
Route::get('/facility_type/list', [RefFacilitytypeController::class, 'list'])

    ->name('facility_type.list');

Route::get('/religions', function () {
    return Inertia::render('Religion/Index');
})->middleware(['auth:sanctum', 'verified', 'can:demographic list'])->name('religion.index');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/religions/list', [RefReligionController::class, 'index'])
        ->middleware('can:demographic list')
        ->name('religion.list');
    Route::get('/religions/info/{id}', [RefReligionController::class, 'show'])
        ->middleware('can:demographic list')
        ->name('religion.info');
    Route::post('/religions/store', [RefReligionController::class, 'store'])
        ->middleware('can:demographic create')
        ->name('religion.store');
    Route::put('/religions/update/{id}', [RefReligionController::class, 'update'])
        ->middleware('can:demographic edit')
        ->name('religion.update');
    Route::delete('/religions/delete/{id}', [RefReligionController::class, 'destroy'])
        ->middleware('can:demographic delete')
        ->name('religion.destroy');
});

Route::get('/demographics', function () {
    return Inertia::render('Demographics/Index');
})->middleware(['auth:sanctum', 'verified', 'can:demographic list'])->name('demographics.index');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/demographics/list', [DemographicController::class, 'index'])
        ->middleware('can:demographic list')
        ->name('demographics.list');
    Route::get('/demographics/options/{level}', [DemographicController::class, 'options'])
        ->middleware('can:demographic list')
        ->name('demographics.options');
    Route::get('/demographics/info/{level}/{id}', [DemographicController::class, 'show'])
        ->middleware('can:demographic list')
        ->name('demographics.info');
    Route::post('/demographics/store/{level}', [DemographicController::class, 'store'])
        ->middleware('can:demographic create')
        ->name('demographics.store');
    Route::put('/demographics/update/{level}/{id}', [DemographicController::class, 'update'])
        ->middleware('can:demographic edit')
        ->name('demographics.update');
    Route::delete('/demographics/delete/{level}/{id}', [DemographicController::class, 'destroy'])
        ->middleware('can:demographic delete')
        ->name('demographics.destroy');
});

Route::get('/demographic/list', [DemographicController::class, 'list'])->name('demographic.list');
Route::get('/region/list', [DemographicController::class, 'region_list'])->name('demographic.region_list');

Route::get('/incoming', function (Request $request) {

    $permissions = [
        'canCreate' => $request->user()->can('incoming create'),
        'canJourney' => $request->user()->can('incoming journey'),
            'canEdit' => $request->user()->can('incoming edit'),
        'canCancel' => $request->user()->can('incoming cancel'),
        'canDelete' => $request->user()->can('incoming delete'),
        'canView' => $request->user()->can('incoming list'),
    ];

    return Inertia::render('Incoming/Index', $permissions);
})->middleware(['auth:sanctum', 'verified', 'can:incoming list'])->name('incoming.index');

Route::get('/bed_tracker', function (Request $request) {
    $permissions = [
        'canCreate' => $request->user()->can('beds create'),
        'canEdit' => $request->user()->can('beds edit'),
        'canDelete' => $request->user()->can('beds delete'),
        'canView' => $request->user()->can('beds list'),
    ];

    return Inertia::render('BedTracker/Index', $permissions);
})->middleware(['auth:sanctum', 'verified', 'can:beds list'])->name('bed_tracker.index');

Route::get('/referrals/create', function (Request $request) {

    $permissions = [
        'canCreate' => $request->user()->can('incoming create'),
        'canJourney' => $request->user()->can('incoming journey'),
            'canEdit' => $request->user()->can('incoming edit'),
        'canCancel' => $request->user()->can('incoming cancel'),
        'canDelete' => $request->user()->can('incoming delete'),
        'canView' => $request->user()->can('incoming list'),
    ];

    return Inertia::render('Incoming/Form', $permissions);
})->middleware(['auth:sanctum', 'verified', 'can:incoming create'])->name('create.referral');

Route::get('/referrals/edit/{id}', function (Request $request, $id) {

    $permissions = [
        'canCreate' => $request->user()->can('incoming create'),
        'canJourney' => $request->user()->can('incoming journey'),
            'canEdit' => $request->user()->can('incoming edit'),
        'canCancel' => $request->user()->can('incoming cancel'),
        'canDelete' => $request->user()->can('incoming delete'),
        'canView' => $request->user()->can('incoming list'),
        'mode' => 'edit',
        'id' => $id,
    ];

    return Inertia::render('Incoming/Form', $permissions);
})->middleware(['auth:sanctum', 'verified', 'can:incoming edit'])->name('incoming.referral.edit-page');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/incoming/list', [ReferralController::class, 'index'])
        ->middleware('can:incoming list')
        ->name('incoming.list');
    Route::get('/incoming/dashboard', [ReferralController::class, 'dashboard'])
        ->middleware('can:incoming list')
        ->name('incoming.dashboard');
    Route::post('/referrals/store', [ReferralController::class, 'store'])
        ->middleware('can:incoming create')
        ->name('referral.store');
    Route::get('/referrals/edit-data/{LogID}', [ReferralController::class, 'edit'])
        ->middleware('can:incoming edit')
        ->name('incoming.referral.edit-data');
    Route::put('/referrals/update/{LogID}', [ReferralController::class, 'update'])
        ->middleware('can:incoming edit')
        ->name('incoming.referral.update');
    Route::delete('/referrals/{LogID}', [ReferralController::class, 'destroy'])
        ->middleware('can:incoming delete')
        ->name('incoming.referral.destroy');

    Route::get('incoming/profile/{id}', function (Request $request, $id) {
        $permissions = [
            'user' => $request->user()->load('roles'),
            'id' => $id,
            'is_include' => true,
            'canJourney' => $request->user()->can('incoming journey'),
            'canEdit' => $request->user()->can('incoming edit'),
        ];

        return Inertia::render('Incoming/IncomingProfile', $permissions);
    })->middleware(['auth:sanctum', 'verified', 'can:incoming list']);

});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/referral-information/{LogID}', [ReferralController::class, 'show'])
        ->middleware('can:incoming list')
        ->name('incoming.show');
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/bed-tracker/list', [BedTrackerController::class, 'index'])
        ->middleware('can:beds list')
        ->name('bed_tracker.list');
    Route::get('/bed-tracker/info/{id}', [BedTrackerController::class, 'show'])
        ->middleware('can:beds list')
        ->name('bed_tracker.info');
    Route::get('/bed-tracker/facilities', [BedTrackerController::class, 'facilityOptions'])
        ->middleware('can:beds list')
        ->name('bed_tracker.facilities');
    Route::post('/bed-tracker/store', [BedTrackerController::class, 'store'])
        ->middleware('can:beds create')
        ->name('bed_tracker.store');
    Route::put('/bed-tracker/update/{id}', [BedTrackerController::class, 'update'])
        ->middleware('can:beds edit')
        ->name('bed_tracker.update');
    Route::delete('/bed-tracker/delete/{id}', [BedTrackerController::class, 'destroy'])
        ->middleware('can:beds delete')
        ->name('bed_tracker.destroy');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/referrals/generate-code/{hfhudcode}', [ReferralController::class, 'generate_hfhudcode'])->name('generate.hfhudcode');
});

// Patient profile
Route::get('/patient', function (Request $request) {
    $permissions = [
        'canCreate' => $request->user()->can('incoming create'),
        'canJourney' => $request->user()->can('incoming journey'),
            'canEdit' => $request->user()->can('incoming edit'),
        'canCancel' => $request->user()->can('incoming cancel'),
        'canDelete' => $request->user()->can('incoming delete'),
        'canVie' => $request->user()->can('incoming list'),
    ];

    return Inertia::render('Incoming/Index', $permissions);
})->middleware(['auth:sanctum', 'verified'])->name('patient');

// Clinical
Route::middleware(['auth:sanctum', 'verified'])
    ->get('/referral-clinical/{LogID}', [ReferralClinicalController::class, 'show']);

// Patient

Route::get('/patient_registry', function (Request $request) {

    $permissions = [
        'canCreate' => $request->user()->can('patient create'),
        'canEdit' => $request->user()->can('patient edit'),
        'canDelete' => $request->user()->can('patient delete'),
        'canView' => $request->user()->can('patient list'),
    ];

    return Inertia::render('Patient/Index', $permissions);
})->middleware(['auth:sanctum', 'verified'])->name('patient_list');

/* Route::get('/patient-list', [ReferralPatientInfoController::class, 'index'])->name('patient_profile.list'); */

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/patient-profile/{LogID}', [ReferralPatientInfoController::class, 'show'])->name('patient_profile.show');
    Route::get('/patient-registry/list', [PatientMasterController::class, 'index'])
        ->middleware('can:patient list')
        ->name('patient_registry.list');
    Route::get('/patient-registry/info/{id}', [PatientMasterController::class, 'show'])
        ->middleware('can:patient list')
        ->name('patient_registry.info');
    Route::post('/patient-registry/store', [PatientMasterController::class, 'store'])
        ->middleware('can:patient create')
        ->name('patient_registry.store');
    Route::put('/patient-registry/update/{id}', [PatientMasterController::class, 'update'])
        ->middleware('can:patient edit')
        ->name('patient_registry.update');
    Route::delete('/patient-registry/delete/{id}', [PatientMasterController::class, 'destroy'])
        ->middleware('can:patient delete')
        ->name('patient_registry.destroy');

});

Route::middleware(['auth:sanctum', 'verified'])->prefix('admin/data-encryption')->group(function () {
    Route::get('/', [DataEncryptionController::class, 'index'])->name('admin.data-encryption.index');
    Route::get('/status', [DataEncryptionController::class, 'status'])->name('admin.data-encryption.status');
    Route::put('/', [DataEncryptionController::class, 'update'])->name('admin.data-encryption.update');
});

Route::get('/admin/audit-trail', [AuditTrailController::class, 'index'])
    ->middleware(['auth:sanctum', 'verified'])
    ->name('admin.audit-trail.index');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->prefix('admin/database-maintenance')->group(function () {
    Route::get('/', [\App\Http\Controllers\DatabaseMaintenanceController::class, 'index'])->name('admin.database-maintenance.index');
    Route::get('/status', [\App\Http\Controllers\DatabaseMaintenanceController::class, 'status'])->name('admin.database-maintenance.status');
    Route::post('/', [\App\Http\Controllers\DatabaseMaintenanceController::class, 'store'])->middleware('throttle:6,1')->name('admin.database-maintenance.store');
});

Route::post('/referrals/cancel', \App\Http\Controllers\Api\CancelReferralController::class)
    ->middleware(['auth', 'verified', 'can:incoming cancel'])
    ->name('incoming.cancel');

Route::middleware(['auth', 'verified'])->prefix('referrals/pathway')->group(function () {
    Route::get('/view', [\App\Http\Controllers\ReferralPathwayController::class, 'index'])->middleware('can:incoming journey')->name('incoming.pathway');
    Route::get('/', [\App\Http\Controllers\ReferralPathwayController::class, 'show'])->middleware('can:incoming journey');
    Route::post('/', [\App\Http\Controllers\ReferralPathwayController::class, 'store'])->middleware('can:incoming forward');
    Route::get('/options', [\App\Http\Controllers\ReferralPathwayController::class, 'options'])->middleware('can:incoming forward');
});
