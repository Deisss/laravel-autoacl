<?php

namespace Deisss\Autoacl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Manage access control list modules.
 *
 * Class Module
 * @package Deisss\Autoacl\Models
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property \Deisss\Autoacl\Models\Role[] $roles
 * @property \Deisss\Autoacl\Models\Method[] $methods
 */
class Module extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'acl_modules';


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['pivot', 'deleted_at'];


    /**
     * The attributes appended to the model's JSON form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];



    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(
            // Model
            'Deisss\Autoacl\Models\Role',
            // Pivot table
            'acl_role_has_acl_modules',
            // "Our" key
            'acl_module_id',
            // "Their" key
            'acl_role_id'
        )
        ->withPivot('is_allowed')
        ->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function methods()
    {
        return $this->hasMany(
            // Model
            'Deisss\Autoacl\Models\Method',
            // Foreign key
            'acl_module_id',
            // Local key
            'id'
        );
    }
}
