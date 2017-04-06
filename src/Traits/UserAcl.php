<?php

namespace Deisss\Autoacl\Traits;

use Carbon\Carbon;
use Deisss\Autoacl\Models\Module;
use Deisss\Autoacl\Models\Role;

/**
 * Add support for Acl on the user model class.
 *
 * Class UserAcl
 * @package Deisss\Autoacl\Traits
 */
trait UserAcl
{
    /**
     * @throws \Exception If the Models does not implements everything needed to run this function.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        if (!method_exists($this, 'belongsToMany')) {
            throw new \Exception('Class does not have a belongsToMany methods, but use the trait UserAcl...');
        }

        return $this->belongsToMany(
        // Model
            'Deisss\Autoacl\Models\Role',
            // Pivot table
            'user_has_acl_roles',
            // "Our" key
            'user_id',
            // "Their" key
            'acl_role_id'
        )
        ->withTimestamps();
    }

    /**
     * Update the credentials used by the system.
     *
     * @throws \Exception If the Models does not implements everything needed to run this function.
     */
    public function updateCacheCredentials()
    {
        // Before crashing everything... Check if the class support everything we need...
        if (!property_exists($this, 'attributes')) {
            throw new \Exception('Class is not a valid Laravel model: property $this->attributes does not exists.');
        }
        if (!method_exists($this, 'roles')) {
            throw new \Exception('Class does not have a roles method, but use the trait UserAcl...');
        }
        /*if (!array_key_exists('cache_credentials', $this->attributes)) {
            throw new \Exception('Class does not have a cache_credentials attribute, but use the trait UserAcl...');
        }*/

        $result = [];
        $roles = $this->roles()->getResults();

        /** @var \Deisss\Autoacl\Models\Role $role */
        foreach ($roles as $role) {
            $json = json_decode($role->cache_credentials, true);

            // If there is something inside, we can continue and parse it
            if (!empty($json)) {
                foreach ($json as $module => $content) {
                    if (!array_key_exists($module, $result)) {
                        $result[$module] = [
                            'id' => $content['id'],
                            'is_allowed' => true,
                            'methods' => []
                        ];
                    }

                    if ($content['is_allowed'] === false) {
                        $result[$module]['is_allowed'] = false;
                    }

                    foreach ($content['methods'] as $method => $inner) {
                        if (!array_key_exists($method, $result[$module])) {
                            $result[$module]['methods'][$method] = [
                                'id' => $inner['id'],
                                'is_allowed' => true
                            ];
                        }

                        // As we set ALL of them to true, the only case we
                        // need to care is if it's false, in this case it can
                        // only go from active to inactive
                        if ($inner['is_allowed'] === false) {
                            $result[$module]['methods'][$method]['is_allowed'] = false;
                        }
                    }
                }
            }
        }

        // Pushing to this element as a JSON document
        $this->cache_credentials = json_encode($result);
    }

    /**
     * Get the credentials in use for this user, it's a double array, the first array is allowed modules, the second
     * one is allowed methods.
     *
     * @throws \Exception If the Models does not implements everything needed to run this function.
     *
     * @return array The allowed modules and methods
     */
    public function getCredentials()
    {
        // Before crashing everything... Check if the class support everything we need...
        if (!property_exists($this, 'attributes')) {
            throw new \Exception('Class is not a valid Laravel model: property $this->attributes does not exists.');
        }
        if (!array_key_exists('cache_credentials', $this->attributes)) {
            throw new \Exception('Class does not have a cache_credentials attribute, but use the trait UserAcl...');
        }

        // We get the credentials allowed to be seen...
        $credentials = json_decode($this->cache_credentials, true);

        $allowedModuleIds = [];
        $allowedMethodIds = [];

        $deniedModuleIds  = [];
        $deniedMethodIds  = [];

        foreach ($credentials as $content) {
            if ($content['is_allowed'] === true) {
                $allowedModuleIds[] = $content['id'];
            } else {
                $deniedModuleIds[] = $content['id'];
            }

            foreach ($content['methods'] as $method) {
                if ($method['is_allowed'] === true) {
                    $allowedMethodIds[] = $method['id'];
                } else {
                    $deniedMethodIds[] = $method['id'];
                }
            }
        }

        // Now we have a list of allowed and denied resources
        // we just need to filter the ones in denied...
        $moduleIds = array_diff($allowedModuleIds, $deniedModuleIds);
        $methodIds = array_diff($allowedMethodIds, $deniedMethodIds);

        return [
            'modules' => $moduleIds,
            'methods' => $methodIds
        ];
    }

    /**
     * Check if user is allowed or not to use given module and method.
     *
     * @throws \Exception If the Models does not implements everything needed to run this function.
     *
     * @param $module
     * @param null $method The method to check, if null, the system just check the module allowance
     * @return Boolean True if it's allowed, false otherwise
     */
    public function hasAccessTo($module, $method = null)
    {
        // Before crashing everything... Check if the class support everything we need...
        if (!property_exists($this, 'attributes')) {
            throw new \Exception('Class is not a valid Laravel model: property $this->attributes does not exists.');
        }
        if (!array_key_exists('cache_credentials', $this->attributes)) {
            throw new \Exception('Class does not have a cache_credentials attribute, but use the trait UserAcl...');
        }

        // We get the credentials allowed to be seen...
        $credentials = json_decode($this->cache_credentials, true);

        // If the module is not listed as existing, no need to do more... No matter what the situation we're in, it's false.
        if (!array_key_exists($module, $credentials)) {
            return false;
        }

        $root = $credentials[$module];

        // If the module is specifically deny, no need to proceed further either
        if ($root['is_allowed'] === false) {
            return false;
        }

        // If there is no method, we just need to check the module existence.
        // We already check everything, se we just need to return true...
        if (empty($method)) {
            return true;
        } else {
            // We apply the same check on the method now

            if (!array_key_exists($method, $root['methods'])) {
                return false;
            }

            return $root['methods'][$method]['is_allowed'];
        }
    }

