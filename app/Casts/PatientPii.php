<?php

namespace App\Casts;

use App\Services\PatientPiiEncryption;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PatientPii implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        $plain = app(PatientPiiEncryption::class)->decrypt($model->getTable(), $key, $value);

        return $key === 'birth_date' && $plain !== null ? Carbon::parse($plain)->startOfDay() : $plain;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }
        $encryption = app(PatientPiiEncryption::class);

        return $encryption->enabled() ? $encryption->encrypt($model->getTable(), $key, $value) : $value;
    }
}
