<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralAttachment extends Model
{
    protected $fillable = [
        'LogID',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'sha256',
        'uploaded_by',
    ];

    protected $hidden = ['disk', 'path', 'sha256'];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'uploaded_by' => 'integer',
        ];
    }
}
