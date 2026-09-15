<?php

namespace App\Models\Concerns;

use App\Casts\PatientPii;
use App\Services\PatientPiiEncryption;

trait EncryptsPatientPii
{
    public function initializeEncryptsPatientPii(): void
    {
        $this->mergeCasts(array_fill_keys(PatientPiiEncryption::FIELDS[$this->getTable()], PatientPii::class));
    }

    public static function bootEncryptsPatientPii(): void
    {
        static::saved(fn ($model) => app(PatientPiiEncryption::class)->syncIndexes($model));
        static::deleted(function ($model) {
            if (app(PatientPiiEncryption::class)->enabled()) {
                $model->getConnection()->table('patient_pii_indexes')
                    ->where('table_name', $model->getTable())->where('record_id', (string) $model->getKey())->delete();
            }
        });
    }

    public function save(array $options = [])
    {
        return $this->getConnection()->transaction(fn () => parent::save($options));
    }
}
