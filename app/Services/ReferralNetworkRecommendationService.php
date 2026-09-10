<?php

namespace App\Services;

use App\Enums\FacilityHierarchyLevel;
use App\Models\FacilityHierarchy;
use Illuminate\Validation\ValidationException;

class ReferralNetworkRecommendationService
{
    public function recommend(string $sourceCode, ?FacilityHierarchyLevel $targetLevel = null): array
    {
        $source = FacilityHierarchy::query()
            ->with('facility:hfhudcode,facility_name,facility_type,region_code,province_code,city_code,bgycode,status')
            ->where('facility_hfhudcode', $sourceCode)
            ->where('is_active', true)
            ->firstOrFail();

        $path = $this->buildEscalationPath($source);
        $target = $targetLevel
            ? $this->findTarget($source, $path, $targetLevel)
            : ($path[1] ?? null);

        return [
            'source' => $this->nodePayload($source, 0, false),
            'recommended_facility' => $target ? $this->nodePayload($target, $this->positionOf($path, $target), true) : null,
            'requested_target_level' => $targetLevel?->value,
            'referral_network' => $source->referral_network,
            'is_apex' => $source->level === FacilityHierarchyLevel::Apex,
            'escalation_path' => collect($path)
                ->map(fn (FacilityHierarchy $node, int $index) => $this->nodePayload($node, $index, $target?->is($node) ?? false))
                ->values(),
            'recommendation_basis' => 'Configured active referral-network hierarchy. Clinical urgency, specialty capability, and live bed availability are not evaluated.',
        ];
    }

    private function buildEscalationPath(FacilityHierarchy $source): array
    {
        $path = [];
        $visited = [];
        $current = $source;

        while ($current) {
            if (isset($visited[$current->facility_hfhudcode])) {
                throw ValidationException::withMessages(['hierarchy' => 'The configured referral path contains a cycle.']);
            }

            if (! $current->is_active || ! $current->facility || $current->facility->status !== 'A') {
                throw ValidationException::withMessages(['hierarchy' => 'The referral path contains an inactive facility or hierarchy mapping.']);
            }

            $visited[$current->facility_hfhudcode] = true;
            $path[] = $current;

            if ($current->level === FacilityHierarchyLevel::Apex) {
                break;
            }

            if (! $current->parent_hfhudcode) {
                throw ValidationException::withMessages(['hierarchy' => 'The configured referral path is incomplete before the apex level.']);
            }

            $parent = FacilityHierarchy::query()
                ->with('facility:hfhudcode,facility_name,facility_type,region_code,province_code,city_code,bgycode,status')
                ->where('facility_hfhudcode', $current->parent_hfhudcode)
                ->first();

            if (! $parent || $parent->level->rank() !== $current->level->rank() - 1 || $parent->referral_network !== $source->referral_network) {
                throw ValidationException::withMessages(['hierarchy' => 'The configured referral path has an invalid parent level or referral network.']);
            }

            $current = $parent;
        }

        return $path;
    }

    private function findTarget(FacilityHierarchy $source, array $path, FacilityHierarchyLevel $targetLevel): FacilityHierarchy
    {
        if ($targetLevel->rank() >= $source->level->rank()) {
            throw ValidationException::withMessages([
                'target_level' => 'The target level must be higher than the source facility level.',
            ]);
        }

        $target = collect($path)->first(fn (FacilityHierarchy $node) => $node->level === $targetLevel);

        if (! $target) {
            throw ValidationException::withMessages([
                'target_level' => 'The requested target level is not available in this facility referral path.',
            ]);
        }

        return $target;
    }

    private function nodePayload(FacilityHierarchy $node, int $sequence, bool $recommended): array
    {
        return [
            'sequence' => $sequence,
            'level' => $node->level->value,
            'level_label' => $node->level->label(),
            'referral_step' => match ($node->level) {
                FacilityHierarchyLevel::Lower => 'RHU / lower-level facility',
                FacilityHierarchyLevel::District => 'District facility',
                FacilityHierarchyLevel::Provincial => 'Provincial facility',
                FacilityHierarchyLevel::Apex => 'Apex hospital',
            },
            'is_recommended' => $recommended,
            'facility' => [
                'hfhudcode' => $node->facility_hfhudcode,
                'name' => $node->facility->facility_name,
                'facility_type' => $node->facility->facility_type,
                'region_code' => $this->normalizeRegionCode($node->facility->region_code),
                'province_code' => $node->facility->province_code,
                'city_code' => $node->facility->city_code,
                'barangay_code' => $node->facility->bgycode,
            ],
        ];
    }

    private function positionOf(array $path, FacilityHierarchy $target): int
    {
        foreach ($path as $index => $node) {
            if ($target->is($node)) {
                return $index;
            }
        }

        return 0;
    }

    private function normalizeRegionCode(mixed $code): string
    {
        $value = trim((string) $code);

        return ctype_digit($value) ? str_pad((string) ((int) $value), 2, '0', STR_PAD_LEFT) : $value;
    }
}
