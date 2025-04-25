<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as OriginalPermission;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermissionModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'permissions';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'name',
        'guard_name',
        'updated_at',
        'created_at'
    ];
}
