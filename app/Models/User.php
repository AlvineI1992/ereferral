<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;
use ParagonIE\CipherSweet\EncryptedRow;

class User extends Authenticatable implements 
    Auditable,
    CipherSweetEncrypted
{
    use HasFactory,
        Notifiable,
        SoftDeletes,
        \OwenIt\Auditing\Auditable,
        HasRoles,
        HasApiTokens,
        UsesCipherSweet; // ✅ REQUIRED

    protected $guard_name = 'web';

    protected $dates = ['deleted_at'];

    /**
     * Configure encrypted fields
     */
    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
       /*  $encryptedRow
            ->addField('name'); // encrypted
 */
        // Example if you also want:
        // $encryptedRow->addField('access_type');
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'access_id',
        'access_type'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
        ];
    }
}
