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
}
