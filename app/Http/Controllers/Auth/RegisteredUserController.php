<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\RoleModel;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;


class RegisteredUserController extends Controller
{
      /**
 * Display a paginated list of users.
 */
public function index(Request $request)
{
    $query = User::query();

    if ($search = $request->input('search')) {
        $query->where('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%");
    }

    $users = $query->paginate(10); // Paginate results

    return response()->json([
        'data' => $users->items(),
        'total' => $users->total(),
    ]);
}

public function role_has_user(Request $request)
{
    $userId = $request->input('user_id');
    $isInclude = filter_var($request->input('is_include'), FILTER_VALIDATE_BOOLEAN);

    if (!$userId) {
        return response()->json(['error' => 'user_id is required'], 400);
    }

    // Get all roles
    $allRoles = RoleModel::all();

    // Get role IDs assigned to the user
    $assignedRoleIds = \DB::table('model_has_roles')
        ->where('model_type', \App\Models\User::class)
        ->where('model_id', $userId)
        ->pluck('role_id')
        ->toArray();

    // Filter based on inclusion flag
    $roles = $allRoles->filter(function ($role) use ($assignedRoleIds, $isInclude) {
        return $isInclude
            ? !in_array($role->id, $assignedRoleIds)
            : in_array($role->id, $assignedRoleIds);
    })->values(); // Reset keys

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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
      
         $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status'=>'A',
            'access_id' => $request->access_id,
            'access_type' => $request->access_type,
        ]);

        event(new Registered($user)); 
        /*  Auth::login($user); */
        return redirect()->route('users')->with('success', 'Added successfully.');
    }



    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        // Update basic info
        $user->name = $request->name;
        $user->email = $request->email;
        $user->status = 'I';
        $user->access_id = $request->access_id;
        $user->access_type = $request->access_type;

        // Only update password if provided
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('users')->with('success', 'Updated successfully.');
    }



    public function show($id)
    {
        $data = User::findOrFail($id);
        return response()->json($data);
    }

    public function sample()
    {
       return  User::find(2)->assignRole('Admin');
    }

    public function assignRolesToUser(Request $request, $userId)
{
    $validated = $request->validate([
        'roleids' => 'required|array|min:1',
        'roleids.*' => 'integer|exists:roles,id',
    ]);
    
    $roleIds = $validated['roleids'];
    $user = User::findOrFail($userId);
    
    // Get the roles that are not already assigned to the user
    $roles = Role::whereIn('id', $roleIds)->get();
    $newRoles = $roles->filter(fn($role) => !$user->hasRole($role));
    
    if ($newRoles->isNotEmpty()) {
        $user->assignRole($newRoles); 
        
        // Add flag for frontend
        $newRoles->each(fn ($role) => $role->is_assigned_to_user = true);
        
        return response()->json([
            'success' => true,
            'roles' => $newRoles,
            'message' => $newRoles->count() . ' role(s) assigned to user successfully!',
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'No new roles to assign.',
    ]);
}

    
public function revokeRolesFromUser(Request $request, $userId)
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


}
