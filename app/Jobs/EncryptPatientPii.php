<?php

namespace App\Jobs;

use App\Models\PatientEncryptionSetting;
use App\Models\PatientModel;
use App\Models\ReferralPatientDemoModel;
use App\Models\ReferralPatientInfoModel;
use App\Services\PatientPiiEncryption;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Throwable;

class EncryptPatientPii implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1200;

    public int $tries = 1;

    public function middleware(): array
    {
        return [(new WithoutOverlapping('patient-pii-conversion'))->expireAfter(1300)];
    }

    public function handle(PatientPiiEncryption $encryption): void
    {
        $setting = PatientEncryptionSetting::current();
        if ($setting->status !== 'converting') {
            return;
        }
        try {
            foreach ([PatientModel::class, ReferralPatientInfoModel::class, ReferralPatientDemoModel::class] as $position => $class) {
                if ($position < $setting->conversion_table) {
                    continue;
                }
                $model = new $class;
                $table = $model->getTable();
                $key = $model->getKeyName();
                DB::table($table)->select($key)
                    ->when($setting->last_record_id !== null, fn ($query) => $query->where($key, '>', $setting->last_record_id))
                    ->chunkById(100, function ($records) use ($class, $table, $key, $encryption, $setting) {
                        foreach ($records as $record) {
                            DB::transaction(function () use ($class, $table, $key, $record, $encryption, $setting) {
                                $row = DB::table($table)->where($key, $record->{$key})->lockForUpdate()->first();
                                if (! $row) {
                                    return;
                                }
                                $plain = $encryption->decryptRecord($table, $row);
                                $updates = [];
                                foreach (PatientPiiEncryption::FIELDS[$table] as $column) {
                                    $updates[$column] = $encryption->encrypt($table, $column, $plain->{$column} ?? null);
                                }
                                DB::table($table)->where($key, $record->{$key})->update($updates);
                                $model = (new $class)->newFromBuilder(array_replace((array) $row, $updates));
                                $encryption->syncIndexes($model);
                                $setting->forceFill(['last_record_id' => (string) $record->{$key}, 'processed_rows' => $setting->processed_rows + 1])->save();
                            });
                        }
                    }, $key);
                $setting->forceFill(['conversion_table' => $position + 1, 'last_record_id' => null])->save();
            }
            DB::table('referral_pathway_steps')->select('log_id')
                ->when($setting->last_record_id !== null, fn ($query) => $query->where('log_id', '>', $setting->last_record_id))
                ->chunkById(100, function ($steps) use ($encryption, $setting) {
                    foreach ($steps as $step) {
                        DB::transaction(function () use ($step, $encryption, $setting) {
                            $row = DB::table('referral_pathway_steps')->where('log_id', $step->log_id)->lockForUpdate()->first();
                            if ($row) {
                                $snapshot = $encryption->readSnapshot($row->snapshot, $row->log_id);
                                DB::table('referral_pathway_steps')->where('log_id', $row->log_id)
                                    ->update(['snapshot' => $encryption->snapshot($snapshot, $row->log_id)]);
                                $setting->forceFill(['last_record_id' => $row->log_id, 'processed_rows' => $setting->processed_rows + 1])->save();
                            }
                        });
                    }
                }, 'log_id');
            $setting->refresh()->update(['enabled' => true, 'status' => 'active', 'total_rows' => $setting->processed_rows, 'completed_at' => now(), 'last_error' => null]);
        } catch (Throwable $exception) {
            $this->failed($exception);
            // Never put patient values or SQL bindings in failed-job exception storage.
            throw new \RuntimeException('Patient conversion failed; existing encrypted records remain protected.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        PatientEncryptionSetting::current()->update(['enabled' => false, 'status' => 'failed', 'last_error' => 'Conversion interrupted. Check database and key availability, then retry activation.']);
    }
}
