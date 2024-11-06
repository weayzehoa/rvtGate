<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {

            //當使用者被確認後，判斷是 User or Admin or Vendor 來的使用者並轉到正確的頁面位置
            switch ($guard) {
                case 'admin':
                    if (Auth::guard($guard)->check()) {
                        return redirect()->route('admin.dashboard');
                    }
                    break;
                case 'vendor':
                    if (Auth::guard($guard)->check()) {
                        return redirect()->route('vendor.dashboard');
                    }
                    break;
                case 'gate':
                    if (Auth::guard($guard)->check()) {
                        return redirect()->route('gate.dashboard');
                    }
                    break;
                default:
                    if (Auth::guard($guard)->check()) {
                        return redirect('/');
                    }
                    break;
            }
            // if (Auth::guard($guard)->check()) {
            //     return redirect(RouteServiceProvider::HOME);
            // }
        }
        return $next($request);
    }
}
