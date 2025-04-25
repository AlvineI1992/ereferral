<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleModel extends Authenticatable
{
   
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use SoftDeletes;

    // Optionally, you can set the name of the column that stores the deleted timestamp
    protected $dates = ['deleted_at'];


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
     /**
     * The primary key for the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the primary key is an incrementing integer.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $fillable = [
        'id',
        'name',
        'guard_name'
    ];

    

   
}
