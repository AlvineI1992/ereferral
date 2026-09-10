<?php

namespace App\OpenApi;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Tag;

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
- The deployed API middleware does not apply endpoint-specific Spatie roles or permissions. The authenticated account's configured facility/access scope still governs application behavior where implemented.
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
    }
}
