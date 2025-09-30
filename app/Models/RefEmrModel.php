<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class RefEmrModel extends Model  implements CipherSweetEncrypted
{
    use HasFactory, HasRoles, SoftDeletes, UsesCipherSweet;

    protected $table = 'ref_emr';
    protected $primaryKey = 'emr_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'emr_name',
        'status',
        'remarks',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Configure which fields should be encrypted.
     */
    public static function configureCipherSweet(\ParagonIE\CipherSweet\EncryptedRow $encryptedRow): void
    {
        // Encrypt sensitive fields
        $encryptedRow->addField('emr_name');
    
        // If status is sensitive too, you can add it:
        // $encryptedRow->addField('status');
    }
}
