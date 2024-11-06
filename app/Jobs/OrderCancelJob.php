<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryUser as UserDB;
use App\Models\iCarryUserPoint as UserPointDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryOrderItemPackage as OrderItemPackageDB;
use App\Models\iCarryShippingSet as ShippingSetDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\OrderCancel as OrderCancelDB;
use App\Models\SyncedOrder as SyncedOrderDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use App\Jobs\iCarryOrderSynchronizeToDigiwinJob as OrderSynchronizeToDigiwinJob;
use Carbon\Carbon;

class OrderCancelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order,$data)
    {
        $this->order = $order;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->order;
        $data = $this->data;
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        //檢查是否同步
        $chkSynced = SyncedOrderDB::with('items')->where('order_id',$order->id)->orderBy('created_at','desc')->first();
        $cancelPerson = auth('gate')->user()->name;
        $originShippingFee = $order->shipping_fee;
        $shippingKgPrice = $order->shipping_kg_price;
        $parcelTax = $order->parcel_tax;
        $originDiscount = $order->discount;
        $originCountry = $order->origin_country;
        $shippingMethod = $order->shipping_method;
        $oldAmount = $order->amount;
        $spendPoint = $order->spend_point;
        $oldSpendPoint = $order->spend_point;
        $shipTo = $order->ship_to;
        $parcelTaxRate = 0;
        $shippingMethodName = null;
        $vendorShippingNos = $purchaseNos = [];
        $shippingMethod == 1 ? $shippingMethodName = '當地機場' : '';
        $shippingMethod == 2 ? $shippingMethodName = '當地旅店' : '';
        $shippingMethod == 3 && $shippingMethod == 6 ? $shippingMethodName = '當地地址' : '';
        $shippingMethod == 4 ? $shippingMethodName = $shipTo : '';
        $originCountry == '台灣' && $shippingMethod == 5 ? $shippingMethodName = '當地地址' : '';
        $originCountry == '日本' && $shipTo == '日本' ? $shippingMethodName = '當地地址' : '';
        //找出行郵稅費率
        $shippingSet = ShippingSetDB::where([['product_sold_country',$originCountry],['shipping_methods',$shippingMethodName],['is_on',1]])->first();
        !empty($shippingSet) ? $parcelTaxRate = $shippingSet->tax_rate : '';

        if(isset($data['status']) && $data['status'] == -1){ //取消整張訂單
            $items = $order->itemData;
            foreach($items as $item){
                $purchaseDigiwinNo = $orderDigiwinNo = $item->digiwin_no;
                $oldItemId = $item->id;
                $newItemId = null;
                if(!empty($item->origin_digiwin_no)){ //轉換貨號, 採購的 product_model_id
                    $pm = ProductModelDB::where('digiwin_no',$item->origin_digiwin_no)->first();
                    $purchaseDigiwinNo = $pm->digiwin_no;
                    $item->product_model_id = $pm->id;
                }
                // 找採購單
                $purchaseOrder = PurchaseOrderItemDB::join($purchaseOrderTable,$purchaseOrderTable.'.purchase_no',$purchaseOrderItemTable.'.purchase_no')
                ->where($purchaseOrderTable.'.order_ids','like',"%$order->id%")
                ->where($purchaseOrderItemTable.'.product_model_id',$item->product_model_id)
                ->select([
                    $purchaseOrderItemTable.'.*',
                    $purchaseOrderTable.'.id as purchase_order_id',
                ])->first();
                !empty($purchaseOrder) ? $purchaseNos[] = $purchaseOrder->purchase_no : '';
                !empty($purchaseOrder) ? $purchaseNo = $purchaseOrder->purchase_no : $purchaseNo = null;
                !empty($purchaseOrder) ? $purchaseOrderId = $purchaseOrder->purchase_order_id : $purchaseOrderId = null;
                !empty($purchaseOrder) && !empty($purchaseOrder->vendor_shipping_no) ? $vendorShippingNo = $purchaseOrder->vendor_shipping_no : $vendorShippingNo = null;
                !empty($purchaseOrder) && !empty($purchaseOrder->vendor_shipping_no) ? $vendorShippingNos[] = $purchaseOrder->vendor_shipping_no : '';
                $item->not_purchase == 1 ? $isChk = 0 : (empty($purchaseOrder) ? $isChk = 1 : $isChk = 0);
                empty($purchaseNo) ? $memo = "尚未採購。" : $memo = null;
                empty($purchaseOrder) ? $purchaseDigiwinNo = null : '';
                $isChk == 1 ? $chkDate = date('Y-m-d H:i:s') : $chkDate = null;
                $isChk == 1 ? $adminId = 9999 : $adminId = null;

                //建立OrderCancel資料
                $orderCancel = OrderCancelDB::create([
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_digiwin_no' => $orderDigiwinNo,
                    'purchase_digiwin_no' => $purchaseDigiwinNo,
                    'quantity' => $item->quantity,
                    'book_shipping_date' => $order->book_shipping_date,
                    'vendor_arrival_date' => $order->vendor_arrival_date,
                    'cancel_time' => date('Y-m-d H:i:s'),
                    'cancel_person' => $cancelPerson,
                    'purchase_order_id' => $purchaseOrderId,
                    'purchase_no' => $purchaseNo,
                    'deduct_quantity' =>null,
                    'memo' => "取消訂單。",
                    'is_chk' => $isChk,
                    'chk_date' => $chkDate,
                    'admin_id' => $adminId,
                    'direct_shipment' => $item->direct_shipment,
                    'vendor_shipping_no' => $vendorShippingNo,
                    'old_ori_id' => $oldItemId,
                    'new_ori_id' => $newItemId,
                ]);
            }
            //檢查是否同步, 若有則自動重新
            if(!empty($chkSynced)){
                request()->request->add(['id' => [$order->id]]); //將 order id 用成陣列放入 request()
                OrderSynchronizeToDigiwinJob::dispatchNow(request());
            }
        }
        if(isset($data['itemQty']) && $data['itemQty'] == 1 && isset($data['items']) && count($data['items']) > 0){
            if(count($data['items']) > 0){
                $chk = $i= 0; $result = $orderItemIds = [];
                //檢查是否有出貨單且數量必須小於等於出貨單數量
                foreach ($data['items'] as $it) {
                    $sellQty = 0;
                    $item = OrderItemDB::with('sells','package','package.sells')
                    ->join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
                    ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                    ->select([
                        $orderItemTable.'.*',
                        $productModelTable.'.sku',
                        $productModelTable.'.digiwin_no',
                        $productModelTable.'.origin_digiwin_no',
                        $productTable.'.package_data',
                    ])->find($it['id']);
                    if(strstr($item->sku,'BOM')){
                        $useQty = 0;
                        $cancelQty = $item->quantity - $it['qty']; //取消的組合品數量
                        $packageData = json_decode(str_replace('	','',$item->package_data));
                        if(is_array($packageData) && count($packageData) > 0){
                            foreach($packageData as $pp){
                                if(isset($pp->is_del)){
                                    if($pp->is_del == 0){
                                        if($item->sku == $pp->bom){
                                            foreach($pp->lists as $list) {
                                                $useQty = $list->quantity;
                                                $itemCancelQty = $useQty * $cancelQty;
                                                foreach($item->package as $package){
                                                    if(count($package->sells) > 0){
                                                        $sellQty=0;
                                                        foreach($package->sells as $sell){
                                                            $sellQty += $sell->sell_quantity;
                                                        }
                                                        if($package->sku == $list->sku){
                                                            if(($itemCancelQty - ($package->quantity - $sellQty)) > 0){
                                                                $chk++;
                                                                $result[] = $item->digiwin_no;
                                                            }
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    if($item->sku == $pp->bom){
                                        foreach($pp->lists as $list) {
                                            $useQty = $list->quantity;
                                            $itemCancelQty = $useQty * $cancelQty;
                                            foreach($item->package as $package){
                                                if(count($package->sells) > 0){
                                                    $sellQty=0;
                                                    foreach($package->sells as $sell){
                                                        $sellQty += $sell->sell_quantity;
                                                    }
                                                    if($package->sku == $list->sku){
                                                        if(($itemCancelQty - ($package->quantity - $sellQty)) > 0){
                                                            $chk++;
                                                            $result[] = $item->digiwin_no;
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }else{
                        if(count($item->sells) > 0){
                            foreach($item->sells as $sell){
                                $sellQty += $sell->sell_quantity;
                            }
                        }
                        $cancelQty = $item->quantity - $it['qty'];
                        if(($cancelQty - ($item->quantity - $sellQty)) > 0){
                            $chk++;
                            $result[] = $item->digiwin_no;
                        }
                    }
                }
                if($chk > 0){
                    return join(',',$result);
                }
                foreach ($data['items'] as $it) {
                    $item = OrderItemDB::with('package','syncedOrderItem')
                    ->join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
                    ->select([
                        $orderItemTable.'.*',
                        $productModelTable.'.sku',
                        $productModelTable.'.digiwin_no',
                        $productModelTable.'.origin_digiwin_no',
                    ])->find($it['id']);
                    $purchaseDigiwinNo = $orderDigiwinNo = $item->digiwin_no;
                    $originQty = $item->quantity;
                    $newNotPurchase = $diffQty = 0;
                    $oldItemId = $item->id;
                    $newItemId = null;
                    $newOrderItemIds = [];
                    if($it['qty'] != $originQty && $it['qty'] >= 0){
                        if($it['qty'] != 0){
                            $diffQty = $item->quantity - $it['qty'];
                            $newData = $item->toArray();
                            !empty($item->syncedOrderItem) ? $syncedData = $item->syncedOrderItem : $syncedData = null;
                            $newData['quantity'] = $it['qty']; //取正數
                            $orderItemIds[$i]['oldId'] = $newData['id'];
                            unset($newData['id']);
                            if(empty($chkSynced)){ //尚未同步, 註記不採購
                                $item->update(['not_purchase' => 1]);
                            }else{
                                if(!empty($syncedData)){ //有同步未採購, 註記不採購
                                    empty($syncedData->purchase_no) ? $item->update(['not_purchase' => 1]) : '';
                                }
                            }
                            $orderItem = OrderItemDB::create($newData);
                            $orderItemIds[$i]['newId'] = $newItemId = $orderItem->id;
                            $newNotPurchase = $orderItem->not_purchase;
                            if(count($item->package) > 0){ // 有組合資料
                                $x=0;
                                foreach($item->package as $package){
                                    if($package->is_del == 0){
                                        $orderItemIds[$i]['package'][$x]['oldId'] = $package->id;
                                        $packageNewData = $package->toArray();
                                        $originPackageQty = $packageNewData['quantity'];
                                        $packageNewData['quantity'] = $it['qty'] * ($originPackageQty / $originQty);
                                        $packageNewData['order_item_id'] = $orderItem->id;
                                        unset($packageNewData['id']);
                                        $orderItemPackage = OrderItemPackageDB::create($packageNewData);
                                        $orderItemIds[$i]['package'][$x]['newId'] = $orderItemPackage->id;
                                        $x++;
                                    }
                                }
                            }
                        }else{ // $it['qty'] == 0
                            $newNotPurchase = $item->not_purchase;
                            $diffQty = $item->quantity;
                            if(empty($chkSynced)){ //尚未同步, 註記不採購
                                $item->update(['not_purchase' => 1]);
                            }else{
                                if(!empty($syncedData)){ //有同步未採購, 註記不採購
                                    empty($syncedData->purchase_no) ? $item->update(['not_purchase' => 1]) : '';
                                }
                            }
                        }
                        $item->update(['is_del' => 1]);
                        if(!empty($item->origin_digiwin_no)){ //轉換貨號, 採購的 product_model_id
                            $pm = ProductModelDB::where('digiwin_no',$item->origin_digiwin_no)->first();
                            $purchaseDigiwinNo = $pm->digiwin_no;
                            $item->product_model_id = $pm->id;
                        }
                        // 找採購單
                        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
                        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
                        $purchaseOrder = PurchaseOrderItemDB::join($purchaseOrderTable,$purchaseOrderTable.'.purchase_no',$purchaseOrderItemTable.'.purchase_no')
                        ->where($purchaseOrderTable.'.order_ids','like',"%$order->id%")
                        ->where($purchaseOrderTable.'.order_item_ids','like',"%$oldItemId%") //先找出舊的採購單
                        ->where($purchaseOrderItemTable.'.product_model_id',$item->product_model_id)
                        ->select([
                            $purchaseOrderItemTable.'.*',
                            $purchaseOrderTable.'.id as purchase_order_id',
                        ])->first();
                        !empty($purchaseOrder) ? $purchaseNos[] = $purchaseOrder->purchase_no : '';
                        !empty($purchaseOrder) ? $purchaseNo = $purchaseOrder->purchase_no : $purchaseNo = null;
                        !empty($purchaseOrder) ? $purchaseOrderId = $purchaseOrder->purchase_order_id : $purchaseOrderId = null;
                        !empty($purchaseOrder) && !empty($purchaseOrder->vendor_shipping_no) ? $vendorShippingNo = $purchaseOrder->vendor_shipping_no : $vendorShippingNo = null;
                        $newNotPurchase == 1 ? $isChk = 0 : (empty($purchaseNo) ? $isChk = 1 : $isChk = 0);
                        empty($purchaseNo) ? $memo = "尚未採購。" : $memo = null;
                        empty($purchaseOrder) ? $purchaseDigiwinNo = null : '';
                        $isChk == 1 ? $chkDate = date('Y-m-d H:i:s') : $chkDate = null;
                        $isChk == 1 ? $adminId = 9999 : $adminId = null;

                        //建立OrderCancel資料
                        $orderCancel = OrderCancelDB::create([
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'order_digiwin_no' => $orderDigiwinNo,
                            'purchase_digiwin_no' => $purchaseDigiwinNo,
                            'quantity' => $originQty - $it['qty'],
                            'book_shipping_date' => $order->book_shipping_date,
                            'vendor_arrival_date' => $order->vendor_arrival_date,
                            'cancel_time' => date('Y-m-d H:i:s'),
                            'cancel_person' => $cancelPerson,
                            'purchase_order_id' => $purchaseOrderId,
                            'purchase_no' => $purchaseNo,
                            'deduct_quantity' =>null,
                            'memo' => null,
                            'is_chk' => $isChk,
                            'chk_date' => $chkDate,
                            'admin_id' => $adminId,
                            'direct_shipment' => $item->direct_shipment,
                            'vendor_shipping_no' => $vendorShippingNo,
                            'old_ori_id' => $oldItemId,
                            'new_ori_id' => $newItemId,
                        ]);
                    }
                }
                //重新計算訂單金額
                $discount = $grossWeight = $amount = 0;
                $newShippingFee = $originShippingFee;
                $newDiscount = $originDiscount;
                $items = OrderItemDB::where('order_id',$order->id)->get();
                foreach($items as $item){
                    if($item->is_del == 0){
                        $amount += $item->price * $item->quantity;
                        $grossWeight += $item->quantity * $item->gross_weight;
                        !empty($item->discount) && $item->discount > 0 ? $discount += $item->discount * $item->quantity : '';
                    }
                }
                //同時調整運費、行郵稅選項啟動
                if(isset($data['shippingFeeModify']) && $data['shippingFeeModify'] == 1){
                    $newShippingFee = ceil($grossWeight / 1000) * $shippingKgPrice;
                    $parcelTax = round($amount * $parcelTaxRate / 100,0);
                    $discount > 0 ? $newDiscount = $discount : '';
                }

                //使用退回購物金方式
                if(isset($data['usingPoint']) && $data['usingPoint'] == 1){
                    $spendPoint = $spendPoint - ($oldAmount - $amount);
                    $spendPoint <= 0 ? $spendPoint = 0 : '';
                    $spendPoint <= 0 ? $returnPoint = $oldSpendPoint : $returnPoint = $oldAmount - $amount;
                    $user = UserDB::find($order->user_id);
                    $return['user_id'] = $user->id;
                    $return['balance'] = $user->points + $returnPoint;
                    $return['dead_time'] = Carbon::now()->addMonth(6);
                    $return['point_type'] = "【 $order->order_number 】商品取消返還購物金。";
                    UserPointDB::create($return);
                    $user->update(['points' => ($user->points + $returnPoint)]);
                }

                $order->update(['amount' => $amount, 'shipping_fee' => $newShippingFee, 'parcel_tax' => $parcelTax, 'spend_point' => $spendPoint, 'discount' => $newDiscount]);
                //檢查是否同步, 若有則自動重新
                $chkSynced = SyncedOrderDB::where('order_id',$order->id)->orderBy('created_at','desc')->first();
                if(!empty($chkSynced)){
                    request()->request->add(['id' => [$order->id]]); //將 order id 用成陣列放入 request()
                    OrderSynchronizeToDigiwinJob::dispatchNow(request());
                    if(count($orderItemIds) > 0){
                        $newId = $orderItemIds[$i]['newId'];
                        $oldId = $orderItemIds[$i]['oldId'];
                        for($i=0;$i<count($orderItemIds);$i++){
                            $oldTemp = SyncedOrderItemDB::where([
                                ['order_id',$order->id],
                                ['order_item_id',$orderItemIds[$i]['oldId']],
                                ['is_del',1]
                                ])->first();
                            $newTemp = SyncedOrderItemDB::where([
                                ['order_id',$order->id],
                                ['order_item_id',$orderItemIds[$i]['newId']],
                                ['is_del',0]
                                ])->first();
                            $erpOrderSno = $newTemp->erp_order_sno;
                            if(!empty($newTemp)){
                                $newTemp->update([
                                    'purchase_no' => $oldTemp->purchase_no,
                                    'erp_purchase_no' => $oldTemp->erp_purchase_no,
                                    'purchase_date' => $oldTemp->purchase_date,
                                ]);
                            }
                            //檢查出貨, 若有移動出貨資料對應新的訂單序號
                            foreach($order->items as $item){
                                if($item->id == $oldId){
                                    if(strstr($item->sku,'BOM')){
                                        foreach($newTemp->package as $package){
                                            $snos = explode(',',$package->erp_order_sno);
                                            $sells = SellItemSingleDB::where([['erp_order_no',$package->erp_order_no],['order_number',$order->order_number],['product_model_id',$package->product_model_id],['is_del',0]])->get();
                                            if(count($sells)>0){
                                                $i=0;
                                                foreach($sells as $sell){
                                                    for($x=0;$x<count($snos);$x++){
                                                        if($i == $x){
                                                            $sell->update([
                                                                'order_item_id' => $newId,
                                                                'order_item_package_id' => $package->order_item_package_id,
                                                                'erp_order_sno' => $snos[$x],
                                                            ]);
                                                        }
                                                    }
                                                    $i++;
                                                }
                                            }
                                        }
                                    }else{
                                        if(count($item->sells) > 0){
                                            foreach($item->sells as $sell){
                                                $sell->update([
                                                    'order_item_id' => $newId,
                                                    'erp_order_sno' => $erpOrderSno,
                                                ]);
                                            }
                                        }
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        return null;
    }
}
