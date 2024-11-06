<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryTicket as TicketDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseExcludeProduct as PurchaseExcludeProductDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\SyncedOrderItemPackage as SyncedOrderItemPackageDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\ErpPURTC as ErpPURTCDB;
use App\Models\ErpPURTD as ErpPURTDDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseOrderItemPackage as PurchaseOrderItemPackageDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use DB;
use Carbon\Carbon;

use App\Jobs\PurchaseOrderSynchronizeToDigiwinJob;

use App\Traits\OrderFunctionTrait;

class iCarryOrderToPurchaseOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, OrderFunctionTrait;

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
        $param = $this->param;
        $syncedOrderItemPackage = env('DB_DATABASE').'.'.(new SyncedOrderItemPackageDB)->getTable();
        $syncedOrderItemTable = env('DB_DATABASE').'.'.(new SyncedOrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $data = SyncedOrderItemDB::with('package')
        ->join($orderTable,$orderTable.'.id',$syncedOrderItemTable.'.order_id')
        ->join($orderItemTable,$orderItemTable.'.id',$syncedOrderItemTable.'.order_item_id')
        ->join($productModelTable,$productModelTable.'.id',$syncedOrderItemTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id');

        //將進來的資料作參數轉換
        foreach ($this->param as $key => $value) {
            $$key = $value;
        }
        if(!empty($selected)){
            $syncedOrderItemIds = [];
            foreach ($selected as $s) {
                $tmp = explode('_@_',$s);
                $productModelId = $tmp[0];
                $orderItemIds = explode(',',$tmp[1]);
                $syncedOrderItemIds = array_merge($syncedOrderItemIds,explode(',',$tmp[2]));
                $quantity = $tmp[3];
            }
            $data = $data->whereIn($syncedOrderItemTable.'.id',$syncedOrderItemIds);
        }elseif(!empty($orderIds)){
            $orderIds = explode(',',$orderIds); //字串轉陣列
            $exculdeProductModelIds = PurchaseExcludeProductDB::select('product_model_id')->orderBy('product_model_id','asc');
            $data = $data->whereIn($syncedOrderItemTable.'.order_id',$orderIds);
            $data = $data->whereNotIn($syncedOrderItemTable.'.product_model_id',$exculdeProductModelIds);
        }else{
            return null;
        }
        $data = $data->whereNull($syncedOrderItemTable.'.purchase_date')
            ->where($syncedOrderItemTable.'.is_del',0)
            ->where($syncedOrderItemTable.'.not_purchase',0)
            ->select([
                $syncedOrderItemTable.'.*',
                $orderTable.'.book_shipping_date',
                $orderTable.'.shipping_memo',
                // $productModelTable.'.gtin13',
                DB::raw("(CASE WHEN $orderItemTable.writeoff_date is not null THEN $orderItemTable.writeoff_date ELSE $syncedOrderItemTable.vendor_arrival_date END) as vendor_arrival_date"),
                DB::raw("(CASE WHEN $productModelTable.gtin13 is not null THEN $productModelTable.gtin13 ELSE $productModelTable.sku END) as gtin13"),
                $productModelTable.'.sku',
                $productModelTable.'.digiwin_no',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable.'.serving_size',
                $productTable.'.unit_name',
                $productTable.'.category_id as product_category_id',
                $productTable.'.vendor_price',
                $productTable.'.price as product_price',
                $productTable.'.package_data',
                $vendorTable.'.id as vendor_id',
                $vendorTable.'.name as vendor_name',
                $vendorTable.'.service_fee',
                $vendorTable.'.digiwin_vendor_no',
                DB::raw("SUM($syncedOrderItemTable.quantity) as quantity"),
                DB::raw("GROUP_CONCAT($orderTable.id) as orderIds"),
                DB::raw("GROUP_CONCAT($syncedOrderItemTable.id) as syncedOrderItemIds"),
                DB::raw("GROUP_CONCAT($syncedOrderItemTable.order_item_id) as orderItemIds"),
            ])->groupBy('vendor_arrival_date','direct_shipment','product_model_id','purchase_price')->orderBy($vendorTable.'.id','asc')->orderBy($orderTable.'.vendor_arrival_date','asc')->get();
        $data = $data->groupBy('vendor_id');
        if(count($data) > 0 ){
            //整理資料
            $purchaseOrderIds = $purchaseOrderItems = $purchaseOrder = [];
            //找出今日最後一筆採購單單號
            $tmp = PurchaseOrderDB::where('purchase_no','>=',date('ymd').'00001')->select('purchase_no')->orderBy('purchase_no','desc')->first();
            !empty($tmp) ? $lastNo = $tmp->purchase_no : $lastNo = 0;
            $c = 1;
            foreach($data as $vendorId => $items){
                $lastNo != 0 ? $purchaseNo = $lastNo + $c : $purchaseNo = date('ymd').str_pad($c,5,0,STR_PAD_LEFT);
                $totalQuantity = $amount = 0;
                $digiwinVendorNo = $productModelIds = $orderItemIds = $orderIds = null;
                foreach($items as $item){
                    !empty($item->digiwin_vendor_no) ? $digiwinVendorNo = $item->digiwin_vendor_no : '';
                }
                !empty($digiwinVendorNo) ? $erpVendor = ErpVendorDB::find($digiwinVendorNo) : $erpVendor = ErpVendorDB::find('A'.str_pad($vendorId,5,0,STR_PAD_LEFT));
                //先建立中繼採購單
                $purchaseOrder = PurchaseOrderDB::create([
                    'type' => 'A331',
                    'vendor_id' => $vendorId,
                    'purchase_no' => $purchaseNo,
                    'purchase_date' => date('Y-m-d'),
                ]);
                $purchaseOrderIds[] = $purchaseOrder->id;
                foreach($items as $item){
                    $tickets = $itemPurchasePrice = 0;
                    if($item->is_del == 0){
                        if($item->product_category_id == 17){
                            //排除渠道票券訂單
                            $checkIds = OrderDB::whereIn('id',explode(',',$item->orderIds))->where('create_type','web')->pluck('id')->all();
                            //檢查是否為票券, 數量變更為已結帳且未採購的票券數量
                            $tmps = TicketDB::where([['digiwin_no',$item->digiwin_no],['status',2]])
                            ->whereNull('purchase_no')
                            ->whereIn('order_id',$checkIds)->orderBy('used_time','asc')->get();
                            $tickets = $item->quantity = count($tmps);
                            if(count($tmps) > 0){
                                foreach($tmps as $ticket){
                                    $orderIds .= $ticket->order_id.',';
                                    $orderItemIds .= $item->orderItemIds.',';
                                    $item->vendor_arrival_date = explode(' ',$ticket->used_time)[0];
                                }
                            }
                        }else{
                            $orderIds .= $item->orderIds.',';
                            $orderItemIds .= $item->orderItemIds.',';
                        }
                        if($item->quantity > 0){
                            //採購價已改直接放在order_item的purchase_price, 暫時保留替代方案以利測試
                            // if($item->purchase_price > 0){
                            //     $itemPurchasePrice = $item->purchase_price;
                            // }else{
                            //     if($item->vendor_price > 0 ){
                            //         $itemPurchasePrice = $item->vendor_price;
                            //     }else{
                            //         if(!empty($item->service_fee)){
                            //             $item->service_fee = str_replace('"percent":}','"percent":0}',$item->service_fee);
                            //             $tmp = json_decode($item->service_fee);
                            //             foreach($tmp as $t){
                            //                 if ($t->name == 'iCarry') {
                            //                     $percent = $t->percent;
                            //                     break;
                            //                 }
                            //             }
                            //             $itemPurchasePrice = $item->product_price - $item->product_price * ( $percent / 100 );
                            //         }
                            //     }
                            // }
                            $itemPurchasePrice = $item->purchase_price;
                            $amount += round($itemPurchasePrice * $item->quantity,0);
                            //建立中繼採購單商品
                            $purchaseOrderItem = PurchaseOrderItemDB::create([
                                'type' => 'A331',
                                'purchase_no' => $purchaseOrder->purchase_no,
                                'product_model_id' => $item->product_model_id,
                                'gtin13' => $item->gtin13,
                                'purchase_price' => $itemPurchasePrice,
                                'quantity' => $item->quantity,
                                'vendor_arrival_date' => $item->vendor_arrival_date,
                                'direct_shipment' => $item->direct_shipment,
                            ]);
                            //組合商品
                            if(strstr($item->sku,'BOM')){
                                $item->purchase_price <= 0 ? $item->purchase_price = $itemPurchasePrice : ''; //測試替代方案
                                $useQty = $totalPrice = 0;
                                $packageData = json_decode(str_replace('	','',$item->package_data));
                                $totalPrice = 0;
                                //計算比例, 並將比例帶入找出分拆的採購價
                                foreach($item->package as $package){
                                    if($package->is_del == 0){
                                        //找出使用數量, 由於前面帶過來的資料是groupBy的資料, 數量並非實際數量, 需重新找出正確的總數量.
                                        foreach($packageData as $pp){
                                            if($item->sku == $pp->bom){
                                                if(!empty($pp->lists)){
                                                    foreach($pp->lists as $list){
                                                        //檢查是否有轉換貨號
                                                        $chkPM = ProductModelDB::where([['sku',$list->sku],['origin_digiwin_no','!=','']])->first();
                                                        if(!empty($chkPM)){
                                                            $newPM = ProductModelDB::where('digiwin_no',$chkPM->origin_digiwin_no)->first();
                                                            if(!empty($newPM)){
                                                                if($package->sku == $newPM->sku){
                                                                    $useQty = $list->quantity;
                                                                    break 2;
                                                                }
                                                            }
                                                        }else{
                                                            if($package->sku == $list->sku){
                                                                $useQty = $list->quantity;
                                                                break 2;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        $package->quantity = $useQty * $item->quantity; //組合品全部的數量
                                        //找出組合品單價
                                        if($package->vendor_price > 0 ){
                                            $package->purchase_price = $package->vendor_price;
                                        }else{
                                            if(!empty($package->service_fee)){
                                                $package->service_fee = str_replace('"percent":}','"percent":0}',$package->service_fee);
                                                $tmp = json_decode($package->service_fee);
                                                foreach($tmp as $t){
                                                    if ($t->name == 'iCarry') {
                                                        $percent = $t->percent;
                                                        break;
                                                    }
                                                }
                                                $package->purchase_price = $package->product_price - $package->product_price * ( $percent / 100 );
                                            }
                                        }
                                        $totalPrice += $package->quantity * $package->purchase_price;
                                    }
                                }
                                $totalPrice == 0 ? $radio = 0 : $radio = ($item->purchase_price * $item->quantity) / $totalPrice;
                                foreach($item->package as $package){
                                    if($package->is_del == 0){
                                        $packagePurchasePrice = 0;
                                        $itemQty = $package->quantity;
                                        $totalQuantity += $itemQty;
                                        $productModelIds .= $package->product_model_id.',';
                                        if($package->vendor_price > 0 ){
                                            $packagePurchasePrice = $package->vendor_price * $radio;
                                        }else{
                                            if(!empty($package->service_fee)){
                                                $package->service_fee = str_replace('"percent":}','"percent":0}',$package->service_fee);
                                                $tmp = json_decode($package->service_fee);
                                                foreach($tmp as $t){
                                                    if ($t->name == 'iCarry') {
                                                        $percent = $t->percent;
                                                        break;
                                                    }
                                                }
                                                $packagePurchasePrice = ($package->product_price - $package->product_price * ( $percent / 100 )) * $radio;
                                            }
                                        }
                                        unset($package->service_fee);
                                        $package->update(['purchase_price' => round($packagePurchasePrice,2),'purchase_no'=> $purchaseNo, 'purchase_date' => date('Y-m-d')]);
                                        //建立中繼採購單組合商品
                                        $purchaseOrderItemPackage = PurchaseOrderItemPackageDB::create([
                                            'purchase_no' => $purchaseOrder->purchase_no,
                                            'purchase_order_item_id' => $purchaseOrderItem->id,
                                            'product_model_id' => $package->product_model_id,
                                            'gtin13' => $package->gtin13,
                                            'vendor_id' => $package->vendor_id,
                                            'purchase_price' => round($packagePurchasePrice,2),
                                            'quantity' => $itemQty,
                                            'vendor_arrival_date' => $item->vendor_arrival_date,
                                            'direct_shipment' => $item->direct_shipment,
                                        ]);
                                        //建立中繼採購單單品資料
                                        $purchaseOrderItemSingle = PurchaseOrderItemSingleDB::create([
                                            'type' => 'A331',
                                            'purchase_no' => $purchaseOrder->purchase_no,
                                            'poi_id' => $purchaseOrderItem->id,
                                            'poip_id' => $purchaseOrderItemPackage->id,
                                            'product_model_id' => $package->product_model_id,
                                            'gtin13' => $package->gtin13,
                                            'vendor_id' => $package->vendor_id,
                                            'purchase_price' => round($packagePurchasePrice,2),
                                            'quantity' => $itemQty,
                                            'vendor_arrival_date' => $item->vendor_arrival_date,
                                            'direct_shipment' => $item->direct_shipment,
                                        ]);
                                    }
                                }
                            }else{
                                $productModelIds .= $item->product_model_id.',';
                                $totalQuantity += $item->quantity;
                                //建立中繼採購單單品資料
                                $purchaseOrderItemSingle = PurchaseOrderItemSingleDB::create([
                                    'type' => 'A331',
                                    'purchase_no' => $purchaseOrder->purchase_no,
                                    'poi_id' => $purchaseOrderItem->id,
                                    'poip_id' => null,
                                    'product_model_id' => $item->product_model_id,
                                    'gtin13' => $item->gtin13,
                                    'purchase_price' => $itemPurchasePrice,
                                    'quantity' => $item->quantity,
                                    'vendor_arrival_date' => $item->vendor_arrival_date,
                                    'direct_shipment' => $item->direct_shipment,
                                ]);
                            }
                            //檢查票券是否全部被使用, 則將該品項同步資料註記已採購
                            if($tickets > 0){
                                $syncedOrderItems = SyncedOrderItemDB::whereIn('order_id',$checkIds)->whereIn('id',explode(',',$item->syncedOrderItemIds))->get();
                                foreach($syncedOrderItems as $syncedItem){
                                    $ticketData = TicketDB::where([['order_id',$syncedItem->order_id],['order_item_id',$syncedItem->order_item_id],['status',2]])
                                    ->whereNull('purchase_no')->get();
                                    if(count($ticketData) == $syncedItem->quantity){
                                        $syncedOrderItems = SyncedOrderItemDB::whereIn('id',explode(',',$item->syncedOrderItemIds))->update(['purchase_no'=> $purchaseNo, 'purchase_date' => date('Y-m-d')]);
                                    }
                                    TicketDB::where([['order_id',$syncedItem->order_id],['order_item_id',$syncedItem->order_item_id],['status',2]])
                                    ->whereNull('purchase_no')->update(['purchase_no'=> $purchaseNo, 'purchase_date' => date('Y-m-d')]);
                                }
                            }else{
                                $syncedOrderItems = SyncedOrderItemDB::whereIn('id',explode(',',$item->syncedOrderItemIds))->update(['purchase_no'=> $purchaseNo, 'purchase_date' => date('Y-m-d')]);
                            }
                        }
                    }
                }
                //如果是1跟2的 要算稅額
                $tax = 0;
                !empty($erpVendor) ? $taxType = $erpVendor->MA044 : $taxType = null;
                if(!empty($erpVendor) && ($erpVendor->MA044 == 1 || $erpVendor->MA044 == 2)){
                    $amount = $amount / 1.05;
                    $tax = $amount * 0.05;
                }
                $orders = OrderDB::with('acOrder')->whereIn('id',array_unique(explode(',',rtrim($orderIds,','))))->get();
                $orderIds = join(',',array_unique(explode(',',rtrim($orderIds,','))));
                $orderItemIds = join(',',array_unique(explode(',',rtrim($orderItemIds,','))));
                $purchaseOrderId = $purchaseOrder->id;
                //更新中繼採購單
                $purchaseOrder->update([
                    'quantity' => $totalQuantity,
                    'amount' => round($amount,0),
                    'tax' => round($tax,0),
                    'tax_type' => $taxType,
                    'order_ids' => $orderIds,
                    'order_item_ids' => $orderItemIds,
                    'product_model_ids' => rtrim($productModelIds,','),
                ]);
                foreach($orders as $order){
                    !empty($order->acOrder) ? $order->acOrder->update(['purchase_id' => $purchaseOrderId, 'purchase_no' => $purchaseNo]) : '';
                }
                //更新order_item內的purchase_no;
                $orderItems = OrderItemDB::whereIn('id',explode(',',$orderItemIds))->update(['purchase_no'=> $purchaseNo]);
                if(isset($return) && $return == 1){
                    return ['purchaseId' => $purchaseOrderId, 'purchaseNo' => $purchaseNo];
                }
                $c++;
            }
            if(isset($type) && $type == 'nidinOrder' && count($purchaseOrderIds) > 0){
                //採購單同步至鼎新
                $param['id'] = $purchaseOrderIds;
                PurchaseOrderSynchronizeToDigiwinJob::dispatchNow($param);
                return $purchaseOrderIds;
            }
        }
    }
}
