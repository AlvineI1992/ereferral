<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class ReferralPatientInfoModel extends Model
{
    use HasFactory; use HasRoles;
    
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
  
 
}
