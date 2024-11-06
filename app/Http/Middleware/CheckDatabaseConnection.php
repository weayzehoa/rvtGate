<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class CheckDatabaseConnection
{
    public function handle($request, Closure $next)
    {
        // Test database connection
        try {
            DB::connection('mysql')->getPdo();
            DB::connection('icarry')->getPdo();
            DB::connection('icarryLang')->getPdo();
            DB::connection('iCarrySMERP')->getPdo();
        } catch (\Exception $e) {
            $errorInfo = $e->errorInfo;
            if ($errorInfo[0] == '08001') {
                dd("SmartERP 資料庫連線失敗。".$errorInfo[2]);
            }elseif($errorInfo[0] == '28000'){
                dd("SmartERP 資料庫登入失敗。".$errorInfo[2]);
            }elseif($errorInfo[0] == 'HY000'){
                if(strstr($errorInfo[2],"Unknown database")){
                    dd("iCarry 資料庫錯誤。");
                }elseif(strstr($errorInfo[2],"Access denied")){
                    dd("iCarry 資料庫登入失敗。");
                }else{
                    dd("iCarry 資料庫連線失敗：".$errorInfo[2]);
                }
            }
        }
        return $next($request);
    }
}
