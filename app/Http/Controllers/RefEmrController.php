<?php

namespace App\Http\Controllers;

use App\Models\RefEmrModel;
use App\Models\RefFacilitiesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RefEmrController extends Controller
{
    public function index(Request $request)
    {
        $query = RefEmrModel::query()
            ->select(['emr_id', 'emr_name', 'status', 'remarks', 'created_at'])
            ->orderBy('emr_name');

        if (Schema::hasTable('ref_facilities')) {
            $query->withCount(['facilities as assigned_facilities_count']);
        }

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('emr_name', 'LIKE', "%{$search}%")
                    ->orWhere('remarks', 'LIKE', "%{$search}%")
                    ->orWhere('emr_id', 'LIKE', "%{$search}%");
            });
        }

        $providers = $query->paginate(10);

        return response()->json([
            'data' => $providers->items(),
            'total' => $providers->total(),
        ]);
    }

    public function list(Request $request)
    {
        $query = RefEmrModel::query()
            ->select(['emr_id', 'emr_name', 'status'])
            ->where('status', 1)
            ->orderBy('emr_name');

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where('emr_name', 'LIKE', "%{$search}%");
        }

        return response()->json($query->get());
    }

    public function create()
    {
        // Show a form (if applicable) or return a response
    }

    public function store(Request $request)
    {
        return $this->persist($request);
    }

    public function show($id)
    {
        $query = RefEmrModel::query();

        if (Schema::hasTable('ref_facilities')) {
            $query->withCount(['facilities as assigned_facilities_count']);
        }

        $provider = $query->findOrFail($id);

        if (Schema::hasTable('ref_facilities')) {
            $facilityQuery = RefFacilitiesModel::query()->where('emr_id', $provider->emr_id);

            $provider->active_facilities_count = (clone $facilityQuery)->where('status', 'A')->count();
            $provider->inactive_facilities_count = (clone $facilityQuery)->where('status', '!=', 'A')->count();
            $provider->coverage_regions_count = (clone $facilityQuery)->whereNotNull('region_code')->distinct()->count('region_code');
            $provider->coverage_types_count = (clone $facilityQuery)->whereNotNull('facility_type')->distinct()->count('facility_type');
        } else {
            $provider->assigned_facilities_count = 0;
            $provider->active_facilities_count = 0;
            $provider->inactive_facilities_count = 0;
            $provider->coverage_regions_count = 0;
            $provider->coverage_types_count = 0;
        }

        return response()->json($provider);
    }

    public function edit($LogID)
    {
    }

    public function update(Request $request, $id)
    {
        return $this->persist($request, (int) $id);
    }

    public function destroy($id)
    {
        $provider = RefEmrModel::findOrFail($id);

        if (Schema::hasTable('ref_facilities') && $provider->facilities()->exists()) {
            return response()->json([
                'message' => 'This provider is still assigned to one or more facilities. Remove those assignments before deleting it.',
            ], 422);
        }

        $provider->delete();

        return response()->json([
            'message' => 'Provider deleted successfully.',
        ]);
    }

    public function assign(Request $request)
    {
        $codes = $request->input('facilities', []);
        $id = $request->input('emr_id');
        $provider = RefEmrModel::findOrFail($id);

        if ((int) $provider->status !== 1) {
            return response()->json([
                'message' => 'Inactive providers cannot receive new facility assignments.',
            ], 422);
        }

        $updatedCount = $this->_assignFacilities($id, $codes);

        return response()->json([
            'message' => "Assigned $updatedCount facilities to EMR $id.",
            'assignedCount' => $updatedCount,
        ]);
    }

    public function revoke(Request $request)
    {
        $codes = $request->input('facilities', []);
        $id = $request->input('emr_id');

        $updatedCount = $this->_revokeFacilities($id, $codes);

        return response()->json([
            'message' => "Revoked $updatedCount facilities from EMR $id.",
            'assignedCount' => $updatedCount,
        ]);
    }

    public function _assignFacilities(string $emrId, array $hfhudcodes)
    {
        return RefFacilitiesModel::whereIn('hfhudcode', $hfhudcodes)
            ->update(['emr_id' => $emrId]);
    }

    public function _revokeFacilities(string $emrId, array $hfhudcodes)
    {
        return RefFacilitiesModel::whereIn('hfhudcode', $hfhudcodes)
            ->update(['emr_id' => null]);
    }

    private function persist(Request $request, ?int $id = null)
    {
        $provider = $id
            ? RefEmrModel::findOrFail($id)
            : new RefEmrModel();

        $request->merge([
            'emr_name' => trim((string) $request->input('emr_name', '')),
            'remarks' => $request->filled('remarks')
                ? trim((string) $request->input('remarks'))
                : null,
        ]);

        $validated = $request->validate(
            [
                'emr_name' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('ref_emr', 'emr_name')->ignore($provider->emr_id, 'emr_id'),
                ],
                'status' => ['required', 'boolean'],
                'remarks' => ['nullable', 'string', 'max:255'],
            ],
            [
                'emr_name.required' => 'Provider name is required.',
                'emr_name.unique' => 'This provider name is already taken.',
                'emr_name.max' => 'Provider name cannot exceed 50 characters.',
                'remarks.max' => 'Remarks cannot exceed 255 characters.',
            ]
        );

        $provider->fill([
            'emr_name' => $validated['emr_name'],
            'status' => $validated['status'] ? 1 : 0,
            'remarks' => $validated['remarks'] ?: null,
        ])->save();

        $message = $provider->wasRecentlyCreated
            ? 'Provider created successfully.'
            : 'Provider updated successfully.';

        return redirect()->route('emr.index')->with('success', $message);
    }
}
