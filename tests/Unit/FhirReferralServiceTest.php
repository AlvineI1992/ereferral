<?php

use App\Services\FhirReferralService;

uses(Tests\TestCase::class);

test('it builds a fhir transaction bundle from the current referral payload', function () {
    $service = app(FhirReferralService::class);

    $bundle = $service->buildReferralBundle(fhirReferralPayload(), 'REF-UNIT-001');

    expect($bundle['resourceType'])->toBe('Bundle')
        ->and($bundle['type'])->toBe('transaction')
        ->and($bundle['identifier']['value'])->toBe('REF-UNIT-001');

    $serviceRequest = collect($bundle['entry'])
        ->pluck('resource')
        ->firstWhere('resourceType', 'ServiceRequest');

    expect($serviceRequest['status'])->toBe('active')
        ->and($serviceRequest['intent'])->toBe('order')
        ->and($serviceRequest['performer'][0]['reference'])->toStartWith('urn:uuid:destination-organization');
});

test('it normalizes a fhir referral bundle for the current referral service', function () {
    $service = app(FhirReferralService::class);
    $bundle = $service->buildReferralBundle(fhirReferralPayload(), 'REF-UNIT-002');

    $payload = $service->normalizeForCurrentService($bundle);

    expect($payload['referral']['facility_from'])->toBe('DOH000000000000001')
        ->and($payload['referral']['facility_to'])->toBe('DOH000000000000002')
        ->and($payload['patient']['first_name'])->toBe('Juan')
        ->and($payload['patient']['sex'])->toBe('M')
        ->and($payload['clinical']['diagnosis'][0])->toBe('Acute appendicitis')
        ->and($payload['patient_providers'][0]['provider_last'])->toBe('Mendoza');
});

test('it returns the log id and full fhir server response', function () {
    $service = app(FhirReferralService::class);
    $serverResponse = [
        'resourceType' => 'Bundle',
        'type' => 'transaction-response',
        'entry' => [[
            'response' => [
                'status' => '201 Created',
                'location' => 'Patient/123',
            ],
        ]],
    ];

    $response = $service->formatReferralResponse(
        ['code' => 'REF-UNIT-003', 'message' => 'Referral successfully transmitted to FHIR server'],
        [
            'success' => true,
            'status' => 200,
            'server_url' => 'http://10.11.133.129:8080',
            'log_id' => 'REF-UNIT-003',
            'bundle' => $service->buildReferralBundle(fhirReferralPayload(), 'REF-UNIT-003'),
            'response' => $serverResponse,
        ],
        'fhir',
        'fhir',
        fhirReferralPayload()
    );

    expect($response['log_id'])->toBe('REF-UNIT-003')
        ->and($response['fhir']['status'])->toBe(200)
        ->and($response['fhir']['response'])->toBe($serverResponse);
});

function fhirReferralPayload(): array
{
    return [
        'referral' => [
            'facility_from' => 'DOH000000000000001',
            'facility_to' => 'DOH000000000000002',
            'type_referral' => 'TRANS',
            'category' => 'ER',
            'reason' => 'SEFTA',
            'other_reason' => '',
            'remarks' => 'Requires urgent transfer.',
            'contact_person' => 'Receiving Nurse',
            'designation' => 'ER Nurse',
            'contact_no' => '09179998888',
            'refer_date' => '2026-04-11',
            'refer_time' => '09:30',
        ],
        'patient' => [
            'family_number' => 'FAM-001',
            'phic_number' => 'PHIC-001',
            'case_no' => 'CASE-001',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'suffix' => 'Jr',
            'birthdate' => '1990-01-01',
            'sex' => 'M',
            'civil_status' => 'S',
            'religion' => 'Catholic',
            'contact_no' => '09171234567',
            'blood_type' => 'O',
            'blood_rh' => '+',
        ],
        'demographics' => [
            'street' => '123 Sample Street',
            'brgy_code' => '012801001',
            'city_code' => '012801',
            'prov_code' => '0128',
            'reg_code' => '01',
            'zipcode' => '1000',
        ],
        'clinical' => [
            'diagnosis' => ['Acute appendicitis'],
            'history' => 'Pain started 8 hours ago.',
            'physical_examination' => null,
            'chief_complaint' => 'Severe abdominal pain',
            'findings' => 'Tenderness on right lower quadrant.',
        ],
        'ICD' => ['K35.8'],
        'vital_signs' => [
            'BP' => '120/80',
            'temp' => '37.0',
            'HR' => '88',
            'RR' => '18',
            'O2_sats' => '98%',
            'weight' => '65',
            'height' => '170',
        ],
        'patient_providers' => [[
            'provider_last' => 'Mendoza',
            'provider_first' => 'Ana',
            'provider_middle' => 'R',
            'provider_suffix' => '',
            'provider_type' => 'REFER',
        ]],
    ];
}
