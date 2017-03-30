<?php

namespace Deisss\Autoacl\Routing;

use \Illuminate\Support\Facades\Route;

/**
 * @see \Illuminate\Routing\Router
 */
class RouteAcl
{
    /**
     * Merge action and necessary ACL requirements.
     *
     * @param string $module The ACL module to bind this route to.
     * @param null|string $method The ACL method to bind this route to.
     * @param \Closure|array|string $action The original action.
     * @return array The result array ready to be used.
     */
    private function getAclAction($module, $method = null, $action = null)
    {
        $middlewares = array('web', 'auth');
        $result = array();

        if (!empty($method)) {
            $middlewares[] = '\Deisss\Autoacl\Middleware\Acl:'.$module.':'.$method;
        } else {
            $middlewares[] = '\Deisss\Autoacl\Middleware\Acl:'.$module;
        }

        if (is_array($action)) {
            foreach ($action as $key => $value) {
                if (!array_key_exists($key, $result)) {
                    $result[$key] = $value;
                }
            }

            if (!array_key_exists('middleware', $result)) {
                $result['middleware'] = $middlewares;
            } else {
                foreach($middlewares as $middleware) {
                    if (!in_array($middleware, $result['middleware'])) {
                        $result['middleware'][] = $middleware;
                    }
                }
            }
        } else {
            $result['middleware'] = $middlewares;
            $result['uses'] = $action;
        }

        return $result;
    }

    public function get($uri, $module, $method = null, $action = null)
    {
        return Route::get($uri, $this->getAclAction($module, $method, $action));
    }

    public function post($uri, $module, $method = null, $action = null)
    {
        return Route::post($uri, $this->getAclAction($module, $method, $action));
    }

    public function put($uri, $module, $method = null, $action = null)
    {
        return Route::put($uri, $this->getAclAction($module, $method, $action));
    }

    public function delete($uri, $module, $method = null, $action = null)
    {
        return Route::delete($uri, $this->getAclAction($module, $method, $action));
    }

    public function patch($uri, $module, $method = null, $action = null)
    {
        return Route::patch($uri, $this->getAclAction($module, $method, $action));
    }

    public function options($uri, $module, $method = null, $action = null)
    {
        return Route::options($uri, $this->getAclAction($module, $method, $action));
    }

    public function match($methods, $uri, $module, $method = null, $action = null)
    {
        return Route::match($methods, $uri, $this->getAclAction($module, $method, $action));
    }

    public function any($uri, $module, $method = null, $action = null)
    {
        return Route::any($uri, $this->getAclAction($module, $method, $action));
    }

    public function resource($name, $controller, $options = [])
    {
        return Route::resource($name, $controller, $options);
    }

    public function group($module, $method, $attributes, $callback)
    {
        return Route::group($this->getAclAction($module, $method, $attributes), $callback);
    }

    public function substituteBindings($route)
    {
        return Route::substituteBindings($route);
    }

    public function substituteImplicitBindings($route)
    {
        return Route::substituteImplicitBindings($route);
    }
}
