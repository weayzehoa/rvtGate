<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\ACErpOrder as ACErpOrderDB;
use App\Models\ACErpOrderItem as ACErpOrderItemDB;
use App\Models\ACErpCustomer as ACErpCustomerDB;
use App\Models\ACErpVendor as ACERPVendorDB;
use App\Models\ACErpCOPTH as ACErpCOPTHDB;
use App\Models\ACErpCOPTG as ACErpCOPTGDB;
use App\Models\ACErpPURTC as ACErpPURTCDB;
use App\Models\ACErpPURTD as ACErpPURTDDB;
use App\Models\ACErpPURTG as ACErpPURTGDB;
use App\Models\ACErpPURTH as ACErpPURTHDB;
use App\Models\iCarryVendor as VendorDB;

use App\Traits\PurchaseOrderFunctionTrait;
use App\Jobs\ACpayDigiwin\iCarryPurchaseOrderToACpayOrderJob;

class iCarryPurchaseOrderToACpayErpJob implements ShouldQueue
{
    use PurchaseOrderFunctionTrait,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $creator = 'iCarryGate';
        $createDate = date('Ymd');
        $createTime = date('H:i:s');
        $erpCustomer = ACErpCustomerDB::find('A00002');
        $purchaseOrderData = $purchaseItemData = $erpSellData = $sellItemData = $orderData = $itemData = [];
        $param = $this->param;
        $param['admin_id'] = 1;
        $purchaseOrders = $this->getPurchaseOrderData($param);

