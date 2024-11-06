<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\ErpPURTC as ErpPURTCDB;
use App\Models\ErpPURTD as ErpPURTDDB;
use App\Models\ErpPURTG as ErpPURTGDB;
use App\Models\ErpPURTH as ErpPURTHDB;
use App\Models\ErpPURTI as ErpPURTIDB;
use App\Models\ErpPURTJ as ErpPURTJDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\SyncedOrderItemPackage as SyncedOrderItemPackageDB;
use App\Models\AutoStockinProduct as AutoStockinProductDB;
use App\Models\StockinImport as StockinImportDB;
use App\Models\iCarryTicket as TicketDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;

use App\Traits\PurchaseOrderFunctionTrait;
use App\Jobs\PurchaseStockinImportJob;

use Log;

class PurchaseOrderSynchronizeToDigiwinJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,PurchaseOrderFunctionTrait;

    protected $param;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        //找出採購單資料, 包含商品資料
        $purchaseOrders = $this->getPurchaseOrderData($this->param);
        !empty(auth('gate')->user()->account) ? $creator = strtoupper(auth('gate')->user()->account) : $creator = 'DS';
        $createDate = date('Ymd');
        $createTime = date('H:i:s');
        isset($this->param['admin_id']) ? $adminId = $this->param['admin_id'] : $adminId = 0;
        $autoStockinProducts = AutoStockinProductDB::get()->pluck('digiwin_no')->all();
        $autoStockins = [];
        $importNo = time().rand(10,99);
        $importNo = (INT)$importNo;
        $warehouseExportTime = date('Y-m-d H:i:s');
        $warehouseStockinNo = date('ymdHis');
        //找出今日最後一筆採購單單號
        foreach($purchaseOrders as $order){
            !empty($order->purchase_date) ? $six = date('ymd',strtotime($order->purchase_date)) : $six = date('ymd');
            $TC002_A331 = ErpPURTCDB::where([['TC002','like',"$six%"],['TC001',$order->type]])->select('TC002')->orderBy('TC002','desc')->first();
            !empty($TC002_A331) ? $erpPurchaseOrderNo = $TC002_A331->TC002 + 1 : $erpPurchaseOrderNo = $six.str_pad(1,5,0,STR_PAD_LEFT);
            !empty($order->digiwin_vendor_no) ? $erpVendor = ErpVendorDB::find($order->digiwin_vendor_no) : $erpVendor = ErpVendorDB::find('A'.str_pad($order->vendor_id,5,0,STR_PAD_LEFT));
            $status = $order->status;
            $orderData = $itemData = [];
            $chkVendorArrivalDate = $amount = $tax = 0;
            if(!empty($order->erp_purchase_no)){ //採購單已在鼎新中
                //上次數量 金額 比對 不相同 或者 此次狀態等於-1 且 上次狀態不等於 -1 才做同步
                if($order->amount != $order->syncedLog['amount'] || $order->quantity != $order->syncedLog['quantity'] || ($order->status == -1 || $order->arrival_date_changed == 1 && $order->syncedLog['status'] != -1)){
                    if($order->status == -1){ //取消訂單
                        $erpItems = ErpPURTDDB::where([['TD001',$order->type],['TD002',$order->erp_purchase_no]])->update(['TD016' => 'y']);
                    }else{
                        $erpPurchaseOrder = ErpPURTCDB::where([['TC001',$order->type],['TC002',$order->erp_purchase_no]])->first();
                        foreach($order->items as $item){
                            if(strstr($item->sku,'BOM')){
                                foreach($item->package as $package){
                                    if(count($package->stockins) > 0){ //如果有入庫單 需先檢查是否已結帳則不給修改.
                                        foreach($package->stockins as $stockin){
                                            $erpStockinItem = ErpPURTHDB::where([['TH002',$stockin->erp_stockin_no],['TH003',$stockin->erp_stockin_sno],['TH012',$stockin->erp_purchase_no],['TH013',$stockin->erp_purchase_sno]])->first();
                                            //入庫已結帳的進貨商品則不給修改
                                            if($erpStockinItem->TH031 == 'N'){
                                                if($erpVendor->MA044 == 2){
                                                    $stockinItemPrice = $stockin->purchase_price;
                                                    $stockinPrice = $stockin->stockin_quantity * $stockinItemPrice;
                                                }else{
                                                    $stockinItemPrice = $stockin->purchase_price / 1.05;
                                                    $stockinPrice = $stockin->stockin_quantity * $stockinItemPrice * 1.05;
                                                }
                                                ErpPURTHDB::where([['TH002',$stockin->erp_stockin_no],['TH003',$stockin->erp_stockin_sno],['TH012',$stockin->erp_purchase_no],['TH013',$stockin->erp_purchase_sno]])->update([
                                                    'TH018' => round($package->purchase_price,4),
                                                    'TH019' => round($stockinPrice,0),
                                                    'TH045' => round($stockin->stockin_quantity * $stockinItemPrice,0), //原幣未稅金額
                                                    'TH046' => round($stockin->stockin_quantity * $stockinItemPrice * 0.05,0), //原幣稅額
                                                    'TH047' => round($stockin->stockin_quantity * $stockinItemPrice,0), //本幣未稅金額
                                                    'TH048' => round($stockin->stockin_quantity * $stockinItemPrice * 0.05,0), //本幣稅額
                                                ]);
                                                // 改用將進貨單資料拉出來, 重新統計後更新
                                                $TH019 = $TH045 = $TH046 = 0;
                                                $erpStockinItems = ErpPURTHDB::where('TH002',$stockin->erp_stockin_no)->get();
                                                if(count($erpStockinItems) > 0){
                                                    foreach($erpStockinItems as $stockinItem){
                                                        $TH019 += $stockinItem->TH019;
                                                        $TH045 += $stockinItem->TH045;
                                                        $TH046 += $stockinItem->TH046;
                                                    }
                                                }
                                                ErpPURTGDB::where('TG002',$stockin->erp_stockin_no)->update([
                                                    'TG017' => round($TH019 ,0),
                                                    'TG019' => round($TH046 ,0),
                                                    'TG028' => round($TH045 ,0),
                                                    'TG031' => round($TH045 ,0),
                                                    'TG032' => round($TH046 ,0),
                                                ]);
                                            }
                                        }
                                        if(count($package->returns) > 0){
                                            foreach($package->returns as $return){
                                                $erpReturnItem = ErpPURTJDB::where([['TJ001','A351'],['TJ002',$return->erp_return_discount_no],['TJ003',$return->erp_return_discount_sno],['TJ016',$return->erp_purchase_type],['TJ017',$return->erp_purchase_no],['TJ018',$return->erp_purchase_sno]])->first();
                                                if($erpReturnItem['TJ021'] == 'N'){
                                                    if($erpVendor->MA044 == 2){
                                                        $returnItemPrice = $return->purchase_price;
                                                        $returnPrice = $return->quantity * $return->purchase_price;
                                                    }else{
                                                        $returnItemPrice = $return->purchase_price / 1.05;
                                                        $returnPrice = $return->quantity * $return->purchase_price * 1.05;
                                                    }
                                                    ErpPURTJDB::where([['TJ001','A351'],['TJ002',$return->erp_return_discount_no],['TJ003',$return->erp_return_discount_sno],['TJ016',$return->erp_purchase_type],['TJ017',$return->erp_purchase_no],['TJ018',$return->erp_purchase_sno]])
                                                    ->update([
                                                        'TJ008' => round($return->purchase_price,4), //單價
                                                        'TJ009' => $return->quantity, //數量
                                                        'TJ010' => round($returnPrice,0), //金額
                                                        'TJ030' => round($return->quantity * $returnItemPrice,0), //原幣未稅金額
                                                        'TJ031' => round($return->quantity * $returnItemPrice * 0.05,0), //原幣稅額
                                                        'TJ032' => round($return->quantity * $returnItemPrice,0), //本幣未稅金額
                                                        'TJ033' => round($return->quantity * $returnItemPrice * 0.05,0), //本幣稅額
                                                    ]);
                                                    $erpReturn = ErpPURTIDB::where('TI001','A351')->find($return->erp_return_discount_no);
                                                    ErpPURTIDB::where([['TI001','A351'],['TI002',$return->erp_return_discount_no]])
                                                    ->update([
                                                        'TI011' => round($erpReturn->TI011 - ($erpReturnItem->TJ030 - round($return->quantity * $returnItemPrice,0))), //原幣退貨金額
                                                        'TI015' => round($erpReturn->TI015 - ($erpReturnItem->TJ031 - round($return->quantity * $returnItemPrice * 0.05,0))), //原幣退貨稅額
                                                        'TI028' => round($erpReturn->TI028 - ($erpReturnItem->TJ030 - round($return->quantity * $returnItemPrice,0))), //本幣退貨金額
                                                        'TI029' => round($erpReturn->TI029 - ($erpReturnItem->TJ031 - round($return->quantity * $returnItemPrice * 0.05,0))), //本幣退貨稅額
                                                    ]);
                                                }
                                            }
                                        }
                                        if($erpVendor->MA044 == 2){
                                            $purchaseItemPrice = $package->purchase_price / 1.05;
                                        }else{
                                            $purchaseItemPrice = $package->purchase_price;
                                        }
                                        //從採購單品資料表中找出鼎新相關採購單商品
                                        $tmp = PurchaseOrderItemSingleDB::where([['type',$order->type],['purchase_no',$order->purchase_no],['poi_id',$item->id],['poip_id',$package->id]])->first();
                                        //注意, MSSQL的UPDATE方式，只能使用下面方法
                                        // $erpItem = ErpPURTDDB::where([['TD002',"$order->erp_purchase_no"],['TD003',"$tmp->erp_purchase_sno"],['TD004',"$item->digiwin_no"]])->first();
                                        // $erpItem->update();
                                        // 以上方式使用條件將資料撈出後再去更新將會造成所有相關資料一起被更新造成錯誤。
                                        ErpPURTDDB::where([['TD001',$order->type],['TD002',"$order->erp_purchase_no"],['TD003',"$tmp->erp_purchase_sno"]])->update([
                                            'TD008' => $package->quantity, //採購數量
                                            'TD010' => round($purchaseItemPrice,4), //單價
                                            'TD011' => round($package->quantity * $purchaseItemPrice,0), //金額
                                            'TD012' => str_replace('-','',$package->vendor_arrival_date), //預交日
                                            'TD016' => $item->is_del == 1 || $item->is_close == 1 ? 'y' : 'N', //結案碼
                                            'TD033' => $package->quantity, //計價數量
                                        ]);
                                    }else{ //沒有入庫單則直接更新採購單內的商品資料
                                        if($erpVendor->MA044 == 2){
                                            $purchaseItemPrice = $package->purchase_price / 1.05;
                                        }else{
                                            $purchaseItemPrice = $package->purchase_price;
                                        }
                                        //從採購單品資料表中找出鼎新相關採購單
                                        $tmp = PurchaseOrderItemSingleDB::where([['type',$order->type],['purchase_no',$order->purchase_no],['poi_id',$item->id],['poip_id',$package->id]])->first();
                                        //注意, MSSQL的UPDATE方式，只能使用下面方法
                                        // $erpItem = ErpPURTDDB::where([['TD002',"$order->erp_purchase_no"],['TD003',"$tmp->erp_purchase_sno"],['TD004',"$item->digiwin_no"]])->first();
                                        // $erpItem->update();
                                        // 以上方式使用條件將資料撈出後再去更新將會造成所有相關資料一起被更新造成錯誤。
                                        ErpPURTDDB::where([['TD001',$order->type],['TD002',"$order->erp_purchase_no"],['TD003',"$tmp->erp_purchase_sno"]])
                                        ->update([
                                            'TD008' => $tmp->quantity, //採購數量
                                            'TD010' => round($purchaseItemPrice,4), //單價
                                            'TD011' => round($tmp->quantity * $purchaseItemPrice,0), //金額
                                            'TD012' => str_replace('-','',$tmp->vendor_arrival_date), //預交日
                                            'TD016' => $item->is_del == 1 || $item->is_close == 1 ? 'y' : 'N', //結案碼
                                            'TD033' => $tmp->quantity, //計價數量
                                        ]);
                                    }
                                }
                            }else{
                                if(count($item->stockins) > 0){ //如果有入庫單 需先檢查是否已結帳則不給修改.
                                    foreach($item->stockins as $stockin){
                                        $erpStockinItem = ErpPURTHDB::where([['TH002',$stockin->erp_stockin_no],['TH003',$stockin->erp_stockin_sno],['TH012',$stockin->erp_purchase_no],['TH013',$stockin->erp_purchase_sno]])->first();
                                        if(!empty($erpStockinItem)){
                                            //入庫已結帳的進貨商品則不給修改
                                            if($erpStockinItem->TH031 == 'N'){
                                                if($erpVendor->MA044 == 2){
                                                    $stockinItemPrice = $stockin->purchase_price;
                                                    $stockinPrice = $stockin->stockin_quantity * $stockinItemPrice;
                                                }else{
                                                    $stockinItemPrice = $stockin->purchase_price / 1.05;
                                                    $stockinPrice = $stockin->stockin_quantity * $stockinItemPrice * 1.05;
                                                }
                                                ErpPURTHDB::where([['TH002',$stockin->erp_stockin_no],['TH003',$stockin->erp_stockin_sno],['TH012',$stockin->erp_purchase_no],['TH013',$stockin->erp_purchase_sno]])->update([
                                                    'TH018' => round($stockin->purchase_price,4),
                                                    'TH019' => round($stockinPrice,0),
                                                    'TH045' => round($stockin->stockin_quantity * $stockinItemPrice,0), //原幣未稅金額
                                                    'TH046' => round($stockin->stockin_quantity * $stockinItemPrice * 0.05,0), //原幣稅額
                                                    'TH047' => round($stockin->stockin_quantity * $stockinItemPrice,0), //本幣未稅金額
                                                    'TH048' => round($stockin->stockin_quantity * $stockinItemPrice * 0.05,0), //本幣稅額
                                                ]);
                                                // 改用將進貨單資料拉出來, 重新統計後更新
                                                $TH019 = $TH045 = $TH046 = 0;
                                                $erpStockinItems = ErpPURTHDB::where('TH002',$stockin->erp_stockin_no)->get();
                                                if(count($erpStockinItems) > 0){
                                                    foreach($erpStockinItems as $stockinItem){
                                                        $TH019 += $stockinItem->TH019;
                                                        $TH045 += $stockinItem->TH045;
                                                        $TH046 += $stockinItem->TH046;
                                                    }
                                                }
                                                ErpPURTGDB::where('TG002',$stockin->erp_stockin_no)->update([
                                                    'TG017' => round($TH019 ,0),
                                                    'TG019' => round($TH046 ,0),
                                                    'TG028' => round($TH045 ,0),
                                                    'TG031' => round($TH045 ,0),
                                                    'TG032' => round($TH046 ,0),
                                                ]);
                                            }
                                        }
                                    }
                                    if(count($item->returns) > 0){
                                        foreach($item->returns as $return){
                                            $erpReturnItem = ErpPURTJDB::where([['TJ002',$return->erp_return_discount_no],['TJ003',$return->erp_return_discount_sno],['TJ016',$return->erp_purchase_type],['TJ017',$return->erp_purchase_no],['TJ018',$return->erp_purchase_sno]])->first();
                                            if(!empty($erpReturnItem)){
                                                if($erpReturnItem['TJ021'] == 'N'){
                                                    if($erpVendor->MA044 == 2){
                                                        $returnItemPrice = $return->purchase_price;
                                                        $returnPrice = $return->quantity * $return->purchase_price;
                                                    }else{
                                                        $returnItemPrice = $return->purchase_price / 1.05;
                                                        $returnPrice = $return->quantity * $return->purchase_price * 1.05;
                                                    }
                                                    ErpPURTJDB::where([['TJ002',$return->erp_return_discount_no],['TJ003',$return->erp_return_discount_sno],['TJ016',$return->erp_purchase_type],['TJ017',$return->erp_purchase_no],['TJ018',$return->erp_purchase_sno]])->update([
                                                        'TJ008' => round($return->purchase_price,4), //單價
                                                        'TJ009' => $return->quantity, //數量
                                                        'TJ010' => round($returnPrice,0), //金額
                                                        'TJ030' => round($return->quantity * $returnItemPrice,0), //原幣未稅金額
                                                        'TJ031' => round($return->quantity * $returnItemPrice * 0.05,0), //原幣稅額
                                                        'TJ032' => round($return->quantity * $returnItemPrice,0), //本幣未稅金額
                                                        'TJ033' => round($return->quantity * $returnItemPrice * 0.05,0), //本幣稅額
                                                    ]);
                                                    $erpReturn = ErpPURTIDB::find($return->erp_return_discount_no);
                                                    $erpReturn->update([
                                                        'TI011' => round($erpReturn->TI011 - ($erpReturnItem->TJ030 - round($return->quantity * $returnItemPrice,0))), //原幣退貨金額
                                                        'TI015' => round($erpReturn->TI015 - ($erpReturnItem->TJ031 - round($return->quantity * $returnItemPrice * 0.05,0))), //原幣退貨稅額
                                                        'TI028' => round($erpReturn->TI028 - ($erpReturnItem->TJ030 - round($return->quantity * $returnItemPrice,0))), //本幣退貨金額
                                                        'TI029' => round($erpReturn->TI029 - ($erpReturnItem->TJ031 - round($return->quantity * $returnItemPrice * 0.05,0))), //本幣退貨稅額
                                                    ]);
                                                }
                                            }
                                        }
                                    }
                                    if($erpVendor->MA044 == 2){
                                        $purchaseItemPrice = $item->purchase_price / 1.05;
                                    }else{
                                        $purchaseItemPrice = $item->purchase_price;
                                    }
                                    $tmp = PurchaseOrderItemSingleDB::where([['purchase_no',$order->purchase_no],['poi_id',$item->id],['poip_id',null]])->first();
                                    //注意, MSSQL的UPDATE方式，只能使用下面方法
                                    // $erpItem = ErpPURTDDB::where([['TD002',"$order->erp_purchase_no"],['TD003',"$tmp->erp_purchase_sno"],['TD004',"$item->digiwin_no"]])->first();
                                    // $erpItem->update();
                                    // 以上方式使用條件將資料撈出後再去更新將會造成所有相關資料一起被更新造成錯誤。
                                    ErpPURTDDB::where([['TD001',$order->type],['TD002',"$order->erp_purchase_no"],['TD003',"$tmp->erp_purchase_sno"]])->update([
                                        'TD008' => $item->quantity, //採購數量
                                        'TD010' => round($purchaseItemPrice,4), //單價
                                        'TD011' => round($item->quantity * $purchaseItemPrice,0), //金額
                                        'TD012' => str_replace('-','',$item->vendor_arrival_date), //預交日
                                        'TD016' => $item->is_del == 1 || $item->is_close == 1 ? 'y' : 'N', //結案碼
                                        'TD033' => $item->quantity, //計價數量
                                    ]);
                                }else{
                                    if($erpVendor->MA044 == 2){
                                        $purchaseItemPrice = $item->purchase_price / 1.05;
                                    }else{
                                        $purchaseItemPrice = $item->purchase_price;
                                    }
                                    $tmp = PurchaseOrderItemSingleDB::where([['type',$order->type],['purchase_no',$order->purchase_no],['poi_id',$item->id],['poip_id',null]])->first();
                                    //注意, MSSQL的UPDATE方式，只能使用下面方法
                                    // $erpItem = ErpPURTDDB::where([['TD002',"$order->erp_purchase_no"],['TD003',"$tmp->erp_purchase_sno"],['TD004',"$item->digiwin_no"]])->first();
                                    // $erpItem->update();
                                    // 以上方式使用條件將資料撈出後再去更新將會造成所有相關資料一起被更新造成錯誤。
                                    ErpPURTDDB::where([['TD001',$order->type],['TD002',"$order->erp_purchase_no"],['TD003',"$tmp->erp_purchase_sno"]])->update([
                                        'TD008' => $item->quantity, //採購數量
                                        'TD010' => round($purchaseItemPrice,4), //單價
                                        'TD011' => round($item->quantity * $purchaseItemPrice,0), //金額
                                        'TD012' => str_replace('-','',$item->vendor_arrival_date), //預交日
                                        'TD016' => $item->is_del == 1 || $item->is_close == 1 ? 'y' : 'N', //結案碼
                                        'TD033' => $item->quantity, //計價數量
                                    ]);
                                }
                            }
                        }
                        //更新中繼採購單及鼎新採購單資料
                        $amount = $tax = 0;
                        $erpPurchaseItems = ErpPURTDDB::where([['TD001',$order->type],['TD002',$order->erp_purchase_no]])->get();
                        foreach($erpPurchaseItems as $erpPurchaseItem){
                            $amount += $erpPurchaseItem->TD008 * $erpPurchaseItem->TD010;
                        }
                        if($erpVendor->MA044 == 1){
                            $amount = $amount / 1.05;
                            $tax = $amount * 0.05;
                        }elseif($erpVendor->MA044 == 2){
                            $tax = $amount * 0.05;
                        }elseif($erpVendor->MA044 == 3){
                            $tax = 0;
                        }
                        ErpPURTCDB::where([['TC001',$order->type],['TC002',$order->erp_purchase_no]])
                        ->update([
                            'TC019' => round($amount,0), //採購金額
                            'TC020' => round($tax,0), //稅額
                            'TC023' => round($order->quantity,0), //數量合計
                        ]);
                        $order->update([
                            'amount' => round($amount,0),
                            'tax' => round($tax,0),
                        ]);
                    }
                    $log = [
                        'admin_id' => $adminId,
                        'vendor_id' => $order->vendor_id,
                        'purchase_order_id' => $order->id,
                        'quantity' => $order->quantity,
                        'amount' => round($amount,0),
                        'tax' => round($tax,0),
                        'status' => $status,
                    ];
                    //更新同步日期
                    $order->update(['synced_time' => date('Y-m-d H:i:s')]);
                }
            }else{ //不存在 要新增
                $status = 1;
                if(count($order->items) > 0){ //有商品才建立
                    $i=0;
                    foreach($order->items as $item){
                        //檢查item是否為票券, 庫別為W14
                        if($item->product_category_id == 17){
                            $TD007 = 'W14';
                        }else{
                            $item->direct_shipment == 1 ? $TD007 = 'W02' : $TD007 = 'W01';
                        }
                        //檢查是否為acOrder庫別為W16
                        !empty($order->acOrder) ? $TD007 = 'W16' : '';
                        in_array($order->vendor_id,[723,729,730]) ? $TD007 = 'W16' : '';
                        if(strstr($item->sku,'BOM')){ //組合商品
                            foreach($item->package as $package){
                                //檢查是否為自動入庫, 是則產生資料
                                if($package->product_category_id == 17 || in_array($package->digiwin_no,$autoStockinProducts) && count($package->stockins) == 0){
                                    $purchaseNos[] = $order->purchase_no;
                                    $autoStockins[] = [
                                        'import_no' => $importNo,
                                        'warehouse_export_time' => $warehouseExportTime,
                                        'warehouse_stockin_no' => $warehouseStockinNo,
                                        'vendor_id' => $package->vendor_id,
                                        'gtin13' => $package->digiwin_no,
                                        'product_name' => $item->product_name,
                                        'expected_quantity' => $package->quantity,
                                        'stockin_quantity' => $package->quantity,
                                        'stockin_time' => $item->vendor_arrival_date.' '.date('H:i:s'),
                                        'purchase_nos' => $order->purchase_no,
                                        'direct_shipment' => $item->direct_shipment,
                                        'created_at' => date('Y-m-d H:i:s'),
                                    ];
                                }
                                $erpVendor->MA044 == 2 ? $TH018 = $package->purchase_price / 1.05 : $TH018 = $package->purchase_price;
                                $amount += round($package->quantity * $TH018 ,0);
                                $itemData[] = [
                                    'COMPANY' => 'iCarry',
                                    'CREATOR' => $creator,
                                    'USR_GROUP' => 'DSC',
                                    'CREATE_DATE' => $createDate,
                                    'FLAG' => 1,
                                    'CREATE_TIME' => $createTime,
                                    'CREATE_AP' => 'iCarry',
                                    'CREATE_PRID' => 'PURI07',
                                    'TD001' => 'A331', //單別
                                    'TD002' => $erpPurchaseOrderNo, //單號
                                    'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號
                                    'TD004' => $package->digiwin_no, //品號
                                    'TD005' => mb_substr($package->product_name,0,110,'utf8'), //品名
                                    'TD006' => mb_substr($package->serving_size,0,110,'utf8'), //規格
                                    'TD007' => $TD007, //此部分依照廠商組合品的設定
                                    'TD008' => $package->quantity, //採購數量
                                    'TD009' => $package->unit_name, //單位
                                    'TD010' => round($TH018,4), //單價
                                    'TD011' => round($package->quantity * $TH018,0), //金額
                                    'TD012' => str_replace('-','',$package->vendor_arrival_date), //預交日
                                    'TD013' => 'A222', //參考單別
                                    'TD014' => null, //備註
                                    'TD015' => 0, //已交數量
                                    'TD016' => $item->is_del == 1 ? 'y' : 'N', //結案碼
                                    'TD018' => 'Y', //確認碼
                                    'TD019' => 0, //庫存數量
                                    'TD025' => 'N', //急料
                                    'TD033' => $package->quantity, //計價數量
                                    'TD034' => $package->unit_name, //計價單位
                                ];
                                //更新erp採購單號到syncedOrderItemPackage
                                $package->update(['erp_purchase_no' => $erpPurchaseOrderNo]);
                                $tmp = PurchaseOrderItemSingleDB::where([['purchase_no',$order->purchase_no],['poi_id',$item->id],['poip_id',$package->id]])->first();
                                $tmp->update(['erp_purchase_no' => $erpPurchaseOrderNo, 'erp_purchase_sno' => str_pad(($i+1),4,0,STR_PAD_LEFT)]);
                                $i++;
                            }
                        }else{ //單一商品
                            //檢查是否為自動入庫, 是則產生資料
                            if($item->product_category_id == 17 || in_array($item->digiwin_no,$autoStockinProducts) && count($item->stockins) == 0){
                                $purchaseNos[] = $order->purchase_no;
                                $autoStockins[] = [
                                    'import_no' => $importNo,
                                    'warehouse_export_time' => $warehouseExportTime,
                                    'warehouse_stockin_no' => $warehouseStockinNo,
                                    'vendor_id' => $item->vendor_id,
                                    'gtin13' => $item->digiwin_no,
                                    'product_name' => $item->product_name,
                                    'expected_quantity' => $item->quantity,
                                    'stockin_quantity' => $item->quantity,
                                    'stockin_time' => $item->vendor_arrival_date.' '.date('H:i:s'),
                                    'purchase_nos' => $order->purchase_no,
                                    'direct_shipment' => $item->direct_shipment,
                                    'created_at' => date('Y-m-d H:i:s'),
                                ];
                            }
                            $erpVendor->MA044 == 2 ? $TH018 = $item->purchase_price / 1.05 : $TH018 = $item->purchase_price;
                            $amount += round($item->quantity * $TH018 ,0);
                            $itemData[] = [
                                'COMPANY' => 'iCarry',
                                'CREATOR' => $creator,
                                'USR_GROUP' => 'DSC',
                                'CREATE_DATE' => $createDate,
                                'FLAG' => 1,
                                'CREATE_TIME' => $createTime,
                                'CREATE_AP' => 'iCarry',
                                'CREATE_PRID' => 'PURI07',
                                'TD001' => 'A331', //單別
                                'TD002' => $erpPurchaseOrderNo, //單號
                                'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號
                                'TD004' => $item->digiwin_no, //品號
                                'TD005' => mb_substr($item->product_name,0,110,'utf8'),
                                'TD006' => mb_substr($item->serving_size,0,110,'utf8'), //規格
                                'TD007' => $TD007, //庫別
                                'TD008' => $item->quantity, //採購數量
                                'TD009' => $item->unit_name, //單位
                                'TD010' => round($TH018,4), //單價
                                'TD011' => round($item->quantity * $TH018,0), //金額
                                'TD012' => str_replace('-','',$item->vendor_arrival_date), //預交日
                                'TD013' => 'A222', //參考單別
                                'TD014' => null, //備註
                                'TD015' => 0, //已交數量
                                'TD016' => $item->is_del == 1 ? 'y' : 'N', //結案碼
                                'TD018' => 'Y', //確認碼
                                'TD019' => 0, //庫存數量
                                'TD025' => 'N', //急料
                                'TD033' => $item->quantity, //計價數量
                                'TD034' => $item->unit_name, //計價單位
                            ];
                            //更新erp採購單號到syncedOrderItem 及 purchaseOrderItemSingle
                            $item->update(['erp_purchase_no' => $erpPurchaseOrderNo]);
                            $tmp = PurchaseOrderItemSingleDB::where([['purchase_no',$order->purchase_no],['poi_id',$item->id],['poip_id',null]])->first();
                            $tmp->update(['erp_purchase_no' => $erpPurchaseOrderNo, 'erp_purchase_sno' => str_pad(($i+1),4,0,STR_PAD_LEFT)]);
                            $i++;
                        }
                    }
                    if($erpVendor->MA044 == 1){
                        $amount = round($amount / 1.05, 0);
                        $tax = round($amount * 0.05, 0);
                    }elseif($erpVendor->MA044 == 2){
                        $tax = round($amount * 0.05, 0);
                    }elseif($erpVendor->MA044 == 4){ //免稅 (金額=未稅)
                        $amount = round($amount / 1.05, 0);
                        $tax = 0;
                    }elseif($erpVendor->MA044 == 3){ //零稅, 不記稅 (金額=含稅)
                        $tax = 0;
                    }else{ //其他
                        $tax = 0;
                    }
                    //採購單資料
                    $orderData[] = [
                        'COMPANY' => 'iCarry',
                        'CREATOR' => $creator,
                        'USR_GROUP' => 'DSC',
                        'CREATE_DATE' => $createDate,
                        'FLAG' => 1,
                        'CREATE_TIME' => $createTime,
                        'CREATE_AP' => 'iCarry',
                        'CREATE_PRID' => 'PURI07',
                        'TC001' => 'A331', //單別
                        'TC002' => $erpPurchaseOrderNo, //單號
                        'TC003' => $createDate, //日期
                        'TC004' => rtrim($erpVendor->MA001,' '), //廠商代號
                        'TC005' => 'NTD', //幣別
                        'TC006' => 1, //匯率
                        'TC008' => $erpVendor->MA025, //付款條件名稱
                        'TC009' => $order->memo, //備註
                        'TC010' => '001', //廠別
                        'TC012' => 1, //列印格式
                        'TC013' => 0, //列印次數
                        'TC014' => 'Y', //確認碼
                        'TC018' => $erpVendor->MA044, //課稅別
                        'TC019' => $amount, //採購金額 (未稅)
                        'TC020' => $tax, //稅額
                        'TC021' => '台北市中山區南京東路三段103號11樓B室', //送貨地址
                        'TC023' => $order->quantity, //數量合計
                        'TC024' => $createDate, //單據日期
                        'TC025' => $creator, //確認者
                        'TC026' => '0.05', //營業稅率
                        'TC027' => 'N', //簽核狀態碼
                        'TC028' => '104', //郵遞區號
                        'TC030' => 0, //傳送次數
                        'TC031' => 1, //訂金比率
                        'TC032' => $erpVendor->MA055, //付款條件代號
                        'TC040' => 'N', //訂金分批
                    ];
                    $log = [
                        'admin_id' => $adminId,
                        'vendor_id' => $order->vendor_id,
                        'purchase_order_id' => $order->id,
                        'quantity' => $order->quantity,
                        'amount' => $amount,
                        'tax' => $tax,
                        'status' => $status,
                    ];
                    !empty($orderData) ? ErpPURTCDB::insert($orderData) : '';
                    if(count($itemData) >= 50){
                        $itemDatas = array_chunk($itemData,50);
                        for($i=0;$i<count($itemDatas);$i++){
                            ErpPURTDDB::insert($itemDatas[$i]);
                        }
                    }else{
                        ErpPURTDDB::insert($itemData);
                    }
                    //更新採購單號到中繼採購單中
                    $order->update(['erp_purchase_no' => $erpPurchaseOrderNo, 'status' => $status, 'synced_time' => date('Y-m-d H:i:s')]);
                    !empty($order->acOrder) ? $order->acOrder->update(['purchase_sync' => 1]) : '';
                }
            }
            //廠商到貨日變更歸零
            $order->update(['arrival_date_changed' => 0]);
            //建立同步log
            !empty($log) ? PurchaseSyncedLogDB::create($log) : '';
            //更新SyncedOrderItem 及 SyncedOrderItemPackage 的 erp_purchase_no
            $tmp2 = SyncedOrderItemDB::where('purchase_no',$order->purchase_no)->update(['erp_purchase_no' => $erpPurchaseOrderNo]);
            $tmp3 = SyncedOrderItemPackageDB::where('purchase_no',$order->purchase_no)->update(['erp_purchase_no' => $erpPurchaseOrderNo]);
        }
        //自動入庫功能
        if(count($autoStockins) > 0){
            $purchaseNos = array_unique($purchaseNos);
            rsort($purchaseNos);
            for($i=0;$i<count($autoStockins);$i++){
                $autoStockins[$i]['purchase_nos'] = join(',',$purchaseNos);
            }
            StockinImportDB::insert($autoStockins);
            if(strstr(env('APP_URL'),'localhost')){
                PurchaseStockinImportJob::dispatchNow([
                    'import_no' => $importNo,
                    'cate' => 'stockin',
                    'purchaseNos' => $purchaseNos,
                    ]);
            }else{
                PurchaseStockinImportJob::dispatch([
                    'import_no' => $importNo,
                    'cate' => 'stockin',
                    'purchaseNos' => $purchaseNos,
                    ]);
            }
        }
    }
}
