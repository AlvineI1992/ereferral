<?php

namespace App\Http\Controllers;

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
            'event' => ['nullable', Rule::in(['created', 'updated', 'deleted', 'restored'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

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
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('audits.event', $event))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('audits.created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('audits.created_at', '<=', $date))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

                $query->where(function ($searchQuery) use ($term) {
                    $searchQuery->where('users.name', 'like', $term)
                        ->orWhere('users.email', 'like', $term)
                        ->orWhere('audits.auditable_type', 'like', $term)
                        ->orWhere('audits.auditable_id', 'like', $term)
                        ->orWhere('audits.ip_address', 'like', $term);
                });
            })
            ->orderByDesc('audits.id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($audit) => [
                'id' => $audit->id,
                'event' => $audit->event,
                'auditable_type' => class_basename($audit->auditable_type),
                'auditable_id' => (string) $audit->auditable_id,
                'user_name' => $audit->user_name ?: 'System',
                'user_email' => $audit->user_email,
                'changed_fields' => array_values(array_unique(array_merge(
                    array_keys($this->decodeValues($audit->old_values)),
                    array_keys($this->decodeValues($audit->new_values)),
                ))),
                'url' => $audit->url,
                'ip_address' => $audit->ip_address,
                'created_at' => $audit->created_at,
            ]);

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
