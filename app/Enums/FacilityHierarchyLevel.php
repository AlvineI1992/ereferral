<?php

namespace App\Enums;

enum FacilityHierarchyLevel: string
{
    case Apex = 'apex';
    case Provincial = 'provincial';
    case District = 'district';
    case Lower = 'lower';

    public function rank(): int
    {
        return match ($this) {
            self::Apex => 1,
            self::Provincial => 2,
            self::District => 3,
            self::Lower => 4,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Apex => 'Apex Hospital',
            self::Provincial => 'Provincial Facility',
            self::District => 'District Facility',
            self::Lower => 'Lower-level Facility',
        };
    }
}
