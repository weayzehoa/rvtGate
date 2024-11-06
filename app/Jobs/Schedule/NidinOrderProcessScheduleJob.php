<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\NidinOrder as NidinOrderDB;
use App\Models\Schedule as ScheduleDB;
use App\Models\NidinInvoiceLog as NidinInvoiceLogDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\NidinTicketLog as NidinTicketLogDB;

use App\Jobs\AdminInvoiceJob;
use App\Jobs\NidinOrderProcessJob;
use App\Jobs\iCarryOrderSynchronizeToDigiwinJob;
use App\Traits\NidinTicketFunctionTrait;
use App\Traits\UniversalFunctionTrait;

use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use DB;

class NidinOrderProcessScheduleJob implements ShouldQueue
{
    use UniversalFunctionTrait,NidinTicketFunctionTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $ticketStartDate = Carbon::today()->toDateString();
        $ticketEndDate = Carbon::today()->addDays(183)->toDateString();
        $expStartDate = str_replace('-','',$ticketStartDate);
        $expEndDate = str_replace('-','',$ticketEndDate);
        //找出未開票券
        $nidinOrders = NidinOrderDB::with('vendor','order','order.items','payment')->whereNotNull('order_number')->where('is_ticket',0)->orderBy('id','desc')->get();
        if(count($nidinOrders) > 0){
            foreach($nidinOrders as $nidinOrder){
                $order = $nidinOrder->order;
                if($order->status == 5){
                    $finish = 0;
                    $getData = json_decode($nidinOrder->get_json,true);
                    $getItems = $getData['items'];
                    $ticketItems = [];
                    $items = $order->items;
                    $message = '訂單建立完成。';
                    foreach($items as $item){
                        if(empty($item->ticket_no) && $item->is_del == 0){
                            $itemMemo = "$item->id";
                            for($i=0; $i<count($getItems);$i++){
                                if($getItems[$i]['set_no'] == $item->set_no && $getItems[$i]['product_num'] == $item->vendor_product_model_id){
                                    $itemMemo = $getItems[$i]['memo'];
                                    break;
                                }
                            }
                            $itemData = [
                                'autoSettlement' => "Y",
                                'count' => (INT)$item->quantity,
                                'expStartDate' => "$expStartDate",
                                'expEndDate' => "$expEndDate",
                                'issuedType' => "voucher",
                                'issuerId' => "acpay",
                                'itemAmount' => ($item->price - $item->discount) * $item->quantity,
                                'itemMemo' => $itemMemo,
                                'itemName' => mb_substr($item->product_name,0,78),
                                'itemNo' => $item->digiwin_no,
                                'merchantNo' => $nidinOrder->merchant_no,
                                'setNo' => $item->set_no,
                            ];
                            ksort($itemData);
                            $ticketItems[] = $itemData;
                        }
                    }
                    if(count($ticketItems) > 0){
                        $ticketLog = NidinTicketLogDB::create([
                            'type' => '訂單自動補開票券',
                            'from_nidin' => json_encode($ticketItems,JSON_UNESCAPED_UNICODE),
                            'nidin_order_no' => $nidinOrder->nidin_order_no,
                            'transaction_id' => $nidinOrder->transaction_id,
                            'platform_no' => $nidinOrder->merchant_no,
                            'key' => $nidinOrder->vendor->merchant_key,
                            'ip' => '::1',
                        ]);
                        $result = $this->nidinOpenTicket($nidinOrder, $ticketItems, $ticketLog);
                        if($result['rtnCode'] == 0){
                            if(count($result['items']) > 0){
                                $resultItems = $result['items'];
                                for($i=0;$i<count($resultItems);$i++){
                                    isset($resultItems[$i]['ticketNos']) && count($resultItems[$i]['ticketNos']) > 0 ? $ticketNo = $resultItems[$i]['ticketNos'][0] : $ticketNo = null;
                                    if(!empty($ticketNo)){
                                        $digiwinNo = $resultItems[$i]['itemNo'];
                                        $setNo = $resultItems[$i]['setNo'];
                                        $productModel = ProductModelDB::where('digiwin_no',$digiwinNo)->first();
                                        $orderItem = OrderItemDB::where([['order_id',$order->id],['set_no',$setNo],['product_model_id',$productModel->id],['is_del',0]])->whereNull('ticket_no')->first();
                                        !empty($orderItem) ? $orderItem->update(['ticket_no' => $ticketNo]) : '';
                                    }
                                }
                                if(count($resultItems) == count($ticketItems)){
                                    $nidinOrder->update(['is_ticket' => 1]);
                                    $order->update(['status' => 1]);
                                    $finish = 1;
                                    $message .= '補開票券完成。';
                                }else{
                                    $message .= '補開立票券API返回票數與實際需求數不符。';
                                }
                            }else{
                                $message .= '補開立票券API返回無票券資料。';
                            }
                        }else{
                            $message .= '開立票券API失敗。';
                        }
                    }

                    if($finish == 1 && $nidinOrder->is_invoice == 0){ //開發票
                        $invoiceParam['id'] = $nidinOrder->order_id;
                        $invoiceParam['type'] = 'create';
                        $invoiceParam['model'] = 'nidinOrderOpenInvoice';
                        $result = AdminInvoiceJob::dispatchNow($invoiceParam);
                        if(!empty($result) && !empty($result['info'])){
                            $pay2goInfo = json_decode($result['info'],true);
                            if(!empty($pay2goInfo['Result']) && strtoupper($pay2goInfo['Status']) == 'SUCCESS'){
                                $pay2goResult = json_decode($pay2goInfo['Result'],true);
                                if(!empty($pay2goResult['InvoiceNumber'])){
                                    $message .= "發票補開立完成。";
                                }else{
                                    $message .= "發票API補開立發票失敗。";
                                }
                            }else{
                                $message .= "發票API補開立發票失敗。";
                            }
                        }else{
                            $message .= "發票API補開立發票失敗。";
                        }
                    }
                    $nidinOrder->update(['message' => $message]);

                    if($nidinOrder->is_sync == 0){
                        $param['nidinOrderId'] = $nidinOrder->id;
                        $param['orderId'] = $order->id;
                        $param['orderNumber'] = $order->order_number;
                        //背端處理檢查
                        $chkQueue = 0;
                        $delay = null;
                        $minutes = 1;
                        $jobName = 'NidinOrderProcessJob';
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
                            !empty($delay) ? NidinOrderProcessJob::dispatch($param)->delay($delay) : NidinOrderProcessJob::dispatch($param);
                        }else{
                            NidinOrderProcessJob::dispatch($param);
                        }
                    }
                }
            }
        }

        //找出未開發票
        $nidinOrders = NidinOrderDB::whereNotNull('order_number')->where([['is_invoice',0],['is_ticket',1]])->orderBy('id','desc')->get();
        if(count($nidinOrders) > 0){
            foreach($nidinOrders as $nidinOrder){
                $message = '訂單建立完成。開立票券完成。';
                $invoiceParam['id'] = $nidinOrder->order_id;
                $invoiceParam['type'] = 'create';
                $invoiceParam['model'] = 'nidinOrderOpenInvoice';
                $result = AdminInvoiceJob::dispatchNow($invoiceParam);
                if(!empty($result) && !empty($result['info'])){
                    $pay2goInfo = json_decode($result['info'],true);
                    if(!empty($pay2goInfo['Result']) && strtoupper($pay2goInfo['Status']) == 'SUCCESS'){
                        $pay2goResult = json_decode($pay2goInfo['Result'],true);
                        if(!empty($pay2goResult['InvoiceNumber'])){
                            $message .= "發票補開立完成。";
                        }else{
                            $message .= "發票API補開立發票失敗。";
                        }
                    }else{
                        $message .= "發票API補開立發票失敗。";
                    }
                }else{
                    $message .= "發票API補開立發票失敗。";
                }
                $nidinOrder->update(['message' => $message]);
            }
        }

        //找出未同步
        $nidinOrders = NidinOrderDB::whereNotNull('order_number')->where([['is_sync',0],['is_invoice',1],['is_ticket',1]])->orderBy('id','desc')->get();
        if(count($nidinOrders) > 0){
            foreach($nidinOrders as $nidinOrder){
                $syncOrderParam['id'] = $nidinOrder->order_id;
                $syncOrderParam['admin_name'] = '中繼系統';
                $result = iCarryOrderSynchronizeToDigiwinJob::dispatchNow($syncOrderParam);
            }
        }

        $schedule = ScheduleDB::where('code','checkAcOrderProcess')->first()->update(['last_update_time' => date('Y-m-d H:i:s')]);
    }
}
