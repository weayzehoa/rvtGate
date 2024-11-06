<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class APIDocsNotFoundWhenProduction
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
        $apiDomain = env('API_DOMAIN');
        $env = env('APP_ENV');
        if($env == 'production'){
            // return $this->unauthorized();
            return null;
        }else{
            if(request()->getHost() != $apiDomain){
                return null;
            }
        }
        return $next($request);
    }
    private function unauthorized($message = null){
        return response()->json([
            'code' => Response::HTTP_FORBIDDEN,
            'message' => $message ? $message : 'You are unauthorized to access this resource',
        ], Response::HTTP_FORBIDDEN);
    }
}
