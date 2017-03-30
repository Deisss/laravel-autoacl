<?php

namespace Deisss\Autoacl\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Role observer is an observer to manage role credentials update.
 *
 * Class RoleObserver
 * @package Deisss\Autoacl\Observers
 */
class RoleObserver
{
    /**
     * Just before saving we always update the cache credentials.
     *
     * @param Model $role The role being created/updated.
     */
    public function saving(Model $role)
    {
        $role->updateCacheCredentials();
    }
}