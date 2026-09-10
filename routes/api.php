<?php

use App\Http\Controllers\Api\References;
use App\Http\Controllers\Api\Referral;
use App\Http\Controllers\Api\ReferralNetworkRecommendationController;
use App\Http\Controllers\BedTrackerController;
use Illuminate\Support\Facades\Route;

Route::prefix('/docs/api')->group(function () {
    Route::post('/referral', [Referral::class, '/docs/api']);
    Route::post('/reference', [References::class, '/docs/api']);
});

Route::get('generate_code/{hfhudcode}', [References::class, 'generate_reference'])->middleware(['auth:sanctum', 'active.api.user', 'abilities:reference:read'])->name('referral.reference');

// Reference
/* Demographics */
Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom', 'abilities:reference:read'])->group(function () {
    Route::get('/demographics', [References::class, 'demographic_reference'])->name('referral.demographics');
    Route::get('/region/{id}', [References::class, 'region'])->name('referral.region');
    Route::get('/province/{id}', [References::class, 'province'])->name('referral.province');
    Route::get('/city/{id}', [References::class, 'city'])->name('referral.city');
    Route::get('/barangay/{id}', [References::class, 'barangay'])->name('referral.barangay');
});
/* Reason for Referral */
Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom', 'abilities:reference:read'])->group(function () {
    Route::get('/reason-referral', [References::class, 'referral_reason'])->name('referral.reason');
    Route::get('/reason-referral-code/{code}', [References::class, 'referral_reason_by_code'])->name('referral.reason.code');
});

/* Referral type */
Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom', 'abilities:reference:read'])->group(function () {
    Route::get('/referral-type', [References::class, 'referral_type'])->name('referral.type');
    Route::get('/referral-type-code/{code}', [References::class, 'referral_type_code'])->name('referral.type.code');

});

Route::get('facility/{id}', [Referral::class, 'get_facility_list'])->middleware(['auth:sanctum', 'active.api.user', 'abilities:reference:read'])->name('referral.get_facility_list');
// Auth
Route::post('login', [Referral::class, 'login'])->middleware('throttle:10,1');

// Transactions
Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/referral-network/recommendations/{hfhudcode}', ReferralNetworkRecommendationController::class)
        ->middleware('abilities:reference:read')
        ->name('referral.network.recommendations');
    Route::post('/refer_patient', [Referral::class, 'patient_referral'])->middleware('abilities:referrals:write')->name('referral.patient_referral');
    Route::get('/referral-attachments/{attachment}/download', [Referral::class, 'download_attachment'])
        ->middleware('abilities:referrals:read')
        ->name('referral.attachments.download');
    Route::post('/incoming/fhir', [Referral::class, 'incoming_fhir_referral'])->middleware('abilities:referrals:write')->name('referral.incoming_fhir');
    Route::get('/incoming/fhir/{LogID}', [Referral::class, 'fetch_incoming_fhir_referral'])->middleware('abilities:referrals:read')->name('referral.incoming_fhir.show');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-referral-information/{LogID}', [Referral::class, 'getReferralData'])->middleware('abilities:referrals:read')->name('referral.referral_information');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-referral-list/{fhudcode}/{emr_id}', [Referral::class, 'get_referral_list'])->middleware('abilities:referrals:read')->name('referral.get_referral_list');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::post('/received', [Referral::class, 'received'])->middleware('abilities:referrals:write')->name('referral.received');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::post('/admit', [Referral::class, 'admit'])->middleware('abilities:referrals:write')->name('admit');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-discharged-data/{LogID}', [Referral::class, 'get_discharged_data'])->middleware('abilities:referrals:read')->name('get_discharged_data');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-accredited-facilities', [Referral::class, 'get_accredited_facilities'])->middleware('abilities:reference:read')->name('get_accredited_facilities');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/blood-types', [Referral::class, 'getBloodtype'])->middleware('abilities:reference:read')->name('getBloodtype');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/religions', [Referral::class, 'getReligion'])->middleware('abilities:reference:read')->name('getBloodtype');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/bed-trackers', [BedTrackerController::class, 'index'])->middleware('abilities:beds:read')->name('bed_trackers.index');
    Route::get('/bed-trackers/facilities', [BedTrackerController::class, 'facilityOptions'])->middleware('abilities:beds:read')->name('bed_trackers.facilities');
    Route::get('/bed-trackers/{id}', [BedTrackerController::class, 'show'])->middleware('abilities:beds:read')->name('bed_trackers.show');
    Route::post('/bed-trackers', [BedTrackerController::class, 'store'])->middleware('abilities:beds:write')->name('bed_trackers.store');
    Route::put('/bed-trackers/{id}', [BedTrackerController::class, 'update'])->middleware('abilities:beds:write')->name('bed_trackers.update');
    Route::delete('/bed-trackers/{id}', [BedTrackerController::class, 'destroy'])->middleware('abilities:beds:write')->name('bed_trackers.destroy');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/referral-status', [Referral::class, 'getReferralStatus'])->middleware('abilities:referrals:read')->name('get_referral_status');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::post('/referral-status/update', [Referral::class, 'saveReferralStatus'])->middleware('abilities:referrals:write')->name('get_saved_referral_status');
});

Route::middleware(['auth:sanctum', 'active.api.user', 'auth.sanctum.custom'])->group(function () {
    Route::get('/referral-status/patient', [Referral::class, 'getAllPatientstatus'])->middleware('abilities:referrals:read')->name('get_patient_referral_status');
});
