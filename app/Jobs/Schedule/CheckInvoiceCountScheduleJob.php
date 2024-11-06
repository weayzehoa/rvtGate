<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SystemSetting as SystemSettingDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryGroupBuyingOrder as GroupbuyOrderDB;
use App\Jobs\AdminSendEmail;
use Carbon\Carbon;

class CheckInvoiceCountScheduleJob implements ShouldQueue
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
        $invoiceCount = SystemSettingDB::find(1)->invoice_count;
        $currentMonth = (INT)date('m');
        if($currentMonth%2 == 1){
            $startTime = Carbon::parse(date('Ymd'))->firstOfMonth()->toDateTimeString();
            $endTime =Carbon::parse(date('Ymd'))->addMonth()->lastOfMonth()->endOfday()->toDateTimeString();
        }else{
            $startTime = Carbon::parse(date('Ymd'))->subMonth()->firstOfMonth()->toDateTimeString();
            $endTime =Carbon::parse(date('Ymd'))->lastOfMonth()->endOfday()->toDateTimeString();
        }
        $count1 = OrderDB::whereNotNull('is_invoice_no')->whereBetween('invoice_time',[$startTime,$endTime])->count();
        $count2 = GroupbuyOrderDB::whereNotNull('is_invoice_no')->whereBetween('invoice_time',[$startTime,$endTime])->count();
        $count = $count1 + $count2;
        if($count >= ($invoiceCount-2000)){ //通知會計購買
            $param['count'] = $count;
            $param['total'] = $invoiceCount;
            $param['subject'] = '發票數量即將不足通知';
            $param['model'] = 'CheckInvoiceCountMailBody';
            $param['from'] = 'icarry@icarry.me'; //寄件者
            $param['name'] = 'iCarry中繼系統'; //寄件者名字
            env('APP_ENV') == 'local' ? $param['to'] = [env('TEST_MAIL_ACCOUNT')] : $param['to'] = ['icarryfn@icarry.me']; //會計群組信箱
            AdminSendEmail::dispatch($param);
        }
    }
}
