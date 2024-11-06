<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\StockinImport as StockinImportDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseOrderItemPackage as PurchaseOrderItemPackageDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\ErpPURTG as ErpPURTGDB;
use App\Models\ErpPURTH as ErpPURTHDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\PurchaseOrderChangeLog as PurchaseOrderChangeLogDB;
use App\Imports\StockinImport;
use App\Imports\PurchaseOrderStockinImport;
use DB;

class AdminImportJob implements ShouldQueue
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
        if($param['cate'] == 'stockin'){
            $param['created_at'] = date('Y-m-d H:i:s');
            $param['import_no'] = time();
        }
        if($param['cate'] == 'directShip'){
            $this->purchaseNos = $param['purchaseNos'];
        }
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
        if($param['cate'] == 'stockin'){
            // Excel::import(new StockinImport($param), $param['filename']);
            $result = Excel::toArray(new PurchaseOrderStockinImport, $param['filename']); //0代表第一個sheet
            $this->stockImport($result);
        }
        if($param['cate'] == 'directShip'){
            $this->stockin();
        }
    }

    private function stockImport($result)
    {
        $tmp = $result[1]; //1代表第二個sheet
        foreach($tmp as $t){
            $purchaseNos[] = (INT)$t[0];
        }
        $param = $this->param;
        $this->purchaseNos = $purchaseNos;
        $i=0;
        $importData = $stockins = [];
        $warehouseExportTime = $result[0][0][1];
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemSingleTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemSingleDB)->getTable();
        foreach($result[0] as $stockin){
            if ($i>=1) {
                if (!empty($stockin[11]) && $stockin[11] > 0 && !empty($stockin[12]) && $stockin[14] == 'N') {
                    $chk = StockinImportDB::where([ ['warehouse_export_time',$warehouseExportTime], ['warehouse_stockin_no',$stockin[5]], ['gtin13',$stockin[7]] ])->first();
                    if(empty($chk)){
                        $productModel = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                        ->orWhere($productModelTable.'.gtin13',$stockin[7])
                        ->select([
                            $productTable.'.vendor_id',
                        ])->first();
                        if(!empty($productModel)){
                            $importData[] = [
                                'import_no' => $this->param['import_no'],
                                'warehouse_export_time' => $warehouseExportTime,
                                'warehouse_stockin_no' => $stockin[5],
                                'vendor_id' => $productModel->vendor_id,
                                'gtin13' => is_numeric($stockin[7]) ? (INT)$stockin[7] : $stockin[7],
                                'product_name' => $stockin[8],
                                'expected_quantity' => (INT)ceil($stockin[10]),
                                'stockin_quantity' => !empty($stockin[11]) ? (INT)ceil($stockin[11]) : 0,
                                'stockin_time' => !empty(ltrim($stockin[12],' ')) ? ltrim($stockin[12],' ') : null,
                                'purchase_nos' => join(',',$purchaseNos),
                                'created_at' => date('Y-m-d H:i:s'),
                            ];
                        }
                    }
                }
            }
            $i++;
        }
        if(!empty($importData)){
            StockinImportDB::insert($importData);
            $this->stockin();
        }
    }

    private function stockin()
    {
        $erpPURTG = [];
        $erpPURTH = [];
        $param = $this->param;
        $createDate = date('Ymd');
        $createTime = date('H:i:s');
        $stockinFinishDate = date('Y-m-d');
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemSingleTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemSingleDB)->getTable();
        !empty($this->param['admin_id']) ? $adminId = $this->param['admin_id'] : $adminId = null;
        !empty(auth('gate')->user()->account) ? $creator = strtoupper(auth('gate')->user()->account) : $creator = 'DS';

        //找出相同單據日期的最後一筆進貨單號碼的流水號
        if($param['cate'] == 'directShip'){
            $TG014 = str_replace('-','',$param['shipping_date']);
        }else{
            $chkDate = StockinImportDB::where('import_no',$this->param['import_no'])
            // $chkDate = StockinImportDB::where('import_no',1654074017)
            ->select([
                'stockin_time',
                DB::raw("DATE_FORMAT(stockin_time,'%Y%m%d') as stockinDate"),
            ])->groupBy('stockin_time')->first();
            $TG014 = $chkDate->stockinDate;
        }
        $six = substr($TG014,2);
        $TG002 = ErpPURTGDB::where('TG002','like',"%$six%")->select('TG002')->orderBy('TG002','desc')->first();
        !empty($TG002) ? $lastNo = $TG002->TG002 : $lastNo = 0;
        $stockins = StockinImportDB::where('import_no',$this->param['import_no'])->get();
        // $stockins = StockinImportDB::where('import_no',165891610746)->get();
        // dd($stockins);
        $stockins = $stockins->groupBy('vendor_id')->all();
        $c = 1;
        foreach($stockins as $vendorId => $value){
            $lastNo != 0 ? $erpStockinNo = $lastNo + $c : $erpStockinNo = $six.str_pad($c,5,0,STR_PAD_LEFT);
            $erpVendor = ErpVendorDB::find('A'.str_pad($vendorId,5,'0',STR_PAD_LEFT));
            $array1 = [
                'COMPANY' => 'iCarry',
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
                'TH014' => $TG014, //驗收日期
                'TH017' => 0, //驗退數量
                'TH020' => 0, //原幣扣款金額
                'TH024' => 0, //進貨費用
                'TH026' => 'N', //暫不付款
                'TH027' => 'N', //逾期碼
                'TH028' => 0, //檢驗狀態
                'TH029' => 'N', //驗退碼
                'TH030' => 'N', //確認碼
                'TH032' => 'N', //更新碼
                'TH033' => null, //備註
                'TH034' => 0, //庫存數量
                'TH038' => null, //確認者
                'TH039' => null, //應付憑單別
                'TH040' => null, //應付憑單號
                'TH041' => null, //應付憑單序號
                'TH042' => null, //專案代號
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
            ];
            $i = $totalTax = $totalPrice = $totalQuantity = 0;
            $array1 = [
                'COMPANY' => 'iCarry',
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
                'TH014' => $TG014, //驗收日期
                'TH017' => 0, //驗退數量
                'TH020' => 0, //原幣扣款金額
                'TH024' => 0, //進貨費用
                'TH026' => 'N', //暫不付款
                'TH027' => 'N', //逾期碼
                'TH028' => 0, //檢驗狀態
                'TH029' => 'N', //驗退碼
                'TH030' => 'N', //確認碼
                'TH032' => 'N', //更新碼
                'TH033' => null, //備註
                'TH034' => 0, //庫存數量
                'TH038' => null, //確認者
                'TH039' => null, //應付憑單別
                'TH040' => null, //應付憑單號
                'TH041' => null, //應付憑單序號
                'TH042' => null, //專案代號
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
            ];
            foreach($value as $stockin){
                $remain = 0;
                $purchaseNos = explode(',',$stockin->purchase_nos);
                $stockinQuantity = $stockin->stockin_quantity;
                $stockinDate = explode(' ',$stockin->stockin_time)[0];
                $productModels = ProductModelDB::where('gtin13',$stockin->gtin13)->orWhere('digiwin_no',$stockin->gtin13)->get();
                if(count($productModels) > 0){
                    //找出對應相同日期的採購商品
                    foreach($productModels as $productModel){
                        $items = PurchaseOrderItemSingleDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemSingleTable.'.product_model_id')
                        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                        ->whereIn($purchaseOrderItemSingleTable.'.purchase_no',$purchaseNos)
                        ->whereNotNull($purchaseOrderItemSingleTable.'.erp_purchase_no')
                        // ->where($purchaseOrderItemSingleTable.'.quantity','!=',StockinItemSingleDB::where('stockin_item_singles.pois_id',$purchaseOrderItemSingleTable.'.id')->sum('quantity'))
                        ->where([[$purchaseOrderItemSingleTable.'.product_model_id',$productModel->id],[$purchaseOrderItemSingleTable.'.vendor_arrival_date',$stockinDate],[$purchaseOrderItemSingleTable.'.is_del',0]])
                        ->where(function($query)use($purchaseOrderItemSingleTable){
                            $query->where($purchaseOrderItemSingleTable.'.is_close',0)->orWhereNull($purchaseOrderItemSingleTable.'.is_close');
                        })->select([
                            $purchaseOrderItemSingleTable.'.*',
                            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                            $productModelTable.'.sku',
                            $productModelTable.'.digiwin_no',
                        ])->orderBy($purchaseOrderItemSingleTable.'.vendor_arrival_date','asc')->orderBy($purchaseOrderItemSingleTable.'.purchase_no','asc')->get();

                        if(count($items) > 0){
                            if($param['cate'] == 'directShip'){
                                $stockinDate = $param['shipping_date'];
                            }
                            foreach($items as $item){
                                // dd($item->erpPurchaseItem);
                                if(!empty($item->erpPurchaseItem) && ($item->is_close == 0 || $item->is_close == null || $item->is_close == '')){ //已被結案則不能再進貨
                                    $stockinItemSingles = StockinItemSingleDB::where([['pois_id',$item->id],['purchase_no',$item->purchase_no]])->get();
                                    $oldStockinQuantity = 0;
                                    if(count($stockinItemSingles) > 0){
                                        foreach($stockinItemSingles as $stockinItemSingle){
                                            $oldStockinQuantity += $stockinItemSingle->stockin_quantity;
                                        }
                                    }
                                    $diffQty = $item->quantity - $oldStockinQuantity; //缺少的採購量
                                    $remain = $stockinQuantity - $diffQty; //這次進來的 扣掉 缺少的採購量 所剩數量
                                    if($remain > 0){
                                        StockinItemSingleDB::create([
                                            'purchase_no' => $item->purchase_no,
                                            'erp_purchase_no' => $item->erp_purchase_no,
                                            'erp_purchase_sno' => $item->erp_purchase_sno,
                                            'poi_id' => $item->poi_id,
                                            'poip_id' => $item->poip_id,
                                            'pois_id' => $item->id,
                                            'product_model_id' => $item->product_model_id,
                                            'erp_stockin_no' => $erpStockinNo,
                                            'erp_stockin_sno' => str_pad($i+1,4,'0',STR_PAD_LEFT),
                                            'stockin_quantity' => $diffQty,
                                            'quantity' => $item->quantity,
                                            'stockin_date' => $stockinDate,
                                            'purchase_price'=> $item->purchase_price,
                                            'is_del'=> $item->is_del,
                                            'is_close'=> $item->is_close,
                                        ]);
                                        $log = PurchaseOrderChangeLogDB::create([
                                            'purchase_no' => $item->purchase_no,
                                            'admin_id' => $adminId,
                                            'poi_id' => $item->poi_id,
                                            'poip_id' => $item->poip_id,
                                            'sku' => $item->sku,
                                            'digiwin_no' => $item->digiwin_no,
                                            'product_name' => $item->product_name,
                                            'status' => '入庫',
                                            'quantity' => $diffQty,
                                            'price' => $item->purchase_price,
                                            'date' => $stockinDate,
                                        ]);
                                        if($erpVendor->MA044 == 1){
                                            $purchasePrice = $item->purchase_price;
                                        }else{
                                            $purchasePrice = $item->purchase_price / 1.05;
                                        }
                                        $erpVendor->MA044 == 1 ? $TH045 = ($diffQty * $purchasePrice) / 1.05 : $TH045 = $diffQty * $purchasePrice;
                                        $erpVendor->MA044 == 1 ? $TH019 = $TH045 * 1.05 : $TH019 = $TH045;
                                        $erpVendor->MA044 == 1 ? $TH046 = ($diffQty * $purchasePrice) - $TH045 : $TH046 = ( $diffQty * $purchasePrice ) * 0.05;
                                        $array2 = [
                                            'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                                            'TH004' => $item->erpPurchaseItem->TD004, //品號
                                            'TH005' => $item->erpPurchaseItem->TD005, //品名
                                            'TH006' => $item->erpPurchaseItem->TD006, //規格
                                            'TH007' => $diffQty, //進貨數量
                                            'TH008' => $item->erpPurchaseItem->TD009, //單位
                                            'TH009' => $item->erpPurchaseItem->TD007, //庫別
                                            'TH011' => $item->erpPurchaseItem->TD001, //採購單別
                                            'TH012' => $item->erpPurchaseItem->TD002, //採購單號
                                            'TH013' => $item->erpPurchaseItem->TD003, //採購序號
                                            'TH015' => $diffQty, //驗收數量
                                            'TH016' => $diffQty, //計價數量
                                            'TH018' => round($purchasePrice,2), //原幣單位進價
                                            'TH019' => round($TH019,0), //原幣進貨金額
                                            'TH045' => round($TH045,0), //原幣未稅金額
                                            'TH046' => round($TH046,0), //原幣稅額
                                            'TH047' => round($TH045,0), //本幣未稅金額
                                            'TH048' => round($TH046,0), //本幣稅額
                                            'TH049' => $item->erpPurchaseItem->TD009, //計價單位
                                        ];
                                        $erpPURTH = array_merge($array1,$array2);
                                        ErpPURTHDB::create($erpPURTH);

                                        $totalQuantity += $diffQty;
                                        $erpVendor->MA044 == 1 ? $totalPrice += $TH045 + $TH046 : $totalPrice = $TH045;
                                        $totalTax += $TH046;
                                        $i++;
                                        continue;
                                    }elseif($remain == 0){
                                        StockinItemSingleDB::create([
                                            'purchase_no' => $item->purchase_no,
                                            'erp_purchase_no' => $item->erp_purchase_no,
                                            'erp_purchase_sno' => $item->erp_purchase_sno,
                                            'poi_id' => $item->poi_id,
                                            'poip_id' => $item->poip_id,
                                            'pois_id' => $item->id,
                                            'product_model_id' => $item->product_model_id,
                                            'erp_stockin_no' => $erpStockinNo,
                                            'erp_stockin_sno' => str_pad($i+1,4,'0',STR_PAD_LEFT),
                                            'stockin_quantity' => $diffQty,
                                            'quantity' => $item->quantity,
                                            'stockin_date' => $stockinDate,
                                            'purchase_price'=> $item->purchase_price,
                                            'is_del'=> $item->is_del,
                                            'is_close'=> $item->is_close,
                                        ]);
                                        $log = PurchaseOrderChangeLogDB::create([
                                            'purchase_no' => $item->purchase_no,
                                            'admin_id' => $adminId,
                                            'poi_id' => $item->poi_id,
                                            'poip_id' => $item->poip_id,
                                            'sku' => $item->sku,
                                            'digiwin_no' => $item->digiwin_no,
                                            'product_name' => $item->product_name,
                                            'status' => '入庫',
                                            'quantity' => $diffQty,
                                            'price' => $item->purchase_price,
                                            'date' => $stockinDate,
                                        ]);
                                        if($erpVendor->MA044 == 1){
                                            $purchasePrice = $item->purchase_price;
                                        }else{
                                            $purchasePrice = $item->purchase_price / 1.05;
                                        }
                                        $erpVendor->MA044 == 1 ? $TH045 = ($diffQty * $purchasePrice) / 1.05 : $TH045 = $diffQty * $purchasePrice;
                                        $erpVendor->MA044 == 1 ? $TH019 = $TH045 * 1.05 : $TH019 = $TH045;
                                        $erpVendor->MA044 == 1 ? $TH046 = ($diffQty * $purchasePrice) - $TH045 : $TH046 = ( $diffQty * $purchasePrice ) * 0.05;
                                        $array2 = [
                                            'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                                            'TH004' => $item->erpPurchaseItem->TD004, //品號
                                            'TH005' => $item->erpPurchaseItem->TD005, //品名
                                            'TH006' => $item->erpPurchaseItem->TD006, //規格
                                            'TH007' => $diffQty, //進貨數量
                                            'TH008' => $item->erpPurchaseItem->TD009, //單位
                                            'TH009' => $item->erpPurchaseItem->TD007, //庫別
                                            'TH011' => $item->erpPurchaseItem->TD001, //採購單別
                                            'TH012' => $item->erpPurchaseItem->TD002, //採購單號
                                            'TH013' => $item->erpPurchaseItem->TD003, //採購序號
                                            'TH015' => $diffQty, //驗收數量
                                            'TH016' => $diffQty, //計價數量
                                            'TH018' => round($purchasePrice,2), //原幣單位進價
                                            'TH019' => round($TH019,0), //原幣進貨金額
                                            'TH045' => round($TH045,0), //原幣未稅金額
                                            'TH046' => round($TH046,0), //原幣稅額
                                            'TH047' => round($TH045,0), //本幣未稅金額
                                            'TH048' => round($TH046,0), //本幣稅額
                                            'TH049' => $item->erpPurchaseItem->TD009, //計價單位
                                        ];
                                        $erpPURTH = array_merge($array1,$array2);
                                        ErpPURTHDB::create($erpPURTH);

                                        $totalQuantity += $diffQty;
                                        $erpVendor->MA044 == 1 ? $totalPrice += $TH045 + $TH046 : $totalPrice = $TH045;
                                        $totalTax += $TH046;
                                        $i++;
                                        break;
                                    }elseif($remain < 0){
                                        StockinItemSingleDB::create([
                                            'purchase_no' => $item->purchase_no,
                                            'erp_purchase_no' => $item->erp_purchase_no,
                                            'erp_purchase_sno' => $item->erp_purchase_sno,
                                            'poi_id' => $item->poi_id,
                                            'poip_id' => $item->poip_id,
                                            'pois_id' => $item->id,
                                            'product_model_id' => $item->product_model_id,
                                            'erp_stockin_no' => $erpStockinNo,
                                            'erp_stockin_sno' => str_pad($i+1,4,'0',STR_PAD_LEFT),
                                            'stockin_quantity' => $stockinQuantity,
                                            'quantity' => $item->quantity,
                                            'stockin_date' => $stockinDate,
                                            'purchase_price'=> $item->purchase_price,
                                            'is_del'=> $item->is_del,
                                            'is_close'=> $item->is_close,
                                        ]);
                                        $log = PurchaseOrderChangeLogDB::create([
                                            'purchase_no' => $item->purchase_no,
                                            'admin_id' => $adminId,
                                            'poi_id' => $item->poi_id,
                                            'poip_id' => $item->poip_id,
                                            'sku' => $item->sku,
                                            'digiwin_no' => $item->digiwin_no,
                                            'product_name' => $item->product_name,
                                            'status' => '入庫',
                                            'quantity' => $stockinQuantity,
                                            'price' => $item->purchase_price,
                                            'date' => $stockinDate,
                                        ]);
                                        if($erpVendor->MA044 == 1){
                                            $purchasePrice = $item->purchase_price;
                                        }else{
                                            $purchasePrice = $item->purchase_price / 1.05;
                                        }
                                        $erpVendor->MA044 == 1 ? $TH045 = ($stockinQuantity * $purchasePrice) / 1.05 : $TH045 = $stockinQuantity * $purchasePrice;
                                        $erpVendor->MA044 == 1 ? $TH019 = $TH045 * 1.05 : $TH019 = $TH045;
                                        $erpVendor->MA044 == 1 ? $TH046 = ($stockinQuantity * $purchasePrice) - $TH045 : $TH046 = ( $stockinQuantity * $purchasePrice ) * 0.05;
                                        $array2 = [
                                            'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                                            'TH004' => $item->erpPurchaseItem->TD004, //品號
                                            'TH005' => $item->erpPurchaseItem->TD005, //品名
                                            'TH006' => $item->erpPurchaseItem->TD006, //規格
                                            'TH007' => $stockinQuantity, //進貨數量
                                            'TH008' => $item->erpPurchaseItem->TD009, //單位
                                            'TH009' => $item->erpPurchaseItem->TD007, //庫別
                                            'TH011' => $item->erpPurchaseItem->TD001, //採購單別
                                            'TH012' => $item->erpPurchaseItem->TD002, //採購單號
                                            'TH013' => $item->erpPurchaseItem->TD003, //採購序號
                                            'TH015' => $stockinQuantity, //驗收數量
                                            'TH016' => $stockinQuantity, //計價數量
                                            'TH018' => round($purchasePrice,2), //原幣單位進價
                                            'TH019' => round($TH019,0), //原幣進貨金額
                                            'TH045' => round($TH045,0), //原幣未稅金額
                                            'TH046' => round($TH046,0), //原幣稅額
                                            'TH047' => round($TH045,0), //本幣未稅金額
                                            'TH048' => round($TH046,0), //本幣稅額
                                            'TH049' => $item->erpPurchaseItem->TD009, //計價單位
                                        ];
                                        $erpPURTH = array_merge($array1,$array2);
                                        ErpPURTHDB::create($erpPURTH);

                                        $totalQuantity += $stockinQuantity;
                                        $erpVendor->MA044 == 1 ? $totalPrice += $TH045 + $TH046 : $totalPrice = $TH045;
                                        $totalTax += $TH046;
                                        $i++;
                                        break;
                                    }
                                }
                            }
                            if($remain > 0){ //餘數大於0, 找之前沒有補的或者尚未進貨的
                                //找出未沖銷的採購單單品
                                $items = PurchaseOrderItemSingleDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemSingleTable.'.product_model_id')
                                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                ->whereIn($purchaseOrderItemSingleTable.'.purchase_no',$purchaseNos)
                                ->whereNotNull($purchaseOrderItemSingleTable.'.erp_purchase_no')
                                // ->where($purchaseOrderItemSingleTable.'.quantity','!=',StockinItemSingleDB::where('stockin_item_singles.pois_id',$purchaseOrderItemSingleTable.'.id')->sum('quantity'))
                                ->where([[$purchaseOrderItemSingleTable.'.product_model_id',$productModel->id],[$purchaseOrderItemSingleTable.'.is_del',0]])
                                ->where(function($query)use($purchaseOrderItemSingleTable){
                                    $query->where($purchaseOrderItemSingleTable.'.is_close',0)->orWhereNull($purchaseOrderItemSingleTable.'.is_close');
                                })->select([
                                    $purchaseOrderItemSingleTable.'.*',
                                    DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                    $productModelTable.'.sku',
                                    $productModelTable.'.digiwin_no',
                                    ])->orderBy($purchaseOrderItemSingleTable.'.vendor_arrival_date','asc')->orderBy($purchaseOrderItemSingleTable.'.purchase_no','asc')->get();
                                foreach($items as $item){
                                    //排除已經進貨的
                                    $stockinSingles = StockinItemSingleDB::where([['pois_id',$item->id],['purchase_no',$item->purchase_no]])->get();
                                    $alreadyStockinQuantity = 0;
                                    if(count($stockinSingles) > 0){
                                        foreach($stockinSingles as $stockinItemSingle){
                                            $alreadyStockinQuantity += $stockinItemSingle->stockin_quantity;
                                        }
                                    }
                                    if($item->quantity != $alreadyStockinQuantity){
                                        $diffQty = $item->quantity - $item->stockin_quantity;
                                        $remain -= $diffQty;
                                        if($item->is_close == 0 || $item->is_close == null || $item->is_close == ''){
                                            if($remain > 0){
                                                StockinItemSingleDB::create([
                                                    'purchase_no' => $item->purchase_no,
                                                    'erp_purchase_no' => $item->erp_purchase_no,
                                                    'erp_purchase_sno' => $item->erp_purchase_sno,
                                                    'poi_id' => $item->poi_id,
                                                    'poip_id' => $item->poip_id,
                                                    'pois_id' => $item->id,
                                                    'product_model_id' => $item->product_model_id,
                                                    'erp_stockin_no' => $erpStockinNo,
                                                    'erp_stockin_sno' => str_pad($i+1,4,'0',STR_PAD_LEFT),
                                                    'stockin_quantity' => $diffQty,
                                                    'quantity' => $item->quantity,
                                                    'stockin_date' => $stockinDate,
                                                    'purchase_price'=> $item->purchase_price,
                                                    'is_del'=> $item->is_del,
                                                    'is_close'=> $item->is_close,
                                                ]);
                                                $log = PurchaseOrderChangeLogDB::create([
                                                    'purchase_no' => $item->purchase_no,
                                                    'admin_id' => $adminId,
                                                    'poi_id' => $item->poi_id,
                                                    'poip_id' => $item->poip_id,
                                                    'sku' => $item->sku,
                                                    'digiwin_no' => $item->digiwin_no,
                                                    'product_name' => $item->product_name,
                                                    'status' => '入庫',
                                                    'quantity' => $diffQty,
                                                    'price' => $item->purchase_price,
                                                    'date' => $stockinDate,
                                                ]);
                                                if($erpVendor->MA044 == 1){
                                                    $purchasePrice = $item->purchase_price;
                                                }else{
                                                    $purchasePrice = $item->purchase_price / 1.05;
                                                }
                                                $erpVendor->MA044 == 1 ? $TH045 = ($diffQty * $purchasePrice) / 1.05 : $TH045 = $diffQty * $purchasePrice;
                                                $erpVendor->MA044 == 1 ? $TH019 = $TH045 * 1.05 : $TH019 = $TH045;
                                                $erpVendor->MA044 == 1 ? $TH046 = ($diffQty * $purchasePrice) - $TH045 : $TH046 = ( $diffQty * $purchasePrice ) * 0.05;
                                                $array2 = [
                                                    'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                                                    'TH004' => $item->erpPurchaseItem->TD004, //品號
                                                    'TH005' => $item->erpPurchaseItem->TD005, //品名
                                                    'TH006' => $item->erpPurchaseItem->TD006, //規格
                                                    'TH007' => $diffQty, //進貨數量
                                                    'TH008' => $item->erpPurchaseItem->TD009, //單位
                                                    'TH009' => $item->erpPurchaseItem->TD007, //庫別
                                                    'TH011' => $item->erpPurchaseItem->TD001, //採購單別
                                                    'TH012' => $item->erpPurchaseItem->TD002, //採購單號
                                                    'TH013' => $item->erpPurchaseItem->TD003, //採購序號
                                                    'TH015' => $diffQty, //驗收數量
                                                    'TH016' => $diffQty, //計價數量
                                                    'TH018' => round($purchasePrice,2), //原幣單位進價
                                                    'TH019' => round($TH019,0), //原幣進貨金額
                                                    'TH045' => round($TH045,0), //原幣未稅金額
                                                    'TH046' => round($TH046,0), //原幣稅額
                                                    'TH047' => round($TH045,0), //本幣未稅金額
                                                    'TH048' => round($TH046,0), //本幣稅額
                                                    'TH049' => $item->erpPurchaseItem->TD009, //計價單位
                                                ];
                                                $erpPURTH = array_merge($array1,$array2);
                                                ErpPURTHDB::create($erpPURTH);
                                                $totalQuantity += $diffQty;
                                                $erpVendor->MA044 == 1 ? $totalPrice += $TH045 + $TH046 : $totalPrice = $TH045;
                                                $totalTax += $TH046;
                                                $i++;
                                                continue;
                                            }elseif($remain == 0){
                                                StockinItemSingleDB::create([
                                                    'purchase_no' => $item->purchase_no,
                                                    'erp_purchase_no' => $item->erp_purchase_no,
                                                    'erp_purchase_sno' => $item->erp_purchase_sno,
                                                    'poi_id' => $item->poi_id,
                                                    'poip_id' => $item->poip_id,
                                                    'pois_id' => $item->id,
                                                    'product_model_id' => $item->product_model_id,
                                                    'erp_stockin_no' => $erpStockinNo,
                                                    'erp_stockin_sno' => str_pad($i+1,4,'0',STR_PAD_LEFT),
                                                    'stockin_quantity' => $diffQty,
                                                    'quantity' => $item->quantity,
                                                    'stockin_date' => $stockinDate,
                                                    'purchase_price'=> $item->purchase_price,
                                                    'is_del'=> $item->is_del,
                                                    'is_close'=> $item->is_close,
                                                ]);
                                                $log = PurchaseOrderChangeLogDB::create([
                                                    'purchase_no' => $item->purchase_no,
                                                    'admin_id' => $adminId,
                                                    'poi_id' => $item->poi_id,
                                                    'poip_id' => $item->poip_id,
                                                    'sku' => $item->sku,
                                                    'digiwin_no' => $item->digiwin_no,
                                                    'product_name' => $item->product_name,
                                                    'status' => '入庫',
                                                    'quantity' => $diffQty,
                                                    'price' => $item->purchase_price,
                                                    'date' => $stockinDate,
                                                ]);
                                                if($erpVendor->MA044 == 1){
                                                    $purchasePrice = $item->purchase_price;
                                                }else{
                                                    $purchasePrice = $item->purchase_price / 1.05;
                                                }
                                                $erpVendor->MA044 == 1 ? $TH045 = ($diffQty * $purchasePrice) / 1.05 : $TH045 = $diffQty * $purchasePrice;
                                                $erpVendor->MA044 == 1 ? $TH019 = $TH045 * 1.05 : $TH019 = $TH045;
                                                $erpVendor->MA044 == 1 ? $TH046 = ($diffQty * $purchasePrice) - $TH045 : $TH046 = ( $diffQty * $purchasePrice ) * 0.05;
                                                $array2 = [
                                                    'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                                                    'TH004' => $item->erpPurchaseItem->TD004, //品號
                                                    'TH005' => $item->erpPurchaseItem->TD005, //品名
                                                    'TH006' => $item->erpPurchaseItem->TD006, //規格
                                                    'TH007' => $diffQty, //進貨數量
                                                    'TH008' => $item->erpPurchaseItem->TD009, //單位
                                                    'TH009' => $item->erpPurchaseItem->TD007, //庫別
                                                    'TH011' => $item->erpPurchaseItem->TD001, //採購單別
                                                    'TH012' => $item->erpPurchaseItem->TD002, //採購單號
                                                    'TH013' => $item->erpPurchaseItem->TD003, //採購序號
                                                    'TH015' => $diffQty, //驗收數量
                                                    'TH016' => $diffQty, //計價數量
                                                    'TH018' => round($purchasePrice,2), //原幣單位進價
                                                    'TH019' => round($TH019,0), //原幣進貨金額
                                                    'TH045' => round($TH045,0), //原幣未稅金額
                                                    'TH046' => round($TH046,0), //原幣稅額
                                                    'TH047' => round($TH045,0), //本幣未稅金額
                                                    'TH048' => round($TH046,0), //本幣稅額
                                                    'TH049' => $item->erpPurchaseItem->TD009, //計價單位
                                                ];
                                                $erpPURTH = array_merge($array1,$array2);
                                                ErpPURTHDB::create($erpPURTH);

                                                $totalQuantity += $diffQty;
                                                $erpVendor->MA044 == 1 ? $totalPrice += $TH045 + $TH046 : $totalPrice = $TH045;
                                                $totalTax += $TH046;
                                                $i++;
                                                break;
                                            }elseif($remain < 0){
                                                StockinItemSingleDB::create([
                                                    'purchase_no' => $item->purchase_no,
                                                    'erp_purchase_no' => $item->erp_purchase_no,
                                                    'erp_purchase_sno' => $item->erp_purchase_sno,
                                                    'poi_id' => $item->poi_id,
                                                    'poip_id' => $item->poip_id,
                                                    'pois_id' => $item->id,
                                                    'product_model_id' => $item->product_model_id,
                                                    'erp_stockin_no' => $erpStockinNo,
                                                    'erp_stockin_sno' => str_pad($i+1,4,'0',STR_PAD_LEFT),
                                                    'stockin_quantity' => $remain,
                                                    'quantity' => $item->quantity,
                                                    'stockin_date' => $stockinDate,
                                                    'purchase_price'=> $item->purchase_price,
                                                    'is_del'=> $item->is_del,
                                                    'is_close'=> $item->is_close,
                                                ]);
                                                $log = PurchaseOrderChangeLogDB::create([
                                                    'purchase_no' => $item->purchase_no,
                                                    'admin_id' => $adminId,
                                                    'poi_id' => $item->poi_id,
                                                    'poip_id' => $item->poip_id,
                                                    'sku' => $item->sku,
                                                    'digiwin_no' => $item->digiwin_no,
                                                    'product_name' => $item->product_name,
                                                    'status' => '入庫',
                                                    'quantity' => $remain,
                                                    'price' => $item->purchase_price,
                                                    'date' => $stockinDate,
                                                ]);
                                                if($erpVendor->MA044 == 1){
                                                    $purchasePrice = $item->purchase_price;
                                                }else{
                                                    $purchasePrice = $item->purchase_price / 1.05;
                                                }
                                                $erpVendor->MA044 == 1 ? $TH045 = ( $remain * $purchasePrice) / 1.05 : $TH045 = ($diffQty + $remain) * $purchasePrice;
                                                $erpVendor->MA044 == 1 ? $TH019 = $TH045 * 1.05 : $TH019 = $TH045;
                                                $erpVendor->MA044 == 1 ? $TH046 = ( $remain * $purchasePrice) - $TH045 : $TH046 = ( ($diffQty + $remain) * $purchasePrice ) * 0.05;
                                                $array2 = [
                                                    'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                                                    'TH004' => $item->erpPurchaseItem->TD004, //品號
                                                    'TH005' => $item->erpPurchaseItem->TD005, //品名
                                                    'TH006' => $item->erpPurchaseItem->TD006, //規格
                                                    'TH007' => $remain, //進貨數量
                                                    'TH008' => $item->erpPurchaseItem->TD009, //單位
                                                    'TH009' => $item->erpPurchaseItem->TD007, //庫別
                                                    'TH011' => $item->erpPurchaseItem->TD001, //採購單別
                                                    'TH012' => $item->erpPurchaseItem->TD002, //採購單號
                                                    'TH013' => $item->erpPurchaseItem->TD003, //採購序號
                                                    'TH015' => $remain, //驗收數量
                                                    'TH016' => $remain, //計價數量
                                                    'TH018' => round($purchasePrice,2), //原幣單位進價
                                                    'TH019' => round($TH019,0), //原幣進貨金額
                                                    'TH045' => round($TH045,0), //原幣未稅金額
                                                    'TH046' => round($TH046,0), //原幣稅額
                                                    'TH047' => round($TH045,0), //本幣未稅金額
                                                    'TH048' => round($TH046,0), //本幣稅額
                                                    'TH049' => $item->erpPurchaseItem->TD009, //計價單位
                                                ];
                                                $erpPURTH = array_merge($array1,$array2);
                                                ErpPURTHDB::create($erpPURTH);

                                                $totalQuantity += $remain;
                                                $erpVendor->MA044 == 1 ? $totalPrice += $TH045 + $TH046 : $totalPrice = $TH045;
                                                $totalTax += $TH046;
                                                $i++;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }else{ //找不到該日進貨的採購, 補之前未沖銷完成的
                            $items = PurchaseOrderItemSingleDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemSingleTable.'.product_model_id')
                            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                            ->whereIn($purchaseOrderItemSingleTable.'.purchase_no',$purchaseNos)
                            ->whereNotNull($purchaseOrderItemSingleTable.'.erp_purchase_no')
                            // ->where($purchaseOrderItemSingleTable.'.quantity','!=',StockinItemSingleDB::where('stockin_item_singles.pois_id',$purchaseOrderItemSingleTable.'.id')->sum('quantity'))
                            ->where([[$purchaseOrderItemSingleTable.'.product_model_id',$productModel->id],[$purchaseOrderItemSingleTable.'.is_del',0]])
                            ->where(function($query)use($purchaseOrderItemSingleTable){
                                $query->where($purchaseOrderItemSingleTable.'.is_close',0)->orWhereNull($purchaseOrderItemSingleTable.'.is_close');
                            })->select([
                                $purchaseOrderItemSingleTable.'.*',
                                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                $productModelTable.'.sku',
                                $productModelTable.'.digiwin_no',
                            ])->orderBy($purchaseOrderItemSingleTable.'.vendor_arrival_date','asc')->orderBy($purchaseOrderItemSingleTable.'.purchase_no','asc')->get();
                            if(count($items) > 0){
                                foreach($items as $item){
                                    if(!empty($item->erpPurchaseItem) && !empty($item->erp_purchase_no) && ($item->is_close == 0 || $item->is_close == null)){
                                        $diffQty = $item->quantity - $item->stockin_quantity;
                                        $stockinQuantity -= $diffQty;
                                        if($stockinQuantity > 0){
                                            StockinItemSingleDB::create([
                                                'purchase_no' => $item->purchase_no,
                                                'erp_purchase_no' => $item->erp_purchase_no,
                                                'erp_purchase_sno' => $item->erp_purchase_sno,
                                                'poi_id' => $item->poi_id,
                                                'poip_id' => $item->poip_id,
                                                'pois_id' => $item->id,
                                                'product_model_id' => $item->product_model_id,
                                                'erp_stockin_no' => $erpStockinNo,
                                                'erp_stockin_sno' => str_pad($i+1,4,'0',STR_PAD_LEFT),
                                                'stockin_quantity' => $diffQty,
                                                'quantity' => $item->quantity,
                                                'stockin_date' => $stockinDate,
                                                'purchase_price'=> $item->purchase_price,
                                                'is_del'=> $item->is_del,
                                                'is_close'=> $item->is_close,
                                            ]);
                                            $log = PurchaseOrderChangeLogDB::create([
                                                'purchase_no' => $item->purchase_no,
                                                'admin_id' => $adminId,
                                                'poi_id' => $item->poi_id,
                                                'poip_id' => $item->poip_id,
                                                'sku' => $item->sku,
                                                'digiwin_no' => $item->digiwin_no,
                                                'product_name' => $item->product_name,
                                                'status' => '入庫',
                                                'quantity' => $diffQty,
                                                'price' => $item->purchase_price,
                                                'date' => $stockinDate,
                                            ]);
                                            if($erpVendor->MA044 == 1){
                                                $purchasePrice = $item->purchase_price;
                                            }else{
                                                $purchasePrice = $item->purchase_price / 1.05;
                                            }
                                            $erpVendor->MA044 == 1 ? $TH045 = ($diffQty * $purchasePrice) / 1.05 : $TH045 = $diffQty * $purchasePrice;
                                            $erpVendor->MA044 == 1 ? $TH019 = $TH045 * 1.05 : $TH019 = $TH045;
                                            $erpVendor->MA044 == 1 ? $TH046 = ($diffQty * $purchasePrice) - $TH045 : $TH046 = ( $diffQty * $purchasePrice ) * 0.05;
                                            $array2 = [
                                                'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                                                'TH004' => $item->erpPurchaseItem->TD004, //品號
                                                'TH005' => $item->erpPurchaseItem->TD005, //品名
                                                'TH006' => $item->erpPurchaseItem->TD006, //規格
                                                'TH007' => $diffQty, //進貨數量
                                                'TH008' => $item->erpPurchaseItem->TD009, //單位
                                                'TH009' => $item->erpPurchaseItem->TD007, //庫別
                                                'TH011' => $item->erpPurchaseItem->TD001, //採購單別
                                                'TH012' => $item->erpPurchaseItem->TD002, //採購單號
                                                'TH013' => $item->erpPurchaseItem->TD003, //採購序號
                                                'TH015' => $diffQty, //驗收數量
                                                'TH016' => $diffQty, //計價數量
                                                'TH018' => round($purchasePrice,0), //原幣單位進價
                                                'TH019' => round($TH019,0), //原幣進貨金額
                                                'TH045' => round($TH045,0), //原幣未稅金額
                                                'TH046' => round($TH046,0), //原幣稅額
                                                'TH047' => round($TH045,0), //本幣未稅金額
                                                'TH048' => round($TH046,0), //本幣稅額
                                                'TH049' => $item->erpPurchaseItem->TD009, //計價單位
                                            ];
                                            $erpPURTH = array_merge($array1,$array2);
                                            ErpPURTHDB::create($erpPURTH);

                                            $totalQuantity += $diffQty;
                                            $erpVendor->MA044 == 1 ? $totalPrice += $TH045 + $TH046 : $totalPrice = $TH045;
                                            $totalTax += $TH046;
                                            $i++;
                                            continue;
                                        }elseif($stockinQuantity == 0){
                                            StockinItemSingleDB::create([
                                                'purchase_no' => $item->purchase_no,
                                                'erp_purchase_no' => $item->erp_purchase_no,
                                                'erp_purchase_sno' => $item->erp_purchase_sno,
                                                'poi_id' => $item->poi_id,
                                                'poip_id' => $item->poip_id,
                                                'pois_id' => $item->id,
                                                'product_model_id' => $item->product_model_id,
                                                'erp_stockin_no' => $erpStockinNo,
                                                'erp_stockin_sno' => str_pad($i+1,4,'0',STR_PAD_LEFT),
                                                'stockin_quantity' => $diffQty,
                                                'quantity' => $item->quantity,
                                                'stockin_date' => $stockinDate,
                                                'purchase_price'=> $item->purchase_price,
                                                'is_del'=> $item->is_del,
                                                'is_close'=> $item->is_close,
                                            ]);
                                            $log = PurchaseOrderChangeLogDB::create([
                                                'purchase_no' => $item->purchase_no,
                                                'admin_id' => $adminId,
                                                'poi_id' => $item->poi_id,
                                                'poip_id' => $item->poip_id,
                                                'sku' => $item->sku,
                                                'digiwin_no' => $item->digiwin_no,
                                                'product_name' => $item->product_name,
                                                'status' => '入庫',
                                                'quantity' => $diffQty,
                                                'price' => $item->purchase_price,
                                                'date' => $stockinDate,
                                            ]);
                                            if($erpVendor->MA044 == 1){
                                                $purchasePrice = $item->purchase_price;
                                            }else{
                                                $purchasePrice = $item->purchase_price / 1.05;
                                            }
                                            $erpVendor->MA044 == 1 ? $TH045 = ($diffQty * $purchasePrice) / 1.05 : $TH045 = $diffQty * $purchasePrice;
                                            $erpVendor->MA044 == 1 ? $TH019 = $TH045 * 1.05 : $TH019 = $TH045;
                                            $erpVendor->MA044 == 1 ? $TH046 = ($diffQty * $purchasePrice) - $TH045 : $TH046 = ( $diffQty * $purchasePrice ) * 0.05;
                                            $array2 = [
                                                'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                                                'TH004' => $item->erpPurchaseItem->TD004, //品號
                                                'TH005' => $item->erpPurchaseItem->TD005, //品名
                                                'TH006' => $item->erpPurchaseItem->TD006, //規格
                                                'TH007' => $diffQty, //進貨數量
                                                'TH008' => $item->erpPurchaseItem->TD009, //單位
                                                'TH009' => $item->erpPurchaseItem->TD007, //庫別
                                                'TH011' => $item->erpPurchaseItem->TD001, //採購單別
                                                'TH012' => $item->erpPurchaseItem->TD002, //採購單號
                                                'TH013' => $item->erpPurchaseItem->TD003, //採購序號
                                                'TH015' => $diffQty, //驗收數量
                                                'TH016' => $diffQty, //計價數量
                                                'TH018' => round($purchasePrice,2), //原幣單位進價
                                                'TH019' => round($TH019,0), //原幣進貨金額
                                                'TH045' => round($TH045,0), //原幣未稅金額
                                                'TH046' => round($TH046,0), //原幣稅額
                                                'TH047' => round($TH045,0), //本幣未稅金額
                                                'TH048' => round($TH046,0), //本幣稅額
                                                'TH049' => $item->erpPurchaseItem->TD009, //計價單位
                                            ];
                                            $erpPURTH = array_merge($array1,$array2);
                                            ErpPURTHDB::create($erpPURTH);

                                            $totalQuantity += $diffQty;
                                            $erpVendor->MA044 == 1 ? $totalPrice += $TH045 + $TH046 : $totalPrice = $TH045;
                                            $totalTax += $TH046;
                                            $i++;
                                            break;
                                        }elseif($stockinQuantity < 0){
                                            StockinItemSingleDB::create([
                                                'purchase_no' => $item->purchase_no,
                                                'erp_purchase_no' => $item->erp_purchase_no,
                                                'erp_purchase_sno' => $item->erp_purchase_sno,
                                                'poi_id' => $item->poi_id,
                                                'poip_id' => $item->poip_id,
                                                'pois_id' => $item->id,
                                                'product_model_id' => $item->product_model_id,
                                                'erp_stockin_no' => $erpStockinNo,
                                                'erp_stockin_sno' => str_pad($i+1,4,'0',STR_PAD_LEFT),
                                                'stockin_quantity' => $stockinQuantity,
                                                'quantity' => $item->quantity,
                                                'stockin_date' => $stockinDate,
                                                'purchase_price'=> $item->purchase_price,
                                                'is_del'=> $item->is_del,
                                                'is_close'=> $item->is_close,
                                            ]);
                                            $log = PurchaseOrderChangeLogDB::create([
                                                'purchase_no' => $item->purchase_no,
                                                'admin_id' => $adminId,
                                                'poi_id' => $item->poi_id,
                                                'poip_id' => $item->poip_id,
                                                'sku' => $item->sku,
                                                'digiwin_no' => $item->digiwin_no,
                                                'product_name' => $item->product_name,
                                                'status' => '入庫',
                                                'quantity' => $stockinQuantity,
                                                'price' => $item->purchase_price,
                                                'date' => $stockinDate,
                                            ]);
                                            if($erpVendor->MA044 == 1){
                                                $purchasePrice = $item->purchase_price;
                                            }else{
                                                $purchasePrice = $item->purchase_price / 1.05;
                                            }
                                            $erpVendor->MA044 == 1 ? $TH045 = ($stockinQuantity * $purchasePrice) / 1.05 : $TH045 = $stockinQuantity * $purchasePrice;
                                            $erpVendor->MA044 == 1 ? $TH019 = $TH045 * 1.05 : $TH019 = $TH045;
                                            $erpVendor->MA044 == 1 ? $TH046 = ($stockinQuantity * $purchasePrice) - $TH045 : $TH046 = ( $stockinQuantity * $purchasePrice ) * 0.05;
                                            $array2 = [
                                                'TH003' => str_pad($i+1,4,'0',STR_PAD_LEFT), //序號
                                                'TH004' => $item->erpPurchaseItem->TD004, //品號
                                                'TH005' => $item->erpPurchaseItem->TD005, //品名
                                                'TH006' => $item->erpPurchaseItem->TD006, //規格
                                                'TH007' => $stockinQuantity, //進貨數量
                                                'TH008' => $item->erpPurchaseItem->TD009, //單位
                                                'TH009' => $item->erpPurchaseItem->TD007, //庫別
                                                'TH011' => $item->erpPurchaseItem->TD001, //採購單別
                                                'TH012' => $item->erpPurchaseItem->TD002, //採購單號
                                                'TH013' => $item->erpPurchaseItem->TD003, //採購序號
                                                'TH015' => $stockinQuantity, //驗收數量
                                                'TH016' => $stockinQuantity, //計價數量
                                                'TH018' => round($purchasePrice,2), //原幣單位進價
                                                'TH019' => round($TH019,0), //原幣進貨金額
                                                'TH045' => round($TH045,0), //原幣未稅金額
                                                'TH046' => round($TH046,0), //原幣稅額
                                                'TH047' => round($TH045,0), //本幣未稅金額
                                                'TH048' => round($TH046,0), //本幣稅額
                                                'TH049' => $item->erpPurchaseItem->TD009, //計價單位
                                            ];
                                            $erpPURTH = array_merge($array1,$array2);
                                            ErpPURTHDB::create($erpPURTH);

                                            $totalQuantity += $stockinQuantity;
                                            $erpVendor->MA044 == 1 ? $totalPrice += $TH045 + $TH046 : $totalPrice = $TH045;
                                            $totalTax += $TH046;
                                            $i++;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $TG026 = $TG017 = $TG019 = $TG028 = $TG031 = $TG032 = 0;
            $erpStockinItems = ErpPURTHDB::where('TH002',$erpStockinNo)->get();
            if(count($erpStockinItems) > 0){
                foreach($erpStockinItems as $erpStockinItem){
                    $TG026 += $erpStockinItem->TH015; //數量
                    $TG017 += $erpStockinItem->TH045; //進貨金額
                    $TG019 += $erpStockinItem->TH046; //原幣稅額
                    $TG028 += $erpStockinItem->TH045; //原幣貨款金額
                    $TG031 += $erpStockinItem->TH045; //本幣貨款金額
                    $TG032 += $erpStockinItem->TH046; //本幣稅額
                }
                $erpVendor->MA044 == 1 ? $totalPriceWithoutTax = $totalPrice / 1.05 : $totalPriceWithoutTax = $totalPrice;
                ErpPURTGDB::create([
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
                    'TG005' => 'A'.str_pad($vendorId,5,'0',STR_PAD_LEFT), //供應廠商
                    'TG007' => 'NTD', //幣別
                    'TG008' => 1, //匯率
                    'TG009' => $erpVendor->MA030, //發票聯數
                    'TG010' => $erpVendor->MA044, //課稅別
                    'TG012' => 0, //列印次數
                    'TG013' => 'N', //確認碼
                    'TG014' => $TG014, //單據日期
                    'TG015' => 'N', //更新碼
                    'TG016' => null, //備註
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
                    'TG027' => null, //發票日期
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
                ]);
            }
            $c++;
        }

        //檢查入庫狀況
        $purchaseOrders = PurchaseOrderItemSingleDB::whereIn('purchase_no',$this->purchaseNos)->where('is_del',0)->orderBy('stockin_date','asc')->get();
        $purchaseOrders = $purchaseOrders->groupBy('purchase_no')->all();
        foreach($purchaseOrders as $purchaseOrderItems){
            $chk = 0;
            $purchaseNo = null;
            $counts = count($purchaseOrderItems);
            foreach($purchaseOrderItems as $item){
                $purchaseNo = $item->purchase_no;
                $stockinItems = StockinItemSingleDB::where('pois_id',$item->id)->get();
                $in = 0;
                foreach($stockinItems as $stockin){
                    $in += $stockin->stockin_quantity;
                }
                if($item->quantity == $in){
                    $chk++;
                }
            }
            if($counts == $chk){
                $purchaseOrder = PurchaseOrderDB::where('purchase_no',$purchaseNo)->first();
                $purchaseOrder->update(['status' => 2, 'stockin_finish_date' => $stockinDate]);
                $log = PurchaseOrderChangeLogDB::create([
                    'purchase_no' => $purchaseOrder->purchase_no,
                    'admin_id' => $adminId,
                    'status' => '入庫',
                    'memo' => '採購單商品已全部入庫 ('.$stockinDate.')',
                ]);
            }
        }
    }
}
