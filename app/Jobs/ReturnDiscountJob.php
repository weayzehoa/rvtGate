<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseOrderItemPackage as PurchaseOrderItemPackageDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ErpVendor as ErpVendorDB;

use App\Models\ErpPURTD as ErpPURTDDB;
use App\Models\ErpPURTH as ErpPURTHDB;
use App\Models\ErpPURTI as ErpPURTIDB;
use App\Models\ErpPURTJ as ErpPURTJDB;

use App\Models\ReturnDiscount as ReturnDiscountDB;
use App\Models\ReturnDiscountItem as ReturnDiscountItemDB;
use App\Models\ReturnDiscountItemPackage as ReturnDiscountItemPackageDB;

use App\Models\PurchaseOrderChangeLog as PurchaseOrderChangeLogDB;

use Session;
use File;
use PDF;
use DB;

class ReturnDiscountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        !empty($this->param['admin_id']) ? $adminId = $this->param['admin_id'] : $adminId = null;
        !empty(auth('gate')->user()->account) ? $creator = strtoupper(auth('gate')->user()->account) : $creator = 'DS';
        !empty($param['return_date']) ? $returnDate = $param['return_date'] : $returnDate = date('Y-m-d');
        $createDate = date('Ymd');
        $createTime = date('H:i:s');
        $createMonth = date('Ym');
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();

        $returnDate = str_replace('-','',$param['return_date']);
        $returnDate6 = substr($returnDate,2);
        //找出今日最後一筆退貨折扣單單號
        $tmp = ErpPURTIDB::where('TI002','like',"$returnDate6%")->select('TI002')->orderBy('TI002','desc')->first();
        !empty($tmp) ? $erpReturnDiscountNo = $tmp->TI002 + 1 : $erpReturnDiscountNo = $returnDate6.str_pad(1,5,0,STR_PAD_LEFT);

        //找出中繼今日最後一筆退貨折讓單單號
        $tmp = ReturnDiscountDB::where('return_discount_no','>=',date('ymd').'00001')->select('return_discount_no')->orderBy('return_discount_no','desc')->first();
        !empty($tmp) ? $lastNo = $tmp->return_discount_no : $lastNo = 0;
        $lastNo != 0 ? $returnDiscountNo = $lastNo + 1 : $returnDiscountNo = date('ymd').str_pad(1,5,0,STR_PAD_LEFT);

        $return = $erpReturn = $returnItems = $returnPackage = $erpReturnItems = [];
        $quantity = $amount = $tax = 0;
        $param['type'] == 'return' ? $type = 'A351' : $type = 'A352';
        $c = 1;
        if($param['type'] == 'return'){ //退貨
            $purchaseOrder = PurchaseOrderDB::with('acOrder','exportItems','exportItems.stockins','exportItems.returns','exportItems.exportPackage','exportItems.exportPackage.stockins','exportItems.exportPackage.returns')->find($param['purchaseOrderId']);
            $vendor = VendorDB::find($purchaseOrder->vendor_id);
            !empty($vendor->digiwin_vendor_no) ? $erpVendor = ErpVendorDB::find($vendor->digiwin_vendor_no) : $erpVendor = ErpVendorDB::find('A'.str_pad($purchaseOrder->vendor_id,5,'0',STR_PAD_LEFT));
            !empty($vendor->digiwin_vendor_no) ? $TI004 = $vendor->digiwin_vendor_no : $TI004 = 'A'.str_pad($purchaseOrder->vendor_id,5,0,STR_PAD_LEFT);
            foreach($param['items'] as $ritem){
                if(!empty($ritem['qty'])){ //有數量才進行退貨動作
                    foreach($purchaseOrder->items as $item){
                        if($ritem['id'] == $item->id){
                            $TJ011 = $item->direct_shipment == 1 ? 'W02' : 'W01';
                            !empty($purchaseOrder->acOrder) ? $TJ011 = 'W16' : '';
                            //是否為組合商品, 須轉為單品
                            if(strstr($item->sku,'BOM')){
                                $returnDiscountItem = ReturnDiscountItemDB::create([
                                    'return_discount_no' => $returnDiscountNo,
                                    'erp_return_discount_no' => $erpReturnDiscountNo,
                                    'erp_return_discount_sno' => null,
                                    'purchase_no' => $purchaseOrder->purchase_no,
                                    'erp_purchase_type' => $purchaseOrder->type,
                                    'erp_purchase_no' => $purchaseOrder->erp_puchase_no,
                                    'erp_purchase_sno' => null,
                                    'poi_id' => $item->id,
                                    'product_model_id' => $item->product_model_id,
                                    'purchase_price' => $item->purchase_price,
                                    'quantity' => $ritem['qty'],
                                    'direct_shipment', $item->direct_shipment,
                                ]);
                                foreach($item->exportPackage as $package){
                                    $sno = str_pad($c,4,'0',STR_PAD_LEFT);
                                    $useQty = $package->quantity / $item->quantity;
                                    $quantity += $ritem['qty'] * $useQty;
                                    if($erpVendor->MA044 ==2){
                                        $package->purchase_price = $package->purchase_price / 1.05;
                                    }
                                    $amount += $ritem['qty'] * $useQty * $package->purchase_price;
                                    $packageAmount = $ritem['qty'] * $useQty * $package->purchase_price;
                                    if($erpVendor->MA044 == 1){
                                        $packageOrigin = $packageAmount / 1.05;
                                        $packageTax = $packageOrigin * 0.05;
                                    }elseif($erpVendor->MA044 == 2){
                                        $packageOrigin = $packageAmount / 1.05;
                                        $packageTax = $packageAmount - $packageOrigin;
                                    }else{
                                        $packageOrigin = $packageAmount;
                                        $packageTax = $packageOrigin * 0.05;
                                    }
                                    $purchaseSingleItem = PurchaseOrderItemSingleDB::where([['purchase_no',$package->purchase_no],['poi_id', $item->id],['poip_id',$package->id]])->first();
                                    $erpPurchaseItem = ErpPURTDDB::where([['TD002',$purchaseSingleItem->erp_purchase_no],['TD003',$purchaseSingleItem->erp_purchase_sno],['TD004',$package->digiwin_no]])->first();
                                    if($ritem['close'] == 1){
                                        ErpPURTDDB::where([['TD002',$purchaseSingleItem->erp_purchase_no],['TD003',$purchaseSingleItem->erp_purchase_sno],['TD004',$package->digiwin_no]])->update(['TD016' => 'y']);
                                        $package->update(['is_close' => 1]);
                                        $item->update(['is_close' => 1]);
                                        $purchaseSingleItem->update(['is_close' => 1]);
                                    }
                                    $returnDiscountItemPackage = ReturnDiscountItemPackageDB::create([
                                        'return_discount_item_id' => $returnDiscountItem->id,
                                        'return_discount_no' => $returnDiscountNo,
                                        'erp_return_discount_no' => $erpReturnDiscountNo,
                                        'erp_return_discount_sno' => $sno,
                                        'purchase_no' => $purchaseOrder->purchase_no,
                                        'erp_purchase_type' => $purchaseOrder->type,
                                        'erp_purchase_no' => $purchaseOrder->erp_purchase_no,
                                        'erp_purchase_sno' => $purchaseSingleItem->erp_purchase_sno,
                                        'poi_id' => $item->id,
                                        'poip_id' => $package->id,
                                        'product_model_id' => $package->product_model_id,
                                        'purchase_price' => $package->purchase_price,
                                        'quantity' => $ritem['qty'] * $useQty,
                                        'direct_shipment', $package->direct_shipment,
                                    ]);
                                    $erpReturnItem = ErpPURTJDB::create([
                                        'COMPANY' => 'iCarry',
                                        'CREATOR' => $creator,
                                        'USR_GROUP' => 'DSC',
                                        'CREATE_DATE' => $createDate,
                                        'FLAG' => 1,
                                        'CREATE_TIME' => $createTime,
                                        'CREATE_AP' => 'iCarry',
                                        'CREATE_PRID' => 'PURI08',
                                        'TJ001' => $type, //單別
                                        'TJ002' => $erpReturnDiscountNo, //單號
                                        'TJ003' => $sno, //序號
                                        'TJ004' => $package->digiwin_no, //品號
                                        'TJ005' => mb_substr($package->product_name,0,110,'utf8'), //品名
                                        'TJ006' => $package->serving_size, //規格
                                        'TJ007' => $package->unit_name, //單位
                                        'TJ008' => $package->purchase_price, //單價
                                        'TJ009' => $ritem['qty'] * $useQty, //數量
                                        'TJ010' => round($packageAmount,0), //金額
                                        'TJ011' => $TJ011, //退貨庫別
                                        'TJ016' => $erpPurchaseItem->TD001, //原採購單別
                                        'TJ017' => $erpPurchaseItem->TD002, //原採購單號
                                        'TJ018' => $erpPurchaseItem->TD003, //原採購序號
                                        'TJ019' => $package->memo, //備註
                                        'TJ020' => 'N', //確認碼
                                        'TJ021' => 'N', //結帳碼
                                        'TJ022' => 0, //庫存數量
                                        'TJ028' => 'N', //更新碼
                                        'TJ030' => round($packageOrigin,0), //原幣未稅金額
                                        'TJ031' => round($packageTax,0), //原幣稅額
                                        'TJ032' => round($packageOrigin,0), //本幣未稅金額
                                        'TJ033' => round($packageTax,0), //本幣稅額
                                        'TJ034' => $ritem['qty'] * $useQty, //計價數量
                                        'TJ035' => $package->unit_name, //計價單位
                                    ]);
                                    $c++;
                                }
                            }else{
                                $quantity += $ritem['qty'];
                                if($erpVendor->MA044 == 2){
                                    $item->purchase_price = $item->purchase_price / 1.05;
                                }
                                $amount += $ritem['qty'] * $item->purchase_price;
                                $itemAmount = $ritem['qty'] * $item->purchase_price;
                                if($erpVendor->MA044 == 1){
                                    $itemOrigin = $itemAmount / 1.05;
                                    $itemTax = $itemOrigin * 0.05;
                                }elseif($erpVendor->MA044 == 2){
                                    $itemOrigin = $itemAmount / 1.05;
                                    $itemTax = $itemAmount - $itemOrigin;
                                }else{
                                    $itemOrigin = $itemAmount;
                                    $itemTax = $itemOrigin * 0.05;
                                }
                                $sno = str_pad($c,4,'0',STR_PAD_LEFT);
                                $purchaseSingleItem = PurchaseOrderItemSingleDB::where([['purchase_no',$item->purchase_no],['poi_id', $item->id],['poip_id',null]])->first();
                                $erpPurchaseItem = ErpPURTDDB::where([['TD002',$purchaseSingleItem->erp_purchase_no],['TD003',$purchaseSingleItem->erp_purchase_sno],['TD004',$item->digiwin_no]])->first();
                                if($ritem['close'] == 1){
                                    ErpPURTDDB::where([['TD002',$purchaseSingleItem->erp_purchase_no],['TD003',$purchaseSingleItem->erp_purchase_sno],['TD004',$item->digiwin_no]])->update(['TD016' => 'y']);
                                    $item->update(['is_close' => 1]);
                                    $purchaseSingleItem->update(['is_close' => 1]);
                                }
                                $returnDiscountItem = ReturnDiscountItemDB::create([
                                    'return_discount_no' => $returnDiscountNo,
                                    'erp_return_discount_no' => $erpReturnDiscountNo,
                                    'erp_return_discount_sno' => $sno,
                                    'purchase_no' => $purchaseOrder->purchase_no,
                                    'erp_purchase_type' => $erpPurchaseItem->TD001,
                                    'erp_purchase_no' => $erpPurchaseItem->TD002,
                                    'erp_purchase_sno' => $erpPurchaseItem->TD003,
                                    'poi_id' => $item->id,
                                    'product_model_id' => $item->product_model_id,
                                    'purchase_price' => $item->purchase_price,
                                    'quantity' => $ritem['qty'],
                                    'direct_shipment', $item->direct_shipment,
                                ]);
                                $erpReturnItem = ErpPURTJDB::create([
                                    'COMPANY' => 'iCarry',
                                    'CREATOR' => $creator,
                                    'USR_GROUP' => 'DSC',
                                    'CREATE_DATE' => $createDate,
                                    'FLAG' => 1,
                                    'CREATE_TIME' => $createTime,
                                    'CREATE_AP' => 'iCarry',
                                    'CREATE_PRID' => 'PURI08',
                                    'TJ001' => $type, //單別
                                    'TJ002' => $erpReturnDiscountNo, //單號
                                    'TJ003' => $sno, //序號
                                    'TJ004' => $item->digiwin_no, //品號
                                    'TJ005' => mb_substr($item->product_name,0,110,'utf8'), //品名
                                    'TJ006' => $item->serving_size, //規格
                                    'TJ007' => $item->unit_name, //單位
                                    'TJ008' => $item->purchase_price, //單價
                                    'TJ009' => $ritem['qty'], //數量
                                    'TJ010' => round($itemAmount,0), //金額
                                    'TJ011' => $TJ011, //退貨庫別
                                    'TJ016' => $erpPurchaseItem->TD001, //原採購單別
                                    'TJ017' => $erpPurchaseItem->TD002, //原採購單號
                                    'TJ018' => $erpPurchaseItem->TD003, //原採購序號
                                    'TJ019' => $item->memo, //備註
                                    'TJ020' => 'N', //確認碼
                                    'TJ021' => 'N', //結帳碼
                                    'TJ022' => 0, //庫存數量
                                    'TJ028' => 'N', //更新碼
                                    'TJ030' => round($itemOrigin,0), //原幣未稅金額
                                    'TJ031' => round($itemTax,0), //原幣稅額
                                    'TJ032' => round($itemOrigin,0), //本幣未稅金額
                                    'TJ033' => round($itemTax,0), //本幣稅額
                                    'TJ034' => $ritem['qty'], //計價數量
                                    'TJ035' => $item->unit_name, //計價單位
                                ]);
                                $c++;
                            }
                            //紀錄, 退貨只記錄item部分(單品或組合)
                            $log = PurchaseOrderChangeLogDB::create([
                                'purchase_no' => $purchaseOrder->purchase_no,
                                'admin_id' => $adminId,
                                'poi_id' => $item->id,
                                'sku' => $item->sku,
                                'digiwin_no' => $item->digiwin_no,
                                'product_name' => $item->product_name,
                                'status' => '退貨',
                                'quantity' => $ritem['qty'],
                                'price' => $item->purchase_price,
                                'date' => $item->vendor_arrival_date,
                            ]);
                        }
                    }
                }
            }
            //如果是1跟2的 要算稅額
            $tax = 0;
            if(!empty($erpVendor) && ($erpVendor->MA044 == 1 || $erpVendor->MA044 == 2)){
                if($erpVendor->MA044 == 1){
                    $amount = $amount / 1.05;
                    $tax = $amount * 0.05;
                }elseif($erpVendor->MA044 == 2){
                    $tax = $amount * 0.05;
                }
            }
            //中繼退貨折抵資料
            $return = ReturnDiscountDB::create([
                'return_discount_no' => $returnDiscountNo,
                'erp_return_discount_no' => $erpReturnDiscountNo,
                'type' => $type,
                'purchase_no' => $purchaseOrder->purchase_no,
                'vendor_id' => $purchaseOrder->vendor_id,
                'quantity' => $quantity,
                'amount' => round($amount,0),
                'tax' => round($tax,0),
                'memo' => mb_substr($param['memo'],0,250),
                'return_date' => $returnDate,
            ]);

            //erp退貨折抵資料
            $erpReturn = ErpPURTIDB::create([
                'COMPANY' => 'iCarry',
                'CREATOR' => $creator,
                'USR_GROUP' => 'DSC',
                'CREATE_DATE' => $createDate,
                'FLAG' => 1,
                'CREATE_TIME' => $createTime,
                'CREATE_AP' => 'iCarry',
                'CREATE_PRID' => 'PURI08',
                'TI001' => $type, //單別
                'TI002' => $erpReturnDiscountNo, //單號
                'TI003' => str_replace(['-','/'],['',''],$returnDate), //退貨日期
                'TI004' => $TI004, //供應廠商
                'TI005' => '001', //廠別
                'TI006' => 'NTD', //幣別
                'TI007' => 1, //匯率
                'TI008' => $erpVendor->MA030, //發票聯數
                'TI009' => $erpVendor->MA044, //課稅別
                'TI010' => 0, //列印次數
                'TI011' => round($amount,0), //原幣退貨金額
                'TI012' => mb_substr($param['memo'],0,250), //備註
                'TI013' => 'N', //確認碼
                'TI014' => str_replace(['-','/'],['',''],$returnDate), //單據日期
                'TI015' => round($tax,0), //原幣退貨稅額
                'TI016' => $erpVendor->MA003, //廠商全名
                'TI017' => $erpVendor->MA005, //統一編號
                'TI018' => null, //發票號碼
                'TI019' => 1, //扣抵區分
                'TI020' => 'N', //菸酒註記
                'TI021' => 0, //件數
                'TI022' => $quantity, //數量合計
                'TI023' => $createDate, //發票日期
                'TI024' => 'N', //產生分錄碼
                'TI025' => $createMonth, //申報年月
                'TI026' => null, //確認者
                'TI027' => 0.05, //營業稅率
                'TI028' => round($amount,0), //本幣退貨金額
                'TI029' => round($tax,0), //本幣退貨稅額
                'TI030' => 'N', //簽核狀態碼
                'TI031' => 0, //包裝數量合計
                'TI032' => 0, //傳送次數
                'TI033' => $erpVendor->MA055, //付款條件代號
            ]);

            //錢街案退貨更新
            !empty($purchaseOrder->acOrder) ? $purchaseOrder->acOrder->update(['stockin_return' => 1]) : '';
        }
        if($param['type'] == 'discount'){ //折抵
            $vendor = VendorDB::find($param['vendor_id']);
            !empty($vendor->digiwin_vendor_no) ? $erpVendor = ErpVendorDB::find($vendor->digiwin_vendor_no) : $erpVendor = ErpVendorDB::find('A'.str_pad($param['vendor_id'],5,'0',STR_PAD_LEFT));
            !empty($vendor->digiwin_vendor_no) ? $TI004 = $vendor->digiwin_vendor_no : $TI004 = 'A'.str_pad($param['vendor_id'],5,0,STR_PAD_LEFT);
            $data = $param['data'];
            sort($data);
            for($i=0;$i<count($data);$i++){
                $product = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                ->select([
                    $productModelTable.'.*',
                    $vendorTable.'.id as vendor_id',
                    $vendorTable.'.name as vendor_name',
                    $productTable.'.package_data',
                    $productTable.'.serving_size',
                    $productTable.'.unit_name',
                    $productTable.'.direct_shipment',
                    // $productTable.'.name as product_name',
                    DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                ])->find($data[$i]['product_model_id']);
                if(strstr($product->sku,'BOM')){ //組合商品
                    $returnDiscountItem = ReturnDiscountItemDB::create([
                        'return_discount_no' => $returnDiscountNo,
                        'erp_return_discount_no' => $erpReturnDiscountNo,
                        'product_model_id' => $data[$i]['product_model_id'],
                        'purchase_price' => $data[$i]['price'],
                        'quantity' => $data[$i]['quantity'],
                    ]);
                    if(!empty($product->package_data)){
                        $packages = json_decode(str_replace('	','',$product->package_data));
                        foreach($packages as $package){
                            if($product->sku == $package->bom){
                                $totalPrice = 0;
                                foreach($package->lists as $list){
                                    $listProduct = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                    ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                    ->where('sku',$list->sku)
                                    ->select([
                                        $productModelTable.'.*',
                                        $productTable.'.serving_size',
                                        $productTable.'.unit_name',
                                        $productTable.'.direct_shipment',
                                        $productTable.'.vendor_price',
                                        $productTable.'.price as product_price',
                                        $vendorTable.'.service_fee',
                                        DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                    ])->first();
                                    if($listProduct->vendor_price > 0 ){
                                        $listProduct->purchase_price = $listProduct->vendor_price;
                                    }else{
                                        if(!empty($listProduct->service_fee)){
                                            $listProduct->service_fee = str_replace('"percent":}','"percent":0}',$listProduct->service_fee);
                                            $tmp = json_decode($listProduct->service_fee);
                                            foreach($tmp as $t){
                                                if ($t->name == 'iCarry') {
                                                    $percent = $t->percent;
                                                    break;
                                                }
                                            }
                                            $listProduct->purchase_price = $listProduct->product_price - $listProduct->product_price * ( $percent / 100 );
                                        }
                                    }
                                    $totalPrice += $list->quantity * $listProduct->purchase_price;
                                }
                                $radio = $data[$i]['price'] / $totalPrice;
                                foreach($package->lists as $list){
                                    $itemQty = $data[$i]['quantity'] * $list->quantity;
                                    $quantity += $itemQty;
                                    $listProduct = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                    ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                    ->where('sku',$list->sku)
                                    ->select([
                                        $productModelTable.'.*',
                                        $productTable.'.serving_size',
                                        $productTable.'.unit_name',
                                        $productTable.'.direct_shipment',
                                        $productTable.'.vendor_price',
                                        $productTable.'.price as product_price',
                                        $vendorTable.'.service_fee',
                                        DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                    ])->first();
                                    if($listProduct->vendor_price > 0 ){
                                        $listProduct->purchase_price = $listProduct->vendor_price;
                                    }else{
                                        if(!empty($listProduct->service_fee)){
                                            $listProduct->service_fee = str_replace('"percent":}','"percent":0}',$listProduct->service_fee);
                                            $tmp = json_decode($listProduct->service_fee);
                                            foreach($tmp as $t){
                                                if ($t->name == 'iCarry') {
                                                    $percent = $t->percent;
                                                    break;
                                                }
                                            }
                                            $listProduct->purchase_price = $listProduct->product_price - $listProduct->product_price * ( $percent / 100 );
                                        }
                                    }
                                    $price = $listProduct->purchase_price * $radio;
                                    $sno = str_pad($c,4,'0',STR_PAD_LEFT);
                                    $returnDiscountItemPackage = ReturnDiscountItemPackageDB::create([
                                        'return_discount_item_id' => $returnDiscountItem->id,
                                        'return_discount_no' => $returnDiscountNo,
                                        'erp_return_discount_no' => $erpReturnDiscountNo,
                                        'erp_return_discount_sno' => $sno,
                                        'product_model_id' => $listProduct->id,
                                        'purchase_price' => $price,
                                        'quantity' => $itemQty,
                                    ]);
                                    $erpReturnItem = ErpPURTJDB::create([
                                        'COMPANY' => 'iCarry',
                                        'CREATOR' => $creator,
                                        'USR_GROUP' => 'DSC',
                                        'CREATE_DATE' => $createDate,
                                        'FLAG' => 1,
                                        'CREATE_TIME' => $createTime,
                                        'CREATE_AP' => 'iCarry',
                                        'CREATE_PRID' => 'PURI08',
                                        'TJ001' => $type, //單別
                                        'TJ002' => $erpReturnDiscountNo, //單號
                                        'TJ003' => $sno, //序號
                                        'TJ004' => $listProduct->digiwin_no, //品號
                                        'TJ005' => mb_substr($listProduct->product_name,0,110,'utf8'), //品名
                                        'TJ006' => $listProduct->serving_size, //規格
                                        'TJ007' => $listProduct->unit_name, //單位
                                        'TJ008' => $price, //單價
                                        'TJ009' => 0, //數量
                                        'TJ010' => round($itemQty * $itemQty,0), //金額
                                        'TJ011' => !empty($vendor->digiwin_vendor_no) ? 'W16' : ($listProduct->direct_shipment == 1 ? 'W02' : 'W01'), //退貨庫別
                                        'TJ019' => null, //備註
                                        'TJ020' => 'N', //確認碼
                                        'TJ021' => 'N', //結帳碼
                                        'TJ022' => 0, //庫存數量
                                        'TJ028' => 'N', //更新碼
                                        'TJ030' => round($price * $itemQty,0), //原幣未稅金額
                                        'TJ031' => round($price * $itemQty * 0.05,0), //原幣稅額
                                        'TJ032' => round($price * $itemQty,0), //本幣未稅金額
                                        'TJ033' => round($price * $itemQty * 0.05,0), //本幣稅額
                                        'TJ034' => $price, //計價數量
                                        'TJ035' => $listProduct->unit_name, //計價單位
                                    ]);
                                    $c++;
                                }
                            }
                        }
                    }
                }else{ //單品
                    $quantity += $data[$i]['quantity'];
                    $purchasePrice = $data[$i]['price']; //填寫的金額為含稅
                    if($erpVendor->MA044 == 2){
                        $purchasePrice = $purchasePrice / 1.05;
                    }
                    $itemAmount = $purchasePrice * $data[$i]['quantity'];
                    $amount += $itemAmount;
                    if($erpVendor->MA044 == 1){
                        $itemOrigin = $itemAmount / 1.05;
                        $itemTax = $itemOrigin * 0.05;
                    }elseif($erpVendor->MA044 == 2){
                        $itemOrigin = $itemAmount / 1.05;
                        $itemTax = $itemAmount - $itemOrigin;
                    }else{
                        $itemOrigin = $itemAmount;
                        $itemTax = $itemOrigin * 0.05;
                    }
                    $sno = str_pad($c,4,'0',STR_PAD_LEFT);
                    $returnDiscountItem = ReturnDiscountItemDB::create([
                        'return_discount_no' => $returnDiscountNo,
                        'erp_return_discount_no' => $erpReturnDiscountNo,
                        'erp_return_discount_sno' => $sno,
                        'product_model_id' => $data[$i]['product_model_id'],
                        'purchase_price' => $purchasePrice,
                        'quantity' => $data[$i]['quantity'],
                    ]);
                    $erpReturnItem = ErpPURTJDB::create([
                        'COMPANY' => 'iCarry',
                        'CREATOR' => $creator,
                        'USR_GROUP' => 'DSC',
                        'CREATE_DATE' => $createDate,
                        'FLAG' => 1,
                        'CREATE_TIME' => $createTime,
                        'CREATE_AP' => 'iCarry',
                        'CREATE_PRID' => 'PURI08',
                        'TJ001' => $type, //單別
                        'TJ002' => $erpReturnDiscountNo, //單號
                        'TJ003' => $sno, //序號
                        'TJ004' => $product->digiwin_no, //品號
                        'TJ005' => mb_substr($product->product_name,0,110,'utf8'), //品名
                        'TJ006' => $product->serving_size, //規格
                        'TJ007' => $product->unit_name, //單位
                        'TJ008' => $purchasePrice, //單價
                        'TJ009' => 0, //數量
                        'TJ010' => round($itemAmount,0), //金額
                        'TJ011' => !empty($vendor->digiwin_vendor_no) ? 'W16' : ($product->direct_shipment == 1 ? 'W02' : 'W01'), //退貨庫別
                        'TJ019' => null, //備註
                        'TJ020' => 'N', //確認碼
                        'TJ021' => 'N', //結帳碼
                        'TJ022' => 0, //庫存數量
                        'TJ028' => 'N', //更新碼
                        'TJ030' => round($itemOrigin,0), //原幣未稅金額
                        'TJ031' => round($itemTax,0), //原幣稅額
                        'TJ032' => round($itemOrigin,0), //本幣未稅金額
                        'TJ033' => round($itemTax,0), //本幣稅額
                        'TJ034' => $data[$i]['quantity'], //計價數量
                        'TJ035' => $product->unit_name, //計價單位
                    ]);
                    $c++;
                }
            }
            //如果是1跟2的 要算稅額
            $tax = 0;
            if(!empty($erpVendor) && ($erpVendor->MA044 == 1 || $erpVendor->MA044 == 2)){
                if($erpVendor->MA044 == 1){
                    $amount = $amount / 1.05;
                    $tax = $amount * 0.05;
                }elseif($erpVendor->MA044 == 2){
                    $tax = $amount * 0.05;
                }
            }
            //中繼退貨折抵資料
            $return = ReturnDiscountDB::create([
                'return_discount_no' => $returnDiscountNo,
                'erp_return_discount_no' => $erpReturnDiscountNo,
                'type' => $type,
                'purchase_no' => null,
                'vendor_id' => $param['vendor_id'],
                'quantity' => 0,
                'amount' => round($amount,0),
                'tax' => round($tax,0),
                'memo' => mb_substr($param['memo'],0,250),
                'return_date' => $param['return_date'],
            ]);
            //erp退貨折抵資料
            $erpReturn = ErpPURTIDB::create([
                'COMPANY' => 'iCarry',
                'CREATOR' => $creator,
                'USR_GROUP' => 'DSC',
                'CREATE_DATE' => $createDate,
                'FLAG' => 1,
                'CREATE_TIME' => $createTime,
                'CREATE_AP' => 'iCarry',
                'CREATE_PRID' => 'PURI08',
                'TI001' => $type, //單別
                'TI002' => $erpReturnDiscountNo, //單號
                'TI003' => $returnDate, //退貨日期
                'TI004' => $TI004, //供應廠商
                'TI005' => '001', //廠別
                'TI006' => 'NTD', //幣別
                'TI007' => 1, //匯率
                'TI008' => $erpVendor->MA030, //發票聯數
                'TI009' => $erpVendor->MA044, //課稅別
                'TI010' => 0, //列印次數
                'TI011' => round($amount,0), //原幣退貨金額
                'TI012' => mb_substr($param['memo'],0,250), //備註
                'TI013' => 'N', //確認碼
                'TI014' => $returnDate, //單據日期
                'TI015' => round($tax,0), //原幣退貨稅額
                'TI016' => $erpVendor->MA003, //廠商全名
                'TI017' => $erpVendor->MA005, //統一編號
                'TI018' => null, //發票號碼
                'TI019' => 1, //扣抵區分
                'TI020' => 'N', //菸酒註記
                'TI021' => 0, //件數
                'TI022' => 0, //數量合計
                'TI023' => $createDate, //發票日期
                'TI024' => 'N', //產生分錄碼
                'TI025' => $createMonth, //申報年月
                'TI026' => null, //確認者
                'TI027' => 0.05, //營業稅率
                'TI028' => round($amount,0), //本幣退貨金額
                'TI029' => round($tax,0), //本幣退貨稅額
                'TI030' => 'N', //簽核狀態碼
                'TI031' => 0, //包裝數量合計
                'TI032' => 0, //傳送次數
                'TI033' => $erpVendor->MA055, //付款條件代號
            ]);
        }
    }
}
