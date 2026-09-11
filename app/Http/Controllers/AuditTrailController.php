<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DataEncryptionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AuditTrailController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAdministrator($request);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', Rule::in(['created', 'updated', 'deleted', 'restored', 'accessed'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $encrypted = app(DataEncryptionManager::class)->isEnabled();
        $emailUserIds = $encrypted && filled($filters['search'] ?? null)
            ? User::query()->where(function ($query) use ($filters) {
                $email = strtolower(trim($filters['search']));
                $query->whereBlind('email', 'email_index', $email);
                if (app(DataEncryptionManager::class)->isConverting()) {
                    $query->orWhere('email', $email);
                }
            })->pluck('id')
            : collect();

        $audits = DB::table('audits')
            ->leftJoin('users', 'audits.user_id', '=', 'users.id')
            ->select([
                'audits.id',
                'audits.event',
                'audits.auditable_type',
                'audits.auditable_id',
                'audits.old_values',
                'audits.new_values',
                'audits.url',
                'audits.ip_address',
                'audits.created_at',
                'users.id as user_id',
                'users.name as user_name',
            ])
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('audits.event', $event))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('audits.created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('audits.created_at', '<=', $date))
            ->when($filters['search'] ?? null, function ($query, $search) use ($encrypted, $emailUserIds) {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

                $query->where(function ($searchQuery) use ($term, $encrypted, $emailUserIds) {
                    $searchQuery->where('users.name', 'like', $term)
                        ->orWhere('audits.auditable_type', 'like', $term)
                        ->orWhere('audits.auditable_id', 'like', $term)
                        ->orWhere('audits.ip_address', 'like', $term);

                    if ($encrypted) {
                        $searchQuery->orWhereIn('users.id', $emailUserIds);
                    } else {
                        $searchQuery->orWhere('users.email', 'like', $term);
                    }
                });
            })
            ->orderByDesc('audits.id')
            ->paginate(25)
            ->withQueryString();

        $auditUsers = User::query()
            ->whereIn('id', collect($audits->items())->pluck('user_id')->filter()->unique())
            ->get(['id', 'email'])
            ->keyBy('id');

        $audits->through(function ($audit) use ($auditUsers) {
            $newValues = $this->decodeValues($audit->new_values);

            return [
                'id' => $audit->id,
                'event' => $audit->event,
                'auditable_type' => class_basename($audit->auditable_type),
                'auditable_id' => (string) $audit->auditable_id,
                'user_name' => $audit->user_name ?: 'System',
                'user_email' => $auditUsers->get($audit->user_id)?->email,
                'changed_fields' => array_values(array_unique(array_merge(
                    array_keys($this->decodeValues($audit->old_values)),
                    array_keys($newValues),
                ))),
                'details' => $audit->event === 'accessed'
                    ? sprintf(
                        '%s %s - HTTP %s (%s ms)',
                        $newValues['method'] ?? 'API',
                        $newValues['path'] ?? '/',
                        $newValues['status_code'] ?? '?',
                        $newValues['duration_ms'] ?? '?',
                    )
                    : null,
                'url' => $audit->url,
                'ip_address' => $audit->ip_address,
                'created_at' => $audit->created_at,
            ];
        });

        return Inertia::render('Admin/AuditTrail', [
            'audits' => $audits,
            'filters' => $filters,
        ]);
    }

    private function decodeValues(?string $values): array
    {
        if (! $values) {
            return [];
        }

        $decoded = json_decode($values, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function authorizeAdministrator(Request $request): void
    {
        $roles = $request->user()?->getRoleNames()->map(fn ($role) => strtolower($role))->all() ?? [];
        abort_unless(in_array('admin', $roles, true) || in_array('super-admin', $roles, true), 403);
    }
}
