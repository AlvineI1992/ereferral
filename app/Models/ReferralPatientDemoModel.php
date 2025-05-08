<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class ReferralPatientDemoModel extends Model
{
    use HasFactory; use HasRoles;

    protected $table = 'referral_patientdemo';
    protected $primaryKey = 'LogID';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'LogID',
        'patientStreetAddress',
        'patientBrgyCode',
        'patientMundCode',
        'patientProvCode',
        'patientRegCode',
        'patientZipCode',
    ];

    public function region()
    {
        return $this->hasOne(RefRegionModel::class, 'regcode', 'patientRegCode');
    }

    public function province()
    {
        return $this->hasOne(RefProvinceModel::class, 'provcode', 'patientProvCode');
    }

    public function city()
    {
        return $this->hasOne(RefCityModel::class, 'citycode', 'patientMundCode');
    }

    public function barangay()
    {
        return $this->hasOne(RefBarangayModel::class, 'bgycode', 'patientBrgyCode');
    }
   
}
