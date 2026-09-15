<?php

namespace App\Models;

class PatientEncryptionSetting extends DataEncryptionSetting
{
    protected $table = 'patient_encryption_settings';

    public static function current(): self
    {
        return self::query()->firstOrCreate([], ['enabled' => false, 'status' => 'inactive']);
    }
}
