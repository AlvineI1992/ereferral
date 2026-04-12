<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefBarangayModel extends Model
{
    use HasFactory;

    protected $table = 'ref_barangay';
    protected $primaryKey = 'bgycode';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'bgycode',
        'regcode',
        'provcode',
        'citycode',
        'bgyname',
        'nscb_brgy_code',
        'nscb_brgy_name',
        'addedby',
        'UserLevelID',
        'dateupdated',
        'status',
        'newcode',
    ];

    public function city()
    {
        return $this->belongsTo(RefCityModel::class, 'citycode', 'citycode');
    }

    public function province()
    {
        return $this->belongsTo(RefProvinceModel::class, 'provcode', 'provcode');
    }

    public function region()
    {
        return $this->belongsTo(RefRegionModel::class, 'regcode', 'regcode');
    }
}
