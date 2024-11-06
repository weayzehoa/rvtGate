<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\AcOrder as AcOrderDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\SellImport as SellImportDB;
use App\Models\Schedule as ScheduleDB;
use App\Jobs\AcOrderProcessJob;
use App\Jobs\AdminInvoiceJob;

use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use DB;

class AcOrderProcessScheduleJob implements ShouldQueue
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
        $acOrders = AcOrderDB::whereNotNull('order_number')->where(function($query){
            $query->where('is_sync',0)
            ->orWhere('is_sell',0)
            ->orWhereNull('purchase_no')
            ->orWhere('purchase_sync',0)
            ->orWhere('is_stockin',0)
            ->orWhere('is_invoice',0);
        })->where('created_at', '<', Carbon::now()->subMinutes(5)->toDateTimeString())
        ->orderBy('id','desc')->get();
        if(count($acOrders) > 0){
            foreach($acOrders as $acOrder){
                $order = OrderDB::with('acOrder','sellImport','itemData')->find($acOrder->order_id);
                if(!empty($order)){
                    //補開發票
                    if($acOrder->is_invoice == 0){
                        $invoiceParam['id'] = $order->id;
                        $invoiceParam['type'] = 'create';
                        $invoiceParam['model'] = 'acOrderOpenInvoice';
                        AdminInvoiceJob::dispatchNow($invoiceParam);
                    }
                    //檢查是否有建立sellImport資料, 如果沒有則建立
                    if(empty($order->sellImport)){
                        if(count($order->itemData) > 0){
                            foreach($order->itemData as $item){
                                $sellImport = SellImportDB::create([
                                    'import_no' => time(),
                                    'type' => 'warehouse',
                                    'order_number' => $order->order_number,
                                    'shipping_number' => $order->partner_order_number,
                                    'gtin13' => $item->sku,
                                    'purchase_no' => null,
                                    'digiwin_no' => null,
                                    'product_name' => $item->product_name,
                                    'quantity' => $item->quantity,
                                    'sell_date' => $order->book_shipping_date,
                                    'stockin_time' => $order->vendor_arrival_date,
                                    'status' => 0,
                                ]);
                            }
                        }
                    }
                    $param['acOrderId'] = $acOrder->id;
                    $param['orderId'] = $order->id;
                    $param['orderNumber'] = $order->order_number;
                    //背端處理檢查
                    $chkQueue = 0;
                    $delay = null;
                    $minutes = 1;
                    $jobName = 'AcOrderProcessJob';
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
                    if($chkQueue > 0){
                        !empty($delay) ? AcOrderProcessJob::dispatch($param)->delay($delay) : AcOrderProcessJob::dispatch($param);
                    }else{
                        AcOrderProcessJob::dispatch($param);
                    }
                }
            }
        }
        $schedule = ScheduleDB::where('code','checkAcOrderProcess')->first()->update(['last_update_time' => date('Y-m-d H:i:s')]);
    }
}
