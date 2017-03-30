<?php

namespace Deisss\Autoacl\Providers;

use Blade;
use Deisss\Autoacl\Models\Role;
use Deisss\Autoacl\Observers\RoleObserver;
use Deisss\Autoacl\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AutoaclServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        /*
         * --------------------------------
         *   CONFIGURATION
         * --------------------------------
         */
        $DS = DIRECTORY_SEPARATOR;

        // Where to locate the base of this plugin
        $root = __DIR__.$DS.'..'.$DS;

        $this->loadMigrationsFrom($root.'Migrations');

        // Publishing configuration file
        $this->publishes(
            array(
                $root.'config'.$DS.'autoacl.php' => config_path('autoacl.php')
            ),
            'config'
        );

        /*
         * --------------------------------
         *   HOOKS
         * --------------------------------
         */
        Role::observe(RoleObserver::class);

        // Trying to grab the user class and attach the observer to it
        $userClass = \Config::get('autoacl.Migrations.class');
        try {
            $userClass::observe(UserObserver::class);
        } catch (\Exception $e) {
            \Log::info('Cannot call the static method observe from the class "'.$userClass.'": '.$e->getMessage());
        }

        /*
         * --------------------------------
         *   BLADE ENGINE
         * --------------------------------
         */
        Blade::directive('hasAccessTo', function($arguments) {
            $parsed = str_replace(['(', ')', '"', "'"], '', $arguments);
            if (strpos($parsed, ',') === false) {
                return "<?php if (\\Auth::check() && \\Auth::user()->hasAccessTo('$parsed')): ?>";
            } else {
                list($module, $method) = explode(',', $parsed);

                // There is potentially some empty chars at the beginning and the end...
                $module = trim($module);
                $method = trim($method);
                return "<?php if (\\Auth::check() && \\Auth::user()->hasAccessTo('$module', '$method')): ?>";
            }
        });
        Blade::directive('hasRole', function($arguments) {
            $parsed = str_replace(['(', ')', '"', "'"], '', $arguments);
            return "<?php if (\\Auth::check() && \\Auth::user()->hasRole('$parsed')): ?>";
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
