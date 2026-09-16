<?php

namespace App\Models;

use App\Services\PatientPiiEncryption;

class PatientEncryptionSetting extends DataEncryptionSetting
{
    protected $table = 'patient_encryption_settings';

    protected static function booted(): void
    {
        static::saved(fn () => app(PatientPiiEncryption::class)->forget());
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], ['enabled' => false, 'status' => 'inactive']);
    }
}
