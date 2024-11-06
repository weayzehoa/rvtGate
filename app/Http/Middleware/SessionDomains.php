<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Config;
class SessionDomains
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if($request->getHost() === env('VENDOR_DOMAIN')){
            config([
                'session.domain' => '.'.env('VENDOR_DOMAIN'),
                'session.cookie' => 'vendor_session',
            ]);
        }
        if($request->getHost() === env('ADMIN_DOMAIN')){
            config([
                'session.domain' => '.'.env('ADMIN_DOMAIN'),
                'session.cookie' => 'admin_session',
            ]);
        }
        if($request->getHost() === env('WEB_DOMAIN')){
            config([
                'session.domain' => '.'.env('WEB_DOMAIN'),
                'session.cookie' => 'web_session',
            ]);
        }
        if($request->getHost() === env('GATE_DOMAIN')){
            config([
                'session.domain' => '.'.env('GATE_DOMAIN'),
                'session.cookie' => 'gate_session',
            ]);
        }
        $sessionDomain = Config::get('session.domain');
        $sessionCookie = Config::get('session.cookie');
        // dd($sessionCookie);
        return $next($request);
    }
}
