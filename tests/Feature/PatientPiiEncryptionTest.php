<?php

use App\Jobs\EncryptPatientPii;
use App\Models\PatientEncryptionSetting;
use App\Models\PatientModel;
use App\Models\ReferralPatientInfoModel;
use App\Models\User;
use App\Services\PatientPiiEncryption;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('ciphersweet.providers.string.key', random_bytes(32));
    config()->set('queue.default', 'sync');
    Storage::fake('local');
    foreach (['referral_patientinfo', 'referral_patientdemo'] as $name) {
        Schema::create($name, function ($table) use ($name) {
            $table->string('LogID')->primary();
            foreach (array_unique([...PatientPiiEncryption::FIELDS[$name], ...(PatientPiiEncryption::IDENTITY_FIELDS[$name] ?? [])]) as $field) {
                $table->text($field)->nullable();
            }
        });
    }
});

function piiIdentity(): array
{
    return ['first_name' => 'ANA', 'middle_name' => null, 'last_name' => 'SANTOS', 'suffix' => null, 'birth_date' => '1990-03-04', 'civil_status' => 'S'];
}

test('activation backs up and encrypts patient records and immutable snapshots', function () {
    $patient = PatientModel::create([...piiIdentity(), 'contact_number' => '09123456789', 'street_address' => 'Private street']);
    ReferralPatientInfoModel::create(['LogID' => 'PII-1', 'patientFirstName' => 'ANA', 'patientLastName' => 'SANTOS']);
    DB::table('referral_patientdemo')->insert(['LogID' => 'PII-1', 'patientStreetAddress' => 'Private street']);
    DB::table('referral_pathway_steps')->insert(['log_id' => 'PII-1', 'root_log_id' => 'PII-1', 'sequence' => 1, 'snapshot' => '{"patient":{"name":"ANA"}}', 'created_at' => now()]);
    $admin = User::factory()->create(['email' => 'admin@referral.doh.gov.ph', 'status' => 'A']);
    $this->actingAs($admin)->putJson('/admin/data-encryption/patients', ['confirmation' => 'ENABLE PATIENT ENCRYPTION'])
        ->assertAccepted()->assertJsonPath('encryption.status', 'active')->assertJsonPath('encryption.processedRows', 4);
    $encryption = app(PatientPiiEncryption::class);
    expect($encryption->encrypted(DB::table('patient_master_list')->value('first_name')))->toBeTrue();
    expect($patient->fresh()->first_name)->toBe('ANA')->and($patient->fresh()->birth_date->format('Y-m-d'))->toBe('1990-03-04');
    expect(ReferralPatientInfoModel::find('PII-1')->patientLastName)->toBe('SANTOS');
    $snapshot = DB::table('referral_pathway_steps')->value('snapshot');
    expect($encryption->encrypted($snapshot))->toBeTrue()->and($encryption->readSnapshot($snapshot, 'PII-1'))->toBe(['patient' => ['name' => 'ANA']]);
    $manifest = json_decode(Crypt::decryptString(Storage::disk('local')->get(PatientEncryptionSetting::current()->backup_path)), true);
    foreach ($manifest as $chunk) {
        expect(hash('sha256', Crypt::decryptString(Storage::disk('local')->get($chunk['path']))))->toBe($chunk['checksum']);
    }
    expect($encryption->search(PatientModel::query(), ' ana ')->count())->toBe(1)
        ->and($encryption->whereIdentity(PatientModel::query(), piiIdentity())->count())->toBe(1);
    $patient->update(['first_name' => 'MARIA']);
    expect($encryption->search(PatientModel::query(), 'ANA')->count())->toBe(0)
        ->and($encryption->search(PatientModel::query(), 'MARIA')->count())->toBe(1);
});

test('conversion can resume with mixed plaintext ciphertext and soft deleted patients', function () {
    $legacy = PatientModel::create(piiIdentity());
    $legacy->delete();
    PatientEncryptionSetting::current()->update(['status' => 'converting']);
    $encrypted = PatientModel::create([...piiIdentity(), 'first_name' => 'MARIA']);
    $service = app(PatientPiiEncryption::class);
    expect($service->whereIdentity(PatientModel::withTrashed(), piiIdentity())->count())->toBe(1);
    (new EncryptPatientPii)->handle($service);
    PatientEncryptionSetting::current()->update(['status' => 'converting']);
    (new EncryptPatientPii)->handle($service);
    expect(PatientModel::withTrashed()->find($legacy->id)->first_name)->toBe('ANA')
        ->and($encrypted->fresh()->first_name)->toBe('MARIA')
        ->and(PatientEncryptionSetting::current()->processed_rows)->toBe(2);
});

test('failed conversion keeps new writes encrypted and rejects corrupt ciphertext', function () {
    PatientEncryptionSetting::current()->update(['status' => 'failed']);
    $patient = PatientModel::create(piiIdentity());
    expect(app(PatientPiiEncryption::class)->encrypted($patient->getRawOriginal('first_name')))->toBeTrue();
    DB::table('patient_master_list')->where('id', $patient->id)->update(['first_name' => 'nacl:corrupt']);
    PatientEncryptionSetting::current()->update(['status' => 'converting']);
    expect(fn () => (new EncryptPatientPii)->handle(app(PatientPiiEncryption::class)))->toThrow(RuntimeException::class);
    expect(PatientEncryptionSetting::current()->status)->toBe('failed');
    expect(DB::table('patient_master_list')->where('id', $patient->id)->value('first_name'))->toBe('nacl:corrupt');
});

test('patient activation requires administrator access and exact confirmation', function () {
    $user = User::factory()->create(['status' => 'A']);
    $this->actingAs($user)->getJson('/admin/data-encryption/patients/status')->assertForbidden();
    $this->putJson('/admin/data-encryption/patients', ['confirmation' => 'ENABLE PATIENT ENCRYPTION'])->assertForbidden();
    $admin = User::factory()->create(['email' => 'admin@referral.doh.gov.ph', 'status' => 'A']);
    $this->actingAs($admin)->putJson('/admin/data-encryption/patients', ['confirmation' => 'ENABLE'])->assertUnprocessable();
    expect(PatientEncryptionSetting::current()->status)->toBe('inactive');
});

test('encrypted snapshots cannot be moved to a different referral', function () {
    PatientEncryptionSetting::current()->update(['status' => 'active']);
    $service = app(PatientPiiEncryption::class);
    $snapshot = $service->snapshot(['patient' => 'ANA'], 'ONE');
    expect(fn () => $service->readSnapshot($snapshot, 'TWO'))->toThrow(SodiumException::class);
});
