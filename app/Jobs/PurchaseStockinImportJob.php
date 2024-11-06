<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\iCarryTicket as TicketDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\StockinImport as StockinImportDB;
use App\Models\StockinAbnormal as StockinAbnormalDB;
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
use App\Models\iCarryProductQuantityRecord as ProductQuantityRecordDB;
use App\Models\SerialNoRecord as SerialNoRecordDB;
use App\Imports\StockinImport;
use App\Imports\PurchaseOrderStockinImport;
use DB;
use Log;
use Exception;

class PurchaseStockinImportJob implements ShouldQueue
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
        $erpStockinNo = null;
        $createDate = date('Ymd');
        $createTime = date('H:i:s');
        $stockinFinishDate = date('Y-m-d');
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemSingleTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemSingleDB)->getTable();
        !empty($param['admin_id']) ? $adminId = $param['admin_id'] : $adminId = null;
        empty($adminId) && !empty(auth('gate')->user()) ? $adminId = auth('gate')->user()->id : '';
        !empty(auth('gate')->user()) ? $creator = strtoupper(auth('gate')->user()->account) : $creator = 'DS';

        //測試用要設定參數
        if(!empty($param['test']) && $param['test'] == true){
            // $param['cate'] = 'directShip';
            // $param['shipping_date'] = '2022-10-28';
            // $TG014 = '20221028';
            $param['cate'] = 'stockin';
            $param['shipping_date'] = '2023-02-24';
            $TG014 = '20230224';
            dd('請設定參數');
        }else{
            //找出相同單據日期的最後一筆進貨單號碼的流水號
            if($param['cate'] == 'directShip'){
                $TG014 = str_replace('-','',$param['shipping_date']);
                $erpStockinNo = $param['erpStockinNo'];
            }else{
                $chkDate = StockinImportDB::where('import_no',$param['import_no'])
                // $chkDate = StockinImportDB::where('import_no',1659433339)
                ->select([
                    'stockin_time',
                    DB::raw("DATE_FORMAT(stockin_time,'%Y%m%d') as stockinDate"),
                ])->groupBy('stockin_time')->first();
                $TG014 = $chkDate->stockinDate;
            }
        }
        $stockins = StockinImportDB::where('import_no',$param['import_no'])->get();
        // $stockins = StockinImportDB::where('import_no',167720095511)->get();

        foreach($stockins as $stockin){
            $purchaseNos = explode(',',$stockin->purchase_nos);
            $sellNo = $stockin->sell_no;
        }
        $this->purchaseNos = $purchaseNos;
        $stockins = $stockins->groupBy('vendor_id')->all();
        $six = substr($TG014,2);
        if(count($stockins) > 0){
            foreach($stockins as $vendorId => $value){
                try {
                    if(empty($erpStockinNo)){
                        //找出鼎新進貨單的最後一筆單號
                        $chkTemp = SerialNoRecordDB::where([['type','ErpStockinNo'],['serial_no','like',"$six%"]])->orderBy('serial_no','desc')->first();
                        !empty($chkTemp) ? $erpStockinNo = $chkTemp->serial_no + 1 : $erpStockinNo = $six.str_pad(1,5,0,STR_PAD_LEFT);
                        $chkTemp = SerialNoRecordDB::create(['type' => 'ErpStockinNo','serial_no' => $erpStockinNo]);
                    }
                    //檢查鼎新進貨單有沒有這個號碼
                    $tmp = ErpPURTGDB::where('TG002','like',"%$six%")->select('TG002')->orderBy('TG002','desc')->first();
                    if(!empty($tmp)){
                        if($tmp->TG002 >= $erpStockinNo){
                            $erpStockinNo = $tmp->TG002+1;
                            $chkTemp = SerialNoRecordDB::create(['type' => 'ErpStockinNo','serial_no' => $erpStockinNo]);
                        }
                    }
                } catch (Exception $exception) {
                    Log::info("入庫執行程序取號重複。採購單號 ".join(',',$purchaseNos)." 可能未完成入庫。");
                    continue;
                }
                $vendor = VendorDB::find($vendorId);
                !empty($vendor) && !empty($vendor->digiwin_vendor_no) ? $erpVendorId = $vendor->digiwin_vendor_no : $erpVendorId = 'A'.str_pad($vendorId,5,'0',STR_PAD_LEFT);
                $erpVendor = ErpVendorDB::find($erpVendorId);
                !empty($vendor) && $vendor->id == 733 ? $TH001 = 'A348' : $TH001 = 'A341';
                $array1 = [
                    'COMPANY' => 'iCarry',
                    'CREATOR' => $creator,
                    'USR_GROUP' => 'DSC',
                    'CREATE_DATE' => $createDate,
                    'FLAG' => 1,
                    'CREATE_TIME' => $createTime,
                    'CREATE_AP' => 'iCarry',
                    'CREATE_PRID' => 'PURI07',
                    'TH001' => $TH001, //單別
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
                ];
                $i = $totalTax = $totalPrice = $totalQuantity = 0;
                foreach($value as $stockin){
                    $stockinQty = $stockin->stockin_quantity;
                    if($param['cate'] == 'stockin'){
                        $stockinDate = explode(' ',$stockin->stockin_time)[0];
                        $directShipment = 0;
                    }else{
                        $directShipment = 1;
                        $stockinDate = $param['shipping_date'];
                    }
                    $now = date('Y-m-d H:i:s');
                    if(empty($stockinDate)){
                        //入庫日期空值
                        $stockinAbnormal = [
                            'stockin_import_id' => $stockin->id,
                            'import_no'=> $stockin->import_no,
                            'gtin13' => $stockin->gtin13,
                            'product_name' => $stockin->product_name,
                            'purchase_quantity' => $stockin->expected_quantity,
                            'need_quantity' => null,
                            'stockin_quantity' => $stockin->stockin_quantity,
                            'quantity' => $stockin->stockin_quantity,
                            'direct_shipment' => $directShipment,
                            'is_chk' => 0,
                            'stockin_date' => $stockin->stockin_time,
                            'memo' => "入庫日期時間未填寫。",
                        ];
                        StockinAbnormalDB::create($stockinAbnormal);
                    }else{
                        if($directShipment == 0 && (strtotime($stockinDate) > strtotime($now))){
                            //非直寄入庫日期大於今日
                            $stockinAbnormal = [
                                'stockin_import_id' => $stockin->id,
                                'import_no'=> $stockin->import_no,
                                'gtin13' => $stockin->gtin13,
                                'product_name' => $stockin->product_name,
                                'purchase_quantity' => $stockin->expected_quantity,
                                'need_quantity' => null,
                                'stockin_quantity' => $stockin->stockin_quantity,
                                'quantity' => $stockin->stockin_quantity,
                                'direct_shipment' => $directShipment,
                                'is_chk' => 0,
                                'stockin_date' => $stockin->stockin_time,
                                'memo' => "入庫日期時間 $stockinDate 大於今日時間 $now 。",
                            ];
                            StockinAbnormalDB::create($stockinAbnormal);
                        }else{
                            if(empty($erpVendor)){
                                //鼎新中找不到商家資料.
                                $stockinAbnormal = [
                                    'stockin_import_id' => $stockin->id,
                                    'import_no'=> $stockin->import_no,
                                    'gtin13' => $stockin->gtin13,
                                    'product_name' => $stockin->product_name,
                                    'purchase_quantity' => $stockin->expected_quantity,
                                    'need_quantity' => null,
                                    'stockin_quantity' => $stockin->stockin_quantity,
                                    'quantity' => $stockin->stockin_quantity,
                                    'direct_shipment' => $directShipment,
                                    'is_chk' => 0,
                                    'stockin_date' => $stockin->stockin_time,
                                    'memo' => "鼎新中找不到 $erpVendorId 商家資料",
                                ];
                                StockinAbnormalDB::create($stockinAbnormal);
                            }else{
                                $productModels = ProductModelDB::where('gtin13',"$stockin->gtin13")->get();
                                if(count($productModels) <= 0){
                                    $productModels = ProductModelDB::where('sku',"$stockin->gtin13")->get();
                                }
                                if(count($productModels) <= 0){
                                    $productModels = ProductModelDB::where('digiwin_no',"$stockin->gtin13")->get();
                                }
                                //由於採購單紀錄當下gtin13,廠商有可能修改條碼, 所以有可能找不到商品資料, 須從記錄去找回是否存在此商品
                                if(count($productModels) <= 0){
                                    $exclude = [0,293,340,351,288,346]; //排除鼎新內無資料的商家
                                    $tmp = ProductQuantityRecordDB::where('before_gtin13',"$stockin->gtin13")->orwhere('after_gtin13',"$stockin->gtin13")->orderBy('create_time','desc')->first();
                                    if(!empty($tmp)){
                                        $productModelId = $tmp->product_model_id;
                                        $productModels = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                        ->whereNotIn($productTable.'.vendor_id',$exclude) //排除商家id
                                        ->where($productModelTable.'.id',$tmp->product_model_id)->get();
                                    }
                                }
                                if(count($productModels) > 0){
                                    $remindQty = $chkProduct = 0;
                                    //找出對應相同的採購商品
                                    foreach($productModels as $productModel){
                                        $alreadyStockinQty = $requiredQty = 0;
                                        //檢查item是否為票券
                                        $chkTicket = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                        ->where($productModelTable.'.digiwin_no',$productModel->digiwin_no)
                                        ->where($productTable.'.category_id',17)->first();
                                        $items = PurchaseOrderItemSingleDB::join($purchaseOrderTable,$purchaseOrderTable.'.purchase_no',$purchaseOrderItemSingleTable.'.purchase_no')
                                            ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemSingleTable.'.product_model_id')
                                            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id');
                                        !empty($chkTicket) ? '' : $items = $items->where($purchaseOrderTable.'.status',1);
                                        $items =$items->whereIn($purchaseOrderItemSingleTable.'.purchase_no',$purchaseNos);
                                        $items =$items->whereNotNull($purchaseOrderItemSingleTable.'.erp_purchase_no');
                                        $items =$items->where(function($query)use($purchaseOrderItemSingleTable,$productModel,$stockin){
                                            $query->where($purchaseOrderItemSingleTable.'.product_model_id',$productModel->id)
                                            ->orWhere($purchaseOrderItemSingleTable.'.gtin13',"$stockin->gtin13");
                                        })->where([
                                            [$purchaseOrderItemSingleTable.'.is_del',0],
                                            [$purchaseOrderItemSingleTable.'.quantity','>',0],
                                        ])->where(function($query)use($purchaseOrderItemSingleTable){
                                            $query->where($purchaseOrderItemSingleTable.'.is_close',0)
                                            ->orWhereNull($purchaseOrderItemSingleTable.'.is_close')
                                            ->orWhere($purchaseOrderItemSingleTable.'.is_close','');
                                        });
                                        $items = $items->where(function($query)use($purchaseOrderItemSingleTable,$directShipment){
                                            if($directShipment == 1){
                                                $query->where($purchaseOrderItemSingleTable.'.direct_shipment',1);
                                            }else{
                                                $query->where($purchaseOrderItemSingleTable.'.direct_shipment',0)
                                                ->orWhereNull($purchaseOrderItemSingleTable.'.direct_shipment')
                                                ->orWhere($purchaseOrderItemSingleTable.'.direct_shipment','');
                                            }
                                        });

                                        if($directShipment != 1){ //非直寄增加判斷廠商交貨日
                                            !empty($chkTicket) ? '' : $items = $items->where($purchaseOrderItemSingleTable.'.vendor_arrival_date','<=',$stockinDate);
                                        }

                                        $items = $items->select([
                                            $purchaseOrderItemSingleTable.'.*',
                                            $purchaseOrderTable.'.vendor_id',
                                            $purchaseOrderTable.'.type',
                                            $productModelTable.'.sku',
                                            $productModelTable.'.digiwin_no',
                                            $productTable.'.name as product_name',
                                        ])->orderBy($purchaseOrderItemSingleTable.'.vendor_arrival_date','asc')->orderBy($purchaseOrderItemSingleTable.'.erp_purchase_sno')->get();
                                        if(count($items) > 0){
                                            $chkProduct++;
                                            foreach($items as $item){
                                                $item->direct_shipment == null || $item->direct_shipment == '' ? $item->update(['direct_shipment'=>0]) : '';
                                                if($item->direct_shipment == $directShipment){
                                                    $stockinItemSingles = StockinItemSingleDB::where([['pois_id',$item->id],['purchase_no',$item->purchase_no],['is_del',0]])->get();
                                                    if(count($stockinItemSingles) > 0){
                                                        foreach($stockinItemSingles as $stockinItemSingle)
                                                        $alreadyStockinQty += $stockinItemSingle->stockin_quantity;
                                                    }
                                                    $requiredQty += $item->quantity;
                                                }
                                            }
                                            $needQty = $requiredQty - $alreadyStockinQty;
                                            $remindQty = $stockinQty;
                                            if($stockinQty <= $needQty){ //可以沖銷
                                                foreach($items as $item){
                                                    $item->direct_shipment == null || $item->direct_shipment == '' ? $item->update(['direct_shipment'=>0]) : '';
                                                    if($item->direct_shipment == $directShipment){
                                                        if(!empty($item->erpPurchaseItem) && ($item->is_close == 0 || $item->is_close == null || $item->is_close == '')){ //已被結案則不能再進貨
                                                            $stockinItemSingles = StockinItemSingleDB::where([['pois_id',$item->id],['purchase_no',$item->purchase_no],['is_del',0]])->get();
                                                            $oldStockinQuantity = 0;
                                                            if(count($stockinItemSingles) > 0){
                                                                foreach($stockinItemSingles as $stockinItemSingle){
                                                                    $oldStockinQuantity += $stockinItemSingle->stockin_quantity;
                                                                }
                                                            }
                                                            $diffQty = $item->quantity - $oldStockinQuantity; //缺少的採購量
                                                            if($diffQty > 0){
                                                                $remindQty < $diffQty ? $diffQty = $remindQty : '';
                                                                $stockin = [
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
                                                                    'sell_no' => $sellNo,
                                                                ];
                                                                StockinItemSingleDB::create($stockin);
                                                                $log = [
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
                                                                ];
                                                                PurchaseOrderChangeLogDB::create($log);
                                                                if($erpVendor->MA044 == 1){
                                                                    $purchasePrice = $item->purchase_price;
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
                                                                    'TH018' => round($purchasePrice,4), //原幣單位進價
                                                                    'TH019' => round($TH019,0), //原幣進貨金額
                                                                    'TH045' => round($TH045,0), //原幣未稅金額
                                                                    'TH046' => round($TH046,0), //原幣稅額
                                                                    'TH047' => round($TH045,0), //本幣未稅金額
                                                                    'TH048' => round($TH046,0), //本幣稅額
                                                                    'TH049' => $item->erpPurchaseItem->TD009, //計價單位
                                                                ];
                                                                $erpPURTH = array_merge($array1,$array2);
                                                                ErpPURTHDB::create($erpPURTH);
                                                                $remindQty = $remindQty - $diffQty;
                                                                $totalQuantity += $diffQty;
                                                                $erpVendor->MA044 == 1 ? $totalPrice += $TH045 + $TH046 : $totalPrice = $TH045;
                                                                $totalTax += $TH046;
                                                                $i++;
                                                                if($remindQty == 0){
                                                                    break 2;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }else{ //入庫數量大於全部需求, 入庫異常.
                                                $stockinAbnormal = [
                                                    'stockin_import_id' => $stockin->id,
                                                    'import_no'=> $stockin->import_no,
                                                    'gtin13' => $stockin->gtin13,
                                                    'product_name' => $stockin->product_name,
                                                    'purchase_quantity' => $stockin->expected_quantity,
                                                    'need_quantity' => $needQty,
                                                    'stockin_quantity' => $stockinQty,
                                                    'quantity' => $stockinQty - $needQty,
                                                    'direct_shipment' => $directShipment,
                                                    'is_chk' => 0,
                                                    'stockin_date' => $stockin->stockin_time,
                                                    'memo' => "檔案第 $stockin->row_no 列，此次入庫數量 $stockinQty 已大於需求數量 $needQty ，此列未寫入資料，請與倉庫或廠商確認數量後重新匯入此列。",
                                                ];
                                                StockinAbnormalDB::create($stockinAbnormal);
                                            }
                                            break;
                                        }
                                    }
                                    if($chkProduct == 0){
                                        //找不到對應商品, 檢查進來的商品與採購的商品.
                                        $stockinAbnormal = [
                                            'stockin_import_id' => $stockin->id,
                                            'import_no'=> $stockin->import_no,
                                            'gtin13' => $stockin->gtin13,
                                            'product_name' => $stockin->product_name,
                                            'purchase_quantity' => $stockin->expected_quantity,
                                            'need_quantity' => null,
                                            'stockin_quantity' => $stockin->stockin_quantity,
                                            'quantity' => $stockin->stockin_quantity,
                                            'direct_shipment' => $directShipment,
                                            'is_chk' => 0,
                                            'stockin_date' => $stockin->stockin_time,
                                            'memo' => "找不到對應商品，匯入的商品 $stockin->gtin13 與採購的商品不符。",
                                        ];
                                        StockinAbnormalDB::create($stockinAbnormal);
                                    }
                                }else{
                                    //找不到商品, 條碼或鼎新品號資料有問題, 註: 直寄與倉庫共用gtin13欄位.
                                    $stockinAbnormal = [
                                        'stockin_import_id' => $stockin->id,
                                        'import_no'=> $stockin->import_no,
                                        'gtin13' => $stockin->gtin13,
                                        'product_name' => $stockin->product_name,
                                        'purchase_quantity' => $stockin->expected_quantity,
                                        'need_quantity' => null,
                                        'stockin_quantity' => $stockin->stockin_quantity,
                                        'quantity' => $stockin->stockin_quantity,
                                        'direct_shipment' => $directShipment,
                                        'is_chk' => 0,
                                        'stockin_date' => $stockin->stockin_time,
                                        'memo' => "找不到商品，$stockin->gtin13 條碼/鼎新貨號資料有問題",
                                    ];
                                    StockinAbnormalDB::create($stockinAbnormal);
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
                    ErpPURTGDB::create([
                        'COMPANY' => 'iCarry',
                        'CREATOR' => $creator,
                        'USR_GROUP' => 'DSC',
                        'CREATE_DATE' => $createDate,
                        'FLAG' => 1,
                        'CREATE_TIME' => $createTime,
                        'CREATE_AP' => 'iCarry',
                        'CREATE_PRID' => 'PURI07',
                        'TG001' => $TH001, //單別
                        'TG002' => $erpStockinNo, //單號
                        'TG003' => $createDate, //進貨日期
                        'TG004' => '001', //廠別
                        'TG005' => $erpVendorId, //供應廠商
                        'TG007' => 'NTD', //幣別
                        'TG008' => 1, //匯率
                        'TG009' => $erpVendor->MA030, //發票聯數
                        'TG010' => $erpVendor->MA044, //課稅別
                        'TG012' => 0, //列印次數
                        'TG013' => 'N', //確認碼
                        'TG014' => $TG014, //單據日期
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
                    ]);
                }
                if($param['cate'] == 'directShip'){
                    $erpStockinNo = null;
                }
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
                    $stockinItems = StockinItemSingleDB::where([['pois_id',$item->id],['is_del',0]])->get();
                    $in = 0;
                    foreach($stockinItems as $stockin){
                        $in += $stockin->stockin_quantity;
                    }
                    if($item->quantity == $in){
                        $chk++;
                    }
                }
                if($counts == $chk){
                    $purchaseOrder = PurchaseOrderDB::with('acOrder')->where('purchase_no',$purchaseNo)->first();
                    $purchaseOrder->update(['status' => 2, 'stockin_finish_date' => $stockinDate]);
                    $log = PurchaseOrderChangeLogDB::create([
                        'purchase_no' => $purchaseOrder->purchase_no,
                        'admin_id' => $adminId,
                        'status' => '入庫',
                        'memo' => '採購單商品已全部入庫 ('.$stockinDate.')',
                    ]);
                    !empty($purchaseOrder->acOrder) ? $purchaseOrder->acOrder->update(['is_stockin' => 1]) : '';
                }
            }
        }
    }
}
