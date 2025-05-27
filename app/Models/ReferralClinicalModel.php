<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class ReferralClinicalModel extends Model
{
    use HasFactory;
    use HasRoles;

    protected $table = 'referral_clinical';

    protected $primaryKey = 'LogID';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'LogID',
        'clinicalDiagnosis',
        'clinicalHistory',
        'physicalExamination',
        'chiefComplaint',
        'findings',
        'vitals'
    ];

    public function getRouteKeyName()
    {
        return 'LogID';
    }

    public function getRouteKey()
    {
        return base64_encode($this->getKey());
    }
}
