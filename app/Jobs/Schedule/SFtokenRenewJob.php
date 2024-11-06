<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemSetting as SystemSettingDB;
use App\Models\Schedule as ScheduleDB;
use Curl;

class SFtokenRenewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $SystemSetting = SystemSettingDB::find(1);
        $timestamp = time();
        $appKey = env('SF_APP_KEY');
        $appSecret = env('SF_APP_SECRET');
        $url = env('SF_API_URL')."token?appKey=$appKey&appSecret=$appSecret";
        $response = Curl::to($url)->withHeaders(['Content-Type:text/html','charset:utf-8','Accept:text/html'])->get();
        if(!empty($response)){
            $result = json_decode($response,true);
            if(isset($result['apiResultCode']) && $result['apiResultCode'] == 0){
                $exprieTime = date("Y-m-d H:i:s", $timestamp + $result['apiResultData']['expireIn'] - 200);
                $accessToken = $result['apiResultData']['accessToken'];
                $SFaccessToken = ['exprieTime' => $exprieTime, 'accessToken' => $accessToken];
                $SystemSetting->update(['sf_token' => json_encode($SFaccessToken,true)]);
            }
        }
        $schedule = ScheduleDB::where('code','sfTokenRenew')->first()->update(['last_update_time' => date('Y-m-d H:i:s')]);
    }
}
