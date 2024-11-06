<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

// ==============================================================

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;

use App\Models\ErpCustomer as ErpCustomerDB;
use App\Models\ErpOrder as ErpOrderDB;
use App\Models\ErpOrderItem as ErpOrderItemDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\ErpCOPTG as ErpCOPTGDB;
use App\Models\ErpCOPTH as ErpCOPTHDB;

use App\Models\Sell as SellDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use App\Models\SellImport as SellImportDB;
use App\Models\SellAbnormal as SellAbnormalDB;
use App\Models\StockinImport as StockinImportDB;
use App\Models\OrderShipping as OrderShippingDB;
use DB;

use App\Imports\WarehouseShipImport;
use App\Imports\VendorDirectShipImport;

use App\Jobs\AdminImportJob;
use App\Traits\OrderFunctionTrait;

class CheckOrderSellAndCreateDigiwinSellJob implements ShouldQueue
{
    use OrderFunctionTrait,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->order;
        $sellAbnormals = SellAbnormalDB::where([['order_id',$order->id],['is_chk',0]])->get();
        //檢查訂單狀態是否為1,2及是否有異常
        if(!empty($order) && ($order->status == 1 || $order->status == 2) && count($sellAbnormals) == 0){
            //檢查訂單是否全部已出貨
            $chkItemOut = $chkPackageOut = 0;
            foreach($order->items as $item){
                if($item->is_del == 0){ //排除掉被取消的商品
                    if(strstr($item,'BOM')){
                        foreach($item->package as $package){
                            $sellPackageQty = 0;
                            $sellItems = SellItemSingleDB::where([['order_item_id',$item->id],['order_item_package_id',$package->id],['is_del',0]])->get();
                            if(count($sellItems) > 0){
                                foreach($sellItems as $sellItem){
                                    $sellPackageQty += $sellItem->sell_quantity;
                                }
                                if($package->quantity > $sellPackageQty){ //出貨數量小於需求
                                    $chkPackageOut++;
                                }
                            }else{ //沒有出貨
                                $chkPackageOut++;
                            }
                        }
                    }else{
                        $sellItemQty = 0;
                        $sellItems = SellItemSingleDB::where('order_item_id',$item->id)->where('is_del',0)->where(function($query){
                            $query->whereNull('order_item_package_id')->orWhere('order_item_package_id','');
                        })->get();
                        if(count($sellItems) > 0){
                            foreach($sellItems as $sellItem){
                                $sellItemQty += $sellItem->sell_quantity;
                            }
                            if($item->quantity > $sellItemQty){ //出貨數量小於需求
                                $chkItemOut++;
                            }
                        }else{ //沒有出貨
                            $chkItemOut++;
                        }
                    }
                }
            }
            if($chkItemOut == 0 && $chkPackageOut == 0){
                //清除無法更新的變數
                $shipping = [];
                unset($order->totalWeight);
                unset($order->totalPrice);
                unset($order->totalQty);
                unset($order->totalSellQty);
                unset($order->total_pay);
                unset($order->shipping_memo_vendor);
                //清除所有相關物流資料
                OrderShippingDB::where('order_id',$order->id)->delete();
                $order->update(['shipping_memo' => null ,'shipping_number' => null]);
                //找出所有出貨資料
                $sellItems = SellItemSingleDB::where('order_number',$order->order_number)
                ->where('is_del',0)
                ->select([
                    'express_way',
                    'express_no',
                ])->groupBy('express_way','express_no')->get();
                if(count($sellItems) > 0){
                    foreach($sellItems as $sellItem){
                        $shipping[] = [
                            'order_id' => $order->id,
                            'express_way' => $sellItem->express_way,
                            'express_no' => $sellItem->express_no,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }
                }
                if(!empty($shipping)){
                    $shippingNumber = $shippingMemo = [];
                    OrderShippingDB::insert($shipping);
                    $orderShippings = OrderShippingDB::where('order_id',$order->id)->get();
                    foreach($orderShippings as $orderShipping){
                        $shippingMemo[] = [
                            'create_time' => str_replace('-','/',$orderShipping->created_at),
                            'express_way' => $orderShipping->express_way,
                            'express_no' => $orderShipping->express_no,
                        ];
                        $shippingNumber[] = $orderShipping->express_no;
                    }
                    $shippingMemo = json_encode($shippingMemo);
                    $shippingNumber = join(',',$shippingNumber);
                    $order->update(['shipping_number' => $shippingNumber, 'shipping_memo' => $shippingMemo]);
                }
                $result = $this->createDigiwinSell();
                if($result == true){
                    $order->update(['status' => 3]);
                }
            }
        }
    }

    private function createDigiwinSell()
    {
        $order = $this->order;
        $erpOrder = ErpOrderDB::with('items')->where('TC061',$order->order_number)->orWhere('TC061',$order->partner_order_number)->first();
        $erpSell = $sell = $erpPURTG = $erpPURTH = [];
        $createDate = date('Ymd');
        $createTime = date('H:i:s');

        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellItemSingleTable = env('DB_DATABASE').'.'.(new SellItemSingleDB)->getTable();
        !empty($this->param['admin_id']) ? $adminId = $this->param['admin_id'] : $adminId = null;
        !empty(auth('gate')->user()->account) ? $creator = strtoupper(auth('gate')->user()->account) : $creator = 'DS';
        //找出今日最後一筆進貨單號碼的流水號
        $tmp = ErpCOPTGDB::where('CREATE_DATE',date('Ymd'))->select('TG002')->orderBy('TG002','desc')->first();
        !empty($tmp) ? $lastErpSellNo = $tmp->TG002 : $lastErpSellNo = 0;
        //找出中繼今日最後一筆銷貨單號碼的流水號
        $tmp = SellDB::where('sell_no','>=',date('ymd').'00001')->select('sell_no')->orderBy('sell_no','desc')->first();
        !empty($tmp) ? $lastSellNo = $tmp->sell_no : $lastSellNo = 0;
        $c = 1;
        $totalQty = $amount = $tax = $itemTax = 0;
        $sell = SellDB::with('items')->where([['order_number',$order->order_number],['is_del',0]])->orderBy('sell_date','desc')->first();
        if(!empty($sell)){
            $erpCustomer = $order->erpCustomer;
            $lastErpSellNo != 0 ? $TG002 = $lastErpSellNo + $c : $TG002 = date('ymd').str_pad($c,5,0,STR_PAD_LEFT);
            $lastSellNo != 0 ? $sellNo = $lastSellNo + $c : $sellNo = date('ymd').str_pad($c,5,0,STR_PAD_LEFT);
            $erpOrder->TC001 == 'A221' ? $TG001 = 'A231' : '';
            $erpOrder->TC001 == 'A222' ? $TG001 = 'A232' : '';
            $order->digiwin_payment_id == '025' ? $TG001 = 'A237' : '';
            $array1 = [
                    'COMPANY' => 'iCarry',
                    'CREATOR' => $creator,
                    'USR_GROUP' => 'DSC',
                    'CREATE_DATE' => $createDate,
                    'MODIFIER' => '',
                    'MODI_DATE' => '',
                    'FLAG' => '1',
                    'CREATE_TIME' => $createTime,
                    'CREATE_AP' => 'iCarry',
                    'CREATE_PRID' => 'COPI08',
                    'MODI_TIME' => '',
                    'MODI_AP' => '',
                    'MODI_PRID' => '',
                    'EF_ERPMA001' => '',
                    'EF_ERPMA002' => '',
                    'TH001' => $TG001, //單別
                    'TH002' => $TG002, //單號
                    'TH010' => 0, //庫存數量
                    'TH011' => '', //小單位
                    'TH017' => '', //批號
                    'TH019' => '', //客戶品號
                    'TH020' => 'N', //確認碼
                    'TH021' => 'N', //更新碼
                    'TH022' => '', //保留欄位
                    'TH023' => '', //保留欄位
                    'TH024' => 0, //贈/備品量
                    'TH026' => 'N', //結帳碼
                    'TH027' => '', //結帳單別
                    'TH028' => '', //結帳單號
                    'TH029' => '', //結帳序號
                    'TH030' => '', //專案代號
                    'TH031' => 1, //類型
                    'TH032' => '', //暫出單別
                    'TH033' => '', //暫出單號
                    'TH034' => '', //暫出序號
                    'TH039' => '', //預留欄位
                    'TH040' => '', //預留欄位
                    'TH041' => '', //預留欄位
                    // 'TH042' => '', //包裝數量
                    // 'TH043' => '', //贈/備品包裝量
                    'TH044' => '', //包裝單位
                    'TH045' => '', //發票號碼
                    'TH046' => '', //生產加工包裝資訊
                    // 'TH057' => '', //產品序號數量
                    'TH074' => '', //CRM來源
                    'TH075' => '', //CRM單別
                    'TH076' => '', //CRM單號
                    'TH077' => '', //CRM序號
                    'TH099' => 1, //品號稅別
            ];
            $i = 1;
            $sellDate = $sell->sell_date;
            $sellItems = SellItemSingleDB::where('order_number',$sell->order_number)
            ->join($productModelTable,$productModelTable.'.id',$sellItemSingleTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->where($sellItemSingleTable.'.is_del',0)
            ->select([
                $sellItemSingleTable.'.*',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable.'.unit_name',
                $productModelTable.'.sku',
                $productModelTable.'.digiwin_no',
                $vendorTable.'.name as vendor_name',
            ])->get();
            foreach($sellItems as $item){
                $erpOrderItem = ErpOrderItemDB::where([['TD002',$item->erp_order_no],['TD003',$item->erp_order_sno],['TD004',$item->digiwin_no]])->first();
                $itemPrice = $erpOrderItem->TD011;
                $price = $item->sell_quantity * $itemPrice;
                if($erpOrder->TC016 == 1){
                    $priceWithoutTax = $price / 1.05;
                    $itemTax = $priceWithoutTax * 0.05;
                }elseif($erpOrder->TC016 == 2){
                    $priceWithoutTax = $price;
                    $itemTax = $price * 0.05;
                }else{
                    $priceWithoutTax = $price;
                    $itemTax = 0;
                }
                $amount += $priceWithoutTax;
                $tax += $itemTax;
                $totalQty += $erpOrderItem->TD008;
                $array2 = [
                    'TH003' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                    'TH004' => $erpOrderItem->TD004, //品號
                    'TH005' => $erpOrderItem->TD005, //品名
                    'TH006' => $erpOrderItem->TD006, //規格
                    'TH007' => $erpOrderItem->TD007, //庫別
                    'TH008' => $item->sell_quantity, //數量
                    'TH009' => $erpOrderItem->TD010, //單位
                    'TH012' => round($itemPrice,2), //單價
                    'TH013' => round($price,0), //金額
                    'TH014' => $erpOrderItem->TD001, //訂單單別
                    'TH015' => $erpOrderItem->TD002, //訂單單號
                    'TH016' => $erpOrderItem->TD003, //訂單序號
                    'TH018' => $item->admin_memo, //備註
                    'TH025' => $erpOrderItem->TD026, //折扣率
                    'TH035' => round($priceWithoutTax,0), //原幣未稅金額
                    'TH036' => round($itemTax,0), //原幣稅額
                    'TH037' => round($priceWithoutTax,0), //本幣未稅金額
                    'TH038' => round($itemTax,0), //本幣稅額
                    'TH047' => $order->order_number, //網購訂單編號
                ];
                $erpCOPTH = array_merge($array1,$array2);
                ErpCOPTHDB::create($erpCOPTH);
                $item->update(['erp_sell_no' => $TG002, 'erp_sell_sno' => str_pad($i,4,'0',STR_PAD_LEFT) ]);
                $i++;
            }
            foreach($erpOrder->items as $erpOrderItem){
                $array2 = $erpCOPTH = [];
                $itemPrice = $erpOrderItem->TD011;
                $price = $itemPrice;
                if($erpOrder->TC016 == 1){
                    $priceWithoutTax = $price / 1.05;
                    $itemTax = $priceWithoutTax * 0.05;
                }elseif($erpOrder->TC016 == 2){
                    $priceWithoutTax = $price;
                    $itemTax = $price * 0.05;
                }else{
                    $priceWithoutTax = $price;
                    $itemTax = 0;
                }
                if($erpOrderItem->TD004 == 999000 || $erpOrderItem->TD004 == 999001 || $erpOrderItem->TD004 == 901001 || $erpOrderItem->TD004 == 901002){ //活動折扣
                    $amount += $priceWithoutTax;
                    $tax += $itemTax;
                    $totalQty++;
                    $array2 = [
                        'TH003' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                        'TH004' => $erpOrderItem->TD004, //品號
                        'TH005' => $erpOrderItem->TD005, //品名
                        'TH006' => $erpOrderItem->TD006, //規格
                        'TH007' => $erpOrderItem->TD007, //庫別
                        'TH008' => $erpOrderItem->TD008, //數量
                        'TH009' => $erpOrderItem->TD010, //單位
                        'TH012' => round($itemPrice,2), //單價
                        'TH013' => round($price,0), //金額
                        'TH014' => $erpOrderItem->TD001, //訂單單別
                        'TH015' => $erpOrderItem->TD002, //訂單單號
                        'TH016' => $erpOrderItem->TD003, //訂單序號
                        'TH018' => $item->admin_memo, //備註
                        'TH025' => $erpOrderItem->TD026, //折扣率
                        'TH035' => round($priceWithoutTax,0), //原幣未稅金額
                        'TH036' => round($itemTax,0), //原幣稅額
                        'TH037' => round($priceWithoutTax,0), //本幣未稅金額
                        'TH038' => round($itemTax,0), //本幣稅額
                        'TH047' => $order->order_number, //網購訂單編號
                    ];
                    $i++;
                }
                if(!empty($array2)){
                    $erpCOPTH = array_merge($array1,$array2);
                    ErpCOPTHDB::create($erpCOPTH);
                }
            }
            $erpSellItems = ErpCOPTHDB::where([['TH001',$TG001],['TH002',$TG002]])->get();
            if(!empty($erpSellItems) && count($erpSellItems) > 0){
                //建立鼎新銷貨單單頭
                $erpSell = [
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
                        'TG001' => $TG001, //單別
                        'TG002' => $TG002, //單號
                        'TG003' => str_replace(['-','/'],['',''],$sellDate), //銷貨日期
                        'TG004' => $erpCustomer->MA001, //客戶代號
                        'TG005' => $erpOrder->TC005, //部門
                        'TG006' => $erpOrder->TC006, //業務員
                        'TG007' => $erpCustomer->MA003, //客戶全名
                        'TG008' => $erpOrder->TC010, //送貨地址一
                        'TG009' => $erpOrder->TC011, //送貨地址二
                        'TG010' => '001', //出貨廠別
                        'TG011' => 'NTD', //幣別
                        'TG012' => 1, //匯率
                        'TG013' => round($amount,0), //原幣銷貨金額
                        'TG014' => '', //發票號碼
                        'TG015' => $order->invoice_number, //統一編號
                        'TG016' => $erpCustomer->MA037, //發票聯數
                        'TG017' => $erpOrder->TC016, //課稅別
                        'TG018' => '', //發票地址一
                        'TG019' => '', //發票地址二
                        'TG020' => '', //備註
                        'TG021' => '', //發票日期
                        'TG022' => 0, //列印次數
                        'TG023' => 'N', //確認碼
                        'TG024' => 'N', //更新碼
                        'TG025' => round($tax,0), //原幣銷貨稅額
                        'TG026' => '', //收款業務員
                        'TG027' => '', //備註一
                        'TG028' => '', //備註二
                        'TG029' => '', //備註三
                        'TG030' => 'N', //發票作廢
                        'TG031' => 1, //通關方式
                        'TG032' => 0, //件數
                        'TG033' => $totalQty, //總數量
                        'TG034' => 'N', //現銷
                        'TG035' => '', //員工代號
                        'TG036' => 'N', //產生分錄碼(收入)
                        'TG037' => 'N', //產生分錄碼(成本)
                        'TG038' => substr(str_replace(['-','/'],['',''],$sellDate),0,6), //申報年月
                        'TG039' => '', //L/C_NO
                        'TG040' => '', //INVOICE_NO
                        'TG041' => 0, //發票列印次數
                        'TG042' => str_replace(['-','/'],['',''],$sellDate), //單據日期
                        'TG043' => '', //確認者
                        'TG044' => 0.05, //營業稅率
                        'TG045' => round($amount,0), //本幣銷貨金額
                        'TG046' => round($tax,0), //本幣銷貨稅額
                        'TG047' => 'N', //簽核狀態碼
                        'TG048' => '', //報單號碼
                        'TG049' => $erpOrder->TC046, //送貨客戶全名
                        'TG050' => $erpOrder->TC018, //連絡人
                        'TG051' => $erpOrder->TC047, //TEL_NO
                        'TG052' => $erpOrder->TC048, //FAX_NO
                        'TG053' => '', //出貨通知單別
                        'TG054' => '', //出貨通知單號
                        // 'TG055' => '', //預留欄位
                        'TG056' => 1, //交易條件
                        'TG057' => 0, //總包裝數量
                        'TG058' => 0, //傳送次數
                        'TG059' => '', //訂單單別
                        'TG060' => '', //訂單單號
                        'TG061' => '', //預收待抵單別
                        'TG062' => '', //預收待抵單號
                        'TG063' => 0, //沖抵金額
                        'TG064' => 0, //沖抵稅額
                        'TG065' => $erpOrder->TC059, //付款條件代號
                        'TG066' => $erpOrder->TC062, //收貨人
                        'TG067' => '', //指定日期
                        'TG068' => '', //配送時段
                        'TG069' => '', //貨運別
                        'TG070' => 0, //代收貨款
                        'TG071' => 0, //運費
                        'TG072' => 'N', //產生貨運文字檔
                        'TG073' => 0, //客戶描述
                        'TG074' => '', //作廢日期
                        'TG075' => '', //作廢時間
                        'TG076' => '', //專案作廢核准文號
                        'TG077' => '', //作廢原因
                        'TG078' => '', //發票開立時間
                        'TG079' => '', //載具顯碼ID
                        'TG080' => '', //載具類別號碼
                        'TG081' => '', //載具隱碼ID
                        'TG082' => '', //發票捐贈對象
                        'TG083' => '', //發票防偽隨機碼
                        'TG106' => '', //來源
                        'TG129' => $order->receiver_nation_number.$order->receiver_phone_number ? $order->receiver_nation_number.$order->receiver_phone_number : $order->receiver_tel, //行動電話
                        'TG130' => '', //信用卡末四碼
                        'TG131' => '', //連絡人EMAIL
                        'TG132' => '', //買受人適用零稅率註記
                        'TG200' => '', //載具行動電話
                        'TG134' => '', //貨運單號
                        'TG091' => 0, //原幣應稅銷售額
                        'TG092' => 0, //原幣免稅銷售額
                        'TG093' => 0, //本幣應稅銷售額
                        'TG094' => 0, //本幣免稅銷售額
                ];
                $erpSell = ErpCOPTGDB::create($erpSell);
                SellDB::where('order_number',$order->order_number)->update(['erp_sell_no' => $TG002]);
                return true;
            }
        }else{
            return false;
        }
    }
}
