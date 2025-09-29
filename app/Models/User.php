<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;
use ParagonIE\CipherSweet\EncryptedRow; // ✅ Use ParagonIE's class
use ParagonIE\CipherSweet\BlindIndex;

class User extends Authenticatable implements AuditableContract, CipherSweetEncrypted
{
    use HasFactory,
        Notifiable,
        SoftDeletes,
        AuditableTrait,
        HasRoles,
        HasApiTokens,
        UsesCipherSweet;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'access_id',
        'access_type',
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
        ];
    }

    public static function configureCipherSweet(EncryptedRow $row): void
    {
        // Encrypt email
        $row->addField('email');

        // Encrypt + searchable with blind index
        $row->addField('name')
            ->addBlindIndex('name', new BlindIndex('name_index', ['name'], 16, false));
    }
}
