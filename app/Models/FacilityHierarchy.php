<?php

namespace App\Models;

use App\Enums\FacilityHierarchyLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacilityHierarchy extends Model
{
    protected $fillable = [
        'facility_hfhudcode', 'parent_hfhudcode', 'level', 'referral_network',
        'coverage_region_code', 'coverage_province_code', 'coverage_notes', 'is_active',
    ];

    protected function casts(): array
    {
        return ['level' => FacilityHierarchyLevel::class, 'is_active' => 'boolean'];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(RefFacilitiesModel::class, 'facility_hfhudcode', 'hfhudcode');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_hfhudcode', 'facility_hfhudcode');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_hfhudcode', 'facility_hfhudcode');
    }
}
