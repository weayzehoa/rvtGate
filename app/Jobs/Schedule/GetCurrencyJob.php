<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemSetting as SettingDB;
use App\Models\Schedule as ScheduleDB;
use Curl;

class GetCurrencyJob implements ShouldQueue
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
        $setting = SettingDB::first();
        $names = ['USD','JPY','SGD','HKD','RMB','MYR','KRW'];
        for($i=0;$i<count($names);$i++){
            $name = $names[$i];
            $name == 'RMB' ? $name = 'CNY' : '';
            $url = "http://api.k780.com:88/?app=finance.rate&scur=$name&tcur=TWD&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4";
            $currency = Curl::to($url)->get();
            $currency = json_decode($currency,true);
            if(is_array($currency)){
                if($currency['success'] == 1 && !empty($currency['result'])){
                    $name == 'CNY' ? $name == 'RMB' : '';
                    $rate = $currency['result']['rate'];
                    $setting->update(['exchange_rate_'.$name => $rate]);
                }
            }
        }
        $schedule = ScheduleDB::where('code','getCurrency')->first()->update(['last_update_time' => date('Y-m-d H:i:s')]);
    }
}
