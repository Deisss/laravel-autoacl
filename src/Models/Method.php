<?php

namespace Deisss\Autoacl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Manage access control list methods.
 *
 * Class Method
 * @package Deisss\Autoacl\Models
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property integer $acl_module_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property \Deisss\Autoacl\Models\Role[] $roles
 * @property \Deisss\Autoacl\Models\Module $module
 */
class Method extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'acl_methods';


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
            'acl_role_has_acl_methods',
            // "Our" key
            'acl_method_id',
            // "Their" key
            'acl_role_id'
        )
        ->withPivot('is_allowed')
        ->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function module()
    {
        return $this->belongsTo(
            // Model
            'Deisss\Autoacl\Models\Module',
            // Foreign key
            'id',
            // Other key
            'acl_module_id'
        );
    }

    /**
     * Set a given module as the parent of the method.
     *
     * @param integer|Module $module The module to link as parent of this method.
     * @return boolean True if everything went fine, false if it did not succeed (like you submit null value for $module
     * will fail this method).
     */
    public function setModule($module)
    {
        $moduleId = 0;
        if (is_integer($module)) {
            $moduleId = intval($module);
        } else if ($module instanceof Module) {
            $moduleId = intval($module->id);
        }

        if ($moduleId > 0) {
            $this->acl_module_id = $moduleId;
            return true;
        } else {
            return false;
        }
    }
}
