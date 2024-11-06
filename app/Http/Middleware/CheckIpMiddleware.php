<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\IpAddress as IpAddressDB;
use App\Models\SystemSetting as SystemSettingDB;
class CheckIpMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $ip = null;
        $whiteIps = IpAddressDB::where('is_on',1)->select('ip')->get()->pluck('ip')->all();
        $systemSetting = SystemSettingDB::first();
        !empty($systemSetting->disable_ip_start) ? $startTime = $systemSetting->disable_ip_start : $startTime = null;
        !empty($systemSetting->disable_ip_end) ? $endTime = $systemSetting->disable_ip_end : $endTime = null;

        if(strtotime($startTime) <= strtotime(date('Y-m-d')) && strtotime(date('Y-m-d')) <= strtotime($endTime)){
            if (auth()->user() && in_array(auth()->id(), [40])) {
                \Debugbar::enable();
            }
            else {
                \Debugbar::disable();
            }
        }else{
            if(!empty($request->header('x-forwarded-for'))){
                $ip = $request->header('x-forwarded-for');
            }else{
                $ip = $request->ip();
            }
            if (!in_array($ip, $whiteIps)) {
                /*
                     You can redirect to any error page.
                */
                // return redirect()->to('https://google.com');
                return response()->json([
                    'code' => 401,
                    'message' => "your ip address ( $ip ) is not valid."
                ],401);
            }else{
                if (auth()->user() && in_array(auth()->id(), [40])) {
                    \Debugbar::enable();
                }
                else {
                    \Debugbar::disable();
                }
            }
        }
        return $next($request);
    }
}