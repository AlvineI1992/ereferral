<?php

namespace App\OpenApi;

use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Tag;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Illuminate\Support\Str;

class ApiDocumentTransformer
{
    public function __invoke(OpenApi $openApi): void
    {
        $openApi->info
            ->setVersion((string) config('scramble.info.version', '1.0.0'))
            ->setDescription(<<<'MARKDOWN'
The eReferral integration API. Existing URLs and HTTP methods are stable deployment contracts.

### Referral network workflow

![Referral network recommendation workflow](/referral-network-workflow.svg)

The default recommendation is the source facility's immediate configured parent. Supply `target_level=district`, `provincial`, or `apex` to select a higher facility from the same escalation path.

### Test an authenticated endpoint

1. Open **Authentication** and run `POST /login` with a valid API account.
2. Copy the returned token.
3. Select **Authorize**, paste the token only (without typing `Bearer`), and save.
4. Open an endpoint, select **Try It**, review the example payload, then send the request.

### Access model

- `POST /login` is public.
- All other documented operations require a valid Sanctum bearer token.
- Each protected operation lists its required permission under guard `api` and its token ability when applicable. Assign the API permissions to an API role, then assign that role to the user.
- Each endpoint has its own API permission, including separate list, read, create, update, delete, and workflow operations. Grant only the operations each EMR account needs. Web-guard permissions do not grant API access.
- Missing API permission returns HTTP 403. The active administrator exception and existing facility/access-scope checks remain in effect.
- Write operations use live application data. Use dedicated test records and confirm identifiers before sending requests.
MARKDOWN);

        $openApi->secure(
            SecurityScheme::http('bearer', 'Sanctum token')
                ->as('sanctum')
                ->setDescription('Paste the token returned by POST /login. Do not include the Bearer prefix.')
                ->default()
        );

        $openApi->tags = [
            new Tag('Authentication', 'Obtain the Sanctum token used by Try It requests.'),
            new Tag('Reference Data', 'Read facility, geography, clinical lookup, and code values.'),
            new Tag('Referrals', 'Create referrals and retrieve referral records or attachments.'),
            new Tag('Referral Workflow', 'Receive, admit, discharge, and update referral workflow state.'),
            new Tag('Bed Tracking', 'Read and maintain facility bed availability.'),
        ];

        $this->promoteBodySchemas($openApi);
    }

    private function promoteBodySchemas(OpenApi $openApi): void
    {
        foreach ($openApi->paths as $path) {
            foreach ($path->operations as $operation) {
                $operationName = $this->componentName(
                    $operation->operationId ?: $operation->method.' '.$path->path
                );

                if ($operation->requestBodyObject !== null) {
                    foreach ($operation->requestBodyObject->content as $mediaType => $schema) {
                        if ($schema instanceof Schema && $this->isStructured($schema)) {
                            $operation->requestBodyObject->content[$mediaType] = $this->schemaReference(
                                $openApi,
                                $operationName.'Request',
                                $schema,
                            );
                        }
                    }
                }

            }
        }
    }

    private function isStructured(Schema $schema): bool
    {
        return $schema->type instanceof ObjectType || $schema->type instanceof ArrayType;
    }

    private function schemaReference(OpenApi $openApi, string $name, Schema $schema): Reference
    {
        if ($openApi->components->hasSchema($name)) {
            return $openApi->components->getSchemaReference($name);
        }

        return $openApi->components->addSchema($name, $schema);
    }

    private function componentName(string $value): string
    {
        return Str::studly((string) preg_replace('/[^A-Za-z0-9]+/', ' ', $value));
    }
}
