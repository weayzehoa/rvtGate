<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryShippingSet as ShippingSetDB;
use App\Models\ErpCOPTI as ErpCOPTIDB;
use App\Models\ErpCOPTJ as ErpCOPTJDB;
use App\Models\ErpOrderItem as ErpOrderItemDB;
use App\Models\SellReturn as SellReturnDB;
use App\Models\SellReturnItem as SellReturnItemDB;
use App\Jobs\CheckErpSellReturnTaxJob;
use App\Traits\OrderFunctionTrait;

class SellReturnJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,OrderFunctionTrait;

    protected $param;
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param,$data)
    {
        $this->param = $param;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $exclude = ['5TWA007170001','5TWA007170002','5TWA007170003']; //排除訂單不指定結案的商品
        $data = $this->data;
        $order = $this->getOrderData($this->param,'show','update');
        $order = $this->oneOrderItemDataTransfer($order); //轉換貨號資料
        $erpOrder = $order->erpOrder;
        $shippingKgPrice = $order->shipping_kg_price;
        $erpOrder->TC016; //課稅別
        $originCountry = $order->origin_country;
        $shippingMethod = $order->shipping_method;
        $shipTo = $order->ship_to;
        $totalPriceWithTax = $totalPriceWithoutTax = $totalTax = $grossWeight = $amount = $shippingFee = $parcelTax = $parcelTaxRate = $zeroFeeModify = 0;
        $erpCOPTJ = $sellReturnItems = $sellReturn = $erpCOPTI = [];
        $shippingMethodName = null;
        $shippingMethod == 1 ? $shippingMethodName = '當地機場' : '';
        $shippingMethod == 2 ? $shippingMethodName = '當地旅店' : '';
        $shippingMethod == 3 && $shippingMethod == 6 ? $shippingMethodName = '當地地址' : '';
        $shippingMethod == 4 ? $shippingMethodName = $shipTo : '';
        $originCountry == '台灣' && $shippingMethod == 5 ? $shippingMethodName = '當地地址' : '';
        $originCountry == '日本' && $shipTo == '日本' ? $shippingMethodName = '當地地址' : '';
        //找出行郵稅費率
        $shippingSet = ShippingSetDB::where([['product_sold_country',$originCountry],['shipping_methods',$shippingMethodName],['is_on',1]])->first();
        !empty($shippingSet) ? $parcelTaxRate = $shippingSet->tax_rate : '';
        !empty($data['returnDate']) ? $returnDate = $data['returnDate'] : $returnDate = date('Y-m-d');
        !empty($data['returnMemo']) ? $memo = $data['returnMemo'] : $memo = '';
        !empty($data['zeroFeeModify']) ? $zeroFeeModify = $data['zeroFeeModify'] : '';
        $returnDateSix = substr(str_replace('-','',$returnDate),2,6);
        //已出貨才可做銷退,且必須填寫原因
        if(!empty($data['returnMemo']) && $data['returnQty'] == 1 && !empty($order) && $order->status >= 3 && count($data['items']) > 0){
            $c = 1;
            $createDate = date('Ymd');
            $createTime = date('H:i:s');
            !empty(auth('gate')->user()->account) ? $creator = strtoupper(auth('gate')->user()->account) : $creator = 'DS';
            //找出鼎新折讓單的最後一筆單號
            $tmp = ErpCOPTIDB::where('TI002','like',"$returnDateSix%")->select('TI002')->orderBy('TI002','desc')->first();
            !empty($tmp) ? $TI002 = $tmp->TI002 + 1 : $TI002 = $returnDateSix.str_pad(1,5,0,STR_PAD_LEFT);
            //找中繼銷退折讓單的最後一筆單號
            $tmp = SellReturnDB::where('return_no','like',"$createDate%")->select('return_no')->orderBy('return_no','desc')->first();
            !empty($tmp) ? $returnNo = $tmp->return_no + 1 : $returnNo = $createDate.str_pad(1,5,0,STR_PAD_LEFT);
            //以下客戶代號 TJ001 = A242 其餘 A241
            $v = ['001','002','003','004','005','006','007','008','009','037','063','073'];
            in_array($order->digiwin_payment_id,$v) ? $TJ001 = 'A242' : $TJ001 = 'A241';
            $array1 = [
                'COMPANY' => 'iCarry',
                'CREATOR' => $creator,
                'USR_GROUP' => 'DSC',
                'CREATE_DATE' => $createDate,
                'MODIFIER' => '',
                'MODI_DATE' => '',
                'FLAG' => 1,
                'CREATE_TIME' => $createTime,
                'CREATE_AP' => 'iCarry',
                'CREATE_PRID' => 'COPI08',
                'MODI_TIME' => '',
                'MODI_AP' => '',
                'MODI_PRID' => '',
                'EF_ERPMA001' => '',
                'EF_ERPMA002' => '',
                'TJ001' => $TJ001,  //單別
                'TJ002' => $TI002,  //單號
                'TJ009' => 0,  //庫存數量
                'TJ010' => '',  //小單位
                'TJ014' => '',  //批號
                'TJ015' => '',  //銷貨單別
                'TJ016' => '',  //銷貨單號
                'TJ017' => '',  //銷貨序號
                'TJ021' => 'N',  //確認碼
                'TJ022' => 'N',  //更新碼
                'TJ023' => '',  //備註
                'TJ024' => 'N',  //結帳碼
                'TJ025' => '',  //結帳單別
                'TJ026' => '',  //結帳單號
                'TJ027' => '',  //結帳序號
                'TJ028' => '',  //專案代號
                'TJ029' => '',  //客戶品號
                'TJ030' => 1,  //類型
                'TJ035' => 0,  //包裝數量
                'TJ036' => '',  //包裝單位
                'TJ041' => 1,  //數量類型
                'TJ042' => 0,  //贈/備品量
                'TJ043' => 0,  //贈/備品包裝量
                'TJ044' => '',  //發票號碼
                'TJ047' => 0,  //產品序號數量
                'TJ052' => 0,  //銷退原因代號
                'TJ099' => 1,  //品號稅別
            ];
            $items = $data['items'];
            $chkReturn = 0;
            for($i=0;$i<count($items);$i++){
                foreach($order->itemData as $it){
                    $TD007 = null;
                    $diffQty = $it->quantity - $it->return_quantity - $items[$i]['qty'];
                    if($diffQty > 0){ //有數量差表示有退貨數量
                        if($it->id == $items[$i]['id']){
                            $grossWeight += $it->gross_weight * $diffQty;
                            $itemPrice = $it->price;
                            $amount += $itemPrice * $diffQty;
                            $TD007 = $it->direct_shipment == 1 ? 'W02' : 'W01';
                            $shippingMemo = json_decode($it->shipping_memo);
                            if(!empty($shippingMemo) && is_array($shippingMemo)){
                                for($i=0;$i<count($shippingMemo);$i++){
                                    $shipping = $shippingMemo[$i];
                                    if(strstr($shipping->express_way,'新莊')){
                                        $TD007 = 'W15';
                                        break;
                                    }
                                }
                            }else{
                                strstr($it->shipping_memo,'新莊') ? $TD007 = 'W15' : '';
                            }
                            !empty($order->acOrder) ? $TD007 = 'W16' : '';
                            if(strstr($it->sku,'BOM')){
                                $packageData = json_decode($it->package_data);
                                if(is_array($packageData) && count($packageData) > 0){
                                    foreach($packageData as $pp){
                                        if($it->sku == $pp->bom){
                                            foreach($pp->lists as $list){
                                                $sku = $list->sku;
                                                $useQty = $list->quantity;
                                                $returnQty = $useQty * $diffQty; //實際要退的單品數量
                                                $listData = ProductModelDB::where('sku',$list->sku)->first();
                                                //檢查是否轉換貨號
                                                if(!empty($listData->origin_digiwin_no)){
                                                    $chkPM = ProductModelDB::where('digiwin_no',$listData->origin_digiwin_no)->first();
                                                    !empty($chkPM) ? $sku = $chkPM->sku : '';
                                                }
                                                if(count($it->package) > 0){
                                                    foreach($it->package as $package){
                                                        if($package->sku == $sku){ //找到對應的資料
                                                            $erpOrderItemSnos = explode(',',$package->syncedOrderItemPackage->erp_order_sno);
                                                            $erpItems = ErpOrderItemDB::where([
                                                                ['TD001',$erpOrder->TC001],
                                                                ['TD002',$erpOrder->TC002],
                                                                ['TD004',$package->digiwin_no],
                                                                ['TD007',$TD007],
                                                            ])->whereIn('TD003',$erpOrderItemSnos)->orderBy('TD003','asc')->get();
                                                            $count = $returnQty;
                                                            if(count($erpItems) > 0){
                                                                foreach($erpItems as $erpItem){
                                                                    //檢查是否已經退貨過
                                                                    $chkReturnItem = SellReturnItemDB::where([
                                                                        'erp_order_type' => $erpItem->TD001,
                                                                        'erp_order_no' => $erpItem->TD002,
                                                                        'erp_order_sno' => $erpItem->TD003,
                                                                        'is_del' => 0,
                                                                    ])->first();
                                                                    if(empty($chkReturnItem)){
                                                                        $price = $erpItem->TD011; //不做4捨5入, 由下面計算程式去做
                                                                        $calculate = $this->priceCalculate($price,$erpOrder->TC016,$zeroFeeModify);
                                                                        $totalPriceWithTax += $calculate['priceWithTax'];
                                                                        $totalPriceWithoutTax += $calculate['priceWithoutTax'];
                                                                        $totalTax += $calculate['tax'];
                                                                        $array2 = [
                                                                            'TJ003' => str_pad($c,4,'0',STR_PAD_LEFT), //序號
                                                                            'TJ004' => $erpItem->TD004,  //品號
                                                                            'TJ005' => $erpItem->TD005,  //品名
                                                                            'TJ006' => $erpItem->TD006,  //規格
                                                                            'TJ007' => 1,  //數量都是1
                                                                            'TJ008' => $erpItem->TD010,  //單位
                                                                            'TJ011' => $erpItem->TD011,  //單價
                                                                            'TJ012' => round($calculate['priceWithTax'],0),  //金額
                                                                            'TJ013' => $it->direct_shipment == 1 ? 'W02' : 'W12',  //退貨庫別
                                                                            'TJ018' => $erpItem->TD001,  //訂單單別
                                                                            'TJ019' => $erpItem->TD002,  //訂單單號
                                                                            'TJ020' => $erpItem->TD003,  //訂單序號
                                                                            'TJ031' => $calculate['priceWithoutTax'],  //原幣未稅金額
                                                                            'TJ032' => $calculate['tax'],  //原幣稅額
                                                                            'TJ033' => $calculate['priceWithoutTax'],  //本幣未稅金額
                                                                            'TJ034' => $calculate['tax'],  //本幣稅額
                                                                            'TJ045' => $erpOrder->TC061,  //網購訂單編號
                                                                        ];
                                                                        $erpCOPTJ[] = array_merge($array1,$array2);
                                                                        $sellReturnItems[] = [
                                                                            'order_id' => $order->id,
                                                                            'order_number' => $order->order_number,
                                                                            'order_item_id' => $it->id,
                                                                            'order_item_package_id' => $package->id,
                                                                            'erp_order_type' => $erpItem->TD001,
                                                                            'erp_order_no' => $erpItem->TD002,
                                                                            'erp_order_sno' => $erpItem->TD003,
                                                                            'return_no' => $returnNo,
                                                                            'erp_return_type' => $TJ001,
                                                                            'erp_return_no' => $TI002,
                                                                            'erp_return_sno' => str_pad($c,4,'0',STR_PAD_LEFT),
                                                                            'order_digiwin_no' => !empty($package->order_digiwin_no) ? $package->order_digiwin_no : $package->digiwin_no,
                                                                            'origin_digiwin_no' => $package->digiwin_no,
                                                                            'quantity' => 1,
                                                                            'return_quantity' => $diffQty,
                                                                            'price' => round($calculate['priceWithTax'],0),
                                                                            'direct_shipment' => $it->direct_shipment,
                                                                            'is_chk' => $it->direct_shipment == 1 ? 1 : 0,
                                                                            'chk_date' => $it->direct_shipment == 1 ? date('Y-m-d') : null,
                                                                            'admin_id' => $it->direct_shipment == 1 ? $data['admin_id'] : null,
                                                                            'is_stockin' => $it->direct_shipment == 1 ? 1 : 0,
                                                                            'is_confirm' => $it->direct_shipment == 1 ? 1 : 0,
                                                                            'created_at' => date('Y-m-d H:i:s'),
                                                                        ];
                                                                        //訂單單身指定結案
                                                                        ErpOrderItemDB::where([
                                                                            ['TD001',$erpItem->TD001],
                                                                            ['TD002',$erpItem->TD002],
                                                                            ['TD003',$erpItem->TD003],
                                                                            ['TD004',$erpItem->TD004],
                                                                            ['TD007',$erpItem->TD007],
                                                                        ])->update(['TD016' => 'y']);
                                                                        $c++;
                                                                        $count--;
                                                                        if($count == 0){
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }else{ //單品
                                if(!empty($order->acOrder)){ //錢街點數訂單
                                    $priceWithoutTax = $tax = 0;
                                    $erpItem = ErpOrderItemDB::where([
                                        ['TD001',$erpOrder->TC001],
                                        ['TD002',$erpOrder->TC002],
                                        ['TD003',$it->syncedOrderItem->erp_order_sno],
                                        ['TD004',$it->digiwin_no],
                                        ['TD007',$TD007],
                                    ])->first();
                                    if(!empty($erpItem)){
                                        $price = $diffQty;
                                        $calculate = $this->priceCalculate($price,$erpOrder->TC016,$zeroFeeModify);
                                        $totalPriceWithTax += $calculate['priceWithTax'];
                                        $totalPriceWithoutTax += $calculate['priceWithoutTax'];
                                        $totalTax += $calculate['tax'];
                                        $array2 = [
                                            'TJ003' => str_pad($c,4,'0',STR_PAD_LEFT), //序號
                                            'TJ004' => $it->digiwin_no,  //品號
                                            'TJ005' => $it->product_name,  //品名
                                            'TJ006' => $it->serving_size,  //規格
                                            'TJ007' => $diffQty,  //數量
                                            'TJ008' => $it->unit_name,  //單位
                                            'TJ011' => $it->price,  //單價
                                            'TJ012' => $calculate['priceWithTax'],  //金額
                                            'TJ013' => 'W16',  //退貨庫別
                                            'TJ018' => $erpItem->TD001,  //訂單單別
                                            'TJ019' => $erpItem->TD002,  //訂單單號
                                            'TJ020' => $erpItem->TD003,  //訂單序號
                                            'TJ031' => $calculate['priceWithoutTax'],  //原幣未稅金額
                                            'TJ032' => $calculate['tax'],  //原幣稅額
                                            'TJ033' => $calculate['priceWithoutTax'],  //本幣未稅金額
                                            'TJ034' => $calculate['tax'],  //本幣稅額
                                            'TJ045' => $erpOrder->TC061,  //網購訂單編號
                                        ];
                                        $erpCOPTJ[] = array_merge($array1,$array2);
                                        $sellReturnItems[] = [
                                            'order_id' => $order->id,
                                            'order_item_id' => $it->id,
                                            'order_item_package_id' => null,
                                            'erp_order_type' => $erpItem->TD001,
                                            'erp_order_no' => $erpItem->TD002,
                                            'erp_order_sno' => $erpItem->TD003,
                                            'order_number' => $order->order_number,
                                            'return_no' => $returnNo,
                                            'erp_return_type' => $TJ001,
                                            'erp_return_no' => $TI002,
                                            'erp_return_sno' => str_pad($c,4,'0',STR_PAD_LEFT),
                                            'order_digiwin_no' => !empty($it->order_digiwin_no) ? $it->order_digiwin_no : $it->digiwin_no,
                                            'origin_digiwin_no' => $it->digiwin_no,
                                            'quantity' => $erpItem->TD008,
                                            'return_quantity' => $diffQty,
                                            'price' => $it->price,
                                            'direct_shipment' => $it->direct_shipment,
                                            'is_chk' => 1,
                                            'chk_date' => date('Y-m-d'),
                                            'admin_id' => 1,
                                            'is_stockin' => 1,
                                            'is_confirm' => 1,
                                            'created_at' => date('Y-m-d H:i:s'),
                                        ];
                                        if(!in_array($it->digiwin_no,$exclude)){
                                            //訂單單身指定結案
                                            ErpOrderItemDB::where([
                                                ['TD001',$erpItem->TD001],
                                                ['TD002',$erpItem->TD002],
                                                ['TD003',$erpItem->TD003],
                                                ['TD004',$erpItem->TD004],
                                                ['TD007',$erpItem->TD007],
                                            ])->update(['TD016' => 'y']);
                                        }
                                        $c++;
                                    }
                                }else{
                                    for($x=0;$x<$diffQty;$x++){
                                        $priceWithoutTax = $tax = 0;
                                        $erpItem = ErpOrderItemDB::where([
                                            ['TD001',$erpOrder->TC001],
                                            ['TD002',$erpOrder->TC002],
                                            ['TD003',$it->syncedOrderItem->erp_order_sno],
                                            ['TD004',$it->digiwin_no],
                                            ['TD007',$TD007],
                                        ])->first();
                                        if(!empty($erpItem)){
                                            $price = $erpItem->TD011;
                                            $calculate = $this->priceCalculate($price,$erpOrder->TC016,$zeroFeeModify);
                                            $totalPriceWithTax += $calculate['priceWithTax'];
                                            $totalPriceWithoutTax += $calculate['priceWithoutTax'];
                                            $totalTax += $calculate['tax'];
                                            $array2 = [
                                                'TJ003' => str_pad($c,4,'0',STR_PAD_LEFT), //序號
                                                'TJ004' => $it->digiwin_no,  //品號
                                                'TJ005' => $it->product_name,  //品名
                                                'TJ006' => $it->serving_size,  //規格
                                                'TJ007' => 1,  //數量都是1
                                                'TJ008' => $it->unit_name,  //單位
                                                'TJ011' => $it->price,  //單價
                                                'TJ012' => $calculate['priceWithTax'],  //金額
                                                'TJ013' => $it->direct_shipment == 1 ? 'W02' : 'W12',  //退貨庫別
                                                'TJ018' => $erpItem->TD001,  //訂單單別
                                                'TJ019' => $erpItem->TD002,  //訂單單號
                                                'TJ020' => $erpItem->TD003,  //訂單序號
                                                'TJ031' => $calculate['priceWithoutTax'],  //原幣未稅金額
                                                'TJ032' => $calculate['tax'],  //原幣稅額
                                                'TJ033' => $calculate['priceWithoutTax'],  //本幣未稅金額
                                                'TJ034' => $calculate['tax'],  //本幣稅額
                                                'TJ045' => $erpOrder->TC061,  //網購訂單編號
                                            ];
                                            $erpCOPTJ[] = array_merge($array1,$array2);
                                            $sellReturnItems[] = [
                                                'order_id' => $order->id,
                                                'order_item_id' => $it->id,
                                                'order_item_package_id' => null,
                                                'erp_order_type' => $erpItem->TD001,
                                                'erp_order_no' => $erpItem->TD002,
                                                'erp_order_sno' => $erpItem->TD003,
                                                'order_number' => $order->order_number,
                                                'return_no' => $returnNo,
                                                'erp_return_type' => $TJ001,
                                                'erp_return_no' => $TI002,
                                                'erp_return_sno' => str_pad($c,4,'0',STR_PAD_LEFT),
                                                'order_digiwin_no' => !empty($it->order_digiwin_no) ? $it->order_digiwin_no : $it->digiwin_no,
                                                'origin_digiwin_no' => $it->digiwin_no,
                                                'quantity' => 1,
                                                'return_quantity' => $diffQty,
                                                'price' => round($calculate['priceWithTax'],0),
                                                'direct_shipment' => $it->direct_shipment,
                                                'is_chk' => $it->direct_shipment == 1 ? 1 : 0,
                                                'chk_date' => $it->direct_shipment == 1 ? date('Y-m-d') : null,
                                                'admin_id' => $it->direct_shipment == 1 ? $data['admin_id'] : null,
                                                'is_stockin' => $it->direct_shipment == 1 ? 1 : 0,
                                                'is_confirm' => $it->direct_shipment == 1 ? 1 : 0,
                                                'created_at' => date('Y-m-d H:i:s'),
                                            ];
                                            if(!in_array($it->digiwin_no,$exclude)){
                                                //訂單單身指定結案
                                                ErpOrderItemDB::where([
                                                    ['TD001',$erpItem->TD001],
                                                    ['TD002',$erpItem->TD002],
                                                    ['TD003',$erpItem->TD003],
                                                    ['TD004',$erpItem->TD004],
                                                    ['TD007',$erpItem->TD007],
                                                ])->update(['TD016' => 'y']);
                                            }
                                            $c++;
                                        }
                                    }
                                }
                            }
                            unset($it->sku);
                            unset($it->vendor_name);
                            unset($it->product_id);
                            unset($it->order_digiwin_no);
                            $it->update(['return_quantity' => ($diffQty + $it->return_quantity)]);
                            $chkReturn++;
                        }
                    }
                }
            }
            if($chkReturn > 0){
                //同時調整運費、行郵稅選項啟動 (只有海外訂單才會動作)
                if(isset($data['shippingFeeModify']) && $data['shippingFeeModify'] == 1){
                    $shippingFee = round($grossWeight / 1000) * $shippingKgPrice;
                    $parcelTax = round($amount * $parcelTaxRate / 100,0);
                }
                if(isset($data['returnShippingFee']) && $data['returnShippingFee'] > 0){
                    $shippingFee = $data['returnShippingFee'];
                }
                if($shippingFee > 0){
                    $erpItem = ErpOrderItemDB::where([
                        ['TD001',$erpOrder->TC001],
                        ['TD002',$erpOrder->TC002],
                        ['TD004','901001'],
                        ['TD007','W07'],
                    ])->first();
                    if(!empty($erpItem)){
                        $calculate = $this->priceCalculate($shippingFee,$erpOrder->TC016,$zeroFeeModify);
                        $totalPriceWithTax += $calculate['priceWithTax'];
                        $totalPriceWithoutTax += $calculate['priceWithoutTax'];
                        $totalTax += $calculate['tax'];
                        $array2 = [
                            'TJ003' => str_pad($c,4,'0',STR_PAD_LEFT), //序號
                            'TJ004' => $erpItem->TD004,  //品號
                            'TJ005' => $erpItem->TD005,  //品名
                            'TJ006' => '',  //規格
                            'TJ007' => 1,  //數量都是1
                            'TJ008' => $erpItem->TD010,  //單位
                            'TJ011' => $erpItem->TD011,  //單價
                            'TJ012' => $calculate['priceWithTax'],  //金額
                            'TJ013' => $erpItem->TD007,  //退貨庫別
                            'TJ018' => $erpItem->TD001,  //訂單單別
                            'TJ019' => $erpItem->TD002,  //訂單單號
                            'TJ020' => $erpItem->TD003,  //訂單序號
                            'TJ031' => $calculate['priceWithoutTax'],  //原幣未稅金額
                            'TJ032' => $calculate['tax'],  //原幣稅額
                            'TJ033' => $calculate['priceWithoutTax'],  //本幣未稅金額
                            'TJ034' => $calculate['tax'],  //本幣稅額
                            'TJ045' => $erpOrder->TC061,  //網購訂單編號
                        ];
                        $erpCOPTJ[] = array_merge($array1,$array2);
                        $sellReturnItems[] = [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'order_item_id' => null,
                            'order_item_package_id' => null,
                            'erp_order_type' => $erpItem->TD001,
                            'erp_order_no' => $erpItem->TD002,
                            'erp_order_sno' => $erpItem->TD003,
                            'return_no' => $returnNo,
                            'erp_return_type' => $TJ001,
                            'erp_return_no' => $TI002,
                            'erp_return_sno' => str_pad($c,4,'0',STR_PAD_LEFT),
                            'order_digiwin_no' => 901001,
                            'origin_digiwin_no' => 901001,
                            'quantity' => 1,
                            'return_quantity' => null,
                            'price' => round($calculate['priceWithTax'],0),
                            'direct_shipment' => 0,
                            'is_chk' => 0,
                            'chk_date' => null,
                            'admin_id' => null,
                            'is_stockin' => 0,
                            'is_confirm' => 0,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                        //訂單單身指定結案
                        ErpOrderItemDB::where([
                            ['TD001',$erpItem->TD001],
                            ['TD002',$erpItem->TD002],
                            ['TD003',$erpItem->TD003],
                            ['TD004',$erpItem->TD004],
                            ['TD007',$erpItem->TD007],
                        ])->update(['TD016' => 'y']);
                        $c++;
                    }
                }
                if($parcelTax > 0){
                    $erpItem = ErpOrderItemDB::where([
                        ['TD001',$erpOrder->TC001],
                        ['TD002',$erpOrder->TC002],
                        ['TD004','901002'],
                        ['TD007','W07'],
                    ])->first();
                    if(!empty($erpItem)){
                        $calculate = $this->priceCalculate($parcelTax,$erpOrder->TC016,$zeroFeeModify);
                        $totalPriceWithTax += $calculate['priceWithTax'];
                        $totalPriceWithoutTax += $calculate['priceWithoutTax'];
                        $totalTax += $calculate['tax'];
                        $array2 = [
                            'TJ003' => str_pad($c,4,'0',STR_PAD_LEFT), //序號
                            'TJ004' => $erpItem->TD004,  //品號
                            'TJ005' => $erpItem->TD005,  //品名
                            'TJ006' => '',  //規格
                            'TJ007' => 1,  //數量都是1
                            'TJ008' => $erpItem->TD010,  //單位
                            'TJ011' => $erpItem->TD011,  //單價
                            'TJ012' => $calculate['priceWithTax'],  //金額
                            'TJ013' => $erpItem->TD007,  //退貨庫別
                            'TJ018' => $erpItem->TD001,  //訂單單別
                            'TJ019' => $erpItem->TD002,  //訂單單號
                            'TJ020' => $erpItem->TD003,  //訂單序號
                            'TJ031' => $calculate['priceWithoutTax'],  //原幣未稅金額
                            'TJ032' => $calculate['tax'],  //原幣稅額
                            'TJ033' => $calculate['priceWithoutTax'],  //本幣未稅金額
                            'TJ034' => $calculate['tax'],  //本幣稅額
                            'TJ045' => $erpOrder->TC061,  //網購訂單編號
                        ];
                        $erpCOPTJ[] = array_merge($array1,$array2);
                        $sellReturnItems[] = [
                            'order_id' => $order->id,
                            'order_item_id' => null,
                            'order_item_package_id' => null,
                            'erp_order_type' => $erpItem->TD001,
                            'erp_order_no' => $erpItem->TD002,
                            'erp_order_sno' => $erpItem->TD003,
                            'order_number' => $order->order_number,
                            'return_no' => $returnNo,
                            'erp_return_type' => $TJ001,
                            'erp_return_no' => $TI002,
                            'erp_return_sno' => str_pad($c,4,'0',STR_PAD_LEFT),
                            'order_digiwin_no' => 901002,
                            'origin_digiwin_no' => 901002,
                            'quantity' => 1,
                            'return_quantity' => null,
                            'price' => round($calculate['priceWithTax'],0),
                            'created_at' => date('Y-m-d H:i:s'),
                            'direct_shipment' => 0,
                            'is_chk' => 0,
                            'chk_date' => null,
                            'admin_id' => null,
                            'is_stockin' => 0,
                            'is_confirm' => 0,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                        //訂單單身指定結案
                        ErpOrderItemDB::where([
                            ['TD001',$erpItem->TD001],
                            ['TD002',$erpItem->TD002],
                            ['TD003',$erpItem->TD003],
                            ['TD004',$erpItem->TD004],
                            ['TD007',$erpItem->TD007],
                        ])->update(['TD016' => 'y']);
                    }
                }
                $erpCOPTI = [
                    'COMPANY' => 'iCarry',
                    'CREATOR' => $creator,
                    'USR_GROUP' => 'DSC',
                    'CREATE_DATE' => $createDate,
                    'MODIFIER' => '',
                    'MODI_DATE' => '',
                    'FLAG' => 1,
                    'CREATE_TIME' => $createTime,
                    'CREATE_AP' => 'iCarry',
                    'CREATE_PRID' => 'COPI08',
                    'MODI_TIME' => '',
                    'MODI_AP' => '',
                    'MODI_PRID' => '',
                    'EF_ERPMA001' => '',
                    'EF_ERPMA002' => '',
                    'TI001' => $TJ001,  //單別
                    'TI002' => $TI002,  //單號
                    'TI003' => str_replace(['-','/'],['',''],$returnDate), //銷退日
                    'TI004' => $order->customer['customer_no'], //客戶
                    'TI005' => $order->customer['MA015'], //部門
                    'TI006' => $order->customer['MA016'], //業務員
                    'TI007' => '001', //廠別
                    'TI008' => 'NTD', //幣別
                    'TI009' => 1, //匯率
                    'TI010' => $totalPriceWithoutTax, //原幣銷退金額
                    'TI011' => round($totalPriceWithoutTax * 0.05,0), //原幣銷退稅額
                    'TI012' => $order->customer['MA037'], //發票聯數
                    'TI013' => $erpOrder->TC016, //課稅別
                    'TI014' => '', //發票號碼
                    'TI015' => $erpOrder->TC001 == 'A221' ? $order->customer['MA010'] : $order->invoice_number, //統一編號
                    'TI016' => 1, //列印次數
                    'TI017' => str_replace(['-','/'],['',''],$returnDate), //發票日期
                    'TI018' => 'N', //更新碼
                    'TI019' => 'N', //確認碼
                    'TI020' => $memo, //備註
                    'TI021' => $order->erpCustomer['customer_name'], //客戶全名
                    'TI022' => '', //收款業務員
                    'TI023' => '', //備註一
                    'TI024' => '', //備註二
                    'TI025' => 0, //折讓列印次數
                    'TI026' => 1, //扣抵區分
                    'TI027' => 1, //通關方式
                    'TI028' => 0, //件數
                    'TI029' => 0, //總數量
                    'TI030' => '', //員工代號
                    'TI031' => 'N', //產生分錄碼(收入)
                    'TI032' => 'N', //產生分錄碼(成本)
                    'TI033' => substr(str_replace(['-','/'],['',''],$returnDate),0,6), //申報年月
                    'TI034' => str_replace(['-','/'],['',''],$returnDate), //單據日期
                    'TI035' => '', //確認者
                    'TI036' => 0.05, //營業稅率
                    'TI037' => $totalPriceWithoutTax, //本幣銷退金額
                    'TI038' => round($totalPriceWithoutTax * 0.05,0), //本幣銷退稅額
                    'TI039' => 'N', //簽核狀態碼
                    'TI040' => 1, //交易條件
                    'TI041' => 0, //總包裝數量
                    'TI042' => 0, //傳送次數
                    'TI043' => $order->customer['MA083'], //付款條件代號
                    'TI044' => '', //客戶描述
                    'TI045' => '', //作廢日期
                    'TI046' => '', //作廢時間
                    'TI047' => '', //作廢原因
                    'TI081' => 0, //原幣應稅銷售額
                    'TI089' => '', //賣方開立折讓單
                    'TI090' => '', //折讓單成立日
                    'TI091' => '', //連絡人EMAIL
                    'TI092' => 0, //原幣免稅銷售額
                    'TI093' => 0, //本幣應稅銷售額
                    'TI094' => 0, //本幣免稅銷售額
                ];
                $sellReturn = [
                    'type' => '銷退',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'return_no' => $returnNo,
                    'erp_return_type' => $TJ001,
                    'erp_return_no' => $TI002,
                    'price' => $totalPriceWithoutTax,
                    'tax' => round($totalPriceWithoutTax * 0.05,0),
                    'memo' => $memo,
                    'return_date' => $returnDate,
                    'return_admin_id' => $data['admin_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                if(count($erpCOPTJ) > 0){
                    //下面方法避開 Tried to bind parameter number 2101.
                    if(count($erpCOPTJ) >= 20){
                        $coptjs = array_chunk($erpCOPTJ,20);
                        for($i=0;$i<count($coptjs);$i++){
                            ErpCOPTJDB::insert($coptjs[$i]);
                        }
                    }else{
                        ErpCOPTJDB::insert($erpCOPTJ);
                    }
                    if(count($sellReturnItems) >= 50){
                        $items = array_chunk($sellReturnItems,50);
                        for($i=0;$i<count($items);$i++){
                            SellReturnItemDB::insert($items[$i]);
                        }
                    }else{
                        SellReturnItemDB::insert($sellReturnItems);
                    }
                    $erpSellReturn = ErpCOPTIDB::create($erpCOPTI);
                    SellReturnDB::insert($sellReturn);

                    !empty($order->acOrder) ? $order->acOrder->update(['sell_return' => 1]) : '';
                    //檢查Erp銷退單稅額是否正確
                    CheckErpSellReturnTaxJob::dispatchNow($erpSellReturn);
                }
            }
        }
    }
}
