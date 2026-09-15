<?php

namespace App\Http\Controllers;

use App\Services\DatabaseMaintenanceService;
use App\Services\ReferralAccessService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DatabaseMaintenanceController extends Controller
{
    public function index(Request $request, DatabaseMaintenanceService $maintenance)
    {
        $this->authorizeAdministrator($request);
        return Inertia::render('Admin/DatabaseMaintenance', ['maintenance' => $maintenance->status()]);
    }

    public function status(Request $request, DatabaseMaintenanceService $maintenance)
    {
        $this->authorizeAdministrator($request);
        return response()->json($maintenance->status())->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, DatabaseMaintenanceService $maintenance)
    {
        $this->authorizeAdministrator($request);
        $data = $request->validate([
            'action' => ['required', Rule::in(['migrate', 'seed'])],
            'seeder' => ['exclude_unless:action,seed', 'required', Rule::in(array_keys(DatabaseMaintenanceService::SEEDERS))],
            'confirmation' => ['required', 'accepted'],
        ]);
        // Migrations may legitimately outlast the web server's request timeout.
        set_time_limit(0);
        $previous = ignore_user_abort(true);
        try {
            $result = $maintenance->run($data['action'], $data['seeder'] ?? null, $request->user()->id);
        } finally {
            ignore_user_abort((bool) $previous);
        }
        return response()->json(['result' => $result], $result['status'] === 'completed' ? 200 : 500);
    }

    private function authorizeAdministrator(Request $request): void
    {
        abort_unless($request->user()?->status === 'A'
            && app(ReferralAccessService::class)->isAdministrator($request->user()), 403);
    }
}
