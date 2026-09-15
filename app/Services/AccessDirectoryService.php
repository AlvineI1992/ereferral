<?php

namespace App\Services;

use App\Models\PermissionModel;
use App\Models\RoleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccessDirectoryService
{
    public function list(Request $request, bool $roles): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'string', 'max:100'],
            'guard' => ['nullable', 'string', 'max:50'],
            'assignment' => ['nullable', Rule::in(['assigned', 'unassigned'])],
            'sort' => ['nullable', Rule::in(['name', 'name_desc', 'newest', 'oldest'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50])],
        ]);
        $permissions = PermissionModel::query()->get(['id', 'name', 'guard_name']);
        $classified = $permissions->mapWithKeys(fn ($p) => [$p->id => $this->classify($p->name)]);
        $query = $roles ? RoleModel::query() : PermissionModel::query();
        $table = $roles ? 'roles' : 'permissions';
        $query->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($nested) => $nested
            ->where('name', 'like', '%'.$search.'%')->orWhere('guard_name', 'like', '%'.$search.'%')));
        $query->when($filters['guard'] ?? null, fn ($q, $guard) => $q->where('guard_name', $guard));

        $pivot = DB::table('role_has_permissions')->join($roles ? 'permissions' : 'roles', function ($join) use ($roles) {
            $join->on($roles ? 'permissions.id' : 'roles.id', '=', $roles ? 'role_has_permissions.permission_id' : 'role_has_permissions.role_id');
        })->whereNull($roles ? 'permissions.deleted_at' : 'roles.deleted_at');
        $foreign = $roles ? 'role_id' : 'permission_id';
        $related = (clone $pivot)->whereColumn('role_has_permissions.'.$foreign, $table.'.id');
        if (! empty($filters['assignment'])) {
            $query->whereExists((clone $related)->selectRaw('1'), 'and', $filters['assignment'] === 'unassigned');
        }
        if (! empty($filters['module']) || (! $roles && ! empty($filters['action']))) {
            $ids = $classified->filter(fn ($parts) =>
                (empty($filters['module']) || $parts['module'] === $filters['module']) &&
                ($roles || empty($filters['action']) || $parts['action'] === $filters['action'])
            )->keys();
            if ($roles) {
                $query->whereExists((clone $related)->whereIn('permission_id', $ids)->selectRaw('1'));
            } else {
                $query->whereIn('id', $ids);
            }
        }
        $query->addSelect($table.'.*')->selectSub((clone $related)->selectRaw('COUNT(*)'), 'assignment_count');
        [$column, $direction] = match ($filters['sort'] ?? 'name') {
            'name_desc' => ['name', 'desc'], 'newest' => ['id', 'desc'], 'oldest' => ['id', 'asc'], default => ['name', 'asc'],
        };
        $page = $query->orderBy($column, $direction)->orderBy('id')->paginate($filters['per_page'] ?? 10);
        $modulesByRole = $roles ? (clone $pivot)->whereIn('role_id', $page->getCollection()->pluck('id'))
            ->get(['role_id', 'permission_id'])->groupBy('role_id') : collect();
        $page->getCollection()->transform(function ($row) use ($roles, $classified, $modulesByRole) {
            $row->classification = $roles
                ? ($modulesByRole->get($row->id, collect())->map(fn ($link) => $classified[$link->permission_id]['module'])->unique()->sort()->values()->all())
                : [$classified[$row->id]['module']];
            $row->action = $roles ? null : $classified[$row->id]['action'];
            $row->endpoint = ! $roles && $row->guard_name === 'api' ? ApiPermissionService::endpointFor($row->name) : null;
            return $row;
        });

        return [
            'data' => $page->items(), 'total' => $page->total(), 'last_page' => $page->lastPage(),
            'filter_options' => [
                'modules' => $classified->pluck('module')->unique()->sort()->values(),
                'actions' => $classified->pluck('action')->filter()->unique()->sort()->values(),
                'guards' => ($roles ? RoleModel::query() : PermissionModel::query())->distinct()->orderBy('guard_name')->pluck('guard_name'),
            ],
        ];
    }

    private function classify(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name));
        $action = count($parts) > 1 ? array_pop($parts) : '';
        return ['module' => implode(' ', $parts), 'action' => $action];
    }
}
