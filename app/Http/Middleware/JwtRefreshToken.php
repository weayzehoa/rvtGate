<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException as TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException as TokenExpiredException;
use Auth;
use App\Traits\ApiResponser;
use App\Traits\ThirdPartyVerify;
use App;

class JwtRefreshToken extends BaseMiddleware
{
    use ThirdPartyVerify,ApiResponser;

    public function handle($request, Closure $next)
    {
        //驗證第三方來的資料
        $this->thirdPartyCheck = $this->thirdPartyVerify();
        //失敗直接返回驗證失敗
        if(!empty($this->thirdPartyCheck) && $this->thirdPartyCheck === 'Fail'){
            return $this->errorResponse('Verify Fail.',400);
        }
        //第三方資料使用者驗證失敗
        if(!empty($this->thirdPartyCheck) && $this->thirdPartyCheck === 'UidFail'){
            return $this->errorResponse('User id Missing.',400);
        }
        //成功直接跳過JWT授權
        if (!empty($this->thirdPartyCheck) && $this->thirdPartyCheck === 'Pass') {
            return $next($request);
        }
        //從header找授權資料
        $authorization = $request->header('authorization');
        //缺少授權資料
        if(empty($authorization)){
            return $this->errorResponse('Authorization required.', 400);
        }
        $chk = explode(' ',$authorization);
        //授權格式錯誤缺少 "Bearer "
        if($chk[0] != 'Bearer'){
            return $this->errorResponse('Authorization format error.', 416);
        }
        try {
            //檢查是否有使用者通過驗證
            if(JWTAuth::parseToken()->authenticate()){
                //Token TTL 到期時間
                $expireTime = JWTAuth::payload()['exp'];
                // 如果 Token 到期時間小於多少分鐘，重新產生一個新的 Token 並附帶在 header 送出，讓前端重新抓取.
                if (($expireTime - time()) < env('JWT_TOKEN_REFRESH_TIME',5) * 60 && ($expireTime - time()) > 0) {
                    $refreshed = JWTAuth::refresh(JWTAuth::getToken());
                    $user = JWTAuth::setToken($refreshed)->toUser();
                    $request->headers->set('Authorization', 'Bearer '.$refreshed);
                }
            }else{
                return $this->errorResponse('This token is not match the user.', 404);
            }
        } catch (Exception $e) {
            if ($e instanceof TokenInvalidException){ //檢查 Token 是否無效
                return $this->errorResponse('This token is invalid. Please Login.', 401);
            }else if ($e instanceof TokenExpiredException){ //檢查 Token 是否到期
                return $this->errorResponse('This token is expired. Please Login.', 400);
            }else{ //找不到 Token.
                return $this->errorResponse('Authorization Token not found. Please Login.', 404);
            }
        }
        return $next($request);
    }
}
