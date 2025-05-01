<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class RefBarangayModel extends Model
{
    use HasFactory; 

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ref_barangay';

    /**
     * The primary key for the table.
     *
     * @var string
     */
    protected $primaryKey = 'bgycode';

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
}
