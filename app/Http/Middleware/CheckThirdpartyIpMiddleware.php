<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ThirdpartyIp as ThirdpartyIpDB;
class CheckThirdpartyIpMiddleware
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
        $whiteIps = ThirdpartyIpDB::where('is_on',1)->select('ip')->get()->pluck('ip')->all();
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
        }
        return $next($request);
    }
}
