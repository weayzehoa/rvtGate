<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryProductQuantityRecord as ProductQuantityRecordDB; //gtin13修改記錄在這
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;

use App\Models\ErpOrder as ErpOrderDB;
use App\Models\ErpOrderItem as ErpOrderItemDB;
use App\Models\ErpCOPTG as ErpCOPTGDB;
use App\Models\ErpCOPTH as ErpCOPTHDB;
use App\Models\ErpPURTG as ErpPURTGDB;
use App\Models\ErpACRTA as ErpACRTADB;

use App\Models\Admin as AdminDB;
use App\Models\Sell as SellDB;
use App\Models\SerialNoRecord as SerialNoRecordDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use App\Models\SellImport as SellImportDB;
use App\Models\StockinImport as StockinImportDB;
use App\Models\SellAbnormal as SellAbnormalDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use DB;
use Log;
use Exception;
use Carbon\Carbon;

use App\Imports\WarehouseShipImport;
use App\Imports\VendorDirectShipImport;

use App\Jobs\PurchaseStockinImportJob;
use App\Jobs\CheckOrderSellJob;
use App\Jobs\CheckErpSellTaxJob;

use App\Jobs\AdminSendEmail;

use App\Traits\OrderFunctionTrait;

class SellImportJobOneOrderJob implements ShouldQueue
{
    use OrderFunctionTrait,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $param;
    protected $orderNumber;
    protected $TG002;
    protected $sellNo;