    /**
     * Check if the user is part of the group/role.
     *
     * @throws \Exception If the Models does not implements everything needed to run this function.
     *
     * @param string $name The name of the group/role to check.
     * @return boolean True if it's part of it, false otherwise.
     */
    public function hasRole($name)
    {
        if (empty($name)) {
            return false;
        }

        // Before crashing everything... Check if the class support everything we need...
        if (!method_exists($this, 'roles')) {
            throw new \Exception('Class does not have a roles methods, but use the trait UserAcl...');
        }

        /** @var \Deisss\Autoacl\Models\Role[] $roles */
        $roles = $this->roles()->getResults();
        $name = strtolower($name);

        foreach ($roles as $role) {
            if (strtolower($role->name) === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Link a user to a group/role.
     *
     * @throws \Exception If the Models does not implements everything needed to run this function.
     *
     * @param string $name The group/role name.
     * @param boolean|null $autoSave Auto-save the model (default: true).
     * @return boolean True if the group has been added, false otherwise.
     */
    public function addRole($name, $autoSave = true)
    {
        // Before crashing everything... Check if the class support everything we need...
        if (!method_exists($this, 'save')) {
            throw new \Exception('Class does not have a save methods, but use the trait UserAcl...');
        }
        if (!property_exists($this, 'exists')) {
            throw new \Exception('Class does not have a exists property, but use the trait UserAcl...');
        }

        // The model has to exists before
        if ($autoSave && !$this->exists) {
            $this->save();
        }

        $role = Role::where('name', '=', $name)->first();

        // Problem? we try with the ID instead.
        if (empty($role) && is_integer($name) && !is_infinite($name)) {
            $role = Role::find($name);
        }

        if (!empty($role)) {
            try {
                //Linking everything
                $this->roles()->attach($role->id, array(
                    'created_at' => Carbon::now()
                ));

                // Updating and saving the model.
                $this->updateCacheCredentials();

                if ($autoSave) {
                    $this->save();
                }

                return true;
            } catch (\Exception $e) {
                \Log::info($e->getMessage());
            }
        }

        return false;
    }

    /**
     * Remove a role the user has.
     *
     * @throws \Exception If the Models does not implements everything needed to run this function.
     *
     * @param string $name The group name to remove
     * @param boolean|null $autoSave Auto-save the model (default: true).
     * @return boolean True if it's has been removed, false otherwise
     */
    public function removeRole($name, $autoSave = true)
    {
        // Before crashing everything... Check if the class support everything we need...
        if (!method_exists($this, 'save')) {
            throw new \Exception('Class does not have a save methods, but use the trait UserAcl...');
        }
        if (!property_exists($this, 'exists')) {
            throw new \Exception('Class does not have a exists property, but use the trait UserAcl...');
        }

        // The model has to exists before
        if ($autoSave && !$this->exists) {
            $this->save();
        }

        $role = Role::where('name', '=', $name)->first();

        // Problem? we try with the ID instead.
        if (empty($role) && is_integer($name) && !is_infinite($name)) {
            $role = Role::find($name);
        }

        if (!empty($role)) {
            try {
                //Linking everything
                $this->roles()->detach($role->id);

                // Updating and saving the model.
                $this->updateCacheCredentials();

                if ($autoSave) {
                    $this->save();
                }

                return true;
            } catch (\Exception $e) {
                \Log::info($e->getMessage());
            }
        }

        return false;
    }

    /**
     * Batch all roles, all at once...
     * Note that this function will erase all previous links if there is...
     *
     * @param array $roles An array of roles and/or ids.
     * @param boolean|null $autoSave Auto-save the model (default: true).
     */
    public function batch($roles, $autoSave = true)
    {
        // The model has to exists before
        if ($autoSave && !$this->exists) {
            $this->save();
        }

        $roleIds   = array();
        $roleNames = array();

        // Separating ids from names
        foreach ($roles as $value) {
            if (is_integer($value)) {
                $id = intval($value);
                if ($id > 0 && !in_array($id, $roleIds)) {
                    $roleIds[] = $id;
                }
            } else {
                $name = trim($value);
                if (!in_array($name, $roleNames)) {
                    $roleNames[] = $name;
                }
            }
        }

        // Searching name to id conversion
        $roleConversion = Module::whereIn('name', $roleNames)->get();
        foreach ($roleConversion as $conversion) {
            $id = $conversion->id;
            if (!in_array($id, $roleIds)) {
                $roleIds[] = $id;
            }
        }

        // Now we sync everything (and so erase previous links)
        $this->roles()->sync($roleIds);

        // Saving the model
        if ($autoSave) {
            $this->save();
        }
    }
}