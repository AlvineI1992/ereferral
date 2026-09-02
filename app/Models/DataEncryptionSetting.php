<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataEncryptionSetting extends Model
{
    protected $fillable = [
        'enabled', 'status', 'processed_rows', 'total_rows', 'last_error',
        'activated_by', 'activated_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'processed_rows' => 'integer',
            'total_rows' => 'integer',
            'activated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], ['enabled' => false, 'status' => 'inactive']);
    }
}