    /**
     * 2022.08.09 討論結果修改成 一個訂單有多張銷貨單
     *
     * @return void
     */
    public function __construct($param,$orderNumber,$TG002,$sellNo)
    {
        empty($param['import_no']) ? $param['import_no'] = null : '';
        $this->param = $param;
        $this->orderNumber = $orderNumber;
        $this->TG002 = $TG002;
        $this->sellNo = $sellNo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $TG002 = $this->TG002;
        $sellNo = $this->sellNo;
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sells = SellImportDB::where([['status',0],['order_number',$this->orderNumber]]);
        $sells = $sells->select([
            '*',
            DB::raw("SUM(quantity) as quantity"),
            DB::raw("GROUP_CONCAT(id) as ids"),
            DB::raw("GROUP_CONCAT(order_item_id) as orderItemIds"),
            DB::raw("GROUP_CONCAT(sell_date) as sellDates"),
        ]);
        if($this->param['type'] == 'directShip'){
            $sells = $sells->groupBy('order_number','shipping_number','digiwin_no')->orderBy('digiwin_no','asc')->get();
        }else{
            $sells = $sells->groupBy('order_number','shipping_number','gtin13')->orderBy('gtin13','asc')->get();
        }
        // dd($sells);
        if(!empty($this->orderNumber) && count($sells) > 0){
            $stockinImport = $purchaseNos = [];
            $importNo = time().rand(1000,9999);
            $importNo = (INT)$importNo;
            $this->param['type'] == 'directShip' ? $directShipment = 1 : $directShipment = 0;
            $chk = $chkStatus = $amount = $tax = $itemTax = 0;
            $createDate = date('Ymd');
            $createTime = date('H:i:s');
            !empty($this->param['admin_id']) ? $adminId = $this->param['admin_id'] : $adminId = null;
            !empty($adminId) ? $admin = AdminDB::find($adminId) : $admin = null;
            !empty($admin) ? $creator = substr(strtoupper($admin->account),0,10) : $creator = 'iCarryGate';
            foreach($sells as $sell){
                $status = explode(',',$sell->chkStatus);
                for($i=0;$i<count($status);$i++){
                    if($status[$i] == -1){
                        $chkStatus++;
                    }
                }
                $temps = explode(',',$sell->sellDates);
                $temps = array_unique($temps);
                arsort($temps);
                foreach($temps as $key => $value) {
                    $sellDate = $value;
                    break;
                }
                $purchaseNos[] = $purchaseNo = $sell->purchase_no;
                $shippingNumber = $sell->shipping_number;
            }
            $purchaseNos = array_unique($purchaseNos);
            $key = env('APP_AESENCRYPT_KEY');
            $order = OrderDB::with('nidinOrder','acOrder','user','shippings','syncedOrder','items','items.sells','items.package','items.package.sells','items.syncedOrderItem','items.package.syncedOrderItemPackage')
            ->where('order_number',$this->orderNumber)
            ->select([
                'id',
                'is_print',
                'vendor_arrival_date',
                'shipping_memo',
                'shipping_number',
                'order_number',
                'user_id',
                'origin_country',
                'ship_to',
                'book_shipping_date',
                'receiver_name',
                'receiver_email',
                'receiver_address',
                'receiver_zip_code',
                'receiver_keyword',
                'receiver_key_time',
                'shipping_method',
                'invoice_type',
                'invoice_sub_type',
                'invoice_number',
                'is_invoice_no',
                'love_code',
                'invoice_title',
                'carrier_type',
                'spend_point',
                'amount',
                'shipping_fee',
                'parcel_tax',
                'pay_method',
                'exchange_rate',
                'discount',
                'user_memo',
                'partner_order_number',
                'pay_time',
                'buyer_name',
                'buyer_email',
                'print_flag',
                'create_type',
                'status',
                'digiwin_payment_id',
                'is_call',
                'create_time',
                'admin_memo',
                'greeting_card',
                'shipping_kg_price',
                'shipping_base_price',
                DB::raw("IF(receiver_phone_number IS NULL,'',AES_DECRYPT(receiver_phone_number,'$key')) as receiver_phone_number"),
                DB::raw("IF(receiver_tel IS NULL,'',AES_DECRYPT(receiver_tel,'$key')) as receiver_tel"),
                DB::raw("MD5(CONCAT('ica',partner_order_number,'ry')) as am_md5")
            ])->first();
            if(!empty($order)){
                if($order->status != 2){
                    $chk++;
                    SellAbnormalDB::create([
                        'import_no' => $this->param['import_no'],
                        'order_id' => $order->id,
                        'order_number' => $this->orderNumber,
                        'direct_shipment' => $directShipment,
                        'sell_date' => $sellDate,
                        'shipping_memo' => $shippingNumber,
                        'memo' => "$this->orderNumber 訂單狀態非集貨中。",
                    ]);
                    foreach($sells as $sell){
                        $ids = explode(',',$sell->ids);
                        sellImportDB::whereIn('id',$ids)->update(['memo' => "$this->orderNumber 訂單狀態非集貨中。", 'status' => -1]);
                    }
                }else{
                    $erpCustomer = $order->erpCustomer;
                    if(empty($erpCustomer)){
                        $chk++;
                        SellAbnormalDB::create([
                            'import_no' => $this->param['import_no'],
                            'order_id' => $order->id,
                            'order_number' => $this->orderNumber,
                            'direct_shipment' => $directShipment,
                            'sell_date' => $sellDate,
                            'shipping_memo' => $shippingNumber,
                            'memo' => "$order->digiwin_payment_id 客戶資料不存在於鼎新中。",
                        ]);
                        foreach($sells as $sell){
                            $ids = explode(',',$sell->ids);
                            sellImportDB::whereIn('id',$ids)->update(['memo' => "$order->digiwin_payment_id 客戶資料不存在於鼎新中。", 'status' => -1]);
                        }
                    }else{
                        if(empty($order->syncedOrder)){
                            $chk++;
                            SellAbnormalDB::create([
                                'import_no' => $this->param['import_no'],
                                'order_id' => $order->id,
                                'order_number' => $this->orderNumber,
                                'direct_shipment' => $directShipment,
                                'sell_date' => $sellDate,
                                'shipping_memo' => $shippingNumber,
                                'memo' => "$this->orderNumber 訂單未同步至鼎新。",
                            ]);
                            foreach($sells as $sell){
                                $ids = explode(',',$sell->ids);
                                sellImportDB::whereIn('id',$ids)->update(['memo' => "$this->orderNumber 訂單未同步至鼎新。", 'status' => -1]);
                            }
                        }else{
                            // $erpOrder = ErpOrderDB::with('items')->where('TC002',$order->syncedOrder->erp_order_no)->first();
                            $erpOrder = ErpOrderDB::with('items')->where('TC002',$order->syncedOrder->erp_order_no)
                            ->where(function($query)use($order){
                                $query = $query->where('TC061',$order->order_number)->orWhere('TC061',$order->partner_order_number);
                            })->first();
                            if(empty($erpOrder)){
                                $chk++;
                                SellAbnormalDB::create([
                                    'import_no' => $this->param['import_no'],
                                    'order_id' => $order->id,
                                    'order_number' => $this->orderNumber,
                                    'direct_shipment' => $directShipment,
                                    'sell_date' => $sellDate,
                                    'shipping_memo' => $shippingNumber,
                                    'memo' => "$this->orderNumber 訂單未同步至鼎新。",
                                ]);
                                foreach($sells as $sell){
                                    $ids = explode(',',$sell->ids);
                                    sellImportDB::whereIn('id',$ids)->update(['memo' => "$this->orderNumber 訂單未同步至鼎新。", 'status' => -1]);
                                }
                            }else{
                                if($chkStatus > 0){
                                    $chk++;
                                    SellAbnormalDB::create([
                                        'import_no' => $this->param['import_no'],
                                        'order_id' => $order->id,
                                        'order_number' => $this->orderNumber,
                                        'direct_shipment' => $directShipment,
                                        'sell_date' => $sellDate,
                                        'shipping_memo' => $shippingNumber,
                                        'memo' => "匯入的 $this->orderNumber 訂單資料檢驗有錯誤。",
                                    ]);
                                    foreach($sells as $sell){
                                        $ids = explode(',',$sell->ids);
                                        sellImportDB::whereIn('id',$ids)->update(['status' => -1]);
                                    }
                                }
                            }
                        }
                    }
                }
                //直寄檢查採購單號
                if($directShipment == 1){
                    foreach($sells as $sell){
                        $purchaseNo = $sell->purchase_no;
                        $digiwinNo = $sell->digiwin_no;
                        $productModel = ProductModelDB::where('digiwin_no',$digiwinNo)->first();
                        $purchaseOrderItem = PurchaseOrderItemDB::where([['direct_shipment',1],['purchase_no',$purchaseNo],['product_model_id',$productModel->id],['is_del',0],['is_lock',0]])->first();
                        if(empty($purchaseOrderItem)){
                            $chk++;
                            $sell->update(['memo' => "找不到對應採購單資料。", 'status' => -1]);
                        }
                    }
                    if($chk > 0){
                        SellAbnormalDB::create([
                            'import_no' => $this->param['import_no'],
                            'order_number' => $this->orderNumber,
                            'direct_shipment' => $directShipment,
                            'sell_date' => $sellDate,
                            'shipping_memo' => $shippingNumber,
                            'memo' => "匯入的資料，找不到對應採購單資料。",
                        ]);
                    }
                }
            }else{
                $chk++;
                SellAbnormalDB::create([
                    'import_no' => $this->param['import_no'],
                    'order_number' => $this->orderNumber,
                    'direct_shipment' => $directShipment,
                    'sell_date' => $sellDate,
                    'shipping_memo' => $shippingNumber,
                    'memo' => "$this->orderNumber 訂單不存在。",
                ]);
                foreach($sells as $sell){
                    $ids = explode(',',$sell->ids);
                    sellImportDB::whereIn('id',$ids)->update(['memo' => "$this->orderNumber 訂單不存在。", 'status' => -1]);
                }
            }
            if($chk == 0){ //檢查是否全部通過
                $totalQty = 0;
                $order = $this->orderItemSplit($this->oneOrderItemTransfer($order),'single');
                $erpItems = $erpOrder->items;
                $items = $order->items;
                $confirm = 'N';
                // !empty($order->acOrder) ? $confirm = 'Y' : $confirm = 'N'; //錢街案直接做確認
                $erpOrder->TC001 == 'A221' ? $TG001 = 'A231' : '';
                $erpOrder->TC001 == 'A222' ? $TG001 = 'A232' : '';
                $order->digiwin_payment_id == '025' ? $TG001 = 'A237' : '';
                $order->digiwin_payment_id == '053' ? $TG001 = 'B231' : '';
                $order->digiwin_payment_id == '054' ? $TG001 = 'B232' : '';
                $order->digiwin_payment_id == '089' ? $TG001 = 'B237' : '';
                strstr($order->digiwin_payment_id,'AC0002') ? $TG001 = 'A239' : ''; //你訂
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
                $x = $i = 1;
                $tmp = $sells->groupBy('shipping_number')->all();
                $orderItemIds = $sellAbnormal = $shippings = [];
                foreach($tmp as $shippingNumber => $value){
                    $erpSellItems = $array2 = [];
                    foreach($value as $sellItem){
                        $error = $chkSell = 0;
                        $gtin13 = $sellItem->gtin13;
                        $digiwinNo = $sellItem->digiwin_no;
                        $quantity = $sellItem->quantity;
                        $ids = explode(',',$sellItem->ids);
                        !empty($sellItem->orderItemIds) ? $orderItemIdsTmp = explode(',',$sellItem->orderItemIds) : $orderItemIdsTmp = [];
                        if(count($orderItemIdsTmp) > 0){
                            $orderItemIds = array_merge($orderItemIds,$orderItemIdsTmp);
                        }
                        //銷貨處理
                        if(!empty($gtin13)){ //倉庫匯入
                            $productModels = [];
                            //資料庫搜尋時, 若為數字型態則須轉為數字才能完全找出所有資料
                            is_numeric($gtin13) ? $gtin13 = (INT)$gtin13 : '';
                            $productModels = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                ->where(function($query)use($productModelTable,$gtin13){
                                    $query->where($productModelTable.'.gtin13',$gtin13)
                                        ->orWhere($productModelTable.'.sku',$gtin13);
                                })->select([
                                    $productModelTable.'.*',
                                    DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                            ])->get();
                            if(count($productModels) <= 0){
                                $tmp = ProductQuantityRecordDB::where('before_gtin13',$gtin13)->orwhere('after_gtin13',$gtin13)->orderBy('create_time','desc')->first();
                                if(!empty($tmp)){
                                    $productModelId = $tmp->product_model_id;
                                    $productModels = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                        ->where($productModelTable.'.id',$productModelId)
                                        ->select([
                                            $productModelTable.'.*',
                                            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                    ])->get();
                                }
                            }
                            //確認商品資料正確
                            if(count($productModels) > 0){
                                $chkProduct = 0;
                                foreach($productModels as $pm){
                                    //找出對應的訂單商品及數量
                                    $chkpm = $totalSellQty = $totalRequireQty = 0; //已沖銷數量, 原始需求數量
                                    foreach($items as $item){
                                        if($item->direct_shipment != 1 && $item->is_del == 0){
                                            if(strstr($item->sku,'BOM')){
                                                foreach($item->package as $package){
                                                    if($package->product_model_id == $pm->id){
                                                        $chkpm++;
                                                        $totalRequireQty += $package->quantity;
                                                        if(count($package->sells) > 0){
                                                            foreach($package->sells as $sell){
                                                                $totalSellQty += $sell->sell_quantity;
                                                            }
                                                        }
                                                    }
                                                }
                                            }else{
                                                if($item->product_model_id == $pm->id){
                                                    $chkpm++;
                                                    $totalRequireQty += $item->quantity;
                                                    if(count($item->sells) > 0){
                                                        foreach($item->sells as $sell){
                                                            $totalSellQty += $sell->sell_quantity;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    if($quantity <= ($totalRequireQty - $totalSellQty)){
                                        $remindQty = $quantity;
                                        if(!empty($order->nidinOrder)){ //你訂商品只有單品
                                            if(count($orderItemIds) > 0){
                                                foreach($items as $item){
                                                    for($y=0; $y < count($orderItemIds); $y++){
                                                        if($item->direct_shipment != 1 && $item->is_del == 0 && $item->id == $orderItemIds[$y]){
                                                            if($remindQty > 0){
                                                                if($item->product_model_id == $pm->id && !empty($item->syncedOrderItem)){
                                                                    $chkProduct++;
                                                                    $erpOrderItem = ErpOrderItemDB::where([['TD002',$erpOrder->TC002],['TD003',$item->syncedOrderItem->erp_order_sno],['TD004',$item->digiwin_no]])->first();
                                                                    if(!empty($erpOrderItem)){
                                                                        //找出中繼是否有出貨資料
                                                                        $sellItems = SellItemSingleDB::where([['erp_order_no',$erpOrderItem->TD002],['erp_order_sno',$erpOrderItem->TD003],['is_del',0]])->get();
                                                                        $sellQty = 0;
                                                                        if(count($sellItems) > 0){ //已出貨
                                                                            foreach($sellItems as $sellItem){
                                                                                $sellQty += $sellItem->sell_quantity;
                                                                            }
                                                                            $requiredQty = $item->quantity - $sellQty;
                                                                        }else{
                                                                            $requiredQty = $item->quantity;
                                                                        }
                                                                        $remindQty < $requiredQty ? $requiredQty = $remindQty : '';
                                                                        $itemPrice = $erpOrderItem->TD011;
                                                                        $price = $requiredQty * $itemPrice;
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
                                                                        $totalQty += $item->quantity;
                                                                        $sellItemSingle = [
                                                                            'sell_no' => $sellNo,
                                                                            'erp_sell_no' => $TG002,
                                                                            'erp_sell_sno' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                                                                            'erp_order_no' => $erpOrderItem->TD002,
                                                                            'erp_order_sno' => $erpOrderItem->TD003,
                                                                            'order_number' => $this->orderNumber,
                                                                            'order_item_id' => $item->id,
                                                                            'order_item_package_id' => null,
                                                                            'order_quantity' => $item->quantity,
                                                                            'sell_quantity' => $requiredQty,
                                                                            'sell_date' => $sellDate,
                                                                            'sell_price' => round($itemPrice,2),
                                                                            'product_model_id' => $item->product_model_id,
                                                                            'memo' => $item->admin_memo,
                                                                            'direct_shipment' => 0,
                                                                            'express_way' => $item->shipping_memo,
                                                                            'express_no' => $shippingNumber,
                                                                            'created_at' => date('Y-m-d H:i:s'),
                                                                        ];
                                                                        $array2 = [
                                                                            'TH003' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                                                                            'TH004' => $erpOrderItem->TD004, //品號
                                                                            'TH005' => $erpOrderItem->TD005, //品名
                                                                            'TH006' => $erpOrderItem->TD006, //規格
                                                                            'TH007' => $erpOrderItem->TD007, //庫別
                                                                            'TH008' => $requiredQty, //數量
                                                                            'TH009' => $erpOrderItem->TD010, //單位
                                                                            'TH012' => round($itemPrice,2), //單價
                                                                            'TH013' => round($price,0), //金額
                                                                            'TH014' => $erpOrderItem->TD001, //訂單單別
                                                                            'TH015' => $erpOrderItem->TD002, //訂單單號
                                                                            'TH016' => $erpOrderItem->TD003, //訂單序號
                                                                            'TH018' => $item->admin_memo, //備註
                                                                            'TH020' => $confirm, //確認碼
                                                                            'TH025' => $erpOrderItem->TD026, //折扣率
                                                                            'TH035' => round($priceWithoutTax,0), //原幣未稅金額
                                                                            'TH036' => round($itemTax,0), //原幣稅額
                                                                            'TH037' => round($priceWithoutTax,0), //本幣未稅金額
                                                                            'TH038' => round($itemTax,0), //本幣稅額
                                                                            'TH047' => $order->partner_order_number ?? $order->order_number, //網購訂單編號
                                                                        ];
                                                                        $erpCOPTH = array_merge($array1,$array2);
                                                                        // dd($erpCOPTH);
                                                                        $chkSell += $requiredQty;
                                                                        ErpCOPTHDB::create($erpCOPTH);
                                                                        $remindQty = $remindQty - $requiredQty;
                                                                        SellItemSingleDB::create($sellItemSingle);
                                                                        $i++;
                                                                        if($remindQty == 0){
                                                                            break;
                                                                        }
                                                                    }else{
                                                                        $error++;
                                                                        SellAbnormalDB::create([
                                                                            'import_no' => $sellItem->import_no,
                                                                            'order_id' => $order->id,
                                                                            'order_number' => $order->order_number,
                                                                            'sell_date' => $sellDate,
                                                                            'shipping_memo' => $shippingNumber,
                                                                            'memo' => "鼎新同步資料異常，請重新同步訂單資料。",
                                                                        ]);
                                                                        sellImportDB::whereIn('id',$ids)->update(['memo' => "鼎新同步資料異常，請重新同步訂單資料。",'status' => -2]);
                                                                    }
                                                                }
                                                            }else{
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }else{
                                            foreach($items as $item){
                                                if($item->direct_shipment != 1 && $item->is_del == 0){
                                                    if($remindQty > 0){
                                                        if(strstr($item->sku,'BOM')){
                                                            foreach($item->package as $package){
                                                                if($package->product_model_id == $pm->id && !empty($package->syncedOrderItemPackage)){
                                                                    $chkProduct++;
                                                                    if(count($package->sells) > 0){
                                                                        $sellQty = 0;
                                                                        foreach($package->sells as $sell){
                                                                            $sellQty += $sell->sell_quantity;
                                                                        }
                                                                        $requiredQty = $package->quantity - $sellQty;
                                                                    }else{
                                                                        $requiredQty = $package->quantity;
                                                                    }
                                                                    //找出紀錄的序號
                                                                    !empty($package->syncedOrderItemPackage->erp_order_sno) ? $erpOrderSnos = explode(',',$package->syncedOrderItemPackage->erp_order_sno) : $erpOrderSnos = [];
                                                                    $erpOrderItems = ErpOrderItemDB::where([['TD002',$erpOrder->TC002],['TD004',$package->digiwin_no]])->whereIn('TD003',$erpOrderSnos)->get();
                                                                    //由於鼎新內的訂單資料是被拆分的, 所以必須針對所有拆分的商品沖銷
                                                                    $x = $requiredQty; //要沖的數量及筆數
                                                                    $z = 1;
                                                                    $useQty = 0;
                                                                    if(count($erpOrderItems) > 0){
                                                                        foreach($erpOrderItems as $erpOrderItem){
                                                                            //找出中繼是否有出貨資料, 只會有一筆
                                                                            $sellItem = SellItemSingleDB::where([['erp_order_no',$erpOrderItem->TD002],['erp_order_sno',$erpOrderItem->TD003],['is_del',0]])->first();
                                                                            if(empty($sellItem)){
                                                                                $useQty += $erpOrderItem->TD008;
                                                                                $price = $itemPrice = $erpOrderItem->TD011;
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
                                                                                $sellItemSingle = [
                                                                                    'sell_no' => $sellNo,
                                                                                    'erp_sell_no' => $TG002,
                                                                                    'erp_sell_sno' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                                                                                    'erp_order_no' =>$erpOrderItem->TD002,
                                                                                    'erp_order_sno' =>$erpOrderItem->TD003,
                                                                                    'order_number' => $this->orderNumber,
                                                                                    'order_item_id' => $item->id,
                                                                                    'order_item_package_id' => $package->id,
                                                                                    'order_quantity' => $package->quantity,
                                                                                    'sell_quantity' => $erpOrderItem->TD008,
                                                                                    'sell_date' => $sellDate,
                                                                                    'sell_price' => round($itemPrice,2),
                                                                                    'product_model_id' => $package->product_model_id,
                                                                                    'memo' => $package->admin_memo,
                                                                                    'direct_shipment' => 0,
                                                                                    'express_way' => $item->shipping_memo,
                                                                                    'express_no' => $shippingNumber,
                                                                                    'created_at' => date('Y-m-d H:i:s'),
                                                                                ];
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
                                                                                    'TH020' => $confirm, //確認碼
                                                                                    'TH025' => $erpOrderItem->TD026, //折扣率
                                                                                    'TH035' => round($priceWithoutTax,0), //原幣未稅金額
                                                                                    'TH036' => round($itemTax,0), //原幣稅額
                                                                                    'TH037' => round($priceWithoutTax,0), //本幣未稅金額
                                                                                    'TH038' => round($itemTax,0), //本幣稅額
                                                                                    'TH047' => $order->partner_order_number ?? $order->order_number, //網購訂單編號
                                                                                ];
                                                                                $chkSell += $erpOrderItem->TD008;
                                                                                $erpCOPTH = array_merge($array1,$array2);
                                                                                // dd($erpCOPTH);
                                                                                ErpCOPTHDB::create($erpCOPTH);
                                                                                $remindQty = $remindQty - 1;
                                                                                $i++;
                                                                                SellItemSingleDB::create($sellItemSingle);
                                                                                if($x == $z || $remindQty == 0){
                                                                                    break 2;
                                                                                }
                                                                                $z++;
                                                                            }
                                                                        }
                                                                    }else{
                                                                        $error++;
                                                                        SellAbnormalDB::create([
                                                                            'import_no' => $sellItem->import_no,
                                                                            'order_id' => $order->id,
                                                                            'order_number' => $order->order_number,
                                                                            'sell_date' => $sellDate,
                                                                            'shipping_memo' => $shippingNumber,
                                                                            'memo' => "鼎新同步資料異常，請重新同步訂單資料。",
                                                                        ]);
                                                                        sellImportDB::whereIn('id',$ids)->update(['memo' => "鼎新同步資料異常，請重新同步訂單資料。",'status' => -2]);
                                                                    }
                                                                }
                                                            }
                                                        }else{
                                                            if($item->product_model_id == $pm->id && !empty($item->syncedOrderItem)){
                                                                $chkProduct++;
                                                                $erpOrderItem = ErpOrderItemDB::where([['TD002',$erpOrder->TC002],['TD003',$item->syncedOrderItem->erp_order_sno],['TD004',$item->digiwin_no]])->first();
                                                                if(!empty($erpOrderItem)){
                                                                    //找出中繼是否有出貨資料
                                                                    $sellItems = SellItemSingleDB::where([['erp_order_no',$erpOrderItem->TD002],['erp_order_sno',$erpOrderItem->TD003],['is_del',0]])->get();
                                                                    $sellQty = 0;
                                                                    if(count($sellItems) > 0){ //已出貨
                                                                        foreach($sellItems as $sellItem){
                                                                            $sellQty += $sellItem->sell_quantity;
                                                                        }
                                                                        $requiredQty = $item->quantity - $sellQty;
                                                                    }else{
                                                                        $requiredQty = $item->quantity;
                                                                    }
                                                                    $remindQty < $requiredQty ? $requiredQty = $remindQty : '';
                                                                    $itemPrice = $erpOrderItem->TD011;
                                                                    $price = $requiredQty * $itemPrice;
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
                                                                    $totalQty += $item->quantity;
                                                                    $sellItemSingle = [
                                                                        'sell_no' => $sellNo,
                                                                        'erp_sell_no' => $TG002,
                                                                        'erp_sell_sno' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                                                                        'erp_order_no' => $erpOrderItem->TD002,
                                                                        'erp_order_sno' => $erpOrderItem->TD003,
                                                                        'order_number' => $this->orderNumber,
                                                                        'order_item_id' => $item->id,
                                                                        'order_item_package_id' => null,
                                                                        'order_quantity' => $item->quantity,
                                                                        'sell_quantity' => $requiredQty,
                                                                        'sell_date' => $sellDate,
                                                                        'sell_price' => round($itemPrice,2),
                                                                        'product_model_id' => $item->product_model_id,
                                                                        'memo' => $item->admin_memo,
                                                                        'direct_shipment' => 0,
                                                                        'express_way' => $item->shipping_memo,
                                                                        'express_no' => $shippingNumber,
                                                                        'created_at' => date('Y-m-d H:i:s'),
                                                                    ];
                                                                    $array2 = [
                                                                        'TH003' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                                                                        'TH004' => $erpOrderItem->TD004, //品號
                                                                        'TH005' => $erpOrderItem->TD005, //品名
                                                                        'TH006' => $erpOrderItem->TD006, //規格
                                                                        'TH007' => $erpOrderItem->TD007, //庫別
                                                                        'TH008' => $requiredQty, //數量
                                                                        'TH009' => $erpOrderItem->TD010, //單位
                                                                        'TH012' => round($itemPrice,2), //單價
                                                                        'TH013' => round($price,0), //金額
                                                                        'TH014' => $erpOrderItem->TD001, //訂單單別
                                                                        'TH015' => $erpOrderItem->TD002, //訂單單號
                                                                        'TH016' => $erpOrderItem->TD003, //訂單序號
                                                                        'TH018' => $item->admin_memo, //備註
                                                                        'TH020' => $confirm, //確認碼
                                                                        'TH025' => $erpOrderItem->TD026, //折扣率
                                                                        'TH035' => round($priceWithoutTax,0), //原幣未稅金額
                                                                        'TH036' => round($itemTax,0), //原幣稅額
                                                                        'TH037' => round($priceWithoutTax,0), //本幣未稅金額
                                                                        'TH038' => round($itemTax,0), //本幣稅額
                                                                        'TH047' => $order->partner_order_number ?? $order->order_number, //網購訂單編號
                                                                    ];
                                                                    $erpCOPTH = array_merge($array1,$array2);
                                                                    // dd($erpCOPTH);
                                                                    $chkSell += $requiredQty;
                                                                    ErpCOPTHDB::create($erpCOPTH);
                                                                    $remindQty = $remindQty - $requiredQty;
                                                                    SellItemSingleDB::create($sellItemSingle);
                                                                    $i++;
                                                                    if($remindQty == 0){
                                                                        break;
                                                                    }
                                                                }else{
                                                                    $error++;
                                                                    SellAbnormalDB::create([
                                                                        'import_no' => $sellItem->import_no,
                                                                        'order_id' => $order->id,
                                                                        'order_number' => $order->order_number,
                                                                        'sell_date' => $sellDate,
                                                                        'shipping_memo' => $shippingNumber,
                                                                        'memo' => "鼎新同步資料異常，請重新同步訂單資料。",
                                                                    ]);
                                                                    sellImportDB::whereIn('id',$ids)->update(['memo' => "鼎新同步資料異常，請重新同步訂單資料。",'status' => -2]);
                                                                }
                                                            }
                                                        }
                                                    }else{
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }else{
                                        if($chkpm > 0){
                                            $needQty = $totalRequireQty - $totalSellQty;
                                            $abnormalQty = $quantity - $needQty;
                                            SellAbnormalDB::create([
                                                'import_no' => $sellItem->import_no,
                                                'order_id' => $order->id,
                                                'order_number' => $order->order_number,
                                                'erp_order_number' => $erpOrder->TC002,
                                                'product_model_id' => $pm->id,
                                                'product_name' => $pm->product_name,
                                                'order_quantity' => $totalRequireQty,
                                                'quantity' => $abnormalQty,
                                                'direct_shipment' => 0,
                                                'sell_date' => $sellDate,
                                                'shipping_memo' => $shippingNumber,
                                                'memo' => "商品 $gtin13 ，此次出庫數量 $quantity 大於應出數量 $needQty ，請與倉庫或廠商確認數量後重新匯入此商品的出貨清單。",
                                                'is_chk' => 0,
                                            ]);
                                            sellImportDB::whereIn('id',$ids)->update(['memo' => "商品 $gtin13 ，此次出庫數量 $quantity 大於應出數量 $needQty 。",'status' => -1]);
                                        }
                                    }
                                }
                                if($chkProduct == 0){
                                    $error++;
                                    $sellAbnormal = [
                                        'import_no' => $sellItem->import_no,
                                        'order_id' => $order->id,
                                        'order_number' => $order->order_number,
                                        'sell_date' => $sellDate,
                                        'shipping_memo' => $shippingNumber,
                                        'memo' => "商品 $gtin13 ，對應不到訂單資料。",
                                        'is_chk' => 0,
                                    ];
                                    SellAbnormalDB::create($sellAbnormal);
                                    sellImportDB::whereIn('id',$ids)->update(['memo' => "商品 $gtin13 ，對應不到訂單資料。",'status' => -1]);
                                }
                            }else{
                                $error++;
                                SellAbnormalDB::create([
                                    'import_no' => $sellItem->import_no,
                                    'order_id' => $order->id,
                                    'order_number' => $order->order_number,
                                    'sell_date' => $sellDate,
                                    'shipping_memo' => $shippingNumber,
                                    'memo' => "商品 $gtin13 ，不存在於iCarry資料庫中。",
                                ]);
                                sellImportDB::whereIn('id',$ids)->update(['memo' => "商品 $gtin13 ，不存在於iCarry資料庫中。",'status' => -1]);
                            }
                        }elseif(!empty($digiwinNo)){ //廠商直寄匯入
                            $pm = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                            ->where($productModelTable.'.digiwin_no',$digiwinNo)
                            ->select([
                                $productModelTable.'.*',
                                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                $productTable.'.package_data',
                            ])->first();
                            if(!empty($pm)){
                                //找出對應的訂單商品及數量, 直寄不需要判斷組合品內的單品數量
                                $chkProduct = $expectedQty = $stockinQty = $chkpm = $totalSellQty = $totalRequireQty = 0;
                                foreach($items as $item){
                                    if($item->direct_shipment == 1 && $item->is_del == 0){
                                        if($item->product_model_id == $pm->id){
                                            $chkpm++;
                                            $totalRequireQty += $item->quantity;
                                            if(count($item->sells) > 0){
                                                foreach($item->sells as $sell){
                                                    $totalSellQty += $sell->sell_quantity;
                                                }
                                            }
                                        }
                                    }
                                }
                                if($quantity <= ($totalRequireQty - $totalSellQty)){
                                    $stockin = [];
                                    foreach($items as $item){
                                        if($item->product_model_id == $pm->id && $item->direct_shipment == 1 && $item->is_del == 0){
                                            if(strstr($item->sku,'BOM')){
                                                $chkSell = $packageQty = 0;;
                                                if(!empty($pm->package_data)){
                                                    $packageData = json_decode($pm->package_data);
                                                    foreach($packageData as $pp){
                                                        if($pp->bom == $item->sku){
                                                            $lists = $pp->lists;
                                                            if(count($lists) > 0){
                                                                foreach($lists as $list){
                                                                    foreach($item->package as $package){
                                                                        if($package->sku == $list->sku && !empty($package->syncedOrderItemPackage)){
                                                                            $chkProduct++;
                                                                            $remindQty = $quantity * $list->quantity;
                                                                            $packageQty += $quantity * $list->quantity; //組合品需求的總數
                                                                            if(count($package->sells) > 0){
                                                                                $sellQty = 0;
                                                                                foreach($package->sells as $sell){
                                                                                    $sellQty += $sell->sell_quantity;
                                                                                }
                                                                                $requiredQty = $package->quantity - $sellQty;
                                                                            }else{
                                                                                $requiredQty = $package->quantity;
                                                                            }
                                                                            $expectedQty += $requiredQty;
                                                                            //找出紀錄的序號
                                                                            !empty($package->syncedOrderItemPackage->erp_order_sno) ? $erpOrderSnos = explode(',',$package->syncedOrderItemPackage->erp_order_sno) : $erpOrderSnos = [];
                                                                            $erpOrderItems = ErpOrderItemDB::where([['TD002',$erpOrder->TC002],['TD004',$package->digiwin_no]])->whereIn('TD003',$erpOrderSnos)->get();
                                                                            //由於鼎新內的訂單資料是被拆分的, 所以必須針對所有拆分的商品沖銷
                                                                            $x = $requiredQty; //要沖的數量及筆數
                                                                            $z = 1;
                                                                            $stockinQty = $useQty = 0;
                                                                            if(count($erpOrderItems) > 0 && $remindQty > 0){
                                                                                foreach($erpOrderItems as $erpOrderItem){
                                                                                    //找出中繼是否有出貨資料, 只會有一筆
                                                                                    $sellItemSingle = SellItemSingleDB::where([['erp_order_no',$erpOrderItem->TD002],['erp_order_sno',$erpOrderItem->TD003],['is_del',0]])->first();
                                                                                    if(empty($sellItemSingle)){
                                                                                        $useQty += $erpOrderItem->TD008;
                                                                                        $price = $itemPrice = $erpOrderItem->TD011;
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
                                                                                        $stockinQty += $erpOrderItem->TD008;
                                                                                        $sellItemSingle = [
                                                                                            'sell_no' => $sellNo,
                                                                                            'erp_sell_no' => $TG002,
                                                                                            'erp_sell_sno' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                                                                                            'erp_order_no' =>$erpOrderItem->TD002,
                                                                                            'erp_order_sno' =>$erpOrderItem->TD003,
                                                                                            'order_number' => $this->orderNumber,
                                                                                            'order_item_id' => $item->id,
                                                                                            'order_item_package_id' => $package->id,
                                                                                            'order_quantity' => $package->quantity,
                                                                                            'sell_quantity' => $erpOrderItem->TD008,
                                                                                            'sell_date' => $sellDate,
                                                                                            'sell_price' => round($itemPrice,2),
                                                                                            'product_model_id' => $package->product_model_id,
                                                                                            'memo' => $package->admin_memo,
                                                                                            'direct_shipment' => 0,
                                                                                            'express_way' => $item->shipping_memo,
                                                                                            'express_no' => $shippingNumber,
                                                                                            'created_at' => date('Y-m-d H:i:s'),
                                                                                        ];
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
                                                                                            'TH020' => $confirm, //確認碼
                                                                                            'TH025' => $erpOrderItem->TD026, //折扣率
                                                                                            'TH035' => round($priceWithoutTax,0), //原幣未稅金額
                                                                                            'TH036' => round($itemTax,0), //原幣稅額
                                                                                            'TH037' => round($priceWithoutTax,0), //本幣未稅金額
                                                                                            'TH038' => round($itemTax,0), //本幣稅額
                                                                                            'TH047' => $order->partner_order_number ?? $order->order_number, //網購訂單編號
                                                                                        ];
                                                                                        $erpCOPTH = array_merge($array1,$array2);
                                                                                        // dd($erpCOPTH);
                                                                                        $chkSell += $erpOrderItem->TD008;
                                                                                        ErpCOPTHDB::create($erpCOPTH);
                                                                                        $remindQty = $remindQty - 1;
                                                                                        $i++;
                                                                                        SellItemSingleDB::create($sellItemSingle);
                                                                                        if($x == $z){
                                                                                            break;
                                                                                        }
                                                                                        $z++;
                                                                                    }
                                                                                }
                                                                            }
                                                                            if($stockinQty > 0){
                                                                                $stockinImport[] = [
                                                                                    'import_no' => $importNo,
                                                                                    'warehouse_export_time' => date('Y-m-d H:i:s'),
                                                                                    'warehouse_stockin_no' => $sellNo,
                                                                                    'vendor_id' => $item->vendor_id,
                                                                                    'gtin13' => $package->digiwin_no,
                                                                                    'product_name' => $package->product_name,
                                                                                    'expected_quantity' => $expectedQty,
                                                                                    'stockin_quantity' => $stockinQty,
                                                                                    'stockin_time' => $sellItem->stockin_time,
                                                                                    'purchase_nos' => join(',',$purchaseNos),
                                                                                    'created_at' => date('Y-m-d H:i:s'),
                                                                                    'sell_no' => $sellNo,
                                                                                    'direct_shipment' => 1,
                                                                                ];
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                                if($chkSell == $packageQty && $chkSell != 0){
                                                    SellImportDB::whereIn('id',$ids)->update(['status' => 1,'memo' => '此列資料已全數沖銷完成。']);
                                                }else{
                                                    SellImportDB::whereIn('id',$ids)->update(['status' => -2,'memo' => '沖銷異常。']);
                                                }
                                            }else{
                                                $remindQty = $quantity;
                                                if(!empty($item->syncedOrderItem)){
                                                    $chkProduct++;
                                                    $erpOrderItem = ErpOrderItemDB::where([['TD002',$erpOrder->TC002],['TD003',$item->syncedOrderItem->erp_order_sno],['TD004',$item->digiwin_no]])->first();
                                                    if(!empty($erpOrderItem)){
                                                        //找出中繼是否有出貨資料
                                                        $sellItems = SellItemSingleDB::where([['erp_order_no',$erpOrderItem->TD002],['erp_order_sno',$erpOrderItem->TD003],['is_del',0]])->get();
                                                        $sellQty = 0;
                                                        if(count($sellItems) > 0){ //已出貨
                                                            foreach($sellItems as $sellItem){
                                                                $sellQty += $sellItem->sell_quantity;
                                                            }
                                                            $requiredQty = $item->quantity - $sellQty;
                                                        }else{
                                                            $requiredQty = $item->quantity;
                                                        }
                                                        $expectedQty += $requiredQty;
                                                        $remindQty < $requiredQty ? $requiredQty = $remindQty : '';
                                                        $itemPrice = $erpOrderItem->TD011;
                                                        $price = $requiredQty * $itemPrice;
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
                                                        $totalQty += $item->quantity;
                                                        $sellItemSingle = [
                                                            'sell_no' => $sellNo,
                                                            'erp_sell_no' => $TG002,
                                                            'erp_sell_sno' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                                                            'erp_order_no' => $erpOrderItem->TD002,
                                                            'erp_order_sno' => $erpOrderItem->TD003,
                                                            'order_number' => $this->orderNumber,
                                                            'order_item_id' => $item->id,
                                                            'order_item_package_id' => null,
                                                            'order_quantity' => $item->quantity,
                                                            'sell_quantity' => $requiredQty,
                                                            'sell_date' => $sellDate,
                                                            'sell_price' => round($itemPrice,2),
                                                            'product_model_id' => $item->product_model_id,
                                                            'memo' => $item->admin_memo,
                                                            'direct_shipment' => 0,
                                                            'express_way' => $item->shipping_memo,
                                                            'express_no' => $shippingNumber,
                                                            'created_at' => date('Y-m-d H:i:s'),
                                                        ];
                                                        $array2 = [
                                                            'TH003' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                                                            'TH004' => $erpOrderItem->TD004, //品號
                                                            'TH005' => $erpOrderItem->TD005, //品名
                                                            'TH006' => $erpOrderItem->TD006, //規格
                                                            'TH007' => $erpOrderItem->TD007, //庫別
                                                            'TH008' => $requiredQty, //數量
                                                            'TH009' => $erpOrderItem->TD010, //單位
                                                            'TH012' => round($itemPrice,2), //單價
                                                            'TH013' => round($price,0), //金額
                                                            'TH014' => $erpOrderItem->TD001, //訂單單別
                                                            'TH015' => $erpOrderItem->TD002, //訂單單號
                                                            'TH016' => $erpOrderItem->TD003, //訂單序號
                                                            'TH018' => $item->admin_memo, //備註
                                                            'TH020' => $confirm, //確認碼
                                                            'TH025' => $erpOrderItem->TD026, //折扣率
                                                            'TH035' => round($priceWithoutTax,0), //原幣未稅金額
                                                            'TH036' => round($itemTax,0), //原幣稅額
                                                            'TH037' => round($priceWithoutTax,0), //本幣未稅金額
                                                            'TH038' => round($itemTax,0), //本幣稅額
                                                            'TH047' => $order->partner_order_number ?? $order->order_number, //網購訂單編號
                                                        ];
                                                        $chkSell += $requiredQty;
                                                        $erpCOPTH = array_merge($array1,$array2);
                                                        ErpCOPTHDB::create($erpCOPTH);
                                                        SellItemSingleDB::create($sellItemSingle);
                                                        $stockinQty += $requiredQty;
                                                        $remindQty = $remindQty - $requiredQty;
                                                        $i++;
                                                    }
                                                }
                                                if($stockinQty > 0){
                                                    $stockinImport[] = [
                                                        'import_no' => $importNo,
                                                        'warehouse_export_time' => date('Y-m-d H:i:s'),
                                                        'warehouse_stockin_no' => $sellNo,
                                                        'vendor_id' => $item->vendor_id,
                                                        'gtin13' => $item->digiwin_no,
                                                        'product_name' => $item->product_name,
                                                        'expected_quantity' => $expectedQty,
                                                        'stockin_quantity' => $stockinQty,
                                                        'stockin_time' => $sellItem->stockin_time,
                                                        'purchase_nos' => join(',',$purchaseNos),
                                                        'created_at' => date('Y-m-d H:i:s'),
                                                        'sell_no' => $sellNo,
                                                        'direct_shipment' => 1,
                                                    ];
                                                }
                                                if($chkSell == $quantity){
                                                    SellImportDB::whereIn('id',$ids)->update(['status' => 1,'memo' => '此列資料已全數沖銷完成。']);
                                                }else{
                                                    SellImportDB::whereIn('id',$ids)->update(['status' => -2,'memo' => '沖銷異常。']);
                                                }
                                                if($remindQty == 0){
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }elseif($quantity > ($totalRequireQty - $totalSellQty)){
                                    $needQty = $totalRequireQty - $totalSellQty;
                                    $abnormalQty = $quantity - $needQty;
                                    $error++;
                                    SellAbnormalDB::create([
                                        'import_no' => $sellItem->import_no,
                                        'order_id' => $order->id,
                                        'order_number' => $order->order_number,
                                        'erp_order_number' => $erpOrder->TC002,
                                        'product_model_id' => $pm->id,
                                        'product_name' => $pm->product_name,
                                        'order_quantity' => $totalRequireQty,
                                        'quantity' => $abnormalQty,
                                        'direct_shipment' => 1,
                                        'sell_date' => $sellDate,
                                        'shipping_memo' => $shippingNumber,
                                        'memo' => "商品 $digiwinNo ，此次出庫數量 $quantity 大於應出數量 $needQty ，請與倉庫或廠商確認數量後重新匯入此商品的出貨清單。",
                                        'is_chk' => 0,
                                    ]);
                                    sellImportDB::whereIn('id',$ids)->update(['memo' => "商品 $digiwinNo ，此次出庫數量 $quantity 大於應出數量 $needQty 。",'status' => -1]);
                                }
                                if($chkProduct == 0){
                                    $error++;
                                    SellAbnormalDB::create([
                                        'import_no' => $sellItem->import_no,
                                        'order_id' => $order->id,
                                        'order_number' => $order->order_number,
                                        'sell_date' => $sellDate,
                                        'shipping_memo' => $shippingNumber,
                                        'memo' => "商品 $digiwinNo ，對應不到訂單資料。",
                                        'is_chk' => 0,
                                    ]);
                                    sellImportDB::whereIn('id',$ids)->update(['memo' => "商品 $digiwinNo ，對應不到訂單資料。",'status' => -1]);
                                }
                            }else{
                                $error++;
                                SellAbnormalDB::create([
                                    'import_no' => $sellItem->import_no,
                                    'order_id' => $order->id,
                                    'order_number' => $order->order_number,
                                    'sell_date' => $sellDate,
                                    'shipping_memo' => $shippingNumber,
                                    'memo' => "商品 $digiwinNo ，不存在於iCarry資料庫中。",
                                ]);
                                sellImportDB::whereIn('id',$ids)->update(['memo' => "商品 $digiwinNo ，不存在於iCarry資料庫中。",'status' => -1]);
                            }
                        }
                        if($error == 0){
                            if(!empty($gtin13)){
                                if($chkSell == $quantity){
                                    SellImportDB::whereIn('id',$ids)->update(['status' => 1,'memo' => '此列資料已全數沖銷完成。']);
                                }else{
                                    SellImportDB::whereIn('id',$ids)->update(['status' => -2,'memo' => '沖銷異常。']);
                                }
                            }
                        }
                        $x++;
                    }
                }
                $sellItemSingles = SellItemSingleDB::where([['sell_no',$sellNo],['order_number',$order->order_number],['is_del',0]])->get();

                //你訂銷單需要 找預收待抵單資料
                $TG001 == 'A239' ? $ACRTA = ErpACRTADB::where([['TA023',$erpOrder->TC001],['TA024',$erpOrder->TC002]])->first() : $ACRTA = null;
                !empty($ACRTA) ? $TG061 = $ACRTA->TA001 : $TG061 = '';
                !empty($ACRTA) ? $TG062 = $ACRTA->TA002 : $TG062 = '';

                if(count($sellItemSingles) > 0){
                    $erpOrder->TC016 == 1 || $erpOrder->TC016 == 2 ? $tax = round($amount * 0.05,0) : $tax = 0;
                    // 建立中繼銷貨單單頭
                    $sell = SellDB::create([
                        'sell_no' => $sellNo,
                        'erp_sell_no' => $TG002,
                        'order_id' => $order->id,
                        'order_number' => $this->orderNumber,
                        'erp_order_number' => $erpOrder->TC002,
                        'quantity' => $totalQty,
                        'amount' => round($amount,0),
                        'tax' => round($tax,0),
                        'is_del' => 0,
                        'sell_date' => $sellDate,
                        'tax_type' => $erpOrder->TC016,
                        'memo' => null,
                        'purchase_no' => $purchaseNo,
                    ]);
                    //建立鼎新銷貨單單頭
                    $erpSell = ErpCOPTGDB::create([
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
                        'TG015' => $TG001 == 'A232' ? $order->invoice_number : $erpCustomer->MA010, //統一編號
                        'TG016' => $erpCustomer->MA037, //發票聯數
                        'TG017' => $erpOrder->TC016, //課稅別
                        'TG018' => '', //發票地址一
                        'TG019' => '', //發票地址二
                        'TG020' => '', //備註
                        'TG021' => '', //發票日期
                        'TG022' => 0, //列印次數
                        'TG023' => $confirm, //確認碼
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
                        'TG059' => $TG001 == 'A239' ? $erpOrder->TC001 : '', //訂單單別, 留空白
                        'TG060' => $TG001 == 'A239' ? $erpOrder->TC002 : '', //訂單單號, 留空白
                        'TG061' => $TG001 == 'A239' ? $TG061 : '', //預收待抵單別, 留空白
                        'TG062' => $TG001 == 'A239' ? $TG062 : '', //預收待抵單號, 留空白
                        'TG063' => $TG001 == 'A239' ? round($amount,0) : 0, //沖抵金額
                        'TG064' => $TG001 == 'A239' ? round($tax,0) : 0, //沖抵稅額
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
                        'TG129' => $order->receiver_nation_number.$order->receiver_phone_number ? mb_substr($order->receiver_nation_number.$order->receiver_phone_number,0,15) : mb_substr($order->receiver_tel,0,15), //行動電話
                        'TG130' => '', //信用卡末四碼
                        'TG131' => '', //連絡人EMAIL
                        'TG132' => '', //買受人適用零稅率註記
                        'TG200' => '', //載具行動電話
                        'TG134' => '', //貨運單號
                        'TG091' => 0, //原幣應稅銷售額
                        'TG092' => 0, //原幣免稅銷售額
                        'TG093' => 0, //本幣應稅銷售額
                        'TG094' => 0, //本幣免稅銷售額
                    ]);

                    //檢查Erp銷貨單稅額是否正確
                    CheckErpSellTaxJob::dispatch($erpSell);

                    //檢查訂單是否全部已出貨並產生鼎新銷貨單
                    CheckOrderSellJob::dispatchNow($order);

                    //發送簡訊
                    //下面客戶才需要發簡訊, 蝦皮010,011發tracking no
                    // $v = ['001','002','003','004','005','006','007','008','009','010','011','037','063','073'];
                    $v = DigiwinPaymentDB::where(function($query){
                        $query = $query->where('create_type','web')->orWhere('create_type','shopee');
                    })->get()->pluck('customer_no')->all();
                    if(in_array($order->digiwin_payment_id,$v)){
                        $sellItems = SellItemSingleDB::where([['sell_no',$sellNo],['order_number',$order->order_number],['is_del',0]])
                        ->whereNotNull('express_way') //排除掉行郵稅、運費的資料
                        ->select([
                            'express_way',
                            'express_no',
                        ])->groupBy('express_way','express_no')->get();
                        if(count($sellItems) > 0){
                            foreach($sellItems as $sellItem){
                                if($sellItem->express_way != '電子郵件'){
                                    if($sellItem->express_way == '廠商發貨'){
                                        $sellItem->express_way = explode('_',$sellItem->express_no)[0];
                                        $sellItem->express_no = explode('_',$sellItem->express_no)[1];
                                    }
                                    $shippings[] = [
                                        'express_way' => $sellItem->express_way,
                                        'express_no' => $sellItem->express_no,
                                    ];
                                }
                            }
                            if(count($shippings) > 0){
                                OrderShippingSendSMSJob::dispatch($this->orderNumber,$shippings);
                            }
                        }
                    }
                }

                if(count($stockinImport) > 0) {
                    StockinImportDB::insert($stockinImport);
                    $TG014 = str_replace('-','',$sellDate);
                    $six = substr($TG014,2);
                    try {
                        //找出鼎新進貨單的最後一筆單號
                        $chkTemp = SerialNoRecordDB::where([['type','ErpStockinNo'],['serial_no','like',"$six%"]])->orderBy('serial_no','desc')->first();
                        !empty($chkTemp) ? $erpStockinNo = $chkTemp->serial_no + 1 : $erpStockinNo = $six.str_pad(1,5,0,STR_PAD_LEFT);
                        $chkTemp = SerialNoRecordDB::create(['type' => 'ErpStockinNo','serial_no' => $erpStockinNo]);
                        //檢查鼎新進貨單有沒有這個號碼
                        $tmp = ErpPURTGDB::where('TG002','like',"%$six%")->select('TG002')->orderBy('TG002','desc')->first();
                        if(!empty($tmp)){
                            if($tmp->TG002 >= $erpStockinNo){
                                $erpStockinNo = $tmp->TG002+1;
                                $chkTemp = SerialNoRecordDB::create(['type' => 'ErpStockinNo','serial_no' => $erpStockinNo]);
                            }
                        }
                    } catch (Exception $exception) {
                        Log::info("銷貨執行程序取入庫單號重複。採購單號 ".join(',',$purchaseNos)." 可能未完成入庫。");
                        return null;
                    }
                    // 這邊不能放入背端執行, 不然會造成鼎新取號衝突
                    PurchaseStockinImportJob::dispatchNow([
                        'import_no' => $importNo,
                        'cate' => 'directShip',
                        'shipping_date' => $sellDate,
                        'admin_id' => $adminId,
                        'erpStockinNo' => $erpStockinNo,
                    ]);
                }
            }
        }
    }
}
