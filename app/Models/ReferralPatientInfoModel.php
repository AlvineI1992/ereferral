<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class ReferralPatientInfoModel extends Model implements CipherSweetEncrypted
{
    use HasFactory, UsesCipherSweet;
    
    protected $table = 'referral_patientinfo';
    protected $primaryKey = 'LogID';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'LogID',
        'FamilyID',
        'phicNum',
        'caseNum',
        'patientLastName',
        'patientFirstName',
        'patientSuffix',
        'patientMiddlename',
        'patientBirthDate',
        'patientSex',
        'patientContactNumber',
        'patientReligion',
        'patientBloodType',
        'patientBloodTypeRH',
        'patientCivilStatus',
    ];

    /**
     * Configure which fields should be encrypted.
     */
    public static function configureCipherSweet(\ParagonIE\CipherSweet\EncryptedRow $encryptedRow): void
    {
        // Encrypt sensitive fields
       
        $encryptedRow->addField('phicNum');
        $encryptedRow->addField('patientLastName');
        $encryptedRow->addField('patientFirstName');
        $encryptedRow->addField('patientMiddlename');
         $encryptedRow->addField('patientSex');
        $encryptedRow->addField('patientBirthDate');
        $encryptedRow->addField('patientContactNumber');
        $encryptedRow->addField('patientReligion');
    }

    public function demographics()
    {
        return $this->hasOne(ReferralPatientDemoModel::class, 'LogID', 'LogID');
    }   
}

