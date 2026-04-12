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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display a paginated list of users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->select(['id', 'name', 'email', 'status', 'access_id', 'access_type', 'created_at'])
            ->with('roles:id,name')
            ->orderBy('name');

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('access_type', 'LIKE', "%{$search}%")
                    ->orWhere('status', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->paginate(10);

        return response()->json([
            'data' => $this->decorateUsers(collect($users->items()))->values(),
            'total' => $users->total(),
        ]);
    }

    public function role_has_user(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        $isInclude = filter_var($request->input('is_include'), FILTER_VALIDATE_BOOLEAN);

        if (! $userId) {
            return response()->json(['error' => 'user_id is required'], 400);
        }

        $allRoles = RoleModel::all();

        $assignedRoleIds = \DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $userId)
            ->pluck('role_id')
            ->toArray();

        $roles = $allRoles->filter(function ($role) use ($assignedRoleIds, $isInclude) {
            return $isInclude
                ? ! in_array($role->id, $assignedRoleIds)
                : in_array($role->id, $assignedRoleIds);
        })->values();

        return response()->json([
            'data' => $roles,
            'total' => $roles->count(),
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
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $validated = $this->validateUser($request, true);

        $user = User::create($this->payloadFromValidated($validated));

        if (! $request->routeIs('user.store')) {
            event(new Registered($user));
            Auth::login($user);

            return redirect()->route('dashboard', absolute: false);
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
                Rule::unique('users', 'email')->ignore($user?->id),
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
