<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralStatusModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'referral_status'; // your table name
     protected $primaryKey = 'LogID';

    protected $fillable = [
        'LogID',
        'referral_status',
        'remarks',
        'referral_fhud',
        'entryby'
    ];

   
}
