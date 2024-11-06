<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryUser as UserDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\SyncedOrder as SyncedOrderDB;
use App\Models\SyncedInvoiceLog as SyncedInvoiceLogDB;
use App\Models\Sell as SellDB;
use App\Models\ErpOrder as ErpOrderDB;
use App\Models\ErpACRTA as ErpACRTADB;
use App\Models\ErpACRTB as ErpACRTBDB;
use App\Models\ErpTAXMC as ErpTAXMCDB;
use App\Models\iCarryGroupBuyingOrder as GroupBuyOrderDB;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use DB;

class OrderInvoiceUpdateJob implements ShouldQueue
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
        $createDate = date('Ymd');
        $createTime = date('H:i:s');
        $erpACRTATable = env('MSDB_SMERP_DATABASE').'.'.(new ErpACRTADB)->getTable();
        $erpACRTBTable = env('MSDB_SMERP_DATABASE').'.'.(new ErpACRTBDB)->getTable();
        //需要同步發票的客戶代號
        $v = DigiwinPaymentDB::where('is_invoice',1)->get()->pluck('customer_no')->all();
        $orders = OrderDB::with('syncedOrder','sell','customer','sells')
        ->whereIn('digiwin_payment_id',$v);
        $orders = $orders->where('status',3)
        ->where(function($q){
            $q->where(function($qq){
                $qq->where([['is_invoice',1],['invoice_time','>=','2022-10-01 00:00:00']])
                ->where(function($qqq){
                    $qqq->whereNotNull('is_invoice_no')
                    ->orWhere('is_invoice_no','!=',null)
                    ->orWhere('is_invoice_no','!=','');
                });
            })->orWhere('create_type','groupbuy');
        })->get();
        foreach($orders as $order){
            /**
             * 1.  用erpOrderNo(只要單別為A222)去找到A651的單別單號，
             *     然後用A651的單別+單號去關聯ACRTB.TB005(單別A651)與ACRTB.TB006(單號)，
             *     找到該筆的ACRTB.TB001(結帳單別A612)與ACRTB.TB002(結帳單號)
             * 2.  用上述的結帳單別、單號去關聯ACRTA.TA001與ACRTA.TA002，去更新發票資料的相關欄位
             * 3.  欄位如下
             *      ACRTA.TA015 發票號碼
             *      ACRTA.TA016 發票日期yyyyMMDD
             *      ACRTA.TA017 發票貨款 (未稅金額)
             *      ACRTA.TA018 發票稅額
             */
            $chkLog = SyncedInvoiceLogDB::where('order_number',$order->order_number)->first();
            if(!empty($order->syncedOrder) && !empty($order->sell) && empty($chkLog)){ //有同步且有銷貨單且沒紀錄過
                $A612 = null;
                //松果、嘖嘖及錢街判斷的方式不一樣, 不使用預收結帳單
                $sp = DigiwinPaymentDB::where([['is_invoice',1],['is_acrtb','!=',1]])->get()->pluck('customer_no')->all();
                $syncedOrder = $order->syncedOrder;
                $erpOrderNo = $syncedOrder->erp_order_no;
                if(in_array($order->digiwin_payment_id,$sp)){
                    foreach($order->sells as $sell){
                        //找出結帳單
                        $A612 = ErpACRTBDB::where([[$erpACRTBTable.'.TB001','A614'],[$erpACRTBTable.'.TB005','A231'],[$erpACRTBTable.'.TB006',$sell->erp_sell_no]])->first();
                        if(!empty($A612)){
                            break;
                        }
                    }
                }else{
                    $erpOrder = ErpOrderDB::where([['TC001','A222'],['TC002',$erpOrderNo]])->first();
                    $A651 = ErpACRTBDB::where([['TB001','A641'],['TB006',$erpOrderNo]])->first();
                    if(!empty($A651)){
                        $A651No = $A651->TB002;
                        //找出結帳單
                        $A612 = ErpACRTBDB::join($erpACRTATable,$erpACRTATable.'.TA002',$erpACRTBTable.'.TB002')
                        ->where([[$erpACRTBTable.'.TB001','A612'],[$erpACRTBTable.'.TB005','A651'],[$erpACRTBTable.'.TB006',$A651No],[$erpACRTATable.'.TA025','Y']])->first();
                    }
                }
                if(!empty($A612)){
                    $tax = 0;
                    if(in_array($order->digiwin_payment_id,$sp)){
                        $A612ACRTA = ErpACRTADB::where([['TA001','A614'],['TA002',$A612->TB002],['TA025','Y']])->first();
                    }else{
                        $A612ACRTA = ErpACRTADB::where([['TA001','A612'],['TA002',$A612->TB002],['TA025','Y']])->first();
                    }
                    $invoiceDate = explode(' ',$order->invoice_time)[0];
                    $invoiceDate = str_replace('-','',$invoiceDate);
                    $invoiceNo = $order->is_invoice_no;
                    $totalPrice = $order->amount + $order->shipping_fee + $order->parcel_tax - $order->discount - $order->spend_point;
                    $MA038 = $order->customer['MA038'];
                    //稅金計算
                    if(in_array($order->digiwin_payment_id,$v)){
                        if($order->ship_to == '台灣'){
                            $totalPrice = round($totalPrice / 1.05, 0);
                            $tax = round($totalPrice * 0.05, 0);
                        }elseif($order->ship_to != '台灣' && ($order->invoice_type == 3 || !empty($order->carrier_num))){
                            $totalPrice = round($totalPrice / 1.05, 0);
                            $tax = round($totalPrice * 0.05, 0);
                        }
                    }else{
                        if($MA038 == 2){ //應稅外加, 除以1.05
                            $totalPrice = round($totalPrice / 1.05, 0);
                            $tax = round($totalPrice * 0.05, 0);
                        }elseif($MA038 == 1){
                            $totalPrice = round($totalPrice / 1.05, 0);
                            $tax = round($totalPrice * 0.05, 0);
                        }else{
                            $tax = 0;
                        }
                    }
                    if(in_array($order->digiwin_payment_id,$sp)){
                        ErpACRTADB::where([['TA001','A614'],['TA002',$A612->TB002],['TA025','Y']])
                        ->update([
                            'TA015' => $invoiceNo,
                            'TA016' => $invoiceDate,
                            'TA017' => $totalPrice,
                            'TA018' => $tax,
                        ]);
                    }else{
                        if(strtolower($order->create_type) != 'groupbuy'){
                            //iCarry Web建立
                            ErpACRTADB::where([['TA001','A612'],['TA002',$A612->TB002],['TA025','Y']])
                            ->update([
                                'TA015' => $invoiceNo,
                                'TA016' => $invoiceDate,
                                'TA017' => $totalPrice,
                                'TA018' => $tax,
                            ]);
                        }else{ //團購發票TAXMC建立
                            $groupBuyMaster = UserDB::find($order->user_id);
                            $groupBuyOrders = GroupBuyDB::where([['partner_order_number',$order->order_number],['status',3]])->get();
                            foreach($groupBuyOrders as $groupBuyOrder){
                                $chkLog = SyncedInvoiceLogDB::where('order_number',$groupBuyOrder->order_number)->first();
                                if(!empty($groupBuyOrder->is_invoice_no) && empty($chkLog)){
                                    $goTotalPrice = round( ($groupBuyOrder->amount + $groupBuyOrder->shipping_fee + $groupBuyOrder->parcel_tax - $groupBuyOrder->discount + $groupBuyOrder->spend_point) / 1.05, 0);
                                    $goTax = round(($goTotalPrice * 0.05),0);
                                    $tmp = ErpTAXMCDB::orderBy('MC006','desc')->frist();
                                    $invoiceTime = $groupBuyOrder->invoice_time;
                                    !empty($tmp) ? $MC006 = str_pad((ltrim($tmp->MC006,'0') + 1),7,0,STR_PAD_LEFT) : $MC006 = '0000001';
                                    ErpTAXMCDB::create([
                                        'COMPANY' => 'iCarry',
                                        'CREATOR' => 'iCarryGate',
                                        'USR_GROUP' => 'DSC',
                                        'CREATE_DATE' => $createDate,
                                        'FLAG' => 1,
                                        'CREATE_TIME' => $createTime,
                                        'CREATE_AP' => 'iCarry',
                                        'CREATE_PRID' => 'TAXB01',
                                        'MC001' => 'iCarry', //申報公司
                                        'MC002' => substr(str_replace('-','',explode(' ',$invoiceTime)[0]),0,6), //申報年月
                                        'MC004' => '31', //格式代號
                                        'MC005' => '080708384', //稅籍編號
                                        'MC006' => $MC006, //流水號
                                        'MC007' => str_replace('-','',explode(' ',$invoiceTime)[0]), //開立日期
                                        'MC008' =>"$groupBuyOrder->invoice_number", //買方統一編號
                                        'MC009' => '46452701', //賣方統一編號
                                        'MC010' => $groupBuyOrder->is_invoice_no, //發票號碼
                                        'MC011' => $goTotalPrice, //銷售金額
                                        'MC012' => 1, //課稅別
                                        'MC013' => $goTax, //營業稅額
                                        'MC014' => '', //扣抵代號
                                        'MC015' => '', //空白欄位
                                        'MC016' => 'N', //彙加註記
                                        'MC017' => 'N', //洋菸酒註記
                                        'MC018' => "團購主訂單號 $order->order_number ，子訂單號 $groupBuyOrder->order_number ", //備註
                                        'MC019' => '1', //來源方式
                                        'MC020' => $A612ACRTA->TA001, //來源單別
                                        'MC021' => $A612ACRTA->TA002, //來源單號
                                        'MC022' => $A612ACRTA->TA004, //買受人代號
                                        'MC023' => $groupBuyMaster->name, //買受人簡稱
                                    ]);
                                    $log = SyncedInvoiceLogDB::create([
                                        'order_id' => $groupBuyOrder->id,
                                        'order_number' => $groupBuyOrder->order_number,
                                        'erp_order_no' => $erpOrderNo,
                                        'invoice_no' => $groupBuyOrder->is_invoice_no,
                                        'invoice_time' => $groupBuyOrder->invoice_time,
                                        'invoice_price' => $goTotalPrice,
                                        'invoice_tax' => $goTax,
                                        'create_type' => $groupBuyOrder->create_type,
                                    ]);
                                    $groupBuyOrder->update(['status' => 4]);
                                }
                            }
                        }
                    }
                    $log = SyncedInvoiceLogDB::create([
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'erp_order_no' => $erpOrderNo,
                        'invoice_no' => $order->is_invoice_no,
                        'invoice_time' => $order->invoice_time,
                        'invoice_price' => $totalPrice,
                        'invoice_tax' => $tax,
                        'create_type' => $order->create_type,
                    ]);
                    $order->update(['status' => 4]);
                }
            }
        }
    }
}
