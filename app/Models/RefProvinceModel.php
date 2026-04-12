<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class RefProvinceModel extends Model
{
    use HasFactory;
    use HasRoles;

    protected $table = 'ref_province';
    protected $primaryKey = 'provcode';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'provcode',
        'regcode',
        'provname',
        'nscb_prov_code',
        'nscb_prov_name',
        'addedby',
        'UserLevelID',
        'dateupdated',
        'status',
        'newcode',
    ];

    public function cities()
    {
        return $this->hasMany(RefCityModel::class, 'provcode', 'provcode');
    }

    public function region()
    {
        return $this->belongsTo(RefRegionModel::class, 'regcode', 'regcode');
    }
}
