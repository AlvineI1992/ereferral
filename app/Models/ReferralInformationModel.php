<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class ReferralInformationModel extends Model
{
    use HasFactory; use HasRoles;

    protected $table = 'referral_information';
    protected $primaryKey = 'LogID';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'LogID',
        'fhudFrom',
        'fhudTo',
        'typeOfReferral',
        'referralReason',
        'otherReasons',
        'remarks',
        'referringProvider',
        'referralCategory',
        'referringProviderContactNumber',
        'referralContactPerson',
        'referralContactPersonDesignation',
        'rprhreferral',
        'rprhreferralmethod',
        'status',
        'refferalDate',
        'refferalTime',
        'srfcode',
        'logDate',
        'tdcode',
        'licno',
        'patientPan',
        'specialinstruct',
        'created_at',
    ];

    protected $casts = [
        'logDate' => 'datetime',
        'created_at' => 'datetime',
    ];

  
    public function demographics()
    {
        return $this->hasOne(ReferralPatientDemoModel::class, 'LogID', 'LogID');
    }

    public function clinical()
    {
        return $this->hasOne(ReferralClinicalModel::class, 'LogID', 'LogID');
    }
    public function patientinformation()
    {
        return $this->hasOne(ReferralPatientInfoModel::class, 'LogID', 'LogID');
    }

    public function medication()
    {
        return $this->hasMany(ReferralMedicationModel::class, 'LogID', 'LogID');
    }

    public function facility_to()
    {
        return $this->hasOne(RefFacilitiesModel::class, 'hfhudcode', 'fhudTo');
    }

    public function facility_from()
    {
        return $this->hasOne(RefFacilitiesModel::class, 'hfhudcode', 'fhudFrom');
    }

    public function destination()
    {
        return $this->belongsTo(RefFacilitiesModel::class, 'emr_id', 'emr_id');
    }

    public function track()
    {
        return $this->belongsTo(ReferralTrackModel::class, 'LogID', 'LogID');
    }

}
