<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class ReferralTrackModel extends Model
{
    use HasFactory; use HasRoles;
    protected $table = 'referral_track';
    protected $primaryKey = 'LogID';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'LogID',
        'receivedDate',
        'receivedPerson',
        'admDate',
        'dischDate',
        'RefID',
        'trackRemarks',
        'status',
        'admDisp',
        'admCond',
        'dischDisp',
        'dischCond',
        'disnotes',
        'hasFollowup',
        'hasMedicine',
        'diagtext',
        'diagnosis',
        'specialinstruct',
    ];

    protected $casts = [
        'receivedDate' => 'datetime',
        'admDate' => 'datetime',
        'dischDate' => 'datetime',
        'hasFollowup' => 'boolean',
        'hasMedicine' => 'boolean',
    ];
    
 
}
