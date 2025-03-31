<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class PatientModel extends Model
{
    use HasFactory; 

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'referral_patientinfo';

    /**
     * The primary key for the table.
     *
     * @var string
     */
    protected $primaryKey = 'LogID';

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
    /* protected $fillable = [
        'hfhudcode',
        'facility_name',
        'fhudaddress',
        'status'
    ]; */
}
