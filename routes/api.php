<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Referral;
use App\Http\Controllers\Api\References;


Route::get('generate_code/{hfhudcode}', [References::class, 'generate_reference'])->middleware('auth:sanctum')->name('referral.reference');

//Reference
/* Demographics */
Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::get('/demographics', [References::class, 'demographic_reference'])->name('referral.demographics');
    Route::get('/region/{id}', [References::class, 'region'])->name('referral.region');
    Route::get('/province/{id}', [References::class, 'province'])->name('referral.province');
    Route::get('/city/{id}', [References::class, 'city'])->name('referral.city');
    Route::get('/barangay/{id}', [References::class, 'barangay'])->name('referral.barangay');
});
/* Reason for Referral */
Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::get('/reason-referral', [References::class, 'referral_reason'])->name('referral.reason');
    Route::get('/reason-referral-code/{code}', [References::class, 'referral_reason_by_code'])->name('referral.reason.code');
});

/* Referral type */
Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::get('/referral-type', [References::class, 'referral_type'])->name('referral.type');
    Route::get('/referral-type-code/{code}', [References::class, 'referral_type_code'])->name('referral.type.code');
  
});

Route::get('facility/{id}', [Referral::class, 'get_facility_list'])->middleware('auth:sanctum')->name('referral.get_facility_list');
//Auth
Route::post('login', [Referral::class, 'login']);

//Transactions
Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::post('/refer_patient', [Referral::class, 'patient_referral'])->name('referral.patient_referral');
});

Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-referral-information/{LogID}', [Referral::class, 'getReferralData'])->name('referral.referral_information');
});

Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::get('/get-referral-list/{fhudcode}/{emr_id}', [Referral::class, 'get_referral_list'])->name('referral.get_referral_list');
});

Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::post('/received', [Referral::class, 'received'])->name('referral.received');
});

Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::post('/admit', [Referral::class, 'admit'])->name('referral.admit');
});

Route::middleware(['auth:sanctum', 'auth.sanctum.custom'])->group(function () {
    Route::post('/get-discharged-data', [Referral::class, 'get_discharged_data'])->name('referral.get.discharge.data');
});






 


