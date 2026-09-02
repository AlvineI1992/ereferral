<?php

namespace App\Services;

use App\Models\RefFacilityModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FhirReferralService
{
    private const IDENTIFIER_BASE = 'https://erefer.doh.gov.ph/fhir/identifier';
    private const HFHUD_SYSTEM = 'https://health.gov.ph/fhir/sid/hfhudcode';
    private const EXTENSION_BASE = 'https://erefer.doh.gov.ph/fhir/StructureDefinition';

    public function normalizeForCurrentService(array $payload): array
    {
        $normalized = $this->isFhirPayload($payload)
            ? $this->fromFhir($payload)
            : $payload;

        return $this->applyCurrentDefaults($normalized);
    }

    public function isFhirPayload(array $payload): bool
    {
        return isset($payload['resourceType'])
            || isset($payload['fhir'])
            || isset($payload['fhir_bundle'])
            || isset($payload['bundle']);
    }

    public function buildReferralBundle(array $payload, ?string $logId = null): array
    {
        $data = $this->applyCurrentDefaults($payload);
        $logId = $logId ?: ($data['referral']['generated_code'] ?? 'pending-'.Str::uuid()->toString());

        $patientId = $this->resourceId('patient', $logId);
        $originId = $this->resourceId('origin-organization', $data['referral']['facility_from'] ?? $logId);
        $destinationId = $this->resourceId('destination-organization', $data['referral']['facility_to'] ?? $logId);
        $providerId = $this->resourceId('requester', $logId);
        $conditionId = $this->resourceId('condition', $logId);
        $serviceRequestId = $this->resourceId('service-request', $logId);
        $observationEntries = $this->buildObservationEntries($data, $patientId, $logId);

        $entries = [
            $this->transactionEntry('Patient', 'urn:uuid:'.$patientId, $this->patientResource($data, $patientId)),
            $this->transactionEntry('Organization', 'urn:uuid:'.$originId, $this->organizationResource($data['referral']['facility_from'] ?? '', $originId)),
            $this->transactionEntry('Organization', 'urn:uuid:'.$destinationId, $this->organizationResource($data['referral']['facility_to'] ?? '', $destinationId)),
            $this->transactionEntry('Practitioner', 'urn:uuid:'.$providerId, $this->practitionerResource($data, $providerId)),
            $this->transactionEntry('Condition', 'urn:uuid:'.$conditionId, $this->conditionResource($data, $conditionId, $patientId)),
            ...$observationEntries,
        ];

        $supportingInfo = [
            ['reference' => 'urn:uuid:'.$conditionId],
            ...array_map(
                fn (array $entry): array => ['reference' => $entry['fullUrl']],
                $observationEntries
            ),
        ];

        $entries[] = $this->transactionEntry(
            'ServiceRequest',
            'urn:uuid:'.$serviceRequestId,
            $this->serviceRequestResource($data, $serviceRequestId, $patientId, $providerId, $originId, $destinationId, $supportingInfo)
        );

        return [
            'resourceType' => 'Bundle',
            'type' => 'transaction',
            'timestamp' => now()->toIso8601String(),
            'identifier' => [
                'system' => self::IDENTIFIER_BASE.'/referral-log-id',
                'value' => $logId,
            ],
            'entry' => $entries,
        ];
    }

    public function submitReferral(array $payload, ?string $logId = null): array
    {
        $bundle = $this->buildReferralBundle($payload, $logId);
        $logId = (string) ($bundle['identifier']['value'] ?? $logId ?? '');
        $baseUrl = rtrim((string) config('services.fhir.base_url', 'http://10.11.133.129:8080/'), '/');

        try {
            $response = Http::timeout((int) config('services.fhir.timeout', 30))
                ->withHeaders([
                    'Accept' => 'application/fhir+json, application/json',
                    'Content-Type' => 'application/fhir+json',
                ])
                ->post($baseUrl, $bundle);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'server_url' => $baseUrl,
                'log_id' => $logId,
                'bundle' => $bundle,
                'response' => $response->json() ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 500,
                'server_url' => $baseUrl,
                'log_id' => $logId,
                'bundle' => $bundle,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function formatReferralResponse(array $currentResponse, ?array $fhirResult, string $format, string $mode, array $payload): array
    {
        $logId = $fhirResult['log_id'] ?? $this->extractLogId($currentResponse, $payload);
        $bundle = $fhirResult['bundle'] ?? $this->buildReferralBundle($payload, $logId);
        $logId = (string) ($logId ?? $bundle['identifier']['value'] ?? '');
        $format = $this->normalizeOption($format, ['current', 'fhir', 'both'], 'current');
        $mode = $this->normalizeOption($mode, ['current', 'fhir', 'both'], 'current');

        if ($format === 'fhir') {
            return [
                'log_id' => $logId,
                'fhir' => $this->fhirResultPayload($fhirResult, $bundle),
            ];
        }

        if ($format === 'both') {
            return [
                'log_id' => $logId,
                'current' => $currentResponse,
                'fhir' => $this->fhirResultPayload($fhirResult, $bundle),
            ];
        }

        if ($fhirResult !== null) {
            return [
                ...$currentResponse,
                'log_id' => $logId,
                'fhir' => $this->fhirResultPayload($fhirResult, $bundle),
            ];
        }

        return $currentResponse;
    }

    public function normalizeOption(string $value, array $allowed, string $default): string
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, $allowed, true) ? $normalized : $default;
    }

    public function extractLogId(array $currentResponse, array $payload): ?string
    {
        $code = (string) ($currentResponse['code'] ?? '');

        if ($code !== '' && ! is_numeric($code)) {
            return $code;
        }

        return $payload['referral']['generated_code'] ?? null;
    }

    private function fromFhir(array $payload): array
    {
        $bundle = $payload['fhir'] ?? $payload['fhir_bundle'] ?? $payload['bundle'] ?? $payload;
        $resources = $this->resourcesByType($bundle);
        $patient = $resources['Patient'][0] ?? [];
        $serviceRequest = $resources['ServiceRequest'][0] ?? [];
        $condition = $resources['Condition'][0] ?? [];
        $observations = $resources['Observation'] ?? [];

        $patientName = Arr::get($patient, 'name.0', []);
        $address = Arr::get($patient, 'address.0', []);
        $requesterOrganization = $this->referencedResource($bundle, Arr::get($serviceRequest, 'requester.reference'), 'Organization');
        $performerOrganization = $this->referencedResource($bundle, Arr::get($serviceRequest, 'performer.0.reference'), 'Organization');
        $provider = $this->referencedResource($bundle, Arr::get($serviceRequest, 'requester.reference'), 'Practitioner');
        $occurrence = (string) (Arr::get($serviceRequest, 'occurrenceDateTime') ?? Arr::get($serviceRequest, 'authoredOn') ?? now()->toDateTimeString());
        $diagnosis = $this->diagnosisFromCondition($condition);

        return [
            'referral' => [
                'facility_from' => $this->organizationCode($requesterOrganization) ?: $this->extensionValue($serviceRequest, 'facility-from'),
                'facility_to' => $this->organizationCode($performerOrganization) ?: $this->extensionValue($serviceRequest, 'facility-to'),
                'type_referral' => Arr::get($serviceRequest, 'code.coding.0.code', 'TRANS'),
                'category' => Arr::get($serviceRequest, 'category.0.coding.0.code', 'ER'),
                'reason' => Arr::get($serviceRequest, 'reasonCode.0.coding.0.code', 'OTHER'),
                'other_reason' => Arr::get($serviceRequest, 'reasonCode.0.text'),
                'remarks' => Arr::get($serviceRequest, 'note.0.text'),
                'contact_person' => $this->extensionValue($serviceRequest, 'contact-person') ?: 'N/A',
                'designation' => $this->extensionValue($serviceRequest, 'contact-designation'),
                'contact_no' => $this->extensionValue($serviceRequest, 'contact-number'),
                'refer_date' => $this->datePart($occurrence),
                'refer_time' => $this->timePart($occurrence),
                'generated_code' => Arr::get($bundle, 'identifier.value'),
            ],
            'patient' => [
                'family_number' => $this->identifierValue($patient, 'family-number'),
                'phic_number' => $this->identifierValue($patient, 'phic-number'),
                'case_no' => $this->identifierValue($patient, 'case-number'),
                'last_name' => Arr::get($patientName, 'family'),
                'first_name' => Arr::get($patientName, 'given.0'),
                'middle_name' => Arr::get($patientName, 'given.1'),
                'suffix' => Arr::get($patientName, 'suffix.0'),
                'birthdate' => Arr::get($patient, 'birthDate'),
                'sex' => $this->legacySex(Arr::get($patient, 'gender')),
                'civil_status' => Arr::get($patient, 'maritalStatus.coding.0.code'),
                'religion' => $this->extensionValue($patient, 'religion') ?: 'N/A',
                'contact_no' => Arr::get($patient, 'telecom.0.value'),
                'blood_type' => $this->extensionValue($patient, 'blood-type'),
                'blood_rh' => $this->extensionValue($patient, 'blood-rh'),
            ],
            'demographics' => [
                'street' => Arr::get($address, 'line.0', 'N/A'),
                'brgy_code' => Arr::get($address, 'district') ?: $this->extensionValue($address, 'barangay-code'),
                'city_code' => Arr::get($address, 'city'),
                'prov_code' => Arr::get($address, 'state'),
                'reg_code' => $this->extensionValue($address, 'region-code'),
                'zipcode' => Arr::get($address, 'postalCode'),
            ],
            'clinical' => [
                'diagnosis' => $diagnosis['diagnosis'],
                'history' => $this->extensionValue($condition, 'clinical-history'),
                'physical_examination' => null,
                'chief_complaint' => Arr::get($serviceRequest, 'reasonCode.0.text') ?: $diagnosis['text'],
                'findings' => Arr::get($condition, 'note.0.text'),
            ],
            'ICD' => $diagnosis['icd'],
            'vital_signs' => $this->vitalsFromObservations($observations),
            'patient_providers' => [$this->providerFromPractitioner($provider)],
        ];
    }

    private function applyCurrentDefaults(array $payload): array
    {
        $payload['referral'] ??= [];
        $payload['patient'] ??= [];
        $payload['demographics'] ??= [];
        $payload['clinical'] ??= [];
        $payload['vital_signs'] ??= [];
        $payload['patient_providers'] ??= [];

        $payload['clinical']['diagnosis'] = Arr::wrap($payload['clinical']['diagnosis'] ?? []);
        $payload['clinical']['history'] ??= null;
        $payload['clinical']['findings'] ??= null;
        $payload['clinical']['physical_examination'] ??= null;
        $payload['ICD'] = Arr::wrap($payload['ICD'] ?? []);

        foreach (['BP', 'temp', 'HR', 'RR', 'O2_sats', 'weight', 'height'] as $key) {
            $payload['vital_signs'][$key] ??= null;
        }

        $provider = $payload['patient_providers'][0] ?? [];
        $payload['patient_providers'][0] = [
            'provider_last' => $provider['provider_last'] ?? $provider['provider_last_name'] ?? 'N/A',
            'provider_first' => $provider['provider_first'] ?? $provider['provider_first_name'] ?? 'N/A',
            'provider_middle' => $provider['provider_middle'] ?? $provider['provider_middle_name'] ?? null,
            'provider_suffix' => $provider['provider_suffix'] ?? null,
            'provider_contact_no' => $provider['provider_contact_no'] ?? $provider['ProviderContactNo'] ?? null,
            'provider_type' => $provider['provider_type'] ?? 'REFER',
        ];

        return $payload;
    }

    private function fhirResponseResource(array $currentResponse, ?array $fhirResult, array $bundle, string $mode): array
    {
        if ($fhirResult === null) {
            return $bundle;
        }

        if ($fhirResult['success'] && is_array($fhirResult['response'] ?? null) && isset($fhirResult['response']['resourceType'])) {
            return $fhirResult['response'];
        }

        return $this->operationOutcome($currentResponse, $fhirResult, $mode);
    }

    private function fhirResultPayload(?array $fhirResult, array $bundle): array
    {
        if ($fhirResult === null) {
            return [
                'success' => true,
                'status' => null,
                'server_url' => null,
                'log_id' => (string) ($bundle['identifier']['value'] ?? ''),
                'response' => $bundle,
                'error' => null,
            ];
        }

        return [
            'success' => (bool) ($fhirResult['success'] ?? false),
            'status' => $fhirResult['status'] ?? null,
            'server_url' => $fhirResult['server_url'] ?? null,
            'log_id' => (string) ($fhirResult['log_id'] ?? $bundle['identifier']['value'] ?? ''),
            'response' => $fhirResult['response'] ?? null,
            'error' => $fhirResult['error'] ?? null,
        ];
    }

    private function operationOutcome(array $currentResponse, ?array $fhirResult, string $mode): array
    {
        $success = $fhirResult !== null
            ? (bool) $fhirResult['success']
            : ! in_array((string) ($currentResponse['code'] ?? ''), ['400', '401', '404', '500'], true);

        return [
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => $success ? 'information' : 'error',
                'code' => $success ? 'informational' : 'processing',
                'diagnostics' => $fhirResult['error'] ?? $currentResponse['message'] ?? 'Referral processed.',
                'details' => [
                    'text' => $mode === 'fhir'
                        ? 'Referral processed through FHIR server.'
                        : 'Referral processed through referral service.',
                ],
            ]],
            'extension' => array_values(array_filter([
                isset($fhirResult['server_url']) ? $this->extension('fhir-server-url', $fhirResult['server_url']) : null,
                isset($fhirResult['status']) ? [
                    'url' => self::EXTENSION_BASE.'/fhir-http-status',
                    'valueInteger' => (int) $fhirResult['status'],
                ] : null,
            ])),
        ];
    }

    private function patientResource(array $data, string $patientId): array
    {
        return [
            'resourceType' => 'Patient',
            'id' => $patientId,
            'identifier' => array_values(array_filter([
                $this->identifier('family-number', $data['patient']['family_number'] ?? null),
                $this->identifier('phic-number', $data['patient']['phic_number'] ?? null),
                $this->identifier('case-number', $data['patient']['case_no'] ?? null),
            ])),
            'name' => [[
                'family' => $data['patient']['last_name'] ?? null,
                'given' => array_values(array_filter([
                    $data['patient']['first_name'] ?? null,
                    $data['patient']['middle_name'] ?? null,
                ])),
                'suffix' => array_values(array_filter([$data['patient']['suffix'] ?? null])),
            ]],
            'gender' => ($data['patient']['sex'] ?? null) === 'F' ? 'female' : 'male',
            'birthDate' => $this->datePart($data['patient']['birthdate'] ?? null),
            'telecom' => array_values(array_filter([
                ! empty($data['patient']['contact_no']) ? ['system' => 'phone', 'value' => $data['patient']['contact_no']] : null,
            ])),
            'maritalStatus' => ! empty($data['patient']['civil_status']) ? [
                'coding' => [[
                    'system' => self::IDENTIFIER_BASE.'/civil-status',
                    'code' => $data['patient']['civil_status'],
                ]],
            ] : null,
            'address' => [[
                'line' => array_values(array_filter([$data['demographics']['street'] ?? null])),
                'district' => $data['demographics']['brgy_code'] ?? null,
                'city' => $data['demographics']['city_code'] ?? null,
                'state' => $data['demographics']['prov_code'] ?? null,
                'postalCode' => $data['demographics']['zipcode'] ?? null,
                'extension' => array_values(array_filter([
                    $this->extension('region-code', $data['demographics']['reg_code'] ?? null),
                ])),
            ]],
            'extension' => array_values(array_filter([
                $this->extension('religion', $data['patient']['religion'] ?? null),
                $this->extension('blood-type', $data['patient']['blood_type'] ?? null),
                $this->extension('blood-rh', $data['patient']['blood_rh'] ?? null),
            ])),
        ];
    }

    private function organizationResource(string $hfhudCode, string $resourceId): array
    {
        $facility = $hfhudCode !== '' && Schema::hasTable('ref_facilities')
            ? RefFacilityModel::query()->where('hfhudcode', $hfhudCode)->first()
            : null;

        return [
            'resourceType' => 'Organization',
            'id' => $resourceId,
            'identifier' => array_values(array_filter([
                ! empty($hfhudCode) ? ['system' => self::HFHUD_SYSTEM, 'value' => $hfhudCode] : null,
            ])),
            'name' => $facility?->facility_name ?? $hfhudCode,
            'address' => array_values(array_filter([
                $facility ? [
                    'line' => array_values(array_filter([$facility->fhudaddress ?? null])),
                    'city' => $facility->city_code ?? null,
                    'state' => $facility->province_code ?? null,
                    'district' => $facility->bgycode ?? null,
                    'extension' => array_values(array_filter([
                        $this->extension('region-code', $facility->region_code ?? null),
                    ])),
                ] : null,
            ])),
        ];
    }

    private function practitionerResource(array $data, string $providerId): array
    {
        $provider = $data['patient_providers'][0] ?? [];

        return [
            'resourceType' => 'Practitioner',
            'id' => $providerId,
            'name' => [[
                'family' => $provider['provider_last'] ?? 'N/A',
                'given' => array_values(array_filter([
                    $provider['provider_first'] ?? null,
                    $provider['provider_middle'] ?? null,
                ])),
                'suffix' => array_values(array_filter([$provider['provider_suffix'] ?? null])),
            ]],
            'telecom' => array_values(array_filter([
                ! empty($provider['provider_contact_no']) ? ['system' => 'phone', 'value' => $provider['provider_contact_no']] : null,
            ])),
        ];
    }

    private function conditionResource(array $data, string $conditionId, string $patientId): array
    {
        return [
            'resourceType' => 'Condition',
            'id' => $conditionId,
            'clinicalStatus' => [
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                    'code' => 'active',
                ]],
            ],
            'subject' => ['reference' => 'urn:uuid:'.$patientId],
            'code' => [
                'coding' => array_map(fn (string $code): array => [
                    'system' => 'http://hl7.org/fhir/sid/icd-10',
                    'code' => $code,
                ], Arr::wrap($data['ICD'] ?? [])),
                'text' => implode(', ', Arr::wrap($data['clinical']['diagnosis'] ?? [])),
            ],
            'note' => array_values(array_filter([
                ! empty($data['clinical']['findings']) ? ['text' => $data['clinical']['findings']] : null,
            ])),
            'extension' => array_values(array_filter([
                $this->extension('clinical-history', $data['clinical']['history'] ?? null),
                $this->extension('physical-examination', isset($data['clinical']['physical_examination']) ? json_encode($data['clinical']['physical_examination']) : null),
            ])),
        ];
    }

    private function serviceRequestResource(
        array $data,
        string $serviceRequestId,
        string $patientId,
        string $providerId,
        string $originId,
        string $destinationId,
        array $supportingInfo
    ): array {
        return [
            'resourceType' => 'ServiceRequest',
            'id' => $serviceRequestId,
            'identifier' => array_values(array_filter([
                $this->identifier('referral-log-id', $data['referral']['generated_code'] ?? null),
            ])),
            'status' => 'active',
            'intent' => 'order',
            'category' => [[
                'coding' => [[
                    'system' => self::IDENTIFIER_BASE.'/referral-category',
                    'code' => $data['referral']['category'] ?? null,
                ]],
            ]],
            'code' => [
                'coding' => [[
                    'system' => self::IDENTIFIER_BASE.'/referral-type',
                    'code' => $data['referral']['type_referral'] ?? null,
                ]],
                'text' => 'Patient referral',
            ],
            'subject' => ['reference' => 'urn:uuid:'.$patientId],
            'requester' => ['reference' => 'urn:uuid:'.$providerId],
            'performer' => [['reference' => 'urn:uuid:'.$destinationId]],
            'supportingInfo' => $supportingInfo,
            'authoredOn' => now()->toIso8601String(),
            'occurrenceDateTime' => $this->dateTime($data['referral']['refer_date'] ?? null, $data['referral']['refer_time'] ?? null),
            'reasonCode' => [[
                'coding' => [[
                    'system' => self::IDENTIFIER_BASE.'/referral-reason',
                    'code' => $data['referral']['reason'] ?? null,
                ]],
                'text' => $data['referral']['other_reason'] ?? null,
            ]],
            'note' => array_values(array_filter([
                ! empty($data['referral']['remarks']) ? ['text' => $data['referral']['remarks']] : null,
            ])),
            'extension' => array_values(array_filter([
                $this->extension('facility-from', $data['referral']['facility_from'] ?? null),
                $this->extension('facility-to', $data['referral']['facility_to'] ?? null),
                $this->extension('requesting-organization', 'urn:uuid:'.$originId),
                $this->extension('contact-person', $data['referral']['contact_person'] ?? null),
                $this->extension('contact-designation', $data['referral']['designation'] ?? null),
                $this->extension('contact-number', $data['referral']['contact_no'] ?? null),
            ])),
        ];
    }

    private function buildObservationEntries(array $data, string $patientId, string $logId): array
    {
        $definitions = [
            'BP' => ['85354-9', 'Blood pressure'],
            'temp' => ['8310-5', 'Body temperature'],
            'HR' => ['8867-4', 'Heart rate'],
            'RR' => ['9279-1', 'Respiratory rate'],
            'O2_sats' => ['2708-6', 'Oxygen saturation'],
            'weight' => ['29463-7', 'Body weight'],
            'height' => ['8302-2', 'Body height'],
        ];

        $entries = [];
        foreach ($definitions as $key => [$loinc, $display]) {
            $value = $data['vital_signs'][$key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $observationId = $this->resourceId('observation-'.$key, $logId);
            $resource = [
                'resourceType' => 'Observation',
                'id' => $observationId,
                'status' => 'final',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => $loinc,
                        'display' => $display,
                    ]],
                    'text' => $key,
                ],
                'subject' => ['reference' => 'urn:uuid:'.$patientId],
            ];

            if ($key === 'BP' && preg_match('/(\d{2,3})\D+(\d{2,3})/', (string) $value, $matches)) {
                $resource['component'] = [
                    [
                        'code' => ['coding' => [['system' => 'http://loinc.org', 'code' => '8480-6', 'display' => 'Systolic blood pressure']]],
                        'valueQuantity' => ['value' => (float) $matches[1], 'unit' => 'mmHg'],
                    ],
                    [
                        'code' => ['coding' => [['system' => 'http://loinc.org', 'code' => '8462-4', 'display' => 'Diastolic blood pressure']]],
                        'valueQuantity' => ['value' => (float) $matches[2], 'unit' => 'mmHg'],
                    ],
                ];
            } else {
                $resource['valueString'] = (string) $value;
            }

            $entries[] = $this->transactionEntry('Observation', 'urn:uuid:'.$observationId, $resource);
        }

        return $entries;
    }

    private function transactionEntry(string $resourceType, string $fullUrl, array $resource): array
    {
        return [
            'fullUrl' => $fullUrl,
            'resource' => $this->cleanFhirArray($resource),
            'request' => [
                'method' => 'POST',
                'url' => $resourceType,
            ],
        ];
    }

    private function resourcesByType(array $bundle): array
    {
        $resources = [];
        foreach ($bundle['entry'] ?? [] as $entry) {
            $resource = $entry['resource'] ?? [];
            if (! empty($resource['resourceType'])) {
                $resources[$resource['resourceType']][] = $resource;
            }
        }

        if (! empty($bundle['resourceType']) && $bundle['resourceType'] !== 'Bundle') {
            $resources[$bundle['resourceType']][] = $bundle;
        }

        return $resources;
    }

    private function referencedResource(array $bundle, ?string $reference, string $type): array
    {
        if (! $reference) {
            return [];
        }

        foreach ($bundle['entry'] ?? [] as $entry) {
            $resource = $entry['resource'] ?? [];
            if (($resource['resourceType'] ?? null) === $type && (($entry['fullUrl'] ?? null) === $reference || ($type.'/'.($resource['id'] ?? null)) === $reference)) {
                return $resource;
            }
        }

        return [];
    }

    private function providerFromPractitioner(array $provider): array
    {
        $name = Arr::get($provider, 'name.0', []);

        return [
            'provider_last' => Arr::get($name, 'family', 'N/A'),
            'provider_first' => Arr::get($name, 'given.0', 'N/A'),
            'provider_middle' => Arr::get($name, 'given.1'),
            'provider_suffix' => Arr::get($name, 'suffix.0'),
            'provider_contact_no' => Arr::get($provider, 'telecom.0.value'),
            'provider_type' => 'REFER',
        ];
    }

    private function vitalsFromObservations(array $observations): array
    {
        $vitals = [];
        $map = [
            '85354-9' => 'BP',
            '8310-5' => 'temp',
            '8867-4' => 'HR',
            '9279-1' => 'RR',
            '2708-6' => 'O2_sats',
            '29463-7' => 'weight',
            '8302-2' => 'height',
        ];

        foreach ($observations as $observation) {
            $code = Arr::get($observation, 'code.coding.0.code');
            $key = $map[$code] ?? Arr::get($observation, 'code.text');
            if (! $key) {
                continue;
            }

            if ($key === 'BP' && isset($observation['component'])) {
                $systolic = Arr::get($observation, 'component.0.valueQuantity.value');
                $diastolic = Arr::get($observation, 'component.1.valueQuantity.value');
                $vitals[$key] = $systolic && $diastolic ? "{$systolic}/{$diastolic}" : null;
                continue;
            }

            $vitals[$key] = Arr::get($observation, 'valueString')
                ?? Arr::get($observation, 'valueQuantity.value');
        }

        return $vitals;
    }

    private function diagnosisFromCondition(array $condition): array
    {
        $coding = Arr::get($condition, 'code.coding', []);
        $icd = array_values(array_filter(array_map(fn (array $item): ?string => $item['code'] ?? null, $coding)));
        $text = Arr::get($condition, 'code.text', 'N/A');

        return [
            'diagnosis' => array_values(array_filter([$text])),
            'icd' => $icd ?: ['Z00.0'],
            'text' => $text,
        ];
    }

    private function identifier(string $code, mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        return [
            'system' => self::IDENTIFIER_BASE.'/'.$code,
            'value' => (string) $value,
        ];
    }

    private function identifierValue(array $resource, string $code): ?string
    {
        foreach ($resource['identifier'] ?? [] as $identifier) {
            if (($identifier['system'] ?? null) === self::IDENTIFIER_BASE.'/'.$code) {
                return $identifier['value'] ?? null;
            }
        }

        return null;
    }

    private function extension(string $code, mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        return [
            'url' => self::EXTENSION_BASE.'/'.$code,
            'valueString' => (string) $value,
        ];
    }

    private function extensionValue(array $resource, string $code): ?string
    {
        foreach ($resource['extension'] ?? [] as $extension) {
            if (($extension['url'] ?? null) === self::EXTENSION_BASE.'/'.$code) {
                return $extension['valueString'] ?? null;
            }
        }

        return null;
    }

    private function organizationCode(array $organization): ?string
    {
        foreach ($organization['identifier'] ?? [] as $identifier) {
            if (($identifier['system'] ?? null) === self::HFHUD_SYSTEM) {
                return $identifier['value'] ?? null;
            }
        }

        return null;
    }

    private function legacySex(?string $gender): ?string
    {
        return match (strtolower((string) $gender)) {
            'female' => 'F',
            'male' => 'M',
            default => null,
        };
    }

    private function dateTime(mixed $date, mixed $time): ?string
    {
        $datePart = $this->datePart($date);
        if (! $datePart) {
            return null;
        }

        return trim($datePart.'T'.($this->timePart($time) ?: '00:00:00'));
    }

    private function datePart(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    private function timePart(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date('H:i:s', $timestamp);
    }

    private function resourceId(string $prefix, mixed $value): string
    {
        return Str::slug($prefix.'-'.$value);
    }

    private function cleanFhirArray(array $value): array
    {
        $isList = array_is_list($value);
        $clean = [];

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $item = $this->cleanFhirArray($item);
            }

            if ($item === null || $item === []) {
                continue;
            }

            if ($isList) {
                $clean[] = $item;
            } else {
                $clean[$key] = $item;
            }
        }

        return $clean;
    }
}
