<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class ReferralMedicationModel extends Model
{
    use HasFactory; use HasRoles;
    
    protected $table = 'referral_medicine';
    protected $primaryKey = 'LogID';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'LogID',
         'drugcode',
         'generic',
         'instructions',
         
    ];
   
    public function demographics()
    {
        return $this->hasOne(ReferralPatientDemoModel::class, 'LogID', 'LogID');
    }
 
}
