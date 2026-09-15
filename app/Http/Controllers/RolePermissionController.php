<?php

namespace App\Http\Controllers;

use App\Models\PermissionModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RolePermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->merge(['is_include' => $request->boolean('is_include')]);

        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_include' => ['required', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:50'],
            'guard' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50])],
        ]);

        $role = \App\Models\RoleModel::findOrFail($validated['role_id']);
        $query = PermissionModel::query()->where('guard_name', $role->guard_name);

        $query->when($validated['search'] ?? null, function ($query, $search) {
            $query->where(fn ($nested) => $nested
                ->where('name', 'LIKE', "%{$search}%")
                ->orWhere('guard_name', 'LIKE', "%{$search}%"));
        });
        $query->when($validated['module'] ?? null, fn ($query, $module) => $query->where('name', 'LIKE', $module.' %'));
        $query->when($validated['action'] ?? null, fn ($query, $action) => $query->where('name', 'LIKE', '% '.$action));
        $query->when($validated['guard'] ?? null, fn ($query, $guard) => $query->where('guard_name', $guard));

        $assignedIds = DB::table('role_has_permissions')->where('role_id', $validated['role_id'])->pluck('permission_id');
        $validated['is_include'] ? $query->whereNotIn('id', $assignedIds) : $query->whereIn('id', $assignedIds);

        $permissions = $query->orderBy('name')->paginate($validated['per_page'] ?? 10);
        $permissions->getCollection()->transform(function ($permission) {
            [$permission->module, $permission->action] = $this->parts($permission->name);
            $permission->endpoint = $permission->guard_name === 'api'
                ? \App\Services\ApiPermissionService::endpointFor($permission->name) : null;

            return $permission;
        });
        $all = PermissionModel::query()->where('guard_name', $role->guard_name)->get(['name', 'guard_name']);

        return response()->json([
            'data' => $permissions->items(),
            'total' => $permissions->total(),
            'last_page' => $permissions->lastPage(),
            'filter_options' => [
                'modules' => $all->map(fn ($permission) => $this->parts($permission->name)[0])->unique()->sort()->values(),
                'actions' => $all->map(fn ($permission) => $this->parts($permission->name)[1])->filter()->unique()->sort()->values(),
                'guards' => $all->pluck('guard_name')->filter()->unique()->sort()->values(),
            ],
        ]);
    }

    private function parts(string $name): array
    {
        $parts = explode(' ', trim($name));
        $action = count($parts) > 1 ? array_pop($parts) : '';

        return [implode(' ', $parts) ?: $name, $action];
    }
}
