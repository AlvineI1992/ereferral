<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class ReferralClinicalModel extends Model
{
    use HasFactory; use HasRoles;
    // Define the table associated with the model
    protected $table = 'referral_clinical';

    // Define the primary key if it's not the default 'id'
    protected $primaryKey = 'LogID';

    // Disable the automatic timestamps if your table doesn't have created_at/updated_at
    public $timestamps = false;

    // Define the attributes that are mass assignable
    protected $fillable = [
        'clinicalDiagnosis',
        'clinicalHistory',
        'physicalExamination',
        'chiefComplaint',
        'findings',
        'vitals'
    ];
}
