<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | Supported: "session", "token"
    |
    */

    'guards' => [
        //前台使用者
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        //前台 User API
        // 'api' => [
        //     'driver' => 'jwt',
        //     'provider' => 'users',
        //     'hash' => false,
        // ],

        //前台 User API
        'webapi' => [
            'driver' => 'jwt',
            'provider' => 'users',
            'hash' => false,
        ],

        //後台管理者
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],

        //後台 Admin API
        'admapi' => [
            'driver' => 'jwt',
            'provider' => 'admins',
            'hash' => false,
        ],

        //商家後台管理者
        'vendor' => [
            'driver' => 'session',
            'provider' => 'vendors',
        ],

        //後台 Admin API
        'vendorapi' => [
            'driver' => 'jwt',
            'provider' => 'vendors',
            'hash' => false,
        ],

        //ERP中繼後台管理者
        'gate' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        //使用 users 資料表
        'users' => [
            // 'driver' => 'eloquent',
            'driver' => 'self-eloquent', //自己定義密碼檢驗規則
            'model' => App\Models\User::class,
        ],

        //使用 admins 資料表
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

        //使用 vendor_accounts 資料表
        'vendors' => [
            // 'driver' => 'eloquent',
            'driver' => 'self-eloquent', //自己定義密碼檢驗規則
            'model' => App\Models\VendorAccount::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expire time is the number of minutes that the reset token should be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | times out and the user is prompted to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => 10800,

];
