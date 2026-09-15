<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class DatabaseMaintenanceService
{
    public const SEEDERS = [
        'permissions' => ['class' => \Database\Seeders\PermissionSeeder::class, 'label' => 'Application permissions (includes API permissions)'],
        'api-permissions' => ['class' => \Database\Seeders\ApiPermissionSeeder::class, 'label' => 'API endpoint permissions'],
    ];

    public function status(): array
    {
        $handle = fopen(storage_path('framework/database-maintenance.lock'), 'c');
        $running = false;
        if ($handle) {
            $running = ! flock($handle, LOCK_EX | LOCK_NB);
            if (! $running) flock($handle, LOCK_UN);
            fclose($handle);
        }
        $migrator = app('migrator');
        $ran = $migrator->repositoryExists() ? $migrator->getRepository()->getRan() : [];
        $files = $migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')]));

        return [
            'running' => $running,
            'migrations' => collect($files)->map(fn ($path, $name) => ['name' => $name, 'ran' => in_array($name, $ran, true)])->values()->all(),
            'seeders' => collect(self::SEEDERS)->map(fn ($seeder, $key) => ['key' => $key, 'label' => $seeder['label']])->values()->all(),
            'history' => Cache::store('file')->get('database-maintenance-history', []),
        ];
    }

    public function run(string $action, ?string $seeder, int $actor): array
    {
        abort_unless($action === 'migrate' || ($action === 'seed' && isset(self::SEEDERS[$seeder])), 422);
        $handle = fopen(storage_path('framework/database-maintenance.lock'), 'c');
        abort_unless($handle, 503, 'Unable to acquire the maintenance lock.');
        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            abort(409, 'Another database maintenance operation is running.');
        }
        $record = ['id' => (string) Str::uuid(), 'action' => $action, 'seeder' => $seeder, 'actor_id' => $actor,
            'started_at' => now()->toIso8601String(), 'finished_at' => null, 'status' => 'running', 'output' => ''];
        try {
            $this->remember($record);
            $command = $action === 'migrate' ? 'migrate' : 'db:seed';
            $arguments = ['--force' => true, '--no-interaction' => true];
            if ($action === 'seed') $arguments['--class'] = self::SEEDERS[$seeder]['class'];
            $exitCode = Artisan::call($command, $arguments);
            $record['status'] = $exitCode === 0 ? 'completed' : 'failed';
            $record['output'] = $exitCode === 0 ? substr(Artisan::output(), -20000) : 'Command failed. Review the application logs before retrying.';
        } catch (Throwable $exception) {
            report($exception);
            $record['status'] = 'failed';
            $record['output'] = 'Operation failed. Review the application logs before retrying; some changes may already have been applied.';
        } finally {
            $record['finished_at'] = now()->toIso8601String();
            try { $this->remember($record); }
            finally { flock($handle, LOCK_UN); fclose($handle); }
        }
        return $record;
    }

    private function remember(array $record): void
    {
        $cache = Cache::store('file');
        $history = collect($cache->get('database-maintenance-history', []))->reject(fn ($entry) => $entry['id'] === $record['id']);
        $cache->forever('database-maintenance-history', $history->prepend($record)->take(10)->values()->all());
    }
}
