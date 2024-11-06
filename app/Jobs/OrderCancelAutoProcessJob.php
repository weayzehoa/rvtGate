<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\VendorShipping as VendorShippingDB;
use App\Models\VendorShippingItem as VendorShippingItemDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\OrderCancel as OrderCancelDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;
use App\Models\PurchaseOrderChangeLog as PurchaseOrderChangeLogDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\OrderCancelExcludeProduct as ExcludeProductDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\SellImport as SellImportDB;
use DB;
use App\Jobs\iCarryOrderToPurchaseOrderJob;
use App\Jobs\PurchaseOrderSynchronizeToDigiwinJob;

class OrderCancelAutoProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $excludeProductTable = env('DB_DATABASE').'.'.(new ExcludeProductDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $excludeProduct = ExcludeProductDB::join($productModelTable,$productModelTable.'.id',$excludeProductTable.'.product_model_id')
        ->select(['digiwin_no']);
        $param = $this->param;
        $param['admin_id'] = $param['adminId'];
        $orderCancels = [];
        if($param['type'] == 'process'){
            // $param['id'] = 4;
            // $param['type'] = 'process';
            // $param['adminId'] = 40;
            $orderCancels = OrderCancelDB::with('order','oldOrderItem','newOrderItem');
            if(is_array($param['id'])){
                $orderCancels = $orderCancels->whereIn('id',$param['id']);
            }else{
                $orderCancels = $orderCancels->where('id',$param['id']);
            }
            $orderCancels = $orderCancels->where('is_chk',0)->whereNotNull('purchase_no')
            ->whereNotIn('purchase_digiwin_no',$excludeProduct)->get();
        }else{
            // $param['orderNumber'] = $order->order_number;
            // $param['type'] = 'auto';
            // $param['adminId'] = 0;
            $orderCancels = OrderCancelDB::with('order','oldOrderItem','newOrderItem')->where([
                ['order_number',$param['orderNumber']],
                ['is_chk',0]
            ])->whereNull('vendor_shipping_no')
            ->whereNotNull('purchase_no')
            ->whereNotIn('purchase_digiwin_no',$excludeProduct)->get();
        }
        if(count($orderCancels) > 0){
            $temps = $orderCancels->groupBy('purchase_no')->all();
            //不同採購單等於不同商家需分開處理
            foreach($temps as $purchaseNo => $cancels){
                $rebuild = $chkPurchaseUpdate = $x = 0;
                $data = [];
                //找出採購單的資料
                $purchaseOrder = PurchaseOrderDB::where('purchase_no',$purchaseNo)->first();
                //加入request
                request()->request->add([
                    'id' => [$purchaseOrder->id],
                    'purchaseNo' => $purchaseNo,
                    'adminId' => $param['adminId'],
                    'admin_id' => $param['adminId']
                ]);
                foreach($cancels as $cancel){
                    $data[$x]['cancel_id'] = $cancel->id;
                    $data[$x]['quantity'] = $cancel->quantity;
                    $directShipment = $cancel->direct_shipment;
                    $cancelQty = $cancel->quantity;
                    $purchaseDigiwinNo = $cancel->purchase_digiwin_no; //採購單的貨號
                    $vendorArrivalDate = $cancel->vendor_arrival_date; //廠商到貨日
                    $productModel = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                    ->where($productModelTable.'.digiwin_no',$purchaseDigiwinNo)
                    ->select([
                        $productModelTable.'.*',
                        $productTable.'.name as product_name',
                    ])->first();
                    $data[$x]['productName'] = $productModel->product_name;
                    //找出採購單內要取消的商品資料
                    $purchaseOrderItem = PurchaseOrderItemDB::with('stockins','package','package.stockins')
                        ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
                        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                        ->where([
                            [$purchaseOrderItemTable.'.purchase_no',$purchaseNo],
                            [$purchaseOrderItemTable.'.direct_shipment',$directShipment],
                            [$purchaseOrderItemTable.'.product_model_id',$productModel->id],
                            [$purchaseOrderItemTable.'.vendor_arrival_date',$vendorArrivalDate]
                        ])->select([
                            $purchaseOrderItemTable.'.*',
                            $productModelTable.'.sku',
                            $productModelTable.'.digiwin_no',
                            $productTable.'.package_data',
                            $productTable.'.name as product_name',
                        ])->first();
                    if(!empty($purchaseOrderItem)){
                        //檢查有沒有入庫
                        $chkPurchaseStockin = 0;
                        if(strstr($purchaseOrderItem->sku,'BOM')){
                            foreach($purchaseOrderItem->package as $package){
                                count($package->stockins) > 0 ? $chkPurchaseStockin++ : '';
                            }
                        }else{
                            count($purchaseOrderItem->stockins) > 0 ? $chkPurchaseStockin++ : '';
                        }
                        if($chkPurchaseStockin == 0){
                            //找出採購單內的order_item_ids資料
                            $newOrderItemIds = [];
                            $oldOrderItemIds = explode(',',$purchaseOrder->order_item_ids);
                            !empty($cancel->new_ori_id) ? $oldOrderItemIds = array_merge($oldOrderItemIds,[$cancel->new_ori_id]) : '';

                            //更新採購單裡面的order_item_ids
                            for($i=0;$i<count($oldOrderItemIds);$i++){
                                if($oldOrderItemIds[$i] == $cancel->old_ori_id){
                                    unset($oldOrderItemIds[$i]);
                                }
                            }
                            sort($oldOrderItemIds);
                            for($i=0;$i<count($oldOrderItemIds);$i++) {
                                if($oldOrderItemIds[$i] != ''){
                                    $newOrderItemIds[] = $oldOrderItemIds[$i];
                                }
                            }
                            //找出新的 order_ids資料
                            count($newOrderItemIds) > 0 ? $orderIds = OrderItemDB::whereIn('id',$newOrderItemIds)->groupBy('order_id')->get()->pluck('order_id')->all() : $orderIds = [];
                            //更新採購單商品數量
                            $beforeQuantity = $purchaseOrderItem->quantity;
                            $newQty = $purchaseOrderItem->quantity - $cancelQty;
                            //組合需修改相關資料
                            if(strstr($purchaseOrderItem->sku,'BOM')){
                                $packageData = json_decode($purchaseOrderItem->package_data);
                                foreach($purchaseOrderItem->exportPackage as $package){
                                    $useQty = 0;
                                    //找出新的使用數量
                                    foreach($packageData as $pp){
                                        if($purchaseOrderItem->sku == $pp->bom){
                                            if(!empty($pp->lists)){
                                                foreach($pp->lists as $list){
                                                    if($package->sku == $list->sku){
                                                        $useQty = $list->quantity;
                                                        break 2;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $package->update(['quantity' => $useQty * $newQty]);
                                    $single = PurchaseOrderItemSingleDB::where([
                                        ['purchase_no',$purchaseOrderItem->purchase_no],
                                        ['poi_id',$purchaseOrderItem->id],
                                        ['poip_id',$package->id]
                                    ])->update(['quantity' => $useQty * $newQty]);
                                }
                            }else{
                                $single = PurchaseOrderItemSingleDB::where([
                                    ['purchase_no',$purchaseOrderItem->purchase_no],
                                    ['poi_id',$purchaseOrderItem->id],
                                    ['poip_id',null]
                                ])->update(['quantity' => $newQty]);
                            }
                            $purchaseOrderItem->update(['quantity' => $newQty]);
                            //重新計算採購單內資料
                            $purchaseOrder = PurchaseOrderDB::with('exportItems','exportItems.exportPackage')->where('purchase_no',$purchaseNo)->first();
                            $orderAmount = $orderQty = 0;
                            foreach($purchaseOrder->exportItems as $item){
                                $orderAmount += $item->quantity * $item->purchase_price;
                                if(strstr($item->sku,'BOM')){
                                    foreach($item->exportPackage as $package){
                                        $orderQty += $package->quantity;
                                    }
                                }else{
                                    $orderQty += $item->quantity;
                                }
                            }
                            //如果是1跟2的 要算稅額
                            $tax = 0;
                            $erpVendor = ErpVendorDB::find('A'.str_pad($purchaseOrder->vendor_id,5,0,STR_PAD_LEFT));
                            if(!empty($erpVendor) && ($erpVendor->MA044 == 1 || $erpVendor->MA044 == 2)){
                                $orderAmount = $orderAmount / 1.05;
                                $tax = $orderAmount * 0.05;
                            }
                            $purchaseOrder->update([
                                'amount' => round($orderAmount,0),
                                'tax' => round($tax,0),
                                'quantity' => $orderQty,
                                'order_item_ids' => join(',',$newOrderItemIds),
                                'order_ids' => join(',',$orderIds)
                            ]);
                            //採購單修改紀錄
                            $log = PurchaseOrderChangeLogDB::create([
                                'purchase_no' => $purchaseOrder->purchase_no,
                                'admin_id' => $param['adminId'] == 9999 ? null : $param['adminId'],
                                'poi_id' => $purchaseOrderItem->id,
                                'sku' => $purchaseOrderItem->sku,
                                'digiwin_no' => $purchaseOrderItem->digiwin_no,
                                'product_name' => $purchaseOrderItem->product_name,
                                'status' => '修改',
                                'quantity' => $beforeQuantity.' => '.$newQty,
                                'memo' => '數量 修改',
                            ]);
                            //商家出貨單取消
                            if(!empty($cancel->vendor_shipping_no)){
                                $shippingItem = VendorShippingItemDB::with('packages')->where([
                                    ['shipping_no',$cancel->vendor_shipping_no],
                                    ['direct_shipment',$directShipment],
                                    ['product_model_id',$productModel->id],
                                    ['vendor_arrival_date',$vendorArrivalDate],
                                    ['purchase_no',$purchaseOrder->purchase_no],
                                ])->first();
                                if($shippingItem->direct_shipment == 0){ //入倉檢查入庫單
                                    $chkStockin = 0;
                                    if(strstr($purchaseOrderItem->sku,'BOM')){
                                        foreach($purchaseOrderItem->package as $package){
                                            count($package->stockins) > 0 ? $chkStockin++ : '';
                                        }
                                    }else{
                                        count($purchaseOrderItem->stockins) > 0 ? $chkStockin++ : '';
                                    }
                                    if($chkStockin == 0){
                                        if(strstr($shippingItem->sku,'BOM')){
                                            foreach($shippingItem->packages as $package){
                                                $package->update(['is_del' => 1]);
                                            }
                                        }
                                        $purchaseOrderItem->update(['vendor_shipping_no' => null]);
                                        $shippingItem->update(['is_del' => 1]);
                                    }
                                }else{ //直寄檢查出貨單
                                    $chkSells = 0;
                                    $shippingItems = VendorShippingItemDB::with('stockins','packages','packages.stockins')->where([['purchase_no',$shippingItem->purchase_no],['product_model_id',$shippingItem->product_model_id],['direct_shipment',1],['is_del',0]])->get();
                                    foreach($shippingItems as $item){
                                        if(strstr($item->sku,'BOM')){
                                            foreach($item->packages as $package){
                                                if(count($package->stockins) > 0){
                                                    $sells = SellItemSingleDB::where('order_number',$item->order_numbers)->where('product_model_id',$package->product_model_id)->get();
                                                    if(count($sells) > 0){
                                                        $chkSells++;
                                                    }
                                                }
                                            }
                                        }else{
                                            if(count($item->stockins) > 0){
                                                $sells = SellItemSingleDB::where('order_number',$item->order_numbers)->where('product_model_id',$item->product_model_id)->get();
                                                if(count($sells) > 0) {
                                                    $chkSells++;
                                                }
                                            }
                                        }
                                    }
                                    if($chkSells == 0){ //取消全部並將採購標記移除
                                        $purchaseOrderItem->update(['vendor_shipping_no' => null]);
                                        foreach($shippingItems as $item){
                                            if(strstr($item->sku,'BOM')){
                                                foreach($shippingItem->packages as $package){
                                                    $package->update(['is_del' => 1]);
                                                }
                                            }
                                            $item->update(['is_del' => 1]);
                                        }
                                    }
                                }

                                //檢查商家出貨單是否全部被取消, 若是則取消整張出貨單
                                $vendorShipping = VendorShippingDB::with('items')->where('shipping_no',$cancel->vendor_shipping_no)->first();
                                $chkVendorShipping = 0;
                                foreach($vendorShipping->items as $vendorItem){
                                    $vendorItem->is_del == 1 ? $chkVendorShipping++ : '';
                                }
                                $chkVendorShipping == count($vendorShipping->items) ? $vendorShipping->update(['status' => -1, 'memo' => '已被iCarry系統取消。']) : '';
                            }
                            $chkPurchaseUpdate++;
                        }else{ //直寄已有入庫時, 舊的採購單數量扣除, 另外重新單獨建立一份新的採購單
                            if($cancel->direct_shipment == 1){
                                //商家出貨單取消
                                if(!empty($cancel->vendor_shipping_no)){
                                    $shippingItem = VendorShippingItemDB::with('packages')->where([
                                        ['shipping_no',$cancel->vendor_shipping_no],
                                        ['direct_shipment',$directShipment],
                                        ['product_model_id',$productModel->id],
                                        ['vendor_arrival_date',$vendorArrivalDate],
                                        ['purchase_no',$purchaseOrder->purchase_no],
                                        ['order_numbers',$cancel->order_number],
                                    ])->first();
                                    $shippingItem->update(['is_del' => 1]);
                                    //檢查商家出貨單是否全部被取消, 若是則取消整張出貨單
                                    $vendorShipping = VendorShippingDB::with('items')->where('shipping_no',$cancel->vendor_shipping_no)->first();
                                    $chkVendorShipping = 0;
                                    foreach($vendorShipping->items as $vendorItem){
                                        $vendorItem->is_del == 1 ? $chkVendorShipping++ : '';
                                    }
                                    $chkVendorShipping == count($vendorShipping->items) ? $vendorShipping->update(['status' => -1, 'memo' => '已被iCarry系統取消。']) : '';
                                }
                                //找出採購單內的order_item_ids資料
                                $newOrderItemIds = [];
                                $oldOrderItemIds = explode(',',$purchaseOrder->order_item_ids);
                                !empty($cancel->new_ori_id) ? $oldOrderItemIds = array_merge($oldOrderItemIds,[$cancel->new_ori_id]) : '';
                                //更新採購單裡面的order_item_ids
                                for($i=0;$i<count($oldOrderItemIds);$i++){
                                    if($oldOrderItemIds[$i] == $cancel->old_ori_id){
                                        unset($oldOrderItemIds[$i]);
                                    }
                                }
                                sort($oldOrderItemIds);
                                for($i=0;$i<count($oldOrderItemIds);$i++) {
                                    if($oldOrderItemIds[$i] != ''){
                                        $newOrderItemIds[] = $oldOrderItemIds[$i];
                                    }
                                }
                                //找出新的 order_ids資料
                                count($newOrderItemIds) > 0 ? $orderIds = OrderItemDB::whereIn('id',$newOrderItemIds)->groupBy('order_id')->get()->pluck('order_id')->all() : $orderIds = [];
                                //更新採購單商品數量
                                $beforeQuantity = $purchaseOrderItem->quantity;
                                //新的數量是扣除掉舊的被刪除的訂單數量
                                $newQty = $purchaseOrderItem->quantity - $cancel->oldOrderItem->quantity;
                                //組合需修改相關資料
                                if(strstr($purchaseOrderItem->sku,'BOM')){
                                    $packageData = json_decode($purchaseOrderItem->package_data);
                                    foreach($purchaseOrderItem->exportPackage as $package){
                                        $useQty = 0;
                                        //找出新的使用數量
                                        foreach($packageData as $pp){
                                            if($purchaseOrderItem->sku == $pp->bom){
                                                if(!empty($pp->lists)){
                                                    foreach($pp->lists as $list){
                                                        if($package->sku == $list->sku){
                                                            $useQty = $list->quantity;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        $package->update(['quantity' => $useQty * $newQty]);
                                        $single = PurchaseOrderItemSingleDB::where([
                                            ['purchase_no',$purchaseOrderItem->purchase_no],
                                            ['poi_id',$purchaseOrderItem->id],
                                            ['poip_id',$package->id]
                                        ])->update(['quantity' => $useQty * $newQty]);
                                    }
                                }else{
                                    $single = PurchaseOrderItemSingleDB::where([
                                        ['purchase_no',$purchaseOrderItem->purchase_no],
                                        ['poi_id',$purchaseOrderItem->id],
                                        ['poip_id',null]
                                    ])->update(['quantity' => $newQty]);
                                }
                                $purchaseOrderItem->update(['quantity' => $newQty]);
                                //重新計算採購單內資料
                                $purchaseOrder = PurchaseOrderDB::with('exportItems','exportItems.exportPackage')->where('purchase_no',$purchaseNo)->first();
                                $orderAmount = $orderQty = 0;
                                foreach($purchaseOrder->exportItems as $item){
                                    $orderAmount += $item->quantity * $item->purchase_price;
                                    if(strstr($item->sku,'BOM')){
                                        foreach($item->exportPackage as $package){
                                            $orderQty += $package->quantity;
                                        }
                                    }else{
                                        $orderQty += $item->quantity;
                                    }
                                }
                                //如果是1跟2的 要算稅額
                                $tax = 0;
                                $erpVendor = ErpVendorDB::find('A'.str_pad($purchaseOrder->vendor_id,5,0,STR_PAD_LEFT));
                                if(!empty($erpVendor) && ($erpVendor->MA044 == 1 || $erpVendor->MA044 == 2)){
                                    $orderAmount = $orderAmount / 1.05;
                                    $tax = $orderAmount * 0.05;
                                }
                                $purchaseOrder->update([
                                    'amount' => round($orderAmount,0),
                                    'tax' => round($tax,0),
                                    'quantity' => $orderQty,
                                    'order_item_ids' => join(',',$newOrderItemIds),
                                    'order_ids' => join(',',$orderIds)
                                ]);
                                //採購單修改紀錄
                                $log = PurchaseOrderChangeLogDB::create([
                                    'purchase_no' => $purchaseOrder->purchase_no,
                                    'admin_id' => $param['adminId'] == 9999 ? null : $param['adminId'],
                                    'poi_id' => $purchaseOrderItem->id,
                                    'sku' => $purchaseOrderItem->sku,
                                    'digiwin_no' => $purchaseOrderItem->digiwin_no,
                                    'product_name' => $purchaseOrderItem->product_name,
                                    'status' => '修改',
                                    'quantity' => $beforeQuantity.' => '.$newQty,
                                    'memo' => '數量 修改',
                                ]);

                                //檢查廠商直寄資料管理是否有已填入,有則刪除
                                $sellImport = SellImportDB::where([['purchase_no',$cancel->purchase_no],['order_number',$cancel->order_number],['digiwin_no',$cancel->purchase_digiwin_no],['status','!=',1]])->first();
                                !empty($sellImport) ? $sellImport->delete() : '';

                                $rebuild++;
                                $chkPurchaseUpdate++;
                            }
                        }
                    }
                    $x++;
                }

                if($chkPurchaseUpdate > 0) {
                    if($purchaseOrder->status == 1){
                        //採購單同步至鼎新
                        PurchaseOrderSynchronizeToDigiwinJob::dispatchNow(request()->all());
                        $chkNotice = 0;
                        $syncedLog = PurchaseSyncedLogDB::where('purchase_order_id',$purchaseOrder->id)
                        ->groupBy('purchase_order_id')->having(DB::raw('count(notice_time)'), '>', 0)->first();
                        //通知廠商, 先檢查是否曾經通知過廠商, 不曾通知過則不做通知, 由採購人員手動通知
                        if(!empty($syncedLog)){
                            $rebuild == 0 ? PurchaseOrderNoticeVendorModify::dispatchNow(request()->all()) : '';
                        }
                    }

                    //重建訂單及通知廠商
                    if($rebuild > 0){
                        $orderId = $cancel->order->id;
                        //移除新的訂單同步資料內的舊採購單號及採購日期
                        $cancel->newOrderItem->syncedOrderItem->update(['purchase_no' => null, 'purchase_date' => null, 'erp_purchase_no' => null]);

                        //建立新的採購單
                        $result = iCarryOrderToPurchaseOrderJob::dispatchNow(['orderIds' => $orderId, 'return' => 1]);

                        $newPurchaseOrder = PurchaseOrderDB::find($result['purchaseId']);
                        $vendor = VendorDB::find($newPurchaseOrder->vendor_id);

                        $exportNo = time();
                        $syncedData = [
                            'method' => 'selected',
                            'cate' => 'SyncToDigiwin',
                            'type' => 'undefined',
                            'filename' => '中繼同步至鼎新_自動同步_'.$exportNo.'.xlsx',
                            'model' => 'purchase',
                            'name' => '中繼同步至鼎新_自動同步',
                            'export_no' => $exportNo,
                            'id' => [$newPurchaseOrder->id],
                            'purchaseNo' => $newPurchaseOrder->purchase_no,
                            'adminId' => $param['adminId'],
                            'admin_id' => $param['adminId'],
                            'start_time' => date('Y-m-d H:i:s'),
                        ];

                        //同步新的採購單到鼎新並通知廠商
                        PurchaseOrderSynchronizeToDigiwinJob::dispatchNow($syncedData);

                        $noticeData = [
                            "method" => "selected",
                            "cate" => "NoticeVendor",
                            "type" => "Email",
                            "filename" => '通知廠商(新)_自動通知_'.$exportNo.'.xlsx',
                            "model" => "purchase",
                            'id' => [$newPurchaseOrder->id],
                            'adminId' => $param['adminId'],
                            'admin_id' => $param['adminId'],
                            "name" => "通知廠商(新)_自動通知",
                            "export_no" => $exportNo,
                            "start_time" => date('Y-m-d H:i:s'),
                            "vendorName" => $vendor->name,
                            "orders" => [$newPurchaseOrder],
                            "version" => "new",
                        ];
                        PurchaseOrderNoticeVendorModify::dispatchNow($noticeData);
                    }
                    if(count($data) > 0){
                        sort($data);
                        //處理OrderCancel資料
                        for($i=0;$i<count($data);$i++){
                            $orderCancel = OrderCancelDB::find($data[$i]['cancel_id']);
                            if(!empty($orderCancel)){
                                $orderCancel->update([
                                    'deduct_quantity' => $data[$i]['quantity'],
                                    'memo' => $orderCancel->memo." 系統自動處理。",
                                    'is_chk' => 1,
                                    'chk_date' => date('Y-m-d H:i:s'),
                                    'admin_id' => $param['adminId'],
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }
}
