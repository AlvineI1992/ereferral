<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\Permission\Traits\HasRoles;  
use Illuminate\Database\Eloquent\SoftDeletes;
class User extends Authenticatable implements CipherSweetEncrypted
{

use HasApiTokens, Notifiable, UsesCipherSweet, HasRoles, SoftDeletes;
    /**
     * Configure which fields should be encrypted with CipherSweet.
     */
    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        // Encrypt the user's name
        $encryptedRow->addField('name');
      //  $encryptedRow->addField('access_type');
    }

      protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'email',     
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
