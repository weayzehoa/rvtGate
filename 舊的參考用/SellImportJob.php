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
use DB;

use App\Imports\WarehouseShipImport;
use App\Imports\VendorDirectShipImport;

use App\Jobs\PurchaseStockinImportJob;
use App\Jobs\CheckOrderSellAndCreateDigiwinSellJob;

use App\Traits\OrderFunctionTrait;

class SellImportJob implements ShouldQueue
{
    use OrderFunctionTrait,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
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
        $digiwinPaymentId = null;
        $erpSell = $sell = $erpPURTG = $erpPURTH = [];
        $createDate = date('Ymd');
        $createTime = date('H:i:s');
        $stockinFinishDate = date('Y-m-d');

        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        !empty($this->param['admin_id']) ? $adminId = $this->param['admin_id'] : $adminId = null;
        !empty(auth('gate')->user()->account) ? $creator = strtoupper(auth('gate')->user()->account) : $creator = 'DS';
        //找出中繼今日最後一筆銷貨單號碼的流水號
        $tmp = SellDB::where('sell_no','>=',date('ymd').'00001')->select('sell_no')->orderBy('sell_no','desc')->first();
        !empty($tmp) ? $lastSellNo = $tmp->sell_no : $lastSellNo = 0;
        $c = 1;
        $sells = SellImportDB::where('import_no',$this->param['import_no'])
        // $sells = SellImportDB::where('import_no',1659930778)
            ->select([
                '*',
                DB::raw("SUM(quantity) as quantity")
            ]);
        if($this->param['type'] == 'directShip'){
            $directShipment = 1;
            $sells = $sells->groupBy('order_number','shipping_number','digiwin_no')->orderBy('digiwin_no','asc')->get();
        }else{
            $directShipment = 0;
            $sells = $sells->groupBy('order_number','shipping_number','gtin13')->orderBy('gtin13','asc')->get();
        }
        // dd($sells);
        $sells = $sells->groupBy('order_number')->all();
        if(count($sells) > 0){
            foreach($sells as $orderNumber => $v){
                $chk = $totalQty = $amount = $tax = $itemTax = 0;
                $order = OrderDB::with('syncedOrder','items','items.sells','items.package','items.package.sells','items.syncedOrderItem','items.package.syncedOrderItemPackage')->where('order_number',$orderNumber)->first();
                if(!empty($this->param['test']) && $this->param['test'] == true){ //測試時pass所有檢查
                    $erpCustomer = $order->erpCustomer;
                    if(empty($erpCustomer)){
                        if(strlen($order->digiwin_payment_id) <= 2 ){
                            $digiwinPaymentId = str_pad($order->digiwin_payment_id,3,'0',STR_PAD_LEFT);
                            $order->update(['digiwin_payment_id' => $digiwinPaymentId]);
                            $erpCustomer = ErpCustomerDB::find($digiwinPaymentId);
                        }
                    }
                    $erpOrder = ErpOrderDB::with('items')->where('TC002',$order->syncedOrder->erp_order_no)->first();
                }else{
                    $shippingNumber = null;
                    $temp = $v->groupBy('shipping_number')->all();
                    foreach($temp as $shippingNum => $va){
                        foreach($va as $si){
                            $sellDate = $si->sell_date;
                            $shippingNumber = $shippingNum;
                        }
                    }
                    if(!empty($order)){
                        if($order->status != 2){
                            $chk++;
                            SellAbnormalDB::create([
                                'import_no' => $this->param['import_no'],
                                'order_id' => $order->id,
                                'order_number' => $orderNumber,
                                'direct_shipment' => $directShipment,
                                'sell_date' => $sellDate,
                                'shipping_memo' => $shippingNumber,
                                'memo' => "$orderNumber 訂單狀態非集貨中。",
                            ]);
                        }else{
                            $erpCustomer = $order->erpCustomer;
                            if(empty($erpCustomer)){
                                $chk++;
                                SellAbnormalDB::create([
                                    'import_no' => $this->param['import_no'],
                                    'order_id' => $order->id,
                                    'order_number' => $orderNumber,
                                    'direct_shipment' => $directShipment,
                                    'sell_date' => $sellDate,
                                    'shipping_memo' => $shippingNumber,
                                    'memo' => "$digiwinPaymentId 客戶資料不存在於鼎新中。",
                                ]);
                            }else{
                                if(empty($order->syncedOrder)){
                                    $chk++;
                                    SellAbnormalDB::create([
                                        'import_no' => $this->param['import_no'],
                                        'order_id' => $order->id,
                                        'order_number' => $orderNumber,
                                        'direct_shipment' => $directShipment,
                                        'sell_date' => $sellDate,
                                        'shipping_memo' => $shippingNumber,
                                        'memo' => "$orderNumber 訂單未同步至鼎新。",
                                    ]);
                                }else{
                                    $erpOrder = ErpOrderDB::with('items')->where('TC002',$order->syncedOrder->erp_order_no)->first();
                                    if(empty($erpOrder)){
                                        $chk++;
                                        SellAbnormalDB::create([
                                            'import_no' => $this->param['import_no'],
                                            'order_id' => $order->id,
                                            'order_number' => $orderNumber,
                                            'direct_shipment' => $directShipment,
                                            'sell_date' => $sellDate,
                                            'shipping_memo' => $shippingNumber,
                                            'memo' => "$orderNumber 訂單未同步至鼎新。",
                                        ]);
                                    }
                                }
                            }
                        }
                    }else{
                        $chk++;
                        SellAbnormalDB::create([
                            'import_no' => $this->param['import_no'],
                            'order_number' => $orderNumber,
                            'direct_shipment' => $directShipment,
                            'sell_date' => $sellDate,
                            'shipping_memo' => $shippingNumber,
                            'memo' => "$orderNumber 訂單不存在。",
                        ]);
                    }
                }
                if($chk == 0){ //檢查是否全部通過
                    $order = $this->orderItemSplit($this->oneOrderItemTransfer($order),'single');
                    $lastSellNo != 0 ? $sellNo = $lastSellNo + $c : $sellNo = date('ymd').str_pad($c,5,0,STR_PAD_LEFT);
                    $erpItems = $erpOrder->items;
                    $items = $order->items;
                    $i=1;
                    $tmp = $v->groupBy('shipping_number')->all();
                    $shipping = [];
                    foreach($tmp as $shippingNumber => $value){
                        $erpSellItems = $array2 = [];
                        foreach($value as $sellItem){
                            $gtin13 = $sellItem->gtin13;
                            $digiwinNo = $sellItem->digiwin_no;
                            $quantity = $sellItem->quantity;
                            $purchaseNo = $sellItem->purchase_no;
                            $sellDate = $sellItem->sell_date;
                            //銷貨處理
                            if(!empty($gtin13)){ //倉庫匯入
                                //資料庫搜尋時, 若為數字型態則須轉為數字才能完全找出所有資料
                                is_numeric($gtin13) ? $gtin13 = (INT)$gtin13 : '';
                                $productModels = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                    ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                    ->where($productModelTable.'.gtin13',$gtin13)
                                    ->select([
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
                                if(count($productModels) <= 0){
                                    $productModels = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                        ->where($productModelTable.'.sku',$gtin13)
                                        ->select([
                                            $productModelTable.'.*',
                                            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                    ])->get();
                                }
                                //確認商品資料正確
                                if(count($productModels) > 0){
                                    foreach($productModels as $pm){
                                        //找出對應的訂單商品及數量
                                        $chkpm = $totalSellQty = $totalRequireQty = 0; //已沖銷數量, 原始需求數量
                                        foreach($items as $item){
                                            if($item->direct_shipment != 1){
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
                                            foreach($items as $item){
                                                if($remindQty > 0 && $item->direct_shipment != 1){
                                                    if(strstr($item->sku,'BOM')){
                                                        foreach($item->package as $package){
                                                            if($package->product_model_id == $pm->id && !empty($package->syncedOrderItemPackage)){
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
                                                                if(count($erpOrderItems) > 0 && $remindQty > 0){
                                                                    foreach($erpOrderItems as $erpOrderItem){
                                                                        //找出中繼是否有出貨資料, 只會有一筆
                                                                        $sellItem = SellItemSingleDB::where([['erp_order_no',$erpOrderItem->TD002],['erp_order_sno',$erpOrderItem->TD003],['is_del',0]])->first();
                                                                        if(empty($sellItem)){
                                                                            $useQty += $erpOrderItem->TD008;
                                                                            $itemPrice = $erpOrderItem->TD011;
                                                                            $price = $quantity * $itemPrice;
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
                                                                            $sellItemSingle = [
                                                                                'sell_no' => $sellNo,
                                                                                'erp_sell_no' => null,
                                                                                'erp_sell_sno' => null,
                                                                                'erp_order_no' =>$erpOrderItem->TD002,
                                                                                'erp_order_sno' =>$erpOrderItem->TD003,
                                                                                'order_number' => $orderNumber,
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
                                                                            $remindQty = $remindQty - 1;
                                                                            $i++;
                                                                            SellItemSingleDB::create($sellItemSingle);
                                                                            if($x == $z || $remindQty == 0){
                                                                                break 2;
                                                                            }
                                                                            $z++;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }else{
                                                        if($item->product_model_id == $pm->id && !empty($item->syncedOrderItem)){
                                                            $erpOrderItem = ErpOrderItemDB::where([['TD002',$erpOrder->TC002],['TD003',$item->syncedOrderItem->erp_order_sno],['TD004',$item->digiwin_no]])->first();
                                                            if(!empty($erpOrderItem) && $remindQty > 0){
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
                                                                $totalQty += $quantity;
                                                                $sellItemSingle = [
                                                                    'sell_no' => $sellNo,
                                                                    'erp_sell_no' => null,
                                                                    'erp_sell_sno' => null,
                                                                    'erp_order_no' => $erpOrderItem->TD002,
                                                                    'erp_order_sno' => $erpOrderItem->TD003,
                                                                    'order_number' => $orderNumber,
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
                                                                $remindQty = $remindQty - $requiredQty;
                                                                SellItemSingleDB::create($sellItemSingle);
                                                                $i++;
                                                            }
                                                        }
                                                    }
                                                }else{
                                                    break 2;
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
                                            }
                                        }
                                    }
                                }else{
                                    SellAbnormalDB::create([
                                        'import_no' => $sellItem->import_no,
                                        'order_id' => $order->id,
                                        'order_number' => $order->order_number,
                                        'sell_date' => $sellDate,
                                        'shipping_memo' => $shippingNumber,
                                        'memo' => "商品 $gtin13 ，不存在於iCarry資料庫中。",
                                    ]);
                                }
                            }elseif(!empty($digiwinNo)){ //廠商直寄匯入
                                $importNo = time().rand(10,99);
                                $importNo = (INT)$importNo;
                                $productModel = ProductModelDB::where($productModelTable.'.digiwin_no',$digiwinNo)->first();
                                if(!empty($productModel)){
                                    //找出對應的訂單商品
                                    foreach($items as $item){
                                        if($item->direct_shipment == 1){
                                            if($item->is_del == 0 && $item->product_model_id == $productModel->id){
                                                $sellQty = $oRequireQty = 0; //已沖銷數量, 原始需求數量
                                                if(strstr($item,'BOM')){
                                                    foreach($item->package as $package){
                                                        //這邊要先算實際出貨數量
                                                        if(!empty($item->package_data)){
                                                            $packageData = json_decode(str_replace('	','',$item->package_data));
                                                            foreach($packageData as $pp){
                                                                if($item->sku == $pp->bom){
                                                                    foreach($pp->lists as $list){
                                                                        if($list->sku == $package->sku){
                                                                            $useQty = $list->quantity;
                                                                            $quantity = $useQty * $quantity;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        $oRequireQty = $package->quantity;
                                                        if(count($package->sells) > 0){
                                                            foreach($package->sells as $sell){
                                                                $sellQty += $sell->sell_quantity;
                                                            }
                                                        }
                                                        if($quantity <= ($oRequireQty - $sellQty)){
                                                            //由於鼎新內的訂單資料是被拆分的, 所以必須針對所有拆分的商品沖銷
                                                            $x = $quantity; //要沖的數量及筆數
                                                            $z = 1;
                                                            $useQty = 0;
                                                            $erpOrderItems = ErpOrderItemDB::where([['TD002',$erpOrder->TC002],['TD004',$package->digiwin_no]])->get();
                                                            if(count($erpOrderItems) > 0){
                                                                foreach($erpOrderItems as $erpOrderItem){
                                                                    //找出中繼是否有出貨資料
                                                                    $sellItemData = SellItemSingleDB::where([['erp_order_no',$erpOrderItem->TD002],['erp_order_sno',$erpOrderItem->TD003],['is_del',0]])->first();
                                                                    if(empty($sellItemData)){
                                                                        $useQty += $erpOrderItem->TD008;
                                                                        $price = $erpOrderItem->TD011 / 1.05;
                                                                        $itemTax = $price * 0.05;
                                                                        $amount += $price;
                                                                        $tax += $itemTax;
                                                                        $totalQty += $erpOrderItem->TD008;
                                                                        $sellItemSingle = [
                                                                            'sell_no' => $sellNo,
                                                                            'erp_sell_no' => null,
                                                                            'erp_sell_sno' => null,
                                                                            'erp_order_no' =>$erpOrderItem->TD002,
                                                                            'erp_order_sno' =>$erpOrderItem->TD003,
                                                                            'order_number' => $orderNumber,
                                                                            'order_item_id' => $item->id,
                                                                            'order_item_package_id' => $package->id,
                                                                            'order_quantity' => $package->quantity,
                                                                            'sell_quantity' => $erpOrderItem->TD008,
                                                                            'sell_date' => $sellDate,
                                                                            'sell_price' => round($erpOrderItem->TD012,0),
                                                                            'product_model_id' => $package->product_model_id,
                                                                            'memo' => $package->admin_memo,
                                                                            'direct_shipment' => 1,
                                                                            'express_way' => $item->shipping_memo,
                                                                            'express_no' => $shippingNumber,
                                                                            'created_at' => date('Y-m-d H:i:s'),
                                                                        ];
                                                                        $i++;
                                                                        SellItemSingleDB::create($sellItemSingle);
                                                                        if($x == $z){
                                                                            break;
                                                                        }
                                                                        $z++;
                                                                    }
                                                                }
                                                            }
                                                            $stockin = [
                                                                'import_no' => $importNo,
                                                                'warehouse_export_time' => date('Y-m-d H:i:s'),
                                                                'warehouse_stockin_no' => $sellNo,
                                                                'vendor_id' => $package->vendor_id,
                                                                'gtin13' => !empty($package->gtin13) ? $package->gtin13 : $package->digiwin_no,
                                                                'product_name' => $package->product_name,
                                                                'expected_quantity' => $package->quantity,
                                                                'stockin_quantity' => $quantity,
                                                                'stockin_time' => $sellItem->stockin_time,
                                                                'purchase_nos' => $sellItem->purchase_no,
                                                                'created_at' => date('Y-m-d H:i:s'),
                                                            ];
                                                            StockinImportDB::create($stockin);
                                                            $param = [
                                                                'import_no' => $importNo,
                                                                'shipping_date' => $sellDate,
                                                                'cate' => 'directShip',
                                                                'purchaseNos' => [$sellItem->purchase_no],
                                                            ];
                                                            PurchaseStockinImportJob::dispatchNow($param);
                                                        }else{
                                                            // dd('廠商直寄組合品異常');
                                                            $needQty = $oRequireQty - $sellQty;
                                                            $abnormalQty = $quantity - $needQty;
                                                            SellAbnormalDB::create([
                                                                'import_no' => $sellItem->import_no,
                                                                'order_id' => $order->id,
                                                                'order_number' => $order->order_number,
                                                                'erp_order_number' => $erpOrder->TC002,
                                                                'product_model_id' => $item->product_model_id,
                                                                'product_name' => $package->product_name,
                                                                'order_quantity' => $package->quantity,
                                                                'quantity' => $abnormalQty,
                                                                'direct_shipment' => 1,
                                                                'sell_date' => $sellDate,
                                                                'shipping_memo' => $shippingNumber,
                                                                'memo' => "商品 $digiwinNo ，此次出庫數量 $quantity 大於應出數量 $needQty ，請與倉庫或廠商確認數量後重新匯入此商品的出貨清單。",
                                                                'is_chk' => 0,
                                                            ]);
                                                        }
                                                    }
                                                }else{
                                                    $oRequireQty = $item->quantity;
                                                    if(count($item->sells) > 0){
                                                        foreach($item->sells as $sell){
                                                            $sellQty += $sell->sell_quantity;
                                                        }
                                                    }
                                                    if($quantity <= ($oRequireQty - $sellQty)){
                                                        //找出對應的訂單商品
                                                        $erpOrderItem = ErpOrderItemDB::where([['TD002',$erpOrder->TC002],['TD004',$item->digiwin_no]])->first();
                                                        //找出中繼是否有出貨資料
                                                        $sellItems = SellItemSingleDB::where([['erp_order_no',$erpOrderItem->TD002],['erp_order_sno',$erpOrderItem->TD003],['is_del',0]])->get();
                                                        $requiredQty = $item->quantity;
                                                        $itemPrice = $erpOrderItem->TD011;
                                                        $price = $quantity * $itemPrice;
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
                                                        if(count($sellItems) > 0){ //已出貨
                                                            if($requiredQty > $sellQty){
                                                                $amount += $priceWithoutTax;
                                                                $tax += $itemTax;
                                                                $totalQty += $quantity;
                                                                $sellItemSingle = [
                                                                    'sell_no' => $sellNo,
                                                                    'erp_sell_no' => null,
                                                                    'erp_sell_sno' => null,
                                                                    'erp_order_no' => $erpOrderItem->TD002,
                                                                    'erp_order_sno' => $erpOrderItem->TD003,
                                                                    'order_number' => $orderNumber,
                                                                    'order_item_id' => $item->id,
                                                                    'order_quantity' => $item->quantity,
                                                                    'sell_quantity' => $quantity,
                                                                    'sell_date' => $sellDate,
                                                                    'sell_price' => round($itemPrice,2),
                                                                    'product_model_id' => $item->product_model_id,
                                                                    'memo' => $item->admin_memo,
                                                                    'direct_shipment' => 1,
                                                                    'express_way' => $item->shipping_memo,
                                                                    'express_no' => $shippingNumber,
                                                                    'created_at' => date('Y-m-d H:i:s'),
                                                                ];
                                                                SellItemSingleDB::create($sellItemSingle);
                                                                $i++;
                                                            }
                                                        }else{ //尚未出貨
                                                            $amount += $priceWithoutTax;
                                                            $tax += $itemTax;
                                                            $totalQty += $quantity;
                                                            $sellItemSingle = [
                                                                'sell_no' => $sellNo,
                                                                'erp_sell_no' => null,
                                                                'erp_sell_sno' => null,
                                                                'erp_order_no' => $erpOrderItem->TD002,
                                                                'erp_order_sno' => $erpOrderItem->TD003,
                                                                'order_number' => $orderNumber,
                                                                'order_item_id' => $item->id,
                                                                'order_quantity' => $item->quantity,
                                                                'sell_quantity' => $quantity,
                                                                'sell_date' => $sellDate,
                                                                'sell_price' => round($itemPrice,2),
                                                                'product_model_id' => $item->product_model_id,
                                                                'memo' => $item->admin_memo,
                                                                'direct_shipment' => 1,
                                                                'express_way' => $item->shipping_memo,
                                                                'express_no' => $shippingNumber,
                                                                'created_at' => date('Y-m-d H:i:s'),
                                                            ];
                                                            SellItemSingleDB::create($sellItemSingle);
                                                            $i++;
                                                        }
                                                        $stockin = [
                                                            'import_no' => $importNo,
                                                            'warehouse_export_time' => date('Y-m-d H:i:s'),
                                                            'warehouse_stockin_no' => $sellNo,
                                                            'vendor_id' => $item->vendor_id,
                                                            'gtin13' => !empty($item->gtin13) ? $item->gtin13 : $item->digiwin_no,
                                                            'product_name' => $item->product_name,
                                                            'expected_quantity' => $item->quantity,
                                                            'stockin_quantity' => $quantity,
                                                            'stockin_time' => $sellItem->stockin_time,
                                                            'purchase_nos' => $sellItem->purchase_no,
                                                            'created_at' => date('Y-m-d H:i:s'),
                                                        ];
                                                        StockinImportDB::create($stockin);
                                                        $param = [
                                                            'import_no' => $importNo,
                                                            'cate' => 'directShip',
                                                            'shipping_date' => $sellDate,
                                                            'purchaseNos' => [$sellItem->purchase_no],
                                                        ];
                                                        PurchaseStockinImportJob::dispatchNow($param);
                                                        break;
                                                    }else{
                                                        // dd('廠商直寄單品異常');
                                                        $needQty = $oRequireQty - $sellQty;
                                                        $abnormalQty = $quantity - $needQty;
                                                        SellAbnormalDB::create([
                                                            'import_no' => $sellItem->import_no,
                                                            'order_id' => $order->id,
                                                            'order_number' => $order->order_number,
                                                            'erp_order_number' => $erpOrder->TC002,
                                                            'product_model_id' => $item->product_model_id,
                                                            'product_name' => $item->product_name,
                                                            'order_quantity' => $item->quantity,
                                                            'quantity' => $abnormalQty,
                                                            'direct_shipment' => 1,
                                                            'sell_date' => $sellDate,
                                                            'shipping_memo' => $shippingNumber,
                                                            'memo' => "商品 $digiwinNo ，此次出庫數量 $quantity 大於應出數量 $needQty ，請與倉庫或廠商確認數量後重新匯入此商品的出貨清單。",
                                                            'is_chk' => 0,
                                                        ]);
                                                    }
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }else{
                                    SellAbnormalDB::create([
                                        'import_no' => $sellItem->import_no,
                                        'order_id' => $order->id,
                                        'order_number' => $order->order_number,
                                        'sell_date' => $sellDate,
                                        'shipping_memo' => $shippingNumber,
                                        'memo' => "商品 $digiwinNo ，不存在於iCarry資料庫中。",
                                    ]);
                                }
                            }
                        }
                    }
                    $sellItemSingles = SellItemSingleDB::where([['sell_no',$sellNo],['order_number',$order->order_number],['is_del',0]])->get();
                    if(!empty($sellItemSingles) && count($sellItemSingles) > 0){
                        // 建立中繼銷貨單單頭
                        $sell = SellDB::create([
                            'sell_no' => $sellNo,
                            'erp_sell_no' => null,
                            'order_id' => $order->id,
                            'order_number' => $orderNumber,
                            'erp_order_number' => $erpOrder->TC002,
                            'quantity' => $totalQty,
                            'amount' => round($amount,0),
                            'tax' => round($tax,0),
                            'is_del' => 0,
                            'sell_date' => $sellDate,
                            'tax_type' => $erpOrder->TC016,
                            'memo' => null,
                        ]);
                        //檢查訂單是否全部已出貨並產生鼎新銷貨單
                        CheckOrderSellAndCreateDigiwinSellJob::dispatchNow($order);
                        $c++;
                    }
                }
            }
        }
    }
}
