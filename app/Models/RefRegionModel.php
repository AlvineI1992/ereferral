<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
class RefRegionModel extends Model
{
    use HasFactory; use HasRoles;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ref_region';

    /**
     * The primary key for the table.
     *
     * @var string
     */
    protected $primaryKey = 'regcode';

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
        'regcode',
        'regname',
        'regabbrev',
        'nscb_reg_code',
        'nscb_reg_name',
        'UserLevelID',
        'addedby',
        'dateupdated',
        'status',
    ];
}
