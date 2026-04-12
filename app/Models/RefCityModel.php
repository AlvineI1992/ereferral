<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class RefCityModel extends Model
{
    use HasFactory;
    use HasRoles;

    protected $table = 'ref_city';
    protected $primaryKey = 'citycode';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'citycode',
        'provcode',
        'regcode',
        'cityname',
        'nscb_city_code',
        'nscb_city_name',
        'cityclassification',
        'chartered',
        'addedby',
        'UserLevelID',
        'dateupdated',
        'status',
        'newcode',
    ];

    public function barangays()
    {
        return $this->hasMany(RefBarangayModel::class, 'citycode', 'citycode');
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
