<?php

namespace App\OpenApi;

use App\OpenApi\Examples\PatientReferralExamples;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\Type;
use Dedoc\Scramble\Support\RouteInfo;

class ApiOperationTransformer
{
    public function __invoke(Operation $operation, RouteInfo $routeInfo): void
    {
        $path = preg_replace('#^api/#', '', $routeInfo->route->uri());
        $isLogin = $path === 'login';

        $operation->setTags([$this->tagFor($path)]);

        if ($operation->summary === '') {
            $operation->summary($this->summaryFor($path, strtoupper($operation->method)));
        }

        $access = $isLogin
            ? '**Access:** Public. Use this operation to obtain the bearer token for testing.'
            : '**Access:** Authenticated API user with a valid Sanctum bearer token. No endpoint-specific Spatie role or permission middleware is applied.';

        $operation->description(trim($operation->description."\n\n".$access));

        if ($isLogin) {
            $operation->security = [new SecurityRequirement([])];
        }

        if ($path === 'refer_patient' && $operation->requestBodyObject !== null) {
            foreach ($operation->requestBodyObject->content as $schema) {
                $type = $schema instanceof Schema ? $schema->type : $schema;

                if ($type instanceof Type) {
                    $type->examples(PatientReferralExamples::all());
                }
            }

            $this->documentPatientReferralResponses($operation);
        }

        $this->documentResponseExamples($operation);
        if ($routeInfo->route->getName() === 'referral.get_referral_list') {
            $this->replaceResponse($operation, 200, 'Untracked referrals for the authorized destination facility. Returns a JSON array without a data wrapper.', \App\OpenApi\Examples\ReferralListResponse::schema());
            $this->replaceResponse($operation, 403, 'Invalid, revoked, or mismatched EMR credential; insufficient token abilities; or facility access denied.',
                (new ObjectType)->addProperty('message', new StringType)->setRequired(['message'])->example(['message' => (new \App\Exceptions\InvalidEmrCredentialException)->getMessage()]));
            $this->replaceResponse($operation, 404, 'No untracked referrals found for this facility and EMR provider.',
                (new ObjectType)->addProperty('error', new StringType)->setRequired(['error'])->example(['error' => 'No referrals found/ facility not assigned to any emr']));
            $parameter = new \Dedoc\Scramble\Support\Generator\Parameter('X-EMR-Token', 'header');
            $parameter->required = true;
            $parameter->description = 'Generate an EMR credential on Users for the same active EMR-provider account used to obtain the bearer token. Shown once; rotation or revocation invalidates the old credential.';
            $parameter->schema = Schema::fromType(new StringType);
            $operation->addParameters([$parameter]);
            $operation->summary('Get referral list using an EMR credential');
            $operation->description($operation->description."\n\nSend X-EMR-Token alongside Authorization: Bearer. Numeric EMR IDs are no longer accepted in the URL. Facility access remains restricted to the authenticated account.");
        }
    }

    private function documentResponseExamples(Operation $operation): void
    {
        $generator = new OpenApiExampleGenerator;

        foreach ($operation->responses ?? [] as $response) {
            if ($response instanceof Reference) {
                $response = $response->resolve();
            }

            if (! $response instanceof Response) {
                continue;
            }

            foreach ($response->content as $schema) {
                if ($schema instanceof Schema || $schema instanceof Reference) {
                    $generator->addTo($schema);
                }
            }
        }
    }

    private function documentPatientReferralResponses(Operation $operation): void
    {
        $success = (new ObjectType)
            ->addProperty('code', new StringType)
            ->addProperty('message', new StringType)
            ->addProperty('data', new ObjectType)
            ->setRequired(['code', 'message', 'data'])
            ->example(PatientReferralExamples::successResponse());

        $facilityError = (new ObjectType)
            ->addProperty('error', new StringType)
            ->setRequired(['error'])
            ->example(PatientReferralExamples::facilityErrorResponse());

        $this->replaceResponse($operation, 200, 'Referral successfully transmitted.', $success);
        $this->replaceResponse($operation, 400, 'A source or destination facility is unavailable or not registered to an EMR provider.', $facilityError);
    }

    private function replaceResponse(Operation $operation, int $code, string $description, Type $type): void
    {
        foreach ($operation->responses ?? [] as $index => $response) {
            if ($response instanceof Response && (int) $response->code === $code) {
                $operation->responses[$index] = Response::make($code)
                    ->setDescription($description)
                    ->setContent('application/json', Schema::fromType($type));

                return;
            }
        }

        $operation->addResponse(
            Response::make($code)
                ->setDescription($description)
                ->setContent('application/json', Schema::fromType($type))
        );
    }

    private function tagFor(string $path): string
    {
        if ($path === 'login') {
            return 'Authentication';
        }

        if (str_starts_with($path, 'bed-trackers')) {
            return 'Bed Tracking';
        }

        if (in_array($path, ['received', 'admit', 'referral-status', 'referral-status/update'], true)
            || str_starts_with($path, 'get-discharged-data')) {
            return 'Referral Workflow';
        }

        if (str_starts_with($path, 'refer_patient')
            || str_starts_with($path, 'referral-network/')
            || str_starts_with($path, 'incoming/fhir')
            || str_starts_with($path, 'get-referral-')
            || str_starts_with($path, 'referral-attachments')) {
            return 'Referrals';
        }

        return 'Reference Data';
    }

    private function summaryFor(string $path, string $method): string
    {
        return match (true) {
            $path === 'bed-trackers' && $method === 'GET' => 'List bed tracker records',
            $path === 'bed-trackers' && $method === 'POST' => 'Create a bed tracker record',
            $path === 'bed-trackers/facilities' => 'List facilities available to bed tracking',
            str_starts_with($path, 'bed-trackers/') && $method === 'GET' => 'Get a bed tracker record',
            str_starts_with($path, 'bed-trackers/') && $method === 'PUT' => 'Update a bed tracker record',
            str_starts_with($path, 'bed-trackers/') && $method === 'DELETE' => 'Delete a bed tracker record',
            str_starts_with($path, 'referral-attachments/') => 'Download a referral attachment',
            str_starts_with($path, 'referral-network/') => 'Recommend a referral facility from the configured network hierarchy',
            $path === 'incoming/fhir' => 'Create an incoming FHIR referral',
            str_starts_with($path, 'incoming/fhir/') => 'Get an incoming FHIR referral',
            default => ucfirst(str_replace(['-', '_', '/', '{', '}'], [' ', ' ', ' - ', '', ''], $path)),
        };
    }
}
