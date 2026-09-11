<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RefEmrModel;
use App\Models\RefFacilitiesModel;
use App\Models\RefRegionModel;
use App\Models\RoleModel;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use App\Services\DataEncryptionManager;
use App\Rules\UniqueEncryptedEmail;

class RegisteredUserController extends Controller
{
    /**
     * Display a paginated list of users.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['A', 'I'])],
            'role' => ['nullable', 'integer', 'exists:roles,id'],
            'access_type' => ['nullable', Rule::in(['EMR', 'CHD', 'HOSP'])],
            'access_scope' => ['nullable', Rule::in(['scoped', 'unscoped'])],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'sort' => ['nullable', Rule::in(['name', 'newest', 'oldest'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50])],
        ]);

        $query = User::query()
            ->select(['id', 'name', 'email', 'status', 'access_id', 'access_type', 'created_at'])
            ->with('roles:id,name');

        $search = trim((string) ($validated['search'] ?? ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'LIKE', "%{$search}%");

                if (app(DataEncryptionManager::class)->isEnabled()) {
                    $builder->orWhereBlind('email', 'email_index', strtolower($search));
                    if (app(DataEncryptionManager::class)->isConverting()) {
                        $builder->orWhere('email', strtolower($search));
                    }
                } else {
                    $builder->orWhere('email', 'LIKE', "%{$search}%");
                }

                $builder
                    ->orWhere('access_type', 'LIKE', "%{$search}%")
                    ->orWhere('status', 'LIKE', "%{$search}%");
            });
        }

        $query
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['role'] ?? null, fn ($query, $roleId) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('roles.id', $roleId)))
            ->when($validated['access_type'] ?? null, fn ($query, $type) => $query->where('access_type', $type))
            ->when(($validated['access_scope'] ?? null) === 'scoped', fn ($query) => $query->whereNotNull('access_id')->where('access_id', '<>', ''))
            ->when(($validated['access_scope'] ?? null) === 'unscoped', fn ($query) => $query->where(fn ($scopeQuery) => $scopeQuery->whereNull('access_id')->orWhere('access_id', '')))
            ->when($validated['created_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['created_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));

        match ($validated['sort'] ?? 'name') {
            'newest' => $query->orderByDesc('created_at'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderBy('name'),
        };

        $users = $query->paginate($validated['per_page'] ?? 10);

        return response()->json([
            'data' => $this->decorateUsers(collect($users->items()))->values(),
            'total' => $users->total(),
            'last_page' => $users->lastPage(),
            'filter_options' => [
                'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function role_has_user(Request $request): JsonResponse
    {
        $request->merge(['is_include' => $request->boolean('is_include')]);
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'is_include' => ['required', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'guard' => ['nullable', 'string', 'max:50'],
            'sort' => ['nullable', Rule::in(['name', 'newest', 'oldest'])],
            'per_page' => ['nullable', 'integer', Rule::in([5, 10, 25])],
        ]);

        $assignedRoleIds = \DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $validated['user_id'])
            ->pluck('role_id');

        $query = RoleModel::query()
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where('name', 'LIKE', "%{$search}%"))
            ->when($validated['guard'] ?? null, fn ($query, $guard) => $query->where('guard_name', $guard));

        $validated['is_include']
            ? $query->whereNotIn('id', $assignedRoleIds)
            : $query->whereIn('id', $assignedRoleIds);

        match ($validated['sort'] ?? 'name') {
            'newest' => $query->orderByDesc('id'),
            'oldest' => $query->orderBy('id'),
            default => $query->orderBy('name'),
        };

        $roles = $query->paginate($validated['per_page'] ?? 10);

        return response()->json([
            'data' => $roles->items(),
            'total' => $roles->total(),
            'last_page' => $roles->lastPage(),
            'filter_options' => [
                'guards' => RoleModel::query()->whereNotNull('guard_name')->distinct()->orderBy('guard_name')->pluck('guard_name'),
            ],
        ]);
    }

    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $validated = $this->validateUser($request, true);

        $user = User::create($this->payloadFromValidated($validated));

        if (! $request->routeIs('user.store')) {
            event(new Registered($user));
            Auth::login($user);

            return redirect()->to(route('dashboard', absolute: false));
        }

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $this->decorateUsers(new Collection([$user->load('roles:id,name')]))->first(),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $this->validateUser($request, false, $user);

        $user->fill($this->payloadFromValidated($validated, $user));
        $user->save();

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $this->decorateUsers(new Collection([$user->fresh()->load('roles:id,name')]))->first(),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account while you are signed in.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function show($id): JsonResponse
    {
        $user = User::with('roles:id,name')->findOrFail($id);

        return response()->json($this->decorateUsers(new Collection([$user]))->first());
    }

    public function sample()
    {
        return User::find(2)?->assignRole('Admin');
    }

    public function assignRolesToUser(Request $request, $userId): JsonResponse
    {
        $validated = $request->validate([
            'roleids' => 'required|array|min:1',
            'roleids.*' => 'integer|exists:roles,id',
        ]);

        $roleIds = $validated['roleids'];
        $user = User::findOrFail($userId);

        $roles = Role::whereIn('id', $roleIds)->get();
        $newRoles = $roles->filter(fn ($role) => ! $user->hasRole($role));

        if ($newRoles->isNotEmpty()) {
            $user->assignRole($newRoles);
            $newRoles->each(fn ($role) => $role->is_assigned_to_user = true);

            return response()->json([
                'success' => true,
                'roles' => $newRoles,
                'message' => $newRoles->count().' role(s) assigned to user successfully!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No new roles to assign.',
        ]);
    }

    public function revokeRolesFromUser(Request $request, $userId): JsonResponse
    {
        $validated = $request->validate([
            'roleids' => 'required|array|min:1',
            'roleids.*' => 'integer|exists:roles,id',
        ]);

        $roleIds = $validated['roleids'];
        $user = User::findOrFail($userId);

        $roles = Role::whereIn('id', $roleIds)
            ->whereIn('id', $user->roles()->pluck('id'))
            ->get();

        $count = 0;

        foreach ($roles as $role) {
            $user->removeRole($role);
            $count++;
        }

        return response()->json([
            'success' => true,
            'roles' => $roles,
            'message' => $count === 0
                ? 'No role to revoke.'
                : "{$count} role(s) revoked from user successfully!",
        ]);
    }

    private function validateUser(Request $request, bool $requirePassword, ?User $user = null): array
    {
        $accessType = strtoupper(trim((string) $request->input('access_type', '')));
        $accessId = trim((string) $request->input('access_id', ''));

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'email' => strtolower(trim((string) $request->input('email', ''))),
            'access_type' => $accessType !== '' ? $accessType : null,
            'access_id' => $accessId !== '' ? $accessId : null,
        ]);

        $passwordRules = $requirePassword
            ? ['required', 'confirmed', Rules\Password::defaults()]
            : ['nullable', 'confirmed', Rules\Password::defaults()];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                app(DataEncryptionManager::class)->isEnabled()
                    ? new UniqueEncryptedEmail($user?->id)
                    : Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => $passwordRules,
            'access_type' => ['nullable', 'string', Rule::in(['EMR', 'CHD', 'HOSP'])],
            'access_id' => [
                Rule::requiredIf(fn () => filled($request->input('access_type'))),
                'nullable',
                'string',
                'max:255',
            ],
            'status' => ['nullable', 'boolean'],
        ]);
    }

    private function payloadFromValidated(array $validated, ?User $user = null): array
    {
        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => array_key_exists('status', $validated)
                ? ($validated['status'] ? 'A' : 'I')
                : ($user?->status ?? 'A'),
            'access_type' => $validated['access_type'] ?: null,
            'access_id' => $validated['access_type'] ? $validated['access_id'] : null,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        return $payload;
    }

    private function decorateUsers(Collection $users): Collection
    {
        if ($users->isEmpty()) {
            return collect();
        }

        $emrIds = $users
            ->where('access_type', 'EMR')
            ->pluck('access_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $regionIds = $users
            ->where('access_type', 'CHD')
            ->pluck('access_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $hospitalIds = $users
            ->where('access_type', 'HOSP')
            ->pluck('access_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $providerLabels = empty($emrIds) || ! Schema::hasTable('ref_emr')
            ? []
            : RefEmrModel::query()->whereIn('emr_id', $emrIds)->pluck('emr_name', 'emr_id')->all();

        $regionLabels = empty($regionIds) || ! Schema::hasTable('ref_region')
            ? []
            : RefRegionModel::query()->whereIn('regcode', $regionIds)->pluck('regname', 'regcode')->all();

        $hospitalLabels = empty($hospitalIds) || ! Schema::hasTable('ref_facilities')
            ? []
            : RefFacilitiesModel::query()->whereIn('hfhudcode', $hospitalIds)->pluck('facility_name', 'hfhudcode')->all();

        return $users->map(function (User $user) use ($providerLabels, $regionLabels, $hospitalLabels) {
            $roleNames = $user->roles instanceof EloquentCollection
                ? $user->roles->pluck('name')->values()
                : collect();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'status_label' => $user->status === 'A' ? 'Active' : 'Inactive',
                'access_type' => $user->access_type,
                'access_id' => $user->access_id,
                'access_label' => $this->resolveAccessLabel($user->access_type, $user->access_id, $providerLabels, $regionLabels, $hospitalLabels),
                'roles' => $roleNames->all(),
                'roles_count' => $roleNames->count(),
                'primary_role' => $roleNames->first(),
                'created_at' => $user->created_at?->toIso8601String(),
            ];
        });
    }

    private function resolveAccessLabel(?string $type, ?string $accessId, array $providerLabels, array $regionLabels, array $hospitalLabels): ?string
    {
        if (! $type || ! $accessId) {
            return null;
        }

        return match ($type) {
            'EMR' => $providerLabels[$accessId] ?? $accessId,
            'CHD' => $regionLabels[$accessId] ?? $accessId,
            'HOSP' => $hospitalLabels[$accessId] ?? $accessId,
            default => $accessId,
        };
    }
}
