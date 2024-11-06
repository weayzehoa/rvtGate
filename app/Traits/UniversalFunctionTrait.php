<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use DB;

trait UniversalFunctionTrait
{
    protected function chkEmail(string $email)
    {
        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
        if(!empty($email) && preg_match($pattern,$email)){
            return $email;
        }else{
            return false;
        }
    }

    protected function chkMobile(string $mobile)
    {
        $pattern = "/^[0-9]{10}$/i";
        if(!empty($mobile) && preg_match($pattern,$mobile)){
            return $mobile;
        }else{
            return false;
        }
    }

    protected function validateDate(string $date, string $format = 'Y-m-d')
    {
        if(is_int($date) || is_null($date) || is_numeric($date)){
            return null;
        }else{
            return date($format, strtotime($date)) == $date;
        }
    }

    protected function chkDate($str){
            if(!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $str)){
                return false;
            }
            $__y = substr($str, 0, 4);
            $__m = substr($str, 5, 2);
            $__d = substr($str, 8, 2);
            return checkdate($__m, $__d, $__y);
        }

    protected function randomString($length = 1, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        if (!is_int($length) || $length < 0) {
            return false;
        }
        $characters_length = strlen($characters) - 1;
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, $characters_length)];
        }
        return $string;
    }

    protected function generateUniqueCode($baseNumber) {
        $digits = str_split($baseNumber);
        $checksum = array_sum($digits) % 10; // 计算检验码
        $uniqueCode = $baseNumber . $checksum; // 将随机数和检验码组合在一起

        return $uniqueCode;
    }

    protected function convertAndValidateDate($date, $format = null)
    {
        // 如果格式參數為 null，則預設輸出格式為 "Y-m-d"
        $outputFormat = $format ? $format : "Y-m-d";

        // 驗證輸入的日期格式是否為 8 位數字
        if (!preg_match('/^\d{8}$/', $date)) {
          // 如果不是數字日期格式，則認為是日期格式
          $timestamp = strtotime($date);

          // 驗證轉換後的時間戳是否有效
          if (!$timestamp) {
            return false;
          }
        } else {
          // 將數字日期轉換為 "YYYY-MM-DD" 格式
          $formattedDate = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);

          // 驗證輸入的日期格式是否為 YYYY-MM-DD
          if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $formattedDate)) {
            return false;
          }

          // 轉換日期為時間戳
          $timestamp = strtotime($formattedDate);

          // 驗證轉換後的時間戳是否有效
          if (!$timestamp) {
            return false;
          }

          // 驗證轉換後的日期是否和輸入的日期一致，避免 2020-02-30 這種非法日期被轉換為 2020-03-01
          if (date('Y-m-d', $timestamp) !== $formattedDate) {
            return false;
          }
        }

        // 轉換日期為指定格式
        return date($outputFormat, $timestamp);
    }

    protected function removeEmoji($string) {

        // Match Emoticons
        $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clear_string = preg_replace($regex_emoticons, '', $string);

        // Match Miscellaneous Symbols and Pictographs
        $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clear_string = preg_replace($regex_symbols, '', $clear_string);

        // Match Transport And Map Symbols
        $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clear_string = preg_replace($regex_transport, '', $clear_string);

        // Match Miscellaneous Symbols
        $regex_misc = '/[\x{2600}-\x{26FF}]/u';
        $clear_string = preg_replace($regex_misc, '', $clear_string);

        // Match Dingbats
        $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
        $clear_string = preg_replace($regex_dingbats, '', $clear_string);

        return $clear_string;
    }

    protected function backgroundJobDelayProcess($jobName, $minutes = null)
    {
        $result = [];
        $chkQueue = 0;
        $delay = null;
        $minutes == null ? $minutes = 2 : '';
        $countQueue = Redis::llen('queues:default');
        $allQueues = Redis::lrange('queues:default', 0, -1);
        $allDelayQueues = Redis::zrange('queues:default:delayed', 0, -1);
        if(count($allQueues) > 0){
            if(count($allDelayQueues) > 0){
                $allDelayQueues = array_reverse($allDelayQueues);
                for($i=0;$i<count($allDelayQueues);$i++){
                    $job = json_decode($allDelayQueues[$i],true);
                    if(strstr($job['displayName'],$jobName)){
                        $commandStr = $job['data']['command'];
                        if(strstr($commandStr,'s:26')){
                            $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                            $command = explode('____',$commandStr);
                            $time = $command[1];
                            $delay = Carbon::parse($time)->addminutes($minutes);
                        }else{
                            $delay = Carbon::now()->addminutes($minutes);
                        }
                        $chkQueue++;
                        break;
                    }
                }
            }else{
                foreach($allQueues as $queue){
                    $job = json_decode($queue,true);
                    if(strstr($job['displayName'],$jobName)){
                        $delay = Carbon::now()->addminutes($minutes);
                        $chkQueue++;
                    }
                }
            }
        }else{
            $queue = DB::table('jobs')->where('payload','like',"%$jobName%")->orderBy('id','desc')->first();
            if(!empty($queue)){
                $payload = $queue->payload;
                $job = json_decode($payload,true);
                $commandStr = $job['data']['command'];
                if(strstr($commandStr,'s:26')){
                    $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                    $command = explode('____',$commandStr);
                    $time = $command[1];
                    $delay = Carbon::parse($time)->addminutes($minutes);
                }else{
                    $delay = Carbon::now()->addminutes($minutes);
                }
                $chkQueue++;
            }
        }
        $result['chkQueue'] = $chkQueue;
        $result['delay'] = $delay;
        return $result;
    }

    public function isIPv6($ip)
    {
        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
            return true;
        else
            return false;
    }
}
