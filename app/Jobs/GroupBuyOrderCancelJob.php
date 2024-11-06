<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryOrder as iCarryOrderDB;
use App\Models\iCarryGroupBuyingOrderItem as OrderItemDB;
use App\Models\iCarryGroupBuyingOrderItemPackage as OrderItemPackageDB;
use App\Models\iCarryShippingSet as ShippingSetDB;

use App\Jobs\OrderCancelJob;

class GroupBuyOrderCancelJob implements ShouldQueue
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
        $groupBuyOrder = $this->order;
        $data = $this->data;
        //檢查是否成團
        $iCarryOrder = iCarryOrderDB::with('itemData')->where('order_number',$groupBuyOrder->partner_order_number)->first();
        if(empty($iCarryOrder)){
            return "此團購訂單尚未成團，無法部分取消，請直接取消整張訂單。";
        }else{
            $items = $groupBuyOrder->itemData;
            $iCarryItems = $iCarryOrder->itemData;
            $cancelPerson = auth('gate')->user()->name;
            $originShippingFee = $groupBuyOrder->shipping_fee;
            $shippingKgPrice = $groupBuyOrder->shipping_kg_price;
            $parcelTax = $groupBuyOrder->parcel_tax;
            $originCountry = $groupBuyOrder->origin_country;
            $shippingMethod = $groupBuyOrder->shipping_method;
            $oldAmount = $groupBuyOrder->amount;
            $spendPoint = $groupBuyOrder->spend_point;
            $oldSpendPoint = $groupBuyOrder->spend_point;
            $shipTo = $groupBuyOrder->ship_to;
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
            $newData['itemQty'] = $newData['discountModify'] = $newData['shippingFeeModify'] = 1;
            $newData['admin_id'] = auth('gate')->user()->id;
            $newData['items'] = [];
            if(isset($data['status']) && $data['status'] == -1){ //取消整張訂單
                $i = 0;
                foreach($items as $item){
                    $digiwinNo = $item->digiwin_no;
                    foreach($iCarryItems as $icarry){
                        if($icarry->digiwin_no == $item->digiwin_no){
                            $newData['items'][$i]['id'] = $icarry->id;
                            $newData['items'][$i]['qty'] = $icarry->quantity - $item->quantity;
                            break;
                        }
                    }
                    $i++;
                }
            }
            if(isset($data['itemQty']) && $data['itemQty'] == 1 && isset($data['items']) && count($data['items']) > 0){
                if(count($data['items']) > 0){
                    $chk = $i= 0; $result = $orderItemIds = [];
                    //檢查取消數量
                    foreach ($data['items'] as $it) {
                        $sellQty = 0;
                        $item = OrderItemDB::find($it['id']);
                        if($it['qty'] >= $item->quantity){
                            $chk++;
                            $result[] = "$item->digiwin_no 數量需小於訂單數量。";
                        }
                    }
                    if($chk > 0){
                        return join('<br>',$result);
                    }else{
                        foreach ($data['items'] as $it) {
                            $item = OrderItemDB::with('packageData')->find($it['id']);
                            $originQty = $item->quantity;
                            $diffQty = 0;
                            $oldItemId = $item->id;
                            $newItemId = null;
                            $newOrderItemIds = [];
                            if($it['qty'] != $originQty && $it['qty'] >= 0){
                                if($it['qty'] != 0){
                                    $diffQty = $item->quantity - $it['qty'];
                                    $itemNewData = $item->toArray();
                                    $itemNewData['quantity'] = $it['qty']; //取正數
                                    $orderItemIds[$i]['oldId'] = $itemNewData['id'];
                                    unset($itemNewData['id']);
                                    $orderItem = OrderItemDB::create($itemNewData);
                                    $orderItemIds[$i]['newId'] = $newItemId = $orderItem->id;
                                    if(count($item->packageData) > 0){ // 有組合資料
                                        $x=0;
                                        foreach($item->packageData as $package){
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
                                    foreach($iCarryItems as $icarry){
                                        if($icarry->digiwin_no == $item->digiwin_no){
                                            $newData['items'][$i]['id'] = $icarry->id;
                                            $newData['items'][$i]['qty'] = $icarry->quantity - $diffQty;
                                            break;
                                        }
                                    }
                                }else{
                                    foreach($iCarryItems as $icarry){
                                        if($icarry->digiwin_no == $item->digiwin_no){
                                            $newData['items'][$i]['id'] = $icarry->id;
                                            $newData['items'][$i]['qty'] = $icarry->quantity - $item->quantity;
                                            break;
                                        }
                                    }
                                }
                                $item->update(['is_del' => 1]);
                            }
                            $i++;
                        }
                        //重新計算訂單金額, 同時調整運費、行郵稅、折扣
                        $discount = $grossWeight = $amount = 0;
                        $newShippingFee = $originShippingFee;
                        $items = OrderItemDB::where([['order_id',$groupBuyOrder->id],['is_del',0]])->get();
                        foreach($items as $item){
                            $amount += $item->price * $item->quantity;
                            $grossWeight += $item->quantity * $item->gross_weight;
                            $discount += $item->discount * $item->quantity;
                        }
                        $newShippingFee = ceil($grossWeight / 1000) * $shippingKgPrice;
                        $parcelTax = round($amount * $parcelTaxRate / 100,0);
                        $groupBuyOrder->update(['amount' => $amount, 'shipping_fee' => $newShippingFee, 'parcel_tax' => $parcelTax, 'spend_point' => $spendPoint, 'discount' => $discount]);
                    }
                }
            }
            OrderCancelJob::dispatchNow($iCarryOrder,$newData);
        }
        return null;
    }
}
