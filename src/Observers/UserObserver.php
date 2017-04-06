<?php

namespace Deisss\Autoacl\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Role observer is an observer to manage role credentials update.
 *
 * Class RoleObserver
 * @package Deisss\Autoacl\Observers
 */
class UserObserver
{
    /**
     * Just before saving we always update the cache credentials.
     *
     * @param Model $user The user being created/updated.
     */
    public function saving(Model $user)
    {
        $user->updateCacheCredentials();
    }
}