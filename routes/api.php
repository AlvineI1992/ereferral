<?php

use App\Http\Controllers\Api\References;
use App\Http\Controllers\Api\Referral;
use App\Http\Controllers\Api\ReferralNetworkRecommendationController;
use App\Http\Controllers\BedTrackerController;
use Illuminate\Support\Facades\Route;

Route::prefix('/docs/api')->middleware(['auth:sanctum', 'active.api.user'])->group(function () {
    Route::post('/referral', [Referral::class, '/docs/api'])->middleware('api.permission:legacy referral documentation create');
    Route::post('/reference', [References::class, '/docs/api'])->middleware('api.permission:legacy reference documentation create');
});

Route::get('generate_code/{hfhudcode}', [References::class, 'generate_reference'])->middleware(['auth:sanctum', 'active.api.user', 'abilities:reference:read'])->name('referral.reference')->middleware('api.permission:reference code generate');

// Reference
/* Demographics */
Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom', 'abilities:reference:read'])->group(function () {
    Route::get('/demographics', [References::class, 'demographic_reference'])->name('referral.demographics')->middleware('api.permission:demographics read');
    Route::get('/region/{id}', [References::class, 'region'])->name('referral.region')->middleware('api.permission:region read');
    Route::get('/province/{id}', [References::class, 'province'])->name('referral.province')->middleware('api.permission:province read');
    Route::get('/city/{id}', [References::class, 'city'])->name('referral.city')->middleware('api.permission:city read');
    Route::get('/barangay/{id}', [References::class, 'barangay'])->name('referral.barangay')->middleware('api.permission:barangay read');
});
/* Reason for Referral */
Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom', 'abilities:reference:read'])->group(function () {
    Route::get('/reason-referral', [References::class, 'referral_reason'])->name('referral.reason')->middleware('api.permission:referral reasons list');
    Route::get('/reason-referral-code/{code}', [References::class, 'referral_reason_by_code'])->name('referral.reason.code')->middleware('api.permission:referral reason read');
});

/* Referral type */
Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom', 'abilities:reference:read'])->group(function () {
    Route::get('/referral-type', [References::class, 'referral_type'])->name('referral.type')->middleware('api.permission:referral types list');
    Route::get('/referral-type-code/{code}', [References::class, 'referral_type_code'])->name('referral.type.code')->middleware('api.permission:referral type read');

});

Route::get('facility/{id}', [Referral::class, 'get_facility_list'])->middleware(['auth:sanctum', 'active.api.user', 'abilities:reference:read'])->name('referral.get_facility_list')->middleware('api.permission:facility referrals list');
// Auth
Route::post('login', [Referral::class, 'login'])->middleware('throttle:10,1');

// Transactions
Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/referral-network/recommendations/{hfhudcode}', ReferralNetworkRecommendationController::class)
        ->middleware(['abilities:reference:read'])
        ->name('referral.network.recommendations')->middleware('api.permission:referral network recommendations read');
    Route::post('/refer_patient', [Referral::class, 'patient_referral'])->name('referral.patient_referral')->middleware('api.permission:patient referral create');
    Route::get('/referral-attachments/{attachment}/download', [Referral::class, 'download_attachment'])
        ->middleware(['abilities:referrals:read'])
        ->name('referral.attachments.download')->middleware('api.permission:referral attachment download');
    Route::post('/incoming/fhir', [Referral::class, 'incoming_fhir_referral'])->middleware(['abilities:referrals:write'])->name('referral.incoming_fhir')->middleware('api.permission:FHIR referral create');
    Route::get('/incoming/fhir/{LogID}', [Referral::class, 'fetch_incoming_fhir_referral'])->middleware(['abilities:referrals:read'])->name('referral.incoming_fhir.show')->middleware('api.permission:FHIR referral read');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-referral-information/{LogID}', [Referral::class, 'getReferralData'])->middleware(['abilities:referrals:read'])->name('referral.referral_information')->middleware('api.permission:referral information read');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-referral-list/{fhudcode}', [Referral::class, 'get_referral_list'])->middleware(['abilities:referrals:read'])->name('referral.get_referral_list')->middleware('api.permission:referral list read');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::post('/received', [Referral::class, 'received'])->middleware(['abilities:referrals:write'])->name('referral.received')->middleware(['api.permission:referral receive', \App\Http\Middleware\EnsureReferralNotCancelled::class]);
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::post('/admit', [Referral::class, 'admit'])->middleware(['abilities:referrals:write'])->name('admit')->middleware(['api.permission:referral admit', \App\Http\Middleware\EnsureReferralNotCancelled::class]);
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-discharged-data/{LogID}', [Referral::class, 'get_discharged_data'])->middleware(['abilities:referrals:read'])->name('get_discharged_data')->middleware('api.permission:discharge data read');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-accredited-facilities', [Referral::class, 'get_accredited_facilities'])->middleware(['abilities:reference:read'])->name('get_accredited_facilities')->middleware('api.permission:accredited facilities list');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/blood-types', [Referral::class, 'getBloodtype'])->middleware(['abilities:reference:read'])->name('getBloodtype')->middleware('api.permission:blood types list');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/religions', [Referral::class, 'getReligion'])->middleware(['abilities:reference:read'])->name('getBloodtype')->middleware('api.permission:religions list');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/bed-trackers', [BedTrackerController::class, 'index'])->middleware(['abilities:beds:read'])->name('bed_trackers.index')->middleware('api.permission:bed trackers list');
    Route::get('/bed-trackers/facilities', [BedTrackerController::class, 'facilityOptions'])->middleware(['abilities:beds:read'])->name('bed_trackers.facilities')->middleware('api.permission:bed tracker facilities list');
    Route::get('/bed-trackers/{id}', [BedTrackerController::class, 'show'])->middleware(['abilities:beds:read'])->name('bed_trackers.show')->middleware('api.permission:bed tracker read');
    Route::post('/bed-trackers', [BedTrackerController::class, 'store'])->middleware(['abilities:beds:write'])->name('bed_trackers.store')->middleware('api.permission:bed trackers create');
    Route::put('/bed-trackers/{id}', [BedTrackerController::class, 'update'])->middleware(['abilities:beds:write'])->name('bed_trackers.update')->middleware('api.permission:bed tracker update');
    Route::delete('/bed-trackers/{id}', [BedTrackerController::class, 'destroy'])->middleware(['abilities:beds:write'])->name('bed_trackers.destroy')->middleware('api.permission:bed tracker delete');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/referral-status', [Referral::class, 'getReferralStatus'])->middleware(['abilities:referrals:read'])->name('get_referral_status')->middleware('api.permission:referral statuses list');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::post('/referral-status/update', [Referral::class, 'saveReferralStatus'])->middleware(['abilities:referrals:write'])->name('get_saved_referral_status')->middleware('api.permission:referral status update');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/referral-status/patient', [Referral::class, 'getAllPatientstatus'])->middleware(['abilities:referrals:read'])->name('get_patient_referral_status')->middleware('api.permission:patient referral statuses list');
});

Route::post('/cancel-referral', \App\Http\Controllers\Api\CancelReferralController::class)
    ->middleware(['auth:sanctum', 'active.api.user', 'abilities:referrals:write', 'api.permission:referral cancel'])
    ->name('referral.cancel');

Route::get('/referrals/journey', [\App\Http\Controllers\ReferralPathwayController::class, 'show'])
    ->middleware(['auth:sanctum', 'active.api.user', 'abilities:referrals:read', 'api.permission:referral journey read'])->name('referral.journey');
Route::post('/referrals/forward', [\App\Http\Controllers\ReferralPathwayController::class, 'store'])
    ->middleware(['auth:sanctum', 'active.api.user', 'abilities:referrals:write', 'api.permission:referral forward'])->name('referral.forward');
