<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if(request()->getHost() === env('ADMIN_DOMAIN')){
            return route('admin.login');
        }

        if(request()->getHost() === env('VENDOR_DOMAIN')){
            return route('vendor.login');
        }

        if(request()->getHost() === env('WEB_DOMAIN')){
            return route('login');
        }

        if(request()->getHost() === env('GATE_DOMAIN')){
            return route('gate.login');
        }

        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
