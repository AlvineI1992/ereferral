<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Referral;
use App\Http\Controllers\Auth\AuthenticatedSessionController;



Route::get('generate_code/{hfhudcode}', [Referral::class, 'generate_reference'])->middleware('auth:sanctum')->name('referral.reference');

//Reference
/* Demographics */
Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::get('/demographics', [Referral::class, 'demographic_reference'])->name('referral.demographics');
    Route::get('/region/{id}', [Referral::class, 'region'])->name('referral.region');
    Route::get('/province/{id}', [Referral::class, 'province'])->name('referral.province');
    Route::get('/city/{id}', [Referral::class, 'city'])->name('referral.city');
    Route::get('/barangay/{id}', [Referral::class, 'barangay'])->name('referral.barangay');
});
/* Reason for Referral */
Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::get('/reason-referral', [Referral::class, 'referral_reason'])->name('referral.reason');
    Route::get('/reason-referral-code/{code}', [Referral::class, 'referral_reason_by_code'])->name('referral.reason');
   // Route::get('/region/{id}', [Referral::class, 'region'])->name('referral.region');
});

Route::get('facility/{id}', [Referral::class, 'get_facility_list'])->middleware('auth:sanctum')->name('referral.get_facility_list');
//Auth
Route::post('login', [Referral::class, 'login']);

//Transactions
Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::get('/refer-patient', [Referral::class, 'patient_referral'])->name('referral.patient_referral');
});

Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-referral-information/{LogID}', [Referral::class, 'getReferralData'])->name('referral.referral_information');
});

Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-referral-list/{fhudcode}/{emr_id}', [Referral::class, 'get_referral_list'])->name('referral.get_referral_list');
});




