# Autoacl

## Introduction

/!\ NOT PRODUCTION READY - STILL UNDER DEVELOPMENT/TESTS /!\

AutoACL is a Laravel package to add deep and configurable ACL (Access Control List)
to your application.  
Instead of restricting a resource by the role, this plugin helps you directly by
the resource role itself. This allow dynamic role creation and usage, as long as
fine grained security.

Why? Because you can create a system where every user create has many roles 
as they would like to, allowing to never be limited (so for example
every customer can create a set of role for his company/structure).  



## Installation

Run the following command from the root folder of your Laravel project:
```bash
composer require deisss/autoacl
```

Once installed, edit the file ```config/app.php``` to add the following provider:

```php
<?php
  
    // (...)
  
    'providers' => [
        
        // (...)
        
        \Deisss\Autoacl\Providers\AutoaclServiceProvider::class,
    ]
```

For a easier integration, you can also add, in the same file, in the ```aliases``` section:

```php
<?php
  
    // (...)
  
    'aliases' => [
        
        // (...)
        
        'RouteAcl' => Deisss\Autoacl\Facades\RouteAcl::class,
    ]
```

That will register ```RouteAcl``` so you can use it in the routes without
declaring it.  

The plugin should now be active. The migration script will be working properly
as long as you have ```users``` table as your main table for users management
(it should be the case if you follow main laravel guide).




## Configuration

If your table responsible for users management isn't called ```users```, or for
any reason you want to tweak the way the plugin install/search for everything, you
can change it's configuration by publishing it:
```bash
php artisan vendor:publish
```

Will add a file ```autoacl.php``` in ```config``` folder, which you can tweak.



## Setup

Before anything else, you must create all the necessary MySQL's table:
```php
php artisan migrate
```

Will generate all tables used by this plugin.  But it's not enough, you still need
to link your user model to the ACL system, edit your model ```User``` (by default it's
located in ```app/user.php```):
```php
<?php
  
// (...)
  
use Deisss\Autoacl\Traits\UserAcl;
  
// (...)
  
class User extends Authenticatable
{
    use UserAcl;
        
    // (...)
}
```

This will add to the user model some role manipulation to setup things more easily.
Notably the system use an internal cache on the ```users``` table, and ```acl_roles```
table, to avoid querying too much the database.  
This care about all of this for you automatically.



## First usage

Now everything is configured and ready to be used, time to see how to use the plugin.



### Setup the ACLs

Before creating a role, you need to define what to secure. The model support
two elements called module and method:
  * A module is a group of methods, for example you may have a category in your application
  to manage users, called ACL, where you can manage users. That is a module.
  * Inside a module, you will found one or more method. This is usually close to a 
  single URL/resource. For example creating a user is a method of the ACL module.

Example:
```php
<?php
  
use Deisss\Autoacl\Models\Module;
use Deisss\Autoacl\Models\Method;
  
// (...)
  
$module = new Module();
$module->name = 'ACL';
$module->save();
  
$method = new Method();
$method->name = 'creating a user';
$method->description = 'Allow to manually register a new user';
// Link it to our module, not that the module MUST have an id at this point
// So it has to be saved first...
$method->setModule($module);
$method->save();
  
$method = new Method();
$method->name = 'editing a user';
$method->description = 'Allow to manually edit an existing user';
$method->setModule($module);
$method->save();
```

We create a module named ```ACL``` and add two method inside it, one for creating a user
and one for editing.  
The modules and methods are fixed, it means they should not change through the
application lifetime (or only during code update).  
Therefore, creating them during the ```db:seed``` (database seeder) command is
recommended.



### Link a role to modules and methods

It can be done by code only.
If the roles aren't supposed to change, then you can do this sort of code 
within the ```db:seed``` (database seeder) command, if your ACL are dynamic,
you will probably need to do that within a controller:

```php
<?php
  
// (...)
  
use Deisss\Autoacl\Models\Module;
use Deisss\Autoacl\Models\Role;
  
$moduleACL = Module::where('name', 'ACL')->first();
  
$role = new Role();
$role->name = 'Administrator';
$role->description = 'The root of everything';
$role->addModule($moduleACL);
  
// Adding all methods of the module to the role
foreach ($moduleACL->methods as $method) {
    $role->addMethod($method);
}
```

That's it, we now have a role ```Administrator``` linked to the ```ACL``` module and
all of it's method. Time to a role to a user.



### Link a user to roles

It's done by code, and can be changed at any time during application's lifetime:
```php
<?php
  
use App\User;
use Deisss\Autoacl\Models\Role;
  
// Get the role
$role = Role::where('name', 'Administrator')->first();
  
// Get the first user in the database
$user = User::find(1);
$user->addRole($role);
```

And voila! The user is now linked to the role ```Administrator```. You can of course
add as many role as you want for any user (and remove them when you want).


### ACL inside route file

Now we got everything setup, let's use it.  
There is various of ways to check security with this plugin, but the most simple
one is to secure on the route level. In ```routes/web.php```:

```php
<?php
  
// (...)
  
RouteAcl::get(
    // Route
    '/test-acl-role',
    // ACL module
    'ACL',
    // ACL method
    'creating a user',
    // Controller/use
    'TestController@testAclCreatingUserRole'
)->name('test-acl');
```

Now any user accessing this resource needs:
  - to be logged
  - to have a role that allow ```ACL``` / ```creating a user``` module/method.



## ACL inside template file

Sometimes you may need to know if the current logged user has access or not to a
part of the application. For example, while generating a menu, you want to hide
the ACL management to user who don't have access to it.

You can do it like this (inside the template file):
```html
@hasRole('Administrator')
  You see this because you have the role administrator.
@else
  You see this because you DON'T have the role administrator.
@endif
  
<br />
  
@hasAccessTo('ACL')
  You see this because you have access to the module ACL.
@else
  You see this because you DON'T have access to the module ACL.
@endif
  
<br />
  
@hasAccessTo('ACL', 'creating a user')
  You see this because you have access to the module ACL AND you can create a user.
@else
  You see this because you DON'T have access to the module ACL and/or you can't create
  a user.
@endif
```



## ACL inside PHP file

You may at some point check ACL manually for any reason, here is an example from
a controller where we manually check some ACL:

```php
<?php
  
namespace App\Http\Controllers;
  
use App\User;
  
class TestController extends Controller
{
    public function testRole()
    {
        /** @var User $user */
        $user = \Auth::user();
        
        // First possibility
        if ($user->hasRole('Administrator')) {
            // he/she has the role
        }
        
        // Second possibility
        $module = 'ACL';
        $method = 'creating a user';
        if ($user->hasAccessTo($module, $method)) {
            // he/she has access to the module and method
        }
    }
}
```



## License

See LICENSE.MD file.
