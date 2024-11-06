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
use App\Models\iCarryProductUpdateRecord as ProductUpdateRecordDB;
use App\Models\iCarryProductQuantityRecord as ProductQuantityRecordDB; //gtin13修改記錄在這

use App\Models\ErpCustomer as ErpCustomerDB;
use App\Models\ErpOrder as ErpOrderDB;
use App\Models\ErpOrderItem as ErpOrderItemDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\ErpCOPTG as ErpCOPTGDB;
use App\Models\ErpCOPTH as ErpCOPTHDB;

use App\Models\Sell as SellDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use App\Models\SellImport as SellImportDB;
use App\Models\StockinImport as StockinImportDB;
use App\Models\OrderShipping as OrderShippingDB;
use App\Models\SellAbnormal as SellAbnormalDB;
use App\Models\SpecialVendor as SpecialVendorDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;

use Carbon\Carbon;
use DB;

use App\Imports\WarehouseShipImport;
use App\Imports\VendorDirectShipImport;
use App\Traits\UniversalFunctionTrait;
use App\Traits\OrderFunctionTrait;

class SellFileImportJob implements ShouldQueue
{
    use OrderFunctionTrait,Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UniversalFunctionTrait;
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
        if($param['test'] == true){
            $result['test'] = $param['test'];
            $result['type'] = $param['type'];
            $result['import_no'] = $param['import_no'];
            $result['admin_id'] = $param['admin_id'];
            return $result;
        }
        if($param['type'] == 'directShip'){
            $result = Excel::toArray(new VendorDirectShipImport, $param['filename']); //0代表第一個sheet
            if(count($result) == 1){
                if(count($result[0][0]) == 19){
                    return $this->resultImport($result[0]);
                }else{
                    return 'rows error';
                }
            }else{
                return 'sheets error';
            }
        }elseif($param['type'] == 'warehouse'){
            $chk = 0;
            $result = Excel::toArray(new WarehouseShipImport, $param['filename']);
            if(count($result) == 1){
                for($i=0;$i<count($result);$i++){
                    if(count($result[$i][0]) != 67){
                        $chk++;
                    }
                }
                if($chk == 0){
                    return $this->resultImport($result[0]);
                }else{
                    return 'rows error';
                }
            }else{
                return 'sheets error';
            }
        }
    }

    private function resultImport($result)
    {
        $param = $this->param;
        if($param['test'] == true){
            $result['type'] = $param['type'];
            $result['import_no'] = $param['import_no'];
            $result['admin_id'] = $param['admin_id'];
            return $result;
        }
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $newResult = $importData = [];
        if($param['type'] == 'warehouse'){
            //把相同訂單的相同商品合併起來找出新的數量
            $data = collect($result)->groupBy(7)->all();
            $i = 0;
            foreach($data as $oNumber => $value ){
                $tmp = collect($value)->groupBy(51)->all();
                foreach($tmp as $sku => $tmps){
                    $qty = 0;
                    foreach($tmps as $item){
                        $qt = (INT)$item[63];
                        $qt <= 0 || $qt == null || $qt == '' ? $qt = 0 : '';
                        $qty += $qt;
                    }
                    $newResult[$i] = $tmps[0];
                    $newResult[$i][63] = $qty;
                    $i++;
                }
            }
        }else{
            $newResult = $result;
        }
        if(count($newResult) > 0){
            for($j=0;$j<count($newResult);$j++){
                if($this->chkData($newResult[$j]) == true){
                    $productModelId = $vendorId = $stockinTime = $purchaseNo = $digiwinNo = $gtin13 = $sellDate = $status = $memo = null;
                    $chkMerge = 0;
                    if($param['type'] == 'warehouse'){
                        $orderNumber = (INT)explode('-',$newResult[$j][7])[0];
                        $shippingNumber = $newResult[$j][8];
                        $gtin13 = $this->strClean($newResult[$j][51]);
                        $productName = $newResult[$j][53];
                        $quantity = (INT)$newResult[$j][63];
                        $quantity <= 0 || $quantity == null || $quantity == '' ? $quantity = 0 : '';
                        $sellDate = str_replace('-','',str_replace('/','-',explode(' ',$newResult[$j][46])[0]));
                        empty($shippingNumber) ? $memo .= "物流單號未填寫。" : '';
                        $sellImport = SellImportDB::where([['order_number',$orderNumber],['type',$param['type']],['shipping_number',$shippingNumber],['gtin13',$gtin13]])->first();
                        if(!empty($gtin13)){
                            $productModel = ProductModelDB::where(function($query)use($gtin13){
                                $query = $query->where('gtin13',"$gtin13")->orWhere('sku',"$gtin13")->orWhere('digiwin_no',"$gtin13");
                            })->first();
                            empty($productModel) ? $memo .= "商品條碼錯誤，找不到商品。" : '';
                        }else{
                            $memo .= "商品條碼未填寫。";
                        }
                    }elseif($param['type'] == 'directShip'){
                        $orderNumber = (INT)$newResult[$j][13];
                        $shippingVendor = $newResult[$j][16];
                        $shippingNumber = $newResult[$j][17];
                        $purchaseNo = $newResult[$j][0];
                        $productName = $newResult[$j][3];
                        $quantity = (INT)$newResult[$j][5];
                        $digiwinNo = $this->strClean($newResult[$j][2]);
                        !empty($newResult[$j][1]) ? $stockinTime = $newResult[$j][1].' '.date('H:i:s') : '';
                        $sellDate = $newResult[$j][18];
                        $quantity <= 0 || $quantity == null || $quantity == '' ? $quantity = 0 : '';
                        empty($shippingNumber) ? $memo .= "物流單號未填寫。" : '';
                        empty($shippingVendor) ? $memo .= "物流商未填寫。" : '';
                        !empty($shippingVendor) && !empty($shippingNumber) ? $shippingNumber = $shippingVendor.'_'.$shippingNumber : $shippingNumber = null;
                        $sellImport = SellImportDB::where([['order_number',$orderNumber],['type',$param['type']],['shipping_number',$shippingVendor.'_'.$shippingNumber],['purchase_no',$purchaseNo],['digiwin_no',$digiwinNo]])->first();
                        if(!empty($purchaseNo)){
                            if(!empty($digiwinNo)){
                                $productModel = ProductModelDB::where('digiwin_no',$digiwinNo)->first();
                                if(!empty($productModel)){
                                    $purchaseOrderItem = PurchaseOrderItemDB::where([['direct_shipment',1],['purchase_no',$purchaseNo],['product_model_id',$productModel->id],['is_del',0],['is_lock',0]])->first();
                                    empty($purchaseOrderItem) ? $memo .= "找不到對應採購單資料。" : '';
                                }else{
                                    $memo .= "鼎新貨號錯誤，找不到商品。";
                                }
                            }else{
                                $memo .= "鼎新貨號未填寫。";
                            }
                        }else{
                            $memo .= "採購單號未填寫。";
                        }
                    }
                    $sellDate == '' ? $sellDate = null : '';
                    if(!empty($sellDate)){
                        if(!is_numeric($sellDate) || strlen($sellDate) != 8){
                            $memo .="出貨日期格式錯誤。(8個數字)";
                            $sellDate = null;
                        }else{
                            $sellDate = str_replace(['-','/'],['',''],$sellDate);
                            $sellDate = substr($sellDate,0,4).'-'.substr($sellDate,4,2).'-'.substr($sellDate,6,2);
                            if ($this->chkDate($sellDate) == false) {
                                $memo .= "出貨日期 $sellDate 錯誤。";
                                $sellDate = null;
                            }
                        }
                    }else{
                        $memo .= "出貨日期未填寫。";
                    }
                    if(!empty($orderNumber)){
                        $order = OrderDB::with('syncedOrder','items')->where('order_number',$orderNumber)->first();
                        $order = $this->oneOrderItemTransfer($order);
                        !empty($order->merge_order) ? $chkMerge = 1 : $chkMerge = 0; //檢查是否合併訂單
                        if(!empty($order)){
                            $erpCustomer = $order->erpCustomer;
                            $digiwinPaymentId = $order->digiwin_payment_id;
                            empty($erpCustomer) ? $memo .= "$digiwinPaymentId 客戶資料不存在於鼎新中。" : '';
                            !empty($order->syncedOrder) ? $erpOrder = ErpOrderDB::with('items')->where('TC002',$order->syncedOrder->erp_order_no)->first() : $erpOrder = null;
                            if($param['type'] == 'directShip'){
                                if(count($order->items) > 0){
                                    $chkItem = 0;
                                    foreach($order->items as $item){
                                        $item->direct_shipment == 1 && $item->is_del == 0 && $item->digiwin_no == $digiwinNo ? $chkItem++ : '';
                                    }
                                    $chkItem == 0 ? $memo .= "$digiwinNo 不存在於 $orderNumber 訂單中。" : '';
                                }
                            }else{
                                if($chkMerge == 0){
                                    if(!empty($erpOrder)){
                                        if(count($erpOrder->items) > 0){
                                            $chkErpItem = 0;
                                            if($param['type'] == 'warehouse'){
                                                foreach($erpOrder->items as $erpItem){
                                                    $productModel = ProductModelDB::where('digiwin_no',$erpItem->TD004)->first();
                                                    if(!empty($productModel)){
                                                        if($gtin13 == $productModel->gtin13 || $gtin13 == $productModel->sku){
                                                            $chkErpItem++;
                                                        }
                                                    }
                                                }
                                                $chkErpItem == 0 ? $memo .= "$gtin13 商品不存在於鼎新同步資料中。" : '';
                                            }else{
                                                //直寄有可能是組合品,須將商品拆成單品來檢查鼎新內單品
                                                foreach($order->items as $item){
                                                    if($item->direct_shipment == 1){
                                                        if(strstr($item->sku,'BOM')){
                                                            foreach($item->package as $package){
                                                                foreach($erpOrder->items as $erpItem){
                                                                    $erpItem->TD007 == 'W02' && $erpItem->TD004 == $package->digiwin_no ? $chkErpItem++ : '';
                                                                }
                                                            }
                                                        }else{
                                                            foreach($erpOrder->items as $erpItem){
                                                                $erpItem->TD007 == 'W02' && $erpItem->TD004 == $item->digiwin_no ? $chkErpItem++ : '';
                                                            }
                                                        }
                                                    }
                                                }
                                                $chkErpItem == 0 ? $memo .= "$digiwinNo 商品不存在於鼎新同步資料中。" : '';
                                            }
                                        }else{
                                            $memo .= "$orderNumber 訂單所有商品不存在於鼎新中，忘記同步？";
                                        }
                                    }else{
                                        $memo .= "$orderNumber 訂單不存在於鼎新中，忘記同步？";
                                    }
                                }
                            }
                            if($param['type'] == 'warehouse'){
                                $productModels = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                ->select([
                                    $productModelTable.'.*',
                                    $vendorTable.'.id as vendor_id',
                                    $vendorTable.'.name as vendor_name',
                                ])->where($productModelTable.'.gtin13',"$gtin13")->get();
                                if(count($productModels) <= 0){
                                    $tmp = ProductQuantityRecordDB::where('before_gtin13',"$gtin13")->orwhere('after_gtin13',"$gtin13")->orderBy('create_time','desc')->get();
                                    if(count($tmp) > 0){
                                        foreach($tmp as $t){
                                            if( $t->before_gtin13 == $gtin13 || $t->after_gtin13 == $gtin13 ){
                                                $productModelId = $t->product_model_id;
                                                break;
                                            }
                                        }
                                        $productModels = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                        ->select([
                                            $productModelTable.'.*',
                                            $vendorTable.'.id as vendor_id',
                                            $vendorTable.'.name as vendor_name',
                                        ])->where($productModelTable.'.id',$productModelId)->get();
                                    }
                                }
                                if(count($productModels) <= 0){
                                    $productModels = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                    ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                    ->select([
                                        $productModelTable.'.*',
                                        $vendorTable.'.id as vendor_id',
                                        $vendorTable.'.name as vendor_name',
                                    ])->where($productModelTable.'.sku',"$gtin13")->get();
                                }
                            }else{
                                $productModels = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                ->select([
                                    $productModelTable.'.*',
                                    $vendorTable.'.id as vendor_id',
                                    $vendorTable.'.name as vendor_name',
                                ])->where($productModelTable.'.digiwin_no',$digiwinNo)->get();
                            }
                            if(count($productModels) > 0){
                                $chkpm = 0;
                                foreach($productModels as $pm){
                                    //找出對應的訂單商品及數量
                                    $totalSellQty = $totalRequireQty = 0; //已沖銷數量, 原始需求數量
                                    if($param['type'] == 'warehouse'){
                                        if($chkMerge == 0){ //合併訂單不檢查數量
                                            foreach($order->items as $item){
                                                if($item->direct_shipment != 1){
                                                    if(strstr($item->sku,'BOM')){
                                                        foreach($item->package as $package){
                                                            if($package->product_model_id == $pm->id){
                                                                $vendorId = $pm->vendor_id;
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
                                                            $vendorId = $pm->vendor_id;
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
                                            if($chkpm > 0){
                                                $needQty = ($totalRequireQty - $totalSellQty);
                                                if($quantity > $needQty){
                                                    if($param['type'] == 'warehouse'){
                                                        $memo .= "商品 $gtin13 ，此次出庫數量 $quantity 大於應出數量 $needQty 。";
                                                    }else{
                                                        $memo .= "商品 $digiwinNo ，此次出庫數量 $quantity 大於應出數量 $needQty 。";
                                                    }
                                                }
                                                break;
                                            }
                                        }
                                    }else{
                                        //直寄不需要比對組合內的單品
                                        foreach($order->items as $item){
                                            if($item->direct_shipment == 1){
                                                if($item->product_model_id == $pm->id){
                                                    $chkpm++;
                                                    $vendorId = $pm->vendor_id;
                                                    $totalRequireQty += $item->quantity;
                                                    if(count($item->sells) > 0){
                                                        foreach($item->sells as $sell){
                                                            $totalSellQty += $sell->sell_quantity;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        if($chkpm > 0){
                                            $needQty = ($totalRequireQty - $totalSellQty);
                                            if($quantity > $needQty){
                                                if($param['type'] == 'warehouse'){
                                                    $memo .= "商品 $gtin13 ，此次出庫數量 $quantity 大於應出數量 $needQty 。";
                                                }else{
                                                    $memo .= "商品 $digiwinNo ，此次出庫數量 $quantity 大於應出數量 $needQty 。";
                                                }
                                            }
                                            break;
                                        }
                                    }
                                }
                                if($chkpm == 0 && $chkMerge == 0){
                                    if($param['type'] == 'warehouse'){
                                        $memo .= "商品 $gtin13 ，找不到對應 $orderNumber 訂單內商品。";
                                    }else{
                                        $memo .= "商品 $digiwinNo ，找不到對應 $orderNumber 訂單內商品。";
                                    }
                                }
                            }else{
                                if($param['type'] == 'warehouse'){
                                    $memo .= "$gtin13 商品不存在於iCarry中。";
                                }else{
                                    $memo .= "$digiwinNo 商品不存在於iCarry中。";
                                }
                            }
                            if(!empty($sellDate)){
                                //特殊廠商排除檢查日期
                                if($param['type'] == 'directShip'){
                                    $spVendors = SpecialVendorDB::where('vendor_id',$vendorId)->orderBy('code','asc')->first();
                                    if(empty($spVendors)){
                                        $sd = strtotime($sellDate);
                                        $vt = strtotime($order->vendor_arrival_date);
                                        $befor5days = strtotime(Carbon::create(date('Y',$vt), date('m',$vt), date('d',$vt))->addDays(-5));
                                        $after3days = strtotime(Carbon::create(date('Y',$vt), date('m',$vt), date('d',$vt))->addDays(3));
                                        if($sd < $befor5days || $sd > $after3days){
                                            $status = -1;
                                            $memo .= "出貨日期範圍錯誤。";
                                        }
                                    }
                                }
                            }
                        }else{
                            $memo .= "$orderNumber 訂單不存在。";
                        }
                    }else{
                        $memo .= "訂單號碼未填寫。";
                    }
                    $quantity <= 0 ? $memo .= "銷貨數量 $quantity 等於小於 0。" : '';
                    !empty($memo) ? $status = -1 : $status = 0;
                    if(empty($sellImport)){
                        $param['type'] == 'directShip' ? $gtin13 = null : $digiwinNo = null;
                        if($chkMerge == 1 && $param['type'] == 'warehouse'){
                            $mergeOrders = explode(',', $order->merge_order);
                            $mergeOrders = array_merge($mergeOrders, [$order->order_number]);
                            $orders = OrderDB::with('items','items.package','items.sells','items.package.sells')->whereIn('order_number',$mergeOrders)->get();
                            $remindQty = $quantity;
                            if( $remindQty > 0){
                                // if(count($importData) > 0){
                                //     for($x=0;$x<count($importData);$x++){
                                //         foreach($orders as $key => $order){
                                //             if($order->order_number == $importData[$x]['order_number']){
                                //                 foreach($order->items as $item){
                                //                     if($item->is_del == 0){
                                //                         if(strstr($item->sku,'BOM')){
                                //                             foreach($item->package as $package){
                                //                                 if($package->is_del == 0){
                                //                                     if($package->quantity == $importData[$x]['quantity']){
                                //                                         $orders->forget($key);
                                //                                     }
                                //                                 }
                                //                             }
                                //                         }else{
                                //                             if($item->quantity == $importData[$x]['quantity']){
                                //                                 $orders->forget($key);
                                //                             }
                                //                         }
                                //                     }
                                //                 }
                                //             }
                                //         }
                                //     }
                                // }
                                foreach($orders as $key => $order){
                                    $order = $this->oneOrderItemTransfer($order);
                                    $sellQty = $needQty = 0;
                                    foreach($order->items as $item){
                                        if($item->is_del == 0){
                                            if(strstr($item->sku,'BOM')){
                                                foreach($item->package as $package){
                                                    if($package->is_del == 0 && $gtin13 == $package->gtin13){
                                                        if(count($package->sells) > 0){
                                                            foreach($package->sells as $sells){
                                                                $sellQty += $sells->sell_quantity;
                                                            }
                                                        }
                                                        $needQty = $package->quantity - $sellQty;
                                                        $remindQty < $needQty ? $needQty = $remindQty : '';
                                                        $chkSellImport = SellImportDB::where([['order_number',$order->order_number],['type',$param['type']],['shipping_number',$shippingNumber],['gtin13',$gtin13]])->first();
                                                        if(empty($chkSellImport) && $needQty > 0){
                                                            $importData[] = [
                                                                'import_no' => $param['import_no'],
                                                                'type' => $param['type'],
                                                                'order_number' => $order->order_number,
                                                                'shipping_number' => $shippingNumber,
                                                                'gtin13' => $gtin13,
                                                                'purchase_no' => $purchaseNo,
                                                                'digiwin_no' => $digiwinNo,
                                                                'product_name' => $productName,
                                                                'quantity' => $needQty,
                                                                'sell_date' => $sellDate,
                                                                'stockin_time' => $stockinTime, //對應廠商到貨日給入庫用
                                                                'status' => $status,
                                                                'memo' => $memo,
                                                                'created_at' => date('Y-m-d H:i:s'),
                                                            ];
                                                        }
                                                        break;
                                                    }
                                                }
                                            }else{
                                                if($item->gtin13 == $gtin13){
                                                    if(count($item->sells) > 0){
                                                        foreach($item->sells as $sells){
                                                            $sellQty += $sells->sell_quantity;
                                                        }
                                                    }
                                                    $needQty = $item->quantity - $sellQty;
                                                    $remindQty < $needQty ? $needQty = $remindQty : '';
                                                    $chkSellImport = SellImportDB::where([['order_number',$order->order_number],['type',$param['type']],['shipping_number',$shippingNumber],['gtin13',$gtin13]])->first();
                                                    if(empty($chkSellImport) && $needQty > 0){
                                                        $importData[] = [
                                                            'import_no' => $param['import_no'],
                                                            'type' => $param['type'],
                                                            'order_number' => $order->order_number,
                                                            'shipping_number' => $shippingNumber,
                                                            'gtin13' => $gtin13,
                                                            'purchase_no' => $purchaseNo,
                                                            'digiwin_no' => $digiwinNo,
                                                            'product_name' => $productName,
                                                            'quantity' => $needQty,
                                                            'sell_date' => $sellDate,
                                                            'stockin_time' => $stockinTime, //對應廠商到貨日給入庫用
                                                            'status' => $status,
                                                            'memo' => $memo,
                                                            'created_at' => date('Y-m-d H:i:s'),
                                                        ];
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    $remindQty = $remindQty - $needQty;
                                    if($remindQty <= 0){
                                        break;
                                    }
                                }
                            }
                        }else{
                            $importData[] = [
                                'import_no' => $param['import_no'],
                                'type' => $param['type'],
                                'order_number' => $orderNumber,
                                'shipping_number' => $shippingNumber,
                                'gtin13' => $gtin13,
                                'purchase_no' => $purchaseNo,
                                'digiwin_no' => $digiwinNo,
                                'product_name' => $productName,
                                'quantity' => $quantity,
                                'sell_date' => $sellDate,
                                'stockin_time' => $stockinTime, //對應廠商到貨日給入庫用
                                'status' => $status,
                                'memo' => $memo,
                                'created_at' => date('Y-m-d H:i:s'),
                            ];
                        }
                    }
                }
            }
        }
        if(count($importData) > 0){
            if(count($importData) > 50){
                $items = array_chunk($importData,50);
                for($i=0;$i<count($items);$i++){
                    SellImportDB::insert($items[$i]);
                }
            }else{
                SellImportDB::insert($importData);
            }
            //為了去除掉檔案改用下面方式傳遞參數回去
            $resultParam['type'] = $param['type'];
            $resultParam['import_no'] = $param['import_no'];
            $resultParam['admin_id'] = $param['admin_id'];
            return $resultParam;
        }else{
            return 'no data';
        }
    }

    private function strClean($str)
    {
        $text = str_replace([' ','	'],['',''],$str);
        return $text;
    }

    private function chkData($result)
    {
        $count = count($result);
        $chk = 0;
        for($i=0;$i<count($result);$i++){
            empty($result[$i]) || $result[$i] == '' || $result[$i] == null ? $chk++ : '';
        }
        if($chk != count($result)){ //表示有資料
            return true;
        }else{ //表示全部空值
            return false;
        }
    }
}
