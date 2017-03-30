<?php

namespace Deisss\Autoacl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Manage access control list roles.
 *
 * Class Role
 * @package Deisss\Autoacl\Models
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $cache_credentials
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property \Deisss\Autoacl\Models\Method[] $methods
 * @property \Deisss\Autoacl\Models\Module[] $modules
 */
class Role extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'acl_roles';


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
    public function methods()
    {
        return $this->belongsToMany(
            // Model
            'Deisss\Autoacl\Models\Method',
            // Pivot table
            'acl_role_has_acl_methods',
            // "Our" key
            'acl_role_id',
            // "Their" key
            'acl_method_id'
        )
        ->withPivot('is_allowed')
        ->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function modules()
    {
        return $this->belongsToMany(
            // Model
            'Deisss\Autoacl\Models\Module',
            // Pivot table
            'acl_role_has_acl_modules',
            // "Our" key
            'acl_role_id',
            // "Their" key
            'acl_module_id'
        )
        ->withPivot('is_allowed')
        ->withTimestamps();
    }

    /**
     * Update the credentials. Based on current modules and methods linked to this role.
     */
    public function updateCacheCredentials()
    {
        // Get database content
        $modules = $this->modules()->getResults();
        $methods = $this->methods()->getResults();

        // Filtering the array of allowed/deny system
        $moduleIds = [];

        foreach ($modules as $module) {
            $moduleIds[$module->id] = [
                'name' => $module->name,
                'is_allowed' => (boolean) $module->pivot->is_allowed,
                'methods' => []
            ];
        }

        foreach ($methods as $method) {
            // We need to be sure this method is also OK on the module side
            // If not, it's disallow by default
            // TODO: if the method is specifically disabled but does not have module what happen?
            if (array_key_exists($method->acl_module_id, $moduleIds)) {
                // Now we need override the method allowed or not if the module is disallow...
                if ($moduleIds[$method->acl_module_id]['is_allowed'] === false) {
                    $moduleIds[$method->acl_module_id]['methods'][$method->id] = [
                        'name' => $method->name,
                        'is_allowed' => false
                    ];
                } else {
                    $moduleIds[$method->acl_module_id]['methods'][$method->id] = [
                        'name' => $method->name,
                        'is_allowed' => (boolean) $method->pivot->is_allowed
                    ];
                }
            }
        }

        // Now we need to "pivot" everything
        $result = [];
        foreach ($moduleIds as $moduleId => $module) {
            $result[$module['name']] = [
                'id' => $moduleId,
                'is_allowed' => $module['is_allowed'],
                'methods' => []
            ];

            foreach ($module['methods'] as $methodId => $method) {
                $result[$module['name']]['methods'][$method['name']] = [
                    'id' => $methodId,
                    'is_allowed' => $method['is_allowed']
                ];
            }
        }

        // Pushing to this element as a JSON document
        $this->cache_credentials = json_encode($result);
    }

    /**
     * Add a module to this role.
     *
     * @param integer|Module $module The module to link.
     * @param boolean $isAllowed If the link is marked as "allowed" or deny (a role can strictly deny something - default: true).
     * @param boolean|null $autoSave Auto-save the model (default: true).
     * @return boolean True if it has succeed to be added, false otherwise.
     */
    public function addModule($module, $isAllowed = true, $autoSave = true)
    {
        // The model has to exists before
        if ($autoSave && !$this->exists) {
            $this->save();
        }

        $moduleId = 0;
        if (is_integer($module)) {
            $moduleId = intval($module);
        } else if ($module instanceof Module) {
            $moduleId = intval($module->id);
        }

        if ($moduleId > 0) {
            try {
                $this->modules()->attach($moduleId, ['is_allowed' => $isAllowed]);
                $this->updateCacheCredentials();

                if ($autoSave) {
                    $this->save();
                }

                return true;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Remove a module from this role.
     *
     * @param integer|Module $module The module to unlink.
     * @param boolean|null $autoSave Auto-save the model (default: true).
     * @return boolean True if it has been removed, false otherwise.
     */
    public function removeModule($module, $autoSave = true)
    {
        // The model has to exists before
        if ($autoSave && !$this->exists) {
            $this->save();
        }

        $moduleId = 0;
        if (is_integer($module)) {
            $moduleId = intval($module);
        } else if ($module instanceof Module) {
            $moduleId = intval($module->id);
        }

        if ($moduleId > 0) {
            try {
                $this->methods()->detach($moduleId);
                $this->updateCacheCredentials();

                if ($autoSave) {
                    $this->save();
                }

                return true;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Add a method to this role.
     *
     * @param integer|Method $method The method to link.
     * @param boolean $isAllowed If the link is marked as "allowed" or deny (a role can strictly deny something - default: true).
     * @param boolean|null $autoSave Auto-save the model (default: true).
     * @return boolean True if it has succeed to be added, false otherwise.
     */
    public function addMethod($method, $isAllowed = true, $autoSave = true)
    {
        // The model has to exists before
        if ($autoSave && !$this->exists) {
            $this->save();
        }

        $methodId = 0;
        if (is_integer($method)) {
            $methodId = intval($method);
        } else if ($method instanceof Method) {
            $methodId = intval($method->id);
        }

        if ($methodId > 0) {
            try {
                $this->methods()->attach($methodId, ['is_allowed' => $isAllowed]);
                $this->updateCacheCredentials();

                if ($autoSave) {
                    $this->save();
                }

                return true;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Remove a method from this role.
     *
     * @param integer|Method $method The method to unlink.
     * @param boolean|null $autoSave Auto-save the model (default: true).
     * @return boolean True if it has been removed, false otherwise.
     */
    public function removeMethod($method, $autoSave = true)
    {
        // The model has to exists before
        if ($autoSave && !$this->exists) {
            $this->save();
        }

        $methodId = 0;
        if (is_integer($method)) {
            $methodId = intval($method);
        } else if ($method instanceof Method) {
            $methodId = intval($method->id);
        }

        if ($methodId > 0) {
            try {
                $this->methods()->detach($methodId);
                $this->updateCacheCredentials();

                if ($autoSave) {
                    $this->save();
                }

                return true;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Batch all modules and methods, all at once...
     * Note that this function will erase all previous links if there is...
     *
     * @param array $modules The keys has to be either the id or the name of modules, inside each key there is an array
     * that contains a list of methods (either ids or names).
     * @param boolean|null $autoSave Auto-save the model (default: true).
     */
    public function batch($modules, $autoSave = true)
    {
        // The model has to exists before
        if ($autoSave && !$this->exists) {
            $this->save();
        }

        $moduleIds = array();
        $methodIds = array();

        $moduleNames = array();
        $methodNames = array();

        // Separating ids from names
        foreach ($modules as $key => $value) {
            if (is_integer($key)) {
                $id = intval($key);
                if ($id > 0 && !in_array($id, $moduleIds)) {
                    $moduleIds[] = $id;
                }
            } else {
                $name = trim($key);
                if (!in_array($name, $moduleNames)) {
                    $moduleNames[] = $name;
                }
            }

            foreach ($value as $method) {
                if (is_integer($method)) {
                    $id = intval($method);
                    if ($id > 0 && !in_array($id, $methodIds)) {
                        $methodIds[] = $id;
                    }
                } else {
                    $name = trim($method);
                    if (!in_array($name, $methodNames)) {
                        $methodNames[] = $name;
                    }
                }
            }
        }

        // Searching name to id conversion
        $moduleConversion = Module::whereIn('name', $moduleNames)->get();
        foreach ($moduleConversion as $conversion) {
            $id = $conversion->id;
            if (!in_array($id, $moduleIds)) {
                $moduleIds[] = $id;
            }
        }

        // Searching method to id conversion
        $methodConversion = Method::whereIn('name', $methodNames)->get();
        foreach ($methodConversion as $conversion) {
            $id = $conversion->id;
            if (!in_array($id, $methodIds)) {
                $methodIds[] = $id;
            }
        }

        // Now we sync everything (and so erase previous links)
        $this->modules()->sync($moduleIds);
        $this->methods()->sync($methodIds);

        // Saving the model
        if ($autoSave) {
            $this->save();
        }
    }
}
