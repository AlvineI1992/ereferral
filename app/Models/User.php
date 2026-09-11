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
use ParagonIE\CipherSweet\BlindIndex;
use App\Services\DataEncryptionManager;
use ParagonIE\CipherSweet\Exception\InvalidCiphertextException;
use Spatie\LaravelCipherSweet\CipherSweetDecryption;
use Spatie\LaravelCipherSweet\Observers\ModelObserver;

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

    public static function bootUsesCipherSweet(): void
    {
        static::$cipherSweetEncryptedRow = null;

        static::retrieved(function (self $model) {
            if (! app(DataEncryptionManager::class)->isEnabled()) {
                return;
            }

            if (CipherSweetDecryption::isSuspended()) {
                $model->cipherSweetRowIsEncrypted = true;
                return;
            }

            try {
                app(ModelObserver::class)->retrieved($model);
            } catch (InvalidCiphertextException) {
                $model->cipherSweetRowIsEncrypted = false;
            }
        });
        static::saving(function (self $model) {
            if (app(DataEncryptionManager::class)->isEnabled()) {
                app(ModelObserver::class)->saving($model);
            }
        });
        static::saved(function (self $model) {
            if (app(DataEncryptionManager::class)->isEnabled()) {
                app(ModelObserver::class)->saved($model);
            }
        });
        static::deleting(function (self $model) {
            if (app(DataEncryptionManager::class)->isEnabled()) {
                app(ModelObserver::class)->deleting($model);
            }
        });
    }

    /**
     * Configure encrypted fields
     */
    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        if (! app(DataEncryptionManager::class)->isEnabled()) {
            return;
        }

        self::configureEncryptionSchema($encryptedRow);
    }

    public static function configureEncryptionSchema(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addTextField('email')
            ->addBlindIndex('email', new BlindIndex('email_index'));
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
