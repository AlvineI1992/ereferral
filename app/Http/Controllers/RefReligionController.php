<?php

namespace App\Http\Controllers;

use App\Models\RefReligionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RefReligionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', 'all'));
        $perPage = (int) $request->input('perPage', 10);
        $page = (int) $request->input('page', 1);

        $query = RefReligionModel::query()->select('relcode', 'reldesc', 'relstat', 'updated_at');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('relcode', 'like', "%{$search}%")
                    ->orWhere('reldesc', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['A', 'I'], true)) {
            $query->where('relstat', $status);
        }

        $paginated = $query
            ->orderBy('reldesc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginated->items(),
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    public function list(Request $request)
    {
        $query = RefReligionModel::query()->select('relcode', 'reldesc', 'relstat');

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('relcode', 'like', "%{$search}%")
                    ->orWhere('reldesc', 'like', "%{$search}%");
            });
        }

        if ($status = trim((string) $request->input('status', 'A'))) {
            if (in_array($status, ['A', 'I', 'all'], true) && $status !== 'all') {
                $query->where('relstat', $status);
            }
        }

        return response()->json($query->orderBy('reldesc')->get());
    }

    public function show(string $id)
    {
        return response()->json(
            RefReligionModel::query()->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'relcode' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_\\-]+$/', Rule::unique('ref_religion', 'relcode')],
            'reldesc' => ['required', 'string', 'max:150', Rule::unique('ref_religion', 'reldesc')],
            'relstat' => ['required', Rule::in(['A', 'I'])],
        ]);

        $religion = RefReligionModel::create([
            'relcode' => $this->normalizeReligionCode($validated['relcode']),
            'reldesc' => trim($validated['reldesc']),
            'relstat' => $validated['relstat'],
        ]);

        return response()->json([
            'message' => 'Religion created successfully.',
            'data' => $religion,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $religion = RefReligionModel::query()->findOrFail($id);

        $validated = $request->validate([
            'reldesc' => ['required', 'string', 'max:150', Rule::unique('ref_religion', 'reldesc')->ignore($religion->relcode, 'relcode')],
            'relstat' => ['required', Rule::in(['A', 'I'])],
        ]);

        $religion->update([
            'reldesc' => trim($validated['reldesc']),
            'relstat' => $validated['relstat'],
        ]);

        return response()->json([
            'message' => 'Religion updated successfully.',
            'data' => $religion->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $religion = RefReligionModel::query()->findOrFail($id);
        $religion->delete();

        return response()->json([
            'message' => 'Religion deleted successfully.',
        ]);
    }

    private function normalizeReligionCode(string $value): string
    {
        return Str::upper(trim($value));
    }
}
