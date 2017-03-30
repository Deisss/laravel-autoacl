<?php

/*
|--------------------------------------------------------------------------
| Autoacl configuration
|--------------------------------------------------------------------------
|
| You can configure here how the ACL system link to your system.
| Autoacl needs to know where is the users table and the primary id field.
|
*/

return [
    'Migrations' => [
        // The 'users' table (null for "auto detection")
        'table' => 'users',
        // The primary field (null for "auto detection")
        'field' => null,
        // It's MySQL type: tinyint, smallint, mediumint, in and bigint  (null for "auto detection")
        'type'  => null,
        // The user class (the full one)
        'class' => 'App\\User'
    ]
];