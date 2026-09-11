<?php

namespace App\OpenApi;

use Dedoc\Scramble\Support\Generator\Combined\AnyOf;
use Dedoc\Scramble\Support\Generator\MissingValue;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\NullType;
use Dedoc\Scramble\Support\Generator\Types\NumberType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\Type;

final class OpenApiExampleGenerator
{
    public function addTo(Schema|Reference $schema): void
    {
        $type = $schema instanceof Schema ? $schema->type : $schema;

        if ($type->example instanceof MissingValue) {
            $type->example($this->generate($type));
        }
    }

    public function generate(Type|Schema $subject, ?string $property = null, int $depth = 0): mixed
    {
        $type = $subject instanceof Schema ? $subject->type : $subject;

        if (! $type->example instanceof MissingValue) {
            return $type->example;
        }

        if ($type->examples !== []) {
            return collect($type->examples)->first(fn ($example) => ! $example instanceof MissingValue);
        }

        if ($type->enum !== []) {
            return $type->enum[0];
        }

        if ($depth > 8) {
            return null;
        }

        if ($type instanceof Reference) {
            $resolved = $type->resolve();

            return $resolved instanceof Schema
                ? $this->generate($resolved, $property, $depth + 1)
                : null;
        }

        if ($type instanceof ObjectType) {
            return collect($type->properties)
                ->mapWithKeys(fn ($child, $name) => [
                    $name => $child instanceof Type || $child instanceof Schema
                        ? $this->generate($child, $name, $depth + 1)
                        : null,
                ])
                ->all();
        }

        if ($type instanceof ArrayType) {
            if ($type->maxItems === 0) {
                return [];
            }

            if ($type->prefixItems !== []) {
                return collect($type->prefixItems)
                    ->map(fn ($item) => $this->generate($item, $property, $depth + 1))
                    ->all();
            }

            return [$this->generate($type->items, $property, $depth + 1)];
        }

        if ($type instanceof AnyOf) {
            $candidate = collect($type->items)->first(fn ($item) => ! $item instanceof NullType);

            return $candidate ? $this->generate($candidate, $property, $depth + 1) : null;
        }

        if ($type instanceof BooleanType) {
            return true;
        }

        if ($type instanceof IntegerType) {
            return 1;
        }

        if ($type instanceof NumberType) {
            return 1.0;
        }

        if ($type instanceof NullType) {
            return null;
        }

        return $this->stringValue($type, $property);
    }

    private function stringValue(Type $type, ?string $property): string
    {
        return match (true) {
            $type->format === 'date-time' => '2026-09-11T13:00:00+08:00',
            $type->format === 'date' => '2026-09-11',
            $type->format === 'email' => 'api.user@example.com',
            $type->format === 'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            str_contains(strtolower((string) $property), 'message') => 'Success!',
            str_contains(strtolower((string) $property), 'token') => '1|sample-sanctum-token',
            str_contains(strtolower((string) $property), 'code') => 'SAMPLE-CODE',
            str_contains(strtolower((string) $property), 'name') => 'Sample Name',
            default => 'sample',
        };
    }
}
