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
        static::saving(function ($model) {
            $encryption = app(PatientPiiEncryption::class);
            $encryption->forget();
            if ($encryption->enabled()) {
                foreach (PatientPiiEncryption::FIELDS[$model->getTable()] as $field) {
                    $value = $model->getAttributes()[$field] ?? null;
                    if ($value !== null && ! $encryption->encrypted($value)) {
                        $model->setAttribute($field, $value);
                    }
                }
            }
        });
        static::saved(fn ($model) => app(PatientPiiEncryption::class)->syncIndexes($model));
        static::deleted(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }
            if (app(PatientPiiEncryption::class)->enabled()) {
                $model->getConnection()->table('patient_pii_indexes')
                    ->where('table_name', $model->getTable())->where('record_id', (string) $model->getKey())->delete();
            }
        });
    }

    public function setRawAttributes(array $attributes, $sync = false)
    {
        foreach (PatientPiiEncryption::FIELDS[$this->getTable()] as $field) {
            foreach (array_keys($attributes) as $key) {
                if ($key !== $field && strcasecmp($key, $field) === 0) {
                    $attributes[$field] = $attributes[$key];
                    unset($attributes[$key]);
                }
            }
        }

        return parent::setRawAttributes($attributes, $sync);
    }

    public function getAttribute($key)
    {
        foreach (PatientPiiEncryption::FIELDS[$this->getTable()] as $field) {
            if (is_string($key) && strcasecmp($key, $field) === 0) {
                $key = $field;
                break;
            }
        }

        return parent::getAttribute($key);
    }

    public function save(array $options = [])
    {
        return $this->getConnection()->transaction(fn () => parent::save($options));
    }
}
