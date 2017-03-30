<?php

namespace Deisss\Autoacl\Middleware;

use Closure;
use Exception;

class Acl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param $params
     * @return mixed
     * @throws Exception
     * @internal param null|string $guard
     */
    public function handle($request, Closure $next, $params)
    {
        $user = $request->user();

        if (!$user) {
            throw new Exception('Please use Authenticate middleware first');
        } else {
            // We create the final parameters
            $tmp = explode(':', $params);
            $module = $tmp[0] ?: null;
            $route  = $tmp[1] ?: null;

            // User does not have the right to access this module
            // OR User has the right to access this module BUT not this
            // route/function
            // OR The credentials is not set (a json decode error for ex.)
            if (!$user->hasAccessTo($module, $route)) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response('Forbidden.', 403);
                } else {
                    return redirect('forbidden');
                }
            }
        }

        return $next($request);
    }
}
