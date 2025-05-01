<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class RefCityModel extends Model
{
    use HasFactory; use HasRoles;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ref_city';

    /**
     * The primary key for the table.
     *
     * @var string
     */
    protected $primaryKey = 'citycode';

    /**
     * Indicates if the primary key is an incrementing integer.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'citycode',
        'provcode',
        'provname',
        'nscb_prov_code',
        'nscb_prov_name',
        'UserLevelID',
        'addedby',
        'dateupdated',
        'status',
    ];

    public function barangays()
    {
        return $this->hasMany(RefBarangayModel::class, 'citycode', 'citycode');
    }
}