        if(count($purchaseOrders) > 0){
            foreach($purchaseOrders as $purchaseOrder){
                $vendor = VendorDB::find($purchaseOrder->vendor_id);
                $erpVendor = ACERPVendorDB::where('MA001',$vendor->ac_digiwin_vendor_no)->first();
                //單據號碼(付款當日)
                $orderDate = str_replace('-','',$purchaseOrder->purchase_date);
                $orderDate6 = substr($orderDate,2); //單據日期六碼
                if(empty($purchaseOrder->ac_erp_order_no)){
                    // //找出單據號碼當日最後一筆訂單號碼的流水號
                    $TC002 = ACErpOrderDB::where('TC002','like',"$orderDate6%")->select('TC002')->orderBy('TC002','desc')->first();
                    !empty($TC002) ? $erpOrderNo = $TC002->TC002 + 1 : $erpOrderNo = $orderDate6.str_pad(1,5,0,STR_PAD_LEFT);
                    $originTotalPrice = $totalItemQuantity = $totalPrice = $tax = $returnPrice = 0;
                    $originTotalPrice = $purchaseOrder->amount + $purchaseOrder->tax;

                    $i=0;
                    foreach($purchaseOrder->items as $item){
                        $totalItemQuantity += $item->quantity;
                        $orderItemData[] = [
                            'COMPANY' => 'AC',
                            'CREATOR' => $creator,
                            'USR_GROUP' => 'DSC',
                            'CREATE_DATE' => $createDate,
                            'FLAG' => 1,
                            'CREATE_TIME' => $createTime,
                            'CREATE_AP' => 'iCarry',
                            'CREATE_PRID' => 'COPI06',
                            'TD001' => 'A222',
                            'TD002' => $erpOrderNo,
                            'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                            'TD004' => $item->digiwin_no, //品號 product_model的digiwin_no
                            'TD005' => mb_substr($item->product_name,0,100,'utf8'), //品名 用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB002
                            'TD006' => mb_substr($item->serving_size,0,110,'utf8'), //用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB003
                            'TD007' => 'W14',
                            'TD008' => $item->quantity, //訂單數量 依拆分後數量
                            'TD009' => 0,
                            'TD010' => $item->unit_name, //單位 product.unit_name
                            'TD011' => $item->purchase_price, //單價 order_item.price
                            'TD012' => round($item->purchase_price * $item->quantity,0), //金額 order_item.price * order_item.quantity
                            'TD013' => $orderDate, //預交日 order.book_shipping_date
                            'TD016' => 'N', //結案碼 DEF 'N'
                            'TD020' => '', //顧客備註
                            'TD021' => 'Y', //確認碼 DEF 'Y'
                            'TD022' => 0, //庫存數量 DEF '0'
                            'TD024' => 0, //贈品量 DEF '0'
                            'TD025' => 0, //贈品已交量 DEF '0'
                            'TD026' => 1, //折扣率 DEF '0'
                            'TD030' => 1, //毛重(Kg) order_item.gross_weight
                            'TD031' => 0,
                            'TD032' => 1,
                            'TD033' => 0,
                            'TD034' => 0,
                            'TD035' => 0,
                            'TD036' => 0,
                        ];
                        $i++;
                    }
                    //稅金計算
                    if($erpCustomer->MA038 == 2){ //應稅外加, 除以1.05
                        $totalPrice = round($originTotalPrice, 0);
                        $tax = round($totalPrice * 0.05, 0);
                    }elseif($erpCustomer->MA038 == 1){
                        $totalPrice = round($originTotalPrice / 1.05, 0);
                        $tax = round($originTotalPrice - $totalPrice, 0);
                    }else{
                        $totalPrice = round($originTotalPrice, 0);
                        $tax = 0;
                    }
                    //新增鼎新訂單資料至鼎新訂單資料表
                    $orderData = [
                        'COMPANY' => 'AC',
                        'CREATOR' => $creator,
                        'USR_GROUP' => 'DSC',
                        'CREATE_DATE' => $createDate,
                        'FLAG' => 1,
                        'CREATE_TIME' => $createTime,
                        'CREATE_AP' => 'iCarry',
                        'CREATE_PRID' => 'COPI06',
                        'TC001' => 'A222',
                        'TC002' => $erpOrderNo, //單號 年月日+五碼流水號 EX:22010100001
                        'TC003' => $orderDate, //訂單日期 CONVERT(VARCHAR(8),pay_time,112)
                        'TC004' => 'A00002', //iCarry在ACpay的客戶代號
                        'TC005' => '',
                        'TC006' => '',
                        'TC007' => '001',
                        'TC008' => 'NTD',
                        'TC009' => 1,
                        'TC010' => '',
                        'TC012' => $purchaseOrder->purchase_no, //客戶單號=iCarry採購單號
                        'TC014' => $erpCustomer->MA031, //付款條件 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA031
                        'TC015' => '',
                        'TC016' => $erpCustomer->MA038, //課稅別 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA038
                        'TC018' => $erpCustomer->MA002,
                        'TC019' => $erpCustomer->MA048, //運輸方式 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA048
                        'TC026' => 0,
                        'TC027' => 'Y',
                        'TC028' => 0,
                        'TC029' => $totalPrice, //訂單金額 各單品加總金額
                        'TC030' => $tax, //訂單稅額 各單品加總金額 * 0.05
                        'TC031' => $totalItemQuantity, //總數量 單品總數量
                        'TC035' => '台灣',
                        'TC039' => $orderDate, //單據日期 CONVERT(VARCHAR(8),pay_time,112)
                        'TC040' => $creator,
                        'TC041' => '0.05',
                        'TC042' => 'N',
                        'TC043' => $erpCustomer->MA003,
                        'TC044' => '',
                        'TC046' => $erpCustomer->MA003,
                        'TC047' => '',
                        'TC050' => 1,
                        'TC055' => 1,
                        'TC057' => 0,
                        'TC058' => 1,
                        'TC059' => $erpCustomer->MA083,
                        'TC061' => $purchaseOrder->purchase_no,
                        'TC062' => $erpCustomer->MA002,
                        'TC063' => $orderDate,
                        'TC064' => 1,
                        'TC068' => '',
                        'TC074' => 'N',
                        'TC075' => 0,
                        'TC094' => '',
                        'TC200' => 'N',
                        'TC201' => 'N',
                    ];
                    //下面方法避開 Tried to bind parameter number 2101. SQL Server supports a maximum of 2100 parameters. 錯誤
                    if(!empty($orderItemData) && count($orderItemData) >= 20){
                        $items = array_chunk($orderItemData,20);
                        for($i=0;$i<count($items);$i++){
                            ACErpOrderItemDB::insert($items[$i]);
                        }
                    }elseif(count($orderItemData) > 0 && count($orderItemData) < 20){
                        $erpOrderItems = ACErpOrderItemDB::insert($orderItemData);
                    }

                    $newErpOrder = ACErpOrderDB::create($orderData);
                    $purchaseOrder->update(['ac_erp_order_no' => $erpOrderNo]);

                }else{
                    $erpOrderNo = $purchaseOrder->ac_erp_order_no;
                }

                //銷貨單
                if(empty($purchaseOrder->ac_erp_sell_no)){
                    $erpOrder = ACErpOrderDB::with('items')->where([['TC001','A222'],['TC002',$erpOrderNo]])->first();
                    //檢查鼎新銷貨單有沒有這個號碼
                    $tmp = ACErpCOPTGDB::where([['TG001','A233'],['TG002','like',"%$orderDate6%"]])->select('TG002')->orderBy('TG002','desc')->first();
                    !empty($tmp) ? $erpSellNo = $tmp->TG002 + 1 : $erpSellNo = $orderDate6.str_pad(1,5,0,STR_PAD_LEFT);
                    $price = $itemTax = $priceWithoutTax = $totalQty = $tax = $amount = $i = 0;
                    foreach($erpOrder->items as $erpOrderItem){
                        $price = $erpOrderItem->TD011 * $erpOrderItem->TD008;
                        if($erpOrder->TC016 == 1){
                            $itemTax = round((round($price,0) / 1.05) * 0.05,0);
                            $priceWithoutTax = $price - $itemTax;
                        }elseif($erpOrder->TC016 == 2){
                            $priceWithoutTax = round($price,0);
                            $itemTax = round(round($price,0) * 0.05);
                        }else{
                            $priceWithoutTax = round($price,0);
                            $itemTax = 0;
                        }
                        $amount += $priceWithoutTax;
                        $tax += $itemTax;
                        $totalQty += $erpOrderItem->TD008;
                        $sellItemData[] = [
                            'COMPANY' => 'AC',
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
                            'TH001' => 'A233', //單別
                            'TH002' => $erpSellNo, //單號
                            'TH010' => 0, //庫存數量
                            'TH011' => '', //小單位
                            'TH017' => '', //批號
                            'TH019' => '', //客戶品號
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
                            'TH044' => '', //包裝單位
                            'TH045' => '', //發票號碼
                            'TH046' => '', //生產加工包裝資訊
                            'TH074' => '', //CRM來源
                            'TH075' => '', //CRM單別
                            'TH076' => '', //CRM單號
                            'TH077' => '', //CRM序號
                            'TH099' => 1, //品號稅別
                            'TH003' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                            'TH004' => $erpOrderItem->TD004, //品號
                            'TH005' => $erpOrderItem->TD005, //品名
                            'TH006' => $erpOrderItem->TD006, //規格
                            'TH007' => $erpOrderItem->TD007, //庫別
                            'TH008' => $erpOrderItem->TD008, //數量
                            'TH009' => $erpOrderItem->TD010, //單位
                            'TH012' => $erpOrderItem->TD011, //單價
                            'TH013' => round($erpOrderItem->TD011 * $erpOrderItem->TD008,0), //金額
                            'TH014' => $erpOrderItem->TD001, //訂單單別
                            'TH015' => $erpOrderItem->TD002, //訂單單號
                            'TH016' => $erpOrderItem->TD003, //訂單序號
                            'TH018' => '', //備註
                            'TH020' => 'N', //確認碼
                            'TH025' => $erpOrderItem->TD026, //折扣率
                            'TH035' => round($priceWithoutTax,0), //原幣未稅金額
                            'TH036' => round($itemTax,0), //原幣稅額
                            'TH037' => round($priceWithoutTax,0), //本幣未稅金額
                            'TH038' => round($itemTax,0), //本幣稅額
                            'TH047' => $purchaseOrder->purchase_no, //網購訂單編號
                        ];
                        $i++;
                    }
                    //建立鼎新銷貨單單頭
                    $erpSellData = [
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
                        'TG001' => 'A233', //單別
                        'TG002' => $erpSellNo, //單號
                        'TG003' => $orderDate, //銷貨日期
                        'TG004' => $erpCustomer->MA001, //客戶代號
                        'TG005' => $erpCustomer->MA015, //部門
                        'TG006' => $erpCustomer->MA016, //業務員
                        'TG007' => $erpCustomer->MA003, //客戶全名
                        'TG008' => mb_substr($erpOrder->TC010,0,250), //送貨地址一
                        'TG009' => mb_substr($erpOrder->TC011,0,250), //送貨地址二
                        'TG010' => '001', //出貨廠別
                        'TG011' => 'NTD', //幣別
                        'TG012' => 1, //匯率
                        'TG013' => round($amount,0), //原幣銷貨金額
                        'TG014' => '', //發票號碼
                        'TG015' => $erpCustomer->MA010, //統一編號
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
                        'TG038' => substr($orderDate,0,6), //申報年月
                        'TG039' => '', //L/C_NO
                        'TG040' => '', //INVOICE_NO
                        'TG041' => 0, //發票列印次數
                        'TG042' => $orderDate, //單據日期
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
                        'TG059' => '', //訂單單別, 留空白
                        'TG060' => '', //訂單單號, 留空白
                        'TG061' => '', //預收待抵單別, 留空白
                        'TG062' => '', //預收待抵單號, 留空白
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
                        'TG129' => '', //行動電話
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
                    if(!empty($sellItemData) && count($sellItemData) >= 20){
                        $sellItems = array_chunk($sellItemData,20);
                        for($i=0;$i<count($sellItems);$i++){
                            ACErpCOPTHDB::insert($sellItems[$i]);
                        }
                    }elseif(count($sellItemData) > 0 && count($sellItemData) < 20){
                        ACErpCOPTHDB::insert($sellItemData);
                    }
                    $erpSell = ACErpCOPTGDB::create($erpSellData);
                    $purchaseOrder->update(['ac_erp_sell_no' => $erpSellNo]);
                }

                //採購單
                if(empty($purchaseOrder->ac_erp_purchase_no)){
                    $tmp = ACErpPURTCDB::where([['TC002','like',"$orderDate6%"],['TC001','A335']])->select('TC002')->orderBy('TC002','desc')->first();
                    !empty($tmp) ? $erpPurchaseOrderNo = $tmp->TC002 + 1 : $erpPurchaseOrderNo = $orderDate6.str_pad(1,5,0,STR_PAD_LEFT);
                    $i = $amount = 0;
                    foreach($purchaseOrder->items as $item){
                        $erpVendor->MA044 == 2 ? $TH018 = $item->purchase_price / 1.05 : $TH018 = $item->purchase_price;
                        $amount += round($item->quantity * $TH018 ,0);
                        $purchaseItemData[] = [
                            'COMPANY' => 'AC',
                            'CREATOR' => $creator,
                            'USR_GROUP' => 'DSC',
                            'CREATE_DATE' => $createDate,
                            'FLAG' => 1,
                            'CREATE_TIME' => $createTime,
                            'CREATE_AP' => 'iCarry',
                            'CREATE_PRID' => 'PURI07',
                            'TD001' => 'A335', //單別
                            'TD002' => $erpPurchaseOrderNo, //單號
                            'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號
                            'TD004' => $item->digiwin_no, //品號
                            'TD005' => mb_substr($item->product_name,0,110,'utf8'),
                            'TD006' => mb_substr($item->serving_size,0,110,'utf8'), //規格
                            'TD007' => 'W14', //庫別
                            'TD008' => $item->quantity, //採購數量
                            'TD009' => $item->unit_name, //單位
                            'TD010' => round($TH018,4), //單價
                            'TD011' => round($item->quantity * $TH018,0), //金額
                            'TD012' => $orderDate, //預交日
                            'TD013' => 'A222', //參考單別
                            'TD014' => '', //備註
                            'TD015' => 0, //已交數量
                            'TD016' => 'N', //結案碼
                            'TD018' => 'Y', //確認碼
                            'TD019' => 0, //庫存數量
                            'TD025' => 'N', //急料
                            'TD033' => $item->quantity, //計價數量
                            'TD034' => $item->unit_name, //計價單位
                        ];
                        $i++;
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
                    $purchaseOrderData = [
                        'COMPANY' => 'iCarry',
                        'CREATOR' => $creator,
                        'USR_GROUP' => 'DSC',
                        'CREATE_DATE' => $createDate,
                        'FLAG' => 1,
                        'CREATE_TIME' => $createTime,
                        'CREATE_AP' => 'iCarry',
                        'CREATE_PRID' => 'PURI07',
                        'TC001' => 'A335', //單別
                        'TC002' => $erpPurchaseOrderNo, //單號
                        'TC003' => $createDate, //日期
                        'TC004' => rtrim($erpVendor->MA001,' '), //廠商代號
                        'TC005' => 'NTD', //幣別
                        'TC006' => 1, //匯率
                        'TC008' => $erpVendor->MA025, //付款條件名稱
                        'TC009' => '', //備註
                        'TC010' => '001', //廠別
                        'TC012' => 1, //列印格式
                        'TC013' => 0, //列印次數
                        'TC014' => 'Y', //確認碼
                        'TC018' => $erpVendor->MA044, //課稅別
                        'TC019' => $amount, //採購金額 (未稅)
                        'TC020' => $tax, //稅額
                        'TC021' => '台北市中山區南京東路三段103號11樓B室', //送貨地址
                        'TC023' => $purchaseOrder->quantity, //數量合計
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

                    if(count($purchaseItemData) >= 50){
                        $itemDatas = array_chunk($purchaseItemData,50);
                        for($i=0;$i<count($itemDatas);$i++){
                            ACErpPURTDDB::insert($itemDatas[$i]);
                        }
                    }else{
                        ACErpPURTDDB::insert($purchaseItemData);
                    }
                    ACErpPURTCDB::create($purchaseOrderData);

                    //更新採購單號到中繼採購單中
                    $purchaseOrder->update(['ac_erp_purchase_no' => $erpPurchaseOrderNo]);
                }else{
                    $erpPurchaseOrderNo = $purchaseOrder->ac_erp_purchase_no;
                }

                //入庫單
                if(empty($purchaseOrder->ac_erp_stockin_no)){
                    $tmp = ACErpPURTGDB::where('TG002','like',"%$orderDate6%")->select('TG002')->orderBy('TG002','desc')->first();
                    !empty($tmp) ? $erpStockinNo = $tmp->TG002 + 1 : $erpStockinNo = $orderDate6.str_pad(1,5,0,STR_PAD_LEFT);
                    $erpPurchaseOrder = ACErpPURTCDB::with('items')->where([['TC001','A335'],['TC002',$erpPurchaseOrderNo]])->first();
                    $erpPurchaseItems = $erpPurchaseOrder->items;
                    $TG026 = $TG017 = $TG019 = $TG028 = $TG031 = $TG032 = $i = 0;
                    foreach($erpPurchaseItems as $erpPurchaseItem){
                        if($erpPurchaseItem->TD001 == 'A335'){
                            $diffQty = $erpPurchaseItem->TD008;
                            $purchasePrice = $erpPurchaseItem->TD010;
                            if($erpVendor->MA044 == 1){
                                $TH019 = round($purchasePrice * $diffQty,0);
                                $TH045 = round(round($diffQty * $purchasePrice,0) / 1.05,0);
                                $TH046 = round($diffQty * $purchasePrice,0) - $TH045;
                            }elseif($erpVendor->MA044 == 2){
                                $purchasePrice = $item->purchase_price / 1.05;
                                $TH019 = $TH045 = round($diffQty * $purchasePrice,0);
                                $TH046 = round($diffQty * $purchasePrice,0) - $TH045;
                            }else{
                                $purchasePrice = $item->purchase_price;
                                $TH019 = $TH045 = round($diffQty * $purchasePrice,0);
                                $TH046 = 0;
                            }
                            $stockinItemData[] = [
                                'COMPANY' => 'AC',
                                'CREATOR' => $creator,
                                'USR_GROUP' => 'DSC',
                                'CREATE_DATE' => $createDate,
                                'FLAG' => 1,
                                'CREATE_TIME' => $createTime,
                                'CREATE_AP' => 'iCarry',
                                'CREATE_PRID' => 'PURI07',
                                'TH001' => 'A341', //單別
                                'TH002' => $erpStockinNo, //單號
                                'TH031' => 'N', //結帳碼
                                'TH014' => $orderDate, //驗收日期
                                'TH017' => 0, //驗退數量
                                'TH020' => 0, //原幣扣款金額
                                'TH024' => 0, //進貨費用
                                'TH026' => 'N', //暫不付款
                                'TH027' => 'N', //逾期碼
                                'TH028' => 0, //檢驗狀態
                                'TH029' => 'N', //驗退碼
                                'TH030' => 'N', //確認碼
                                'TH032' => 'N', //更新碼
                                'TH033' => ' ', //備註
                                'TH034' => 0, //庫存數量
                                'TH038' => '', //確認者
                                'TH039' => ' ', //應付憑單別
                                'TH040' => ' ', //應付憑單號
                                'TH041' => ' ', //應付憑單序號
                                'TH042' => ' ', //專案代號
                                'TH043' => 'N', //產生分錄碼
                                'TH044' => 'N', //沖自籌額碼
                                'TH050' => 'N', //簽核狀態碼
                                'TH051' => 0, //原幣沖自籌額
                                'TH052' => 0, //本幣沖自籌額
                                'TH054' => 0, //抽樣數量
                                'TH055' => 0, //不良數量
                                'TH058' => 0, //缺點數
                                'TH059' => 0, //進貨包裝數量
                                'TH060' => 0, //驗收包裝數量
                                'TH061' => 0, //驗退包裝數量
                                'TH064' => 0, //產品序號數量
                                'EF_ERPMA001' => ' ',
                                'EF_ERPMA002' => ' ',
                                'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                                'TH004' => $erpPurchaseItem->TD004, //品號
                                'TH005' => $erpPurchaseItem->TD005, //品名
                                'TH006' => $erpPurchaseItem->TD006, //規格
                                'TH007' => $erpPurchaseItem->TD008, //進貨數量
                                'TH008' => $erpPurchaseItem->TD009, //單位
                                'TH009' => $erpPurchaseItem->TD007, //庫別
                                'TH011' => $erpPurchaseItem->TD001, //採購單別
                                'TH012' => $erpPurchaseItem->TD002, //採購單號
                                'TH013' => $erpPurchaseItem->TD003, //採購序號
                                'TH015' => $erpPurchaseItem->TD008, //驗收數量
                                'TH016' => $erpPurchaseItem->TD008, //計價數量
                                'TH018' => round($purchasePrice,4), //原幣單位進價
                                'TH019' => round($TH019,0), //原幣進貨金額
                                'TH045' => round($TH045,0), //原幣未稅金額
                                'TH046' => round($TH046,0), //原幣稅額
                                'TH047' => round($TH045,0), //本幣未稅金額
                                'TH048' => round($TH046,0), //本幣稅額
                                'TH049' => $erpPurchaseItem->TD009, //計價單位
                            ];
                            $TG026 += $erpPurchaseItem->TD008; //數量
                            $TG017 += round($TH019,0); //進貨金額
                            $TG019 += round($TH046,0); //原幣稅額
                            $TG028 += round($TH045,0); //原幣貨款金額
                            $TG031 += round($TH045,0); //本幣貨款金額
                            $TG032 += round($TH046,0); //本幣稅額
                            $i++;
                        }
                    }
                    if(count($stockinItemData) > 0){
                        if(count($stockinItemData) >= 30){
                            $itemDatas = array_chunk($stockinItemData,30);
                            for($i=0;$i<count($itemDatas);$i++){
                                ACErpPURTHDB::insert($itemDatas[$i]);
                            }
                        }else{
                            ACErpPURTHDB::insert($stockinItemData);
                        }
                        $erpStockinData = [
                            'COMPANY' => 'iCarry',
                            'CREATOR' => $creator,
                            'USR_GROUP' => 'DSC',
                            'CREATE_DATE' => $createDate,
                            'FLAG' => 1,
                            'CREATE_TIME' => $createTime,
                            'CREATE_AP' => 'iCarry',
                            'CREATE_PRID' => 'PURI07',
                            'TG001' => 'A341', //單別
                            'TG002' => $erpStockinNo, //單號
                            'TG003' => $createDate, //進貨日期
                            'TG004' => '001', //廠別
                            'TG005' => $erpVendor->MA001, //供應廠商
                            'TG007' => 'NTD', //幣別
                            'TG008' => 1, //匯率
                            'TG009' => $erpVendor->MA030, //發票聯數
                            'TG010' => $erpVendor->MA044, //課稅別
                            'TG012' => 0, //列印次數
                            'TG013' => 'N', //確認碼
                            'TG014' => $orderDate, //單據日期
                            'TG015' => 'N', //更新碼
                            'TG016' => ' ', //備註
                            'TG017' => $TG017, //進貨金額
                            'TG018' => 0, //扣款金額
                            'TG019' => $TG019, //原幣稅額
                            'TG020' => 0, //進貨費用
                            'TG021' => $erpVendor->MA003, //廠商全名
                            'TG022' => $erpVendor->MA005, //統一編號
                            'TG023' => 1, //扣抵區分
                            'TG024' => 'N', //菸酒註記
                            'TG025' => 0, //件數
                            'TG026' => $TG026, //數量合計
                            'TG027' => ' ', //發票日期
                            'TG028' => $TG028, //原幣貨款金額
                            'TG029' => date('Ym'), //申報年月
                            'TG030' => 0.05, //營業稅率
                            'TG031' => $TG031, //本幣貨款金額
                            'TG032' => $TG032, //本幣稅額
                            'TG033' => 'N', //簽核狀態碼
                            'TG038' => 0, //沖抵金額
                            'TG039' => 0, //沖抵稅額
                            'TG040' => 0, //預留欄位
                            'TG041' => 0, //本幣沖自籌額
                            'TG045' => 0, //預留欄位
                            'TG046' => 0, //原幣沖自籌額
                            'TG047' => 0, //包裝數量合計
                            'TG048' => 0, //傳送次數
                            'TG049' => $erpVendor->MA055, //付款條件代號
                            'EF_ERPMA001' => ' ',
                            'EF_ERPMA002' => ' ',
                        ];
                        ACErpPURTGDB::create($erpStockinData);
                        $purchaseOrder->update(['ac_erp_stockin_no' => $erpStockinNo]);
                    }
                }
            }
        }
        return null;
    }
}
