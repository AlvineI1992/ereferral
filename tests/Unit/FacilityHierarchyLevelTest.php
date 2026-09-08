<?php

use App\Enums\FacilityHierarchyLevel;

it('orders referral hierarchy levels from apex to lower-level care', function () {
    expect(FacilityHierarchyLevel::Apex->rank())->toBe(1)
        ->and(FacilityHierarchyLevel::Provincial->rank())->toBe(2)
        ->and(FacilityHierarchyLevel::District->rank())->toBe(3)
        ->and(FacilityHierarchyLevel::Lower->rank())->toBe(4);
});

it('provides human-readable hierarchy labels', function () {
    expect(FacilityHierarchyLevel::Apex->label())->toBe('Apex Hospital')
        ->and(FacilityHierarchyLevel::Lower->label())->toBe('Lower-level Facility');
});
