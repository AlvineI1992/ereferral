<?php

use App\OpenApi\OpenApiExampleGenerator;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

test('response examples are generated from nested OpenAPI schemas', function () {
    $schema = (new ObjectType)
        ->addProperty('message', new StringType)
        ->addProperty('success', new BooleanType)
        ->addProperty('items', (new ArrayType)->setItems(
            (new ObjectType)->addProperty('code', new StringType)
        ));

    expect((new OpenApiExampleGenerator)->generate($schema))->toBe([
        'message' => 'Success!',
        'success' => true,
        'items' => [['code' => 'SAMPLE-CODE']],
    ]);
});
