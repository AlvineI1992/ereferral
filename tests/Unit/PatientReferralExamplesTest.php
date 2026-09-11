<?php

use App\Http\Requests\PatientReferralRequest;
use App\OpenApi\Examples\PatientReferralExamples;
use Illuminate\Support\Facades\Validator;

uses(Tests\TestCase::class);

test('documented patient referral JSON examples comply with request validation', function () {
    $request = new PatientReferralRequest;

    foreach (PatientReferralExamples::all() as $example) {
        expect(Validator::make($example, $request->rules())->errors()->toArray())->toBeEmpty();
    }
});

test('patient referral ICD codes are optional', function () {
    $request = new PatientReferralRequest;
    $payload = PatientReferralExamples::minimal();
    unset($payload['ICD']);

    expect(Validator::make($payload, $request->rules())->errors()->toArray())->toBeEmpty();
});

test('patient referral response examples use the legacy response contract', function () {
    expect(PatientReferralExamples::successResponse())
        ->toHaveKeys(['code', 'message', 'data'])
        ->and(PatientReferralExamples::facilityErrorResponse())
        ->toHaveKey('error');
});
