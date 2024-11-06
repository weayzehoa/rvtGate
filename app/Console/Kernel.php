<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Schedule as ScheduleDB;

use App\Jobs\Schedule\ProductUpdateScheduleJob as ProductUpdate;
use App\Jobs\Schedule\CustomerUpdateScheduleJob as CustomerUpdate;
use App\Jobs\Schedule\CleanExportFileScheduleJob as CleanExportFile;
use App\Jobs\Schedule\DigiwinPurchaseOrderSynchronizeJob as DigiwinPurchaseOrderSync;
use App\Jobs\Schedule\QuotationUpdateScheduleJob as QuotationUpdate;
use App\Jobs\Schedule\OrderInvoiceUpdateJob as OrderInvoiceUpdate;
use App\Jobs\Schedule\ErpProductCategoryUpdateToIcarryJob as ErpProductCategoryUpdate;
use App\Jobs\Schedule\CheckMailTemplateScheduleJob as CheckMailTemplate;
use App\Jobs\Schedule\CheckSFShippingStatusScheduleJob as CheckSFShippingStatus;
use App\Jobs\Schedule\HotProductSettingJob as HotProductSetting;
use App\Jobs\Schedule\ProductPriceChangeJob as ProductPriceChange;
use App\Jobs\Schedule\SFtokenRenewJob;
use App\Jobs\Schedule\ErpACRTCProcessJob;
use App\Jobs\Schedule\AcOrderProcessScheduleJob;
use App\Jobs\Schedule\GetCurrencyJob;
use App\Jobs\AdminInvoiceJob;
use App\Jobs\SellImportJob;
use App\Jobs\TicketSettleJob;
use App\Jobs\CheckTicketStatusJob;
use DB;
use Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     * @參考 https://laravel.com/docs/8.x/scheduling
     * @參考 https://learnku.com/docs/laravel/8.x/scheduling/9399
     * @在系統的 crontab -e 新增一行常駐 * * * * * cd /var/www/html/iCarryBackend && php artisan schedule:run >> ~/crontab_log 2>&1
     * @在系統的 /etc/rc.d/rc.local 新增一行常駐 cd /var/www/html/iCarryBackend && php artisan queue:work >> ~/laravel_queue_log &
     */
    protected function schedule(Schedule $schedule)
    {
        $param['admin_id'] = null;
        /* Get all schedule from the database */
        $tasks = ScheduleDB::where('is_on',1)->get();
        if(count($tasks) > 0){
            foreach ($tasks as $task) {
                $frequency = $task->frequency;
                $schedule->call(function() use($task) {

                    // 中介層邏輯 - 檢查資料庫連線
                    try {
                        DB::connection('mysql')->getPdo();
                        DB::connection('icarry')->getPdo();
                        DB::connection('icarryLang')->getPdo();
                        DB::connection('iCarrySMERP')->getPdo();
                    } catch (\Exception $e) {
                        return;
                    }

                    if($task->code == 'productUpdate'){
                        ProductUpdate::dispatchNow();
                    }
                    if($task->code == 'erpCustomerUpdateToIcarry'){
                        CustomerUpdate::dispatchNow();
                    }
                    if($task->code == 'cleanExportFile'){
                        CleanExportFile::dispatchNow();
                    }
                    if($task->code == 'erpPurchaseSyncToGate'){
                        $param['admin_id'] = 0;
                        DigiwinPurchaseOrderSync::dispatchNow($param);
                    }
                    if($task->code == 'erpQuotationUpdate'){
                        QuotationUpdate::dispatchNow();
                    }
                    if($task->code == 'OrderInvoiceUpdate'){
                        OrderInvoiceUpdate::dispatchNow();
                    }
                    if($task->code == 'erpProductCategoryUpdateToIcarry'){
                        ErpProductCategoryUpdate::dispatchNow();
                    }
                    if($task->code == 'VendorShippingSellData'){
                        $param['method'] = 'Schedule';
                        $param['type'] = 'directShip';
                        SellImportJob::dispatchNow($param);
                    }
                    if($task->code == 'ticketSellSettle'){
                        TicketSettleJob::dispatchNow();
                    }
                    if($task->code == 'checkTicketStatus'){
                        CheckTicketStatusJob::dispatchNow();
                    }
                    if($task->code == 'checkMailTemplate'){
                        CheckMailTemplate::dispatchNow();
                    }
                    if($task->code == 'OrderOpenInvoice'){
                        $param['model'] = 'OrderOpenInvoice';
                        $param['type'] = 'create';
                        AdminInvoiceJob::dispatchNow($param);
                    }
                    if($task->code == 'checkSFShippingStatus'){
                        CheckSFShippingStatus::dispatchNow();
                    }
                    if($task->code == 'hotProductSetting'){
                        HotProductSetting::dispatchNow();
                    }
                    if($task->code == 'productPriceChange'){
                        ProductPriceChange::dispatchNow();
                    }
                    if($task->code == 'checkAcOrderProcess'){
                        AcOrderProcessScheduleJob::dispatchNow();
                    }
                    if($task->code == 'sfTokenRenew'){
                        SFtokenRenewJob::dispatchNow();
                    }
                    if($task->code == 'erpACRTCProcess'){
                        ErpACRTCProcessJob::dispatchNow();
                    }
                    if($task->code == 'getCurrency'){
                        GetCurrencyJob::dispatchNow();
                    }
                    Log::info($task->code.' '.\Carbon\Carbon::now());
                })->$frequency();
            }
        }
    }
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
