<?php

namespace App\Http\Middleware;

use App\Services\UserAccessLabelService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        if (app()->environment('local')) {
            return null;
        }

        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user();
        $roles = $user?->getRoleNames()->map(fn ($role) => strtolower($role)) ?? collect();
        $isAdministrator = $roles->contains('admin') || $roles->contains('super-admin');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'access_type' => $user->access_type,
                    'access_label' => app(UserAccessLabelService::class)->resolve($user),
                    'navigation' => [
                        'dashboard' => $user->canAny([
                            'dashboard_referral monthly',
                            'dashboard_referral typeofservice',
                            'dashboard_referral reasonofreferral',
                        ]),
                        'incoming' => $user->can('incoming list'),
                        'patients' => $user->can('patient list'),
                        'appointments' => Route::has('appointments.index') && $user->can('appointment list'),
                        'beds' => $user->can('beds list'),
                        'demographics' => $user->can('demographic list'),
                        'facilities' => $user->can('facility list'),
                        'facilityHierarchy' => $user->can('facility hierarchy list'),
                        'religions' => $user->can('demographic list'),
                        'reports' => $user->can('referral report list') || $user->can('diagnosis heatmap list'),
                        'referralReport' => $user->can('referral report list'),
                        'diagnosisHeatmap' => $user->can('diagnosis heatmap list'),
                        'providers' => $user->can('provider list'),
                        'users' => $user->can('user list'),
                        'roles' => $user->can('role list'),
                        'permissions' => $user->can('permission list'),
                        'dataEncryption' => $isAdministrator,
                        'auditTrail' => $isAdministrator,
                    ],
                ] : null,
            ],

            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
