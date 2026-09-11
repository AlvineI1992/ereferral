<?php

use App\Models\DataEncryptionSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('ciphersweet.providers.string.key', random_bytes(32));
    config()->set('queue.default', 'sync');
    Storage::fake('local');
});

test('an administrator can activate encryption after a verified backup', function () {
    $role = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
    $administrator = User::factory()->create(['email' => 'admin@example.com', 'status' => 'A']);
    $administrator->assignRole($role);

    $response = $this->actingAs($administrator)->putJson('/admin/data-encryption', [
        'enabled' => true,
        'confirmation' => 'ENABLE ENCRYPTION',
    ]);

    $response->assertAccepted()->assertJsonPath('encryption.enabled', true);

    $setting = DataEncryptionSetting::current();
    expect($setting->status)->toBe('active')
        ->and($setting->processed_rows)->toBe(1)
        ->and($setting->backup_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($setting->backup_path))->toBeTrue()
        ->and(DB::table('users')->where('id', $administrator->id)->value('email'))->not->toBe('admin@example.com')
        ->and(User::findOrFail($administrator->id)->email)->toBe('admin@example.com')
        ->and(User::whereBlind('email', 'email_index', 'admin@example.com')->exists())->toBeTrue();

    Auth::guard('web')->logout();
    expect(Auth::guard('web')->attempt(['email' => 'admin@example.com', 'password' => 'password']))->toBeTrue();
});

test('activation requires the exact confirmation phrase', function () {
    $role = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
    $administrator = User::factory()->create(['status' => 'A']);
    $administrator->assignRole($role);

    $this->actingAs($administrator)->putJson('/admin/data-encryption', [
        'enabled' => true,
        'confirmation' => 'ENABLE',
    ])->assertUnprocessable();

    expect(DataEncryptionSetting::current()->enabled)->toBeFalse();
});
