<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\OrderCancel as OrderCancelDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;
use App\Models\Admin as AdminDB;

use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use App\Models\PurchaseOrderItemPackage as PurchaseOrderItemPackageDB;
use App\Models\ReturnDiscountItem as ReturnDiscountItemDB;
use App\Models\ReturnDiscountItemPackage as ReturnDiscountItemPackageDB;
use App\Models\PurchaseOrderChangeLog as PurchaseOrderChangeLogDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\ErpPURTD as ErpPURTDDB;
use App\Models\ErpPURTG as ErpPURTGDB;
use App\Models\ErpPURTH as ErpPURTHDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\SellReturn as SellReturnDB;
use App\Models\SellReturnItem as SellReturnItemDB;
use App\Models\VendorShipping as VendorShippingDB;
use App\Models\VendorShippingItem as VendorShippingItemDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;

use DB;
use Session;

use App\Traits\PurchaseOrderFunctionTrait;
use App\Traits\OrderFunctionTrait;

use App\Jobs\PurchaseStockinImportJob;
use App\Jobs\PurchaseStockinFileImportJob;
use App\Jobs\AdminExportJob;
use App\Jobs\ReturnDiscountJob;
use App\Jobs\PurchaseOrderSynchronizeToDigiwinJob as PurchaseOrderSyncToDigiwin;
use App\Jobs\Schedule\DigiwinPurchaseOrderSynchronizeJob as DigiwinPurchaseOrderSynchronize;
use App\Jobs\PurchaseOrderNoticeVendorJob as PurchaseOrderNoticeVendor;
use App\Jobs\CancelPurchaseOrderJob as CancelPurchaseOrder;
use App\Jobs\PurchaseOrderNoticeVendorModify;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class PurchasesController extends Controller
{
    use PurchaseOrderFunctionTrait,OrderFunctionTrait;

    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ctime = microtime(true); //紀錄開始時間
        $menuCode = 'M28S1';
        $appends = [];
        $compact = [];
        $purchases = [];
        $NGReturn = $NGStockin = [];
        $sellReturnTable = env('DB_DATABASE').'.'.(new SellReturnDB)->getTable();
        $sellReturnItemTable = env('DB_DATABASE').'.'.(new SellReturnItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $orderCancelTable = env('DB_DATABASE').'.'.(new OrderCancelDB)->getTable();

        //將進來的資料作參數轉換及附加到appends及compact中
        foreach (request()->all() as $key => $value) {
            $$key = $value;
            if (isset($$key)) {
                $appends = array_merge($appends, [$key => $value]);
                $compact = array_merge($compact, [$key]);
            }
            !empty($value) ? $con[$key] = $value : '';
        }

        if (!isset($status)) {
            $status = '0,1,2,3';
            $compact = array_merge($compact, ['status']);
        }

        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }

        $purchases = $this->getPurchaseOrderData(request(),'index');
        $i = $NGStockinCount = $NGReturnCount = 0;
        foreach($purchases as $purchase){
            if(!empty($purchase->notice) && !empty($purchase->notice['notice_time'])){
                $purchase->noticeVendor = 'Y';
            }else{
                $purchase->noticeVendor = 'N';
            }
            $purchase->ng = 0;
            foreach($purchase->items as $item){
                $item->is_del == 0 && $purchase->status == 0 && $item->quantity <= 0 ? $purchase->ng = 1 : '';
                $item->stockinQty = 0;
                if(strstr($item->sku,'BOM')){
                    foreach($item->package as $package){
                        $package->is_del == 0 && $purchase->status == 0 && $package->quantity <= 0 ? $purchase->ng = 1 : '';
                        if(count($package->returns) > 0){
                            foreach($package->returns as $return){
                                $package->returnQty += $return->quantity;
                            }
                        }
                        if(count($package->stockins) > 0){
                            $package->stockinQty;
                            foreach($package->stockins as $stockin){
                                $package->stockinQty += $stockin->stockin_quantity;
                            }
                        }
                    }
                }else{
                    if(count($item->stockins) > 0){
                        foreach($item->stockins as $stockin){
                            $item->stockinQty += $stockin->stockin_quantity;
                        }
                    }
                    foreach($item->returns as $return){
                        $item->returnQty += $return->quantity;
                    }
                }
            }
            if($purchase->status == 1 || $purchase->status == 2){
                foreach($purchase->items as $item){
                    if(strstr($item->sku,'BOM')){
                        if(count($item->package) > 0){
                            $stockinQty = $returnQty = 0;
                            foreach($item->package as $package){
                                $stockinQty = $returnQty = 0;
                                if($item->quantity == 0){
                                    $useQty = 0;
                                }else{
                                    $useQty = $package->quantity / $item->quantity;
                                }
                                $purchaseQty = $package->quantity;
                                if(count($package->stockins) > 0){
                                    foreach($package->stockins as $stockin){
                                        $stockinQty += $stockin->stockin_quantity; //進貨數量
                                    }
                                    if($stockinQty > $purchaseQty){
                                        $NGStockin['poids'][] = $purchase->id;
                                        $NGStockin['purchaseNos'][] = $purchase->purchase_no;
                                        $NGStockinCount++;
                                    }
                                }
                                if(count($package->returns) > 0){
                                    foreach($package->returns as $return){
                                        $returnQty += $return->quantity; //退貨數量
                                    }
                                    if($returnQty > $purchaseQty || $returnQty > $stockinQty){
                                        $NGReturn['poids'][] = $purchase->id;
                                        $NGReturn['purchaseNos'][] = $purchase->purchase_no;
                                        $NGReturnCount++;
                                    }
                                }
                            }
                        }
                    }else{
                        $stockinQty = $returnQty = 0;
                        $purchaseQty = $item->quantity;
                        if(count($item->stockins) > 0){
                            foreach($item->stockins as $stockin){
                                $stockinQty += $stockin->stockin_quantity; //進貨數量
                            }
                            if($stockinQty > $purchaseQty){
                                $NGStockin['poids'][] = $purchase->id;
                                $NGStockin['purchaseNos'][] = $purchase->purchase_no;
                                $NGStockinCount++;
                            }
                        }
                        if(count($item->returns) > 0){
                            foreach($item->returns as $return){
                                $returnQty += $return->quantity; //退貨數量
                            }
                            if($returnQty > $purchaseQty || $returnQty > $stockinQty){
                                $NGReturn['poids'][] = $purchase->id;
                                $NGReturn['purchaseNos'][] = $purchase->purchase_no;
                                $NGReturnCount++;
                            }
                        }
                    }
                }
            }
        }
        $orderCancels = $sellReturns = [];
        //訂單取消庫存提示
        // $orderCancels = OrderCancelDB::join($productModelTable,$productModelTable.'.digiwin_no',$orderCancelTable.'.purchase_digiwin_no')
        // ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        // ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        $orderCancels = OrderCancelDB::whereNotNull($orderCancelTable.'.purchase_no')
        ->where($orderCancelTable.'.is_chk',0)
        ->select([
            $orderCancelTable.'.*',
            // 'product_name' => ProductModelDB::whereColumn($productModelTable.'.digiwin_no',$orderCancelTable.'.purchase_digiwin_no')
            //     ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            //     ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            //     ->select([
            //         DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            //     ])->limit(1),
            // DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            DB::raw("SUM($orderCancelTable.quantity) as quantity"),
            DB::raw("SUM($orderCancelTable.deduct_quantity) as deduct_quantity"),
        ])->groupBy($orderCancelTable.'.purchase_digiwin_no',$orderCancelTable.'.vendor_arrival_date')->get();

        foreach($orderCancels as $orderCancel){
            $tmp = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->where($productModelTable.'.digiwin_no',$orderCancel->purchase_digiwin_no)
            ->select([
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            ])->first();
            !empty($tmp) ? $orderCancel->product_name = $tmp->product_name : $orderCancel->product_name = null;
            !empty($orderCancel->book_shipping_date) ? $orderCancel->book_shipping_date = str_replace('-','/',substr($orderCancel->book_shipping_date,5,10)) : '';
            !empty($orderCancel->vendor_arrival_date) ? $orderCancel->vendor_arrival_date = str_replace('-','/',substr($orderCancel->vendor_arrival_date,5,10)) : '';
        }

        //銷退單品庫存提示
        $sellReturns = SellReturnItemDB::join($sellReturnTable,$sellReturnTable.'.return_no',$sellReturnItemTable.'.return_no')
        ->join($productModelTable,$productModelTable.'.digiwin_no',$sellReturnItemTable.'.origin_digiwin_no')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->where($sellReturnTable.'.type','銷退')
        ->where($sellReturnTable.'.is_del',0)
        ->where($sellReturnItemTable.'.is_chk',0)
        ->where($sellReturnItemTable.'.is_del',0)
        ->where(function($query)use($sellReturnItemTable){ //排除運費及跨境稅
            $query->where($sellReturnItemTable.'.origin_digiwin_no','!=','901001')
            ->where($sellReturnItemTable.'.origin_digiwin_no','!=','901002');
        })->select([
            $sellReturnItemTable.'.*',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            DB::raw("SUM($sellReturnItemTable.quantity) as quantity"),
        ])->groupBy($sellReturnItemTable.'.origin_digiwin_no',$sellReturnItemTable.'.expiry_date')->get();

        foreach($sellReturns as $sellReturn){
            !empty($sellReturn->expiry_date) ? $sellReturn->expiry_date = str_replace('-','/',substr($sellReturn->expiry_date,5,10)) : '';
        }

        !empty($NGStockin['poids']) ? $NGStockin['poids'] = array_unique($NGStockin['poids']) : $NGStockin['poids'] = [];
        !empty($NGStockin['poids']) ? $NGStockin['count'] = count($NGStockin['poids']) : $NGStockin['count'] = 0; //排除重複後再計算數量
        !empty($NGStockin['poids']) ? sort($NGStockin['poids']) : '';
        !empty($NGStockin['poids']) ? $NGStockin['poids'] = join(',',$NGStockin['poids']) : $NGStockin['poids'] = null;

        !empty($NGStockin['purchaseNos']) ? $NGStockin['purchaseNos'] = array_unique($NGStockin['purchaseNos']) : $NGStockin['purchaseNos'] = [];
        !empty($NGStockin['purchaseNos']) ? sort($NGStockin['purchaseNos']) : '';
        !empty($NGStockin['purchaseNos']) ? $NGStockin['purchaseNos'] = join(',',$NGStockin['purchaseNos']) : $NGStockin['purchaseNos'] = null;

        !empty($NGReturn['poids']) ? $NGReturn['poids'] = array_unique($NGReturn['poids']) : $NGReturn['poids'] = [];
        !empty($NGReturn['poids']) ? $NGReturn['count'] = count($NGReturn['poids']) : $NGReturn['count'] = 0; //排除重複後再計算數量
        !empty($NGReturn['poids']) ? sort($NGReturn['poids']) : '';
        !empty($NGReturn['poids']) ? $NGReturn['poids'] = join(',',$NGReturn['poids']) : $NGReturn['poids'] = null;

        !empty($NGReturn['purchaseNos']) ? $NGReturn['purchaseNos'] = array_unique($NGReturn['purchaseNos']) : $NGReturn['purchaseNos'] = [];
        !empty($NGReturn['purchaseNos']) ? sort($NGReturn['purchaseNos']) : '';
        !empty($NGReturn['purchaseNos']) ? $NGReturn['purchaseNos'] = join(',',$NGReturn['purchaseNos']) : $NGReturn['purchaseNos'] = null;

        $compact = array_merge($compact, ['menuCode','purchases','appends','NGReturn','NGStockin','orderCancels','sellReturns']);
        return view('gate.purchases.index', compact($compact));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $menuCode = 'M28S1';
        $compact = [];
        $shippingMethods = [];
        $shippingVendors = [];
        $vendors = [];
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchase = PurchaseOrderDB::join($vendorTable,$vendorTable.'.id',$purchaseOrderTable.'.vendor_id')
        ->with('changeLogs','returns','syncedLog', 'items', 'items.package','items.stockins','items.returns','items.package.stockins','items.package.returns','checkStockin','lastStockin','returns')
            ->select([
                $purchaseOrderTable.'.*',
                DB::raw("(CASE WHEN $purchaseOrderTable.status = -1 THEN '已取消' WHEN $purchaseOrderTable.status = 0 THEN '尚未採購' WHEN $purchaseOrderTable.status = 1 THEN '已採購' WHEN $purchaseOrderTable.status = 2 THEN '已入庫' WHEN $purchaseOrderTable.status = 3 THEN '已結案' END) as status_text"),
                $vendorTable.'.name as vendor_name',
            ])->findOrFail($id);
        if(!empty($purchase->order_ids)){
            $purchase->orders = OrderDB::whereIn('id',explode(',',$purchase->order_ids))->orderBy('id','desc')->get();
        }

        //由於進貨為單品，故須透過單品查詢組合品是否有單品進貨
        foreach($purchase->items as $item){
            if(strstr($item->sku,'BOM')){
                foreach($item->package as $package){
                    if(!empty($package->stockin_date)){
                        $item->stockin_date = $package->stockin_date;
                        break;
                    }
                }
            }
        }
        foreach($purchase->items as $item){
            $item->stockinQty = 0;
            if(strstr($item->sku,'BOM')){
                foreach($item->package as $package){
                    if(count($package->returns) > 0){
                        foreach($package->returns as $return){
                            $package->returnQty += $return->quantity;
                        }
                    }
                    if(count($package->stockins) > 0){
                        $package->stockinQty;
                        foreach($package->stockins as $stockin){
                            $package->stockinQty += $stockin->stockin_quantity;
                            $item->stockinQty += $stockin->stockin_quantity;
                        }
                    }
                }
            }else{
                if(count($item->stockins) > 0){
                    foreach($item->stockins as $stockin){
                        $item->stockinQty += $stockin->stockin_quantity;
                    }
                }
                foreach($item->returns as $return){
                    $item->returnQty += $return->quantity;
                }
            }
        }
        $compact = array_merge($compact, ['menuCode','purchase','shippingMethods','shippingVendors','vendors']);
        return view('gate.purchases.show', compact($compact));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->data;
        sort($data);
        $order = PurchaseOrderDB::findOrFail($id);
        !empty($request->memo) ? $orderMemo = $request->memo : $orderMemo = null;
        !empty($request->purchase_date) ? $purchaseDate = $request->purchase_date : '';
        empty($purchaseDate) ? $purchaseDate = $order->purchase_date : '';
        empty($purchaseDate) ? $purchaseDate = explode(' ',$order->created_at)[0] : '';
        $this->updateModify($order, $data, $purchaseDate, $orderMemo);
        !empty($orderMemo) && $order->memo != $orderMemo ? $order->update(['memo' => $orderMemo]) : '';
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function cancel(Request $request)
    {
        if(!empty($request->id)){
            CancelPurchaseOrder::dispatchNow([
                "id" => [$request->id],
                "method" => "selected",
                "cate" => "CancelOrder",
                "type" => "undefined",
                "model" => "purchase",
                "admin_id" => auth('gate')->user()->id,
                "admin_name" => auth('gate')->user()->name,
                'export_no' => time(),
                'start_time' => date('Y-m-d H:i:s'),
            ]);
            Session::put('success', '選擇的採購單已被取消。');
        }
        return redirect()->back();
    }

    public function close(Request $request)
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchaseOrderItemSingleTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemSingleDB)->getTable();
        if(!empty($request->id)){
            $item = PurchaseOrderItemDB::with('exportPackage')
                ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                ->select([
                    $purchaseOrderItemTable.'.*',
                    $productModelTable.'.sku',
                    $productModelTable.'.digiwin_no',
                    DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                ])->findOrFail($request->id);
            $beforeQuantity = $item->quantity;
            $beforePurchasePrice = $item->purchase_price;
            $beforeVendorArrivalDate = $item->vendor_arrival_date;
            if(strstr($item->sku,'BOM')){
                foreach($item->exportPackage as $package){
                    $single = PurchaseOrderItemSingleDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemSingleTable.'.product_model_id')
                    ->where([[$purchaseOrderItemSingleTable.'.purchase_no',$package->purchase_no],[$purchaseOrderItemSingleTable.'.poi_id',$item->id],[$purchaseOrderItemSingleTable.'.poip_id',$package->id]])
                    ->select([
                        $purchaseOrderItemSingleTable.'.*',
                        $productModelTable.'.sku',
                        $productModelTable.'.digiwin_no',
                    ])->first();
                    $erpPurchaseItem = ErpPURTDDB::where([['TD001',$single->type],['TD002',$single->erp_purchase_no],['TD003',$single->erp_purchase_sno],['TD004',$single->digiwin_no]])->update(['TD016' => 'y']);
                    $single->update(['is_close' => 1]);
                    $package->update(['is_close' => 1]);
                }
            }else{
                $single = PurchaseOrderItemSingleDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemSingleTable.'.product_model_id')
                    ->where([[$purchaseOrderItemSingleTable.'.purchase_no',$item->purchase_no],[$purchaseOrderItemSingleTable.'.poi_id',$item->id],[$purchaseOrderItemSingleTable.'.poip_id',null]])
                    ->select([
                        $purchaseOrderItemSingleTable.'.*',
                        $productModelTable.'.sku',
                        $productModelTable.'.digiwin_no',
                    ])->first();
                $erpPurchaseItem = ErpPURTDDB::where([['TD001',$single->type],['TD002',$single->erp_purchase_no],['TD003',$single->erp_purchase_sno],['TD004',$single->digiwin_no]])->update(['TD016' => 'y']);
                $single->update(['is_close' => 1]);
            }
            $item->update(['is_close' => 1]);
            $log = PurchaseOrderChangeLogDB::create([
                'purchase_no' => $item->purchase_no,
                'admin_id' => auth('gate')->user()->id,
                'poi_id' => $item->id,
                'sku' => $item->sku,
                'digiwin_no' => $item->digiwin_no,
                'product_name' => $item->product_name,
                'status' => '結案',
                'memo' => '商品指定結案',
            ]);
        }
        return redirect()->back();
    }

    public function multiProcess(Request $request)
    {
        //將進來的資料作參數轉換及附加到$param中
        foreach ($request->all() as $key => $value) {
            $param[$key] = $value;
        }
        $method = null;
        $url = 'https://'.env('GATE_DOMAIN').'/exportCenter';
        $param['admin_id'] = auth('gate')->user()->id;
        $param['admin_name'] = auth('gate')->user()->name;
        if(!empty($param['method'])){
            $param['method'] == 'selected' ? $method = '自行勾選' : '';
            $param['method'] == 'allOnPage' ? $method = '目前頁面全選' : '';
            $param['method'] == 'byQuery' ? $method = '依查詢條件' : '';
            $param['method'] == 'allData' ? $method = '全部資料' : '';
        }
        !empty($method) ? $param['name'] = $param['filename'].'_'.$method : $param['name'] = $param['filename'];
        $param['export_no'] = time();
        $param['start_time'] = date('Y-m-d H:i:s');
        $param['cate'] == 'pdf' || $param['type'] == 'pdf' ? $param['filename'] = $param['name'].'_'.time().'.pdf' : $param['filename'] = $param['name'].'_'.time().'.xlsx';
        $message = $param['name'].'，工作單號：'.$param['export_no'].'<br>匯出已於背端執行，請過一段時間至匯出中心下載，<br>檔案名稱：'.$param['filename'].'<br>匯出中心連結：<a href="'.$url.'" target="_blank"><span class="text-danger">'.$url.'</span></a>';
        if($param['cate'] == 'SyncToDigiwin'){ //同步鼎新
            if($param['method'] == 'byQuery'){
                $message = '執行編號：'.$param['export_no'].'<br>'.$param['name'].'，依條件查詢資料量較大，改由於背端執行，請稍後一段時間，再重新查詢或按F5重新整理。';
                PurchaseOrderSyncToDigiwin::dispatch($param);
            }else{
                $message = '執行編號：'.$param['export_no'].'<br>'.$param['name'].'，選擇的訂單已同步至頂新。';
                PurchaseOrderSyncToDigiwin::dispatchNow($param);
            }
            // PurchaseOrderSyncToDigiwin::dispatchNow($param);
        }elseif($param['cate'] == 'SyncToGate'){ //鼎新採購單同步至中繼
            $message = '執行編號：'.$param['export_no'].'<br>'.$param['name'].'，已於背端執行，請稍後一段時間，再重新查詢或按F5重新整理。';
            $message = '執行編號：'.$param['export_no'].'<br>'.$param['name'].'，鼎新採購單已同步至中繼站。';
            DigiwinPurchaseOrderSynchronize::dispatch($param);
            // DigiwinPurchaseOrderSynchronize::dispatchNow($param);
        }elseif($param['cate'] == 'Export'){ //匯出採購單
            if(env('APP_ENV') == 'local'){
                //本機測試用
                return AdminExportJob::dispatchNow($param); //直接馬上下載則必須使用 return
            }else{
                //放入隊列
                $param['store'] = true;
                AdminExportJob::dispatch($param);
            }
        }elseif(strstr($param['cate'],'Notice')){
            $param['type'] == 'Download' ? $param['filename'] = $param['name'].'_'.time().'.zip' : '';
            $param['type'] == 'Download' ? $message = $param['name'].'，工作單號：'.$param['export_no'].'<br>匯出已於背端執行，請過一段時間至匯出中心下載，<br>檔案名稱：'.$param['filename'].'<br>匯出中心連結：<a href="'.$url.'" target="_blank"><span class="text-danger">'.$url.'</span></a>' : $message = '通知廠商已於背端執行，請過一段時間與廠商確認是否有收到採購單信件。';
            env('APP_ENV') == 'local' ? PurchaseOrderNoticeVendor::dispatchNow($param) : PurchaseOrderNoticeVendor::dispatch($param);
        }elseif($param['cate'] == 'CancelOrder'){
            $message = '選擇的採購單已被取消。';
            env('APP_ENV') == 'local' ? CancelPurchaseOrder::dispatchNow($param) : CancelPurchaseOrder::dispatch($param);
        }
        Session::put('info', $message);
        return redirect()->back();
    }

    public function notice(Request $request)
    {
        if(!empty($request->id)){
            $param['id'] = [$request->id];
            $param['cate'] = 'Notice';
            $param['type'] = 'Email';
            $param['model'] = 'purchase';
            $param['version'] = 'old';
            $param['admin_id'] = auth('gate')->user()->id;
            $param['admin_name'] = auth('gate')->user()->name;
            $param['name'] = '通知廠商_自行勾選';
            $param['export_no'] = time();
            $param['start_time'] = date('Y-m-d H:i:s');
            PurchaseOrderNoticeVendor::dispatch($param);
            $message = '通知廠商已於背端執行，請過一段時間與廠商確認是否有收到採購單信件。';
            Session::put('info', $message);
        }
        return redirect()->back();
    }

    public function noticeNew(Request $request)
    {
        if(!empty($request->id)){
            $param['id'] = [$request->id];
            $param['cate'] = 'NoticeVendor';
            $param['type'] = 'Email';
            $param['model'] = 'purchase';
            $param['version'] = 'new';
            $param['admin_id'] = auth('gate')->user()->id;
            $param['admin_name'] = auth('gate')->user()->name;
            $param['name'] = '通知廠商_自行勾選';
            $param['export_no'] = time();
            $param['start_time'] = date('Y-m-d H:i:s');
            PurchaseOrderNoticeVendor::dispatch($param);
            $message = '通知廠商已於背端執行，請過一段時間與廠商確認是否有收到採購單信件。';
            Session::put('info', $message);
        }
        return redirect()->back();
    }

    public function getChangeLog(Request $request)
    {
        if(!empty($request->purchase_no) && is_numeric($request->purchase_no)){
            $purchaseNo = $request->purchase_no;
            $logs = PurchaseOrderChangeLogDB::where('purchase_no',$purchaseNo)
                ->select([
                    '*',
                    DB::raw("DATE_FORMAT(created_at,'%Y/%m/%d %H:%i:%s') as modify_time"),
                    'erp_purchase_no' => PurchaseOrderDB::whereColumn('purchase_order_change_logs.purchase_no','purchase_orders.purchase_no')->select('erp_purchase_no')->limit(1),
                    'admin_name' => AdminDB::whereColumn('admins.id','purchase_order_change_logs.admin_id')->select('name')->limit(1),
                ])->orderBy('id','desc')->get();
            return response()->json($logs);
        }
        return null;
    }

    public function getLog(Request $request)
    {
        if(!empty($request->id) && is_numeric($request->id)){
            $id = $request->id;
            $logs = PurchaseSyncedLogDB::join('purchase_orders','purchase_orders.id','purchase_synced_logs.purchase_order_id')
            ->where('purchase_synced_logs.purchase_order_id',$id)
                ->select([
                    'purchase_synced_logs.*',
                    'purchase_orders.purchase_no',
                    'purchase_orders.erp_purchase_no',
                    'purchase_orders.status',
                    DB::raw("DATE_FORMAT(purchase_synced_logs.created_at,'%Y-%m-%d %H:%i:%s') as synced_time"),
                ])->orderBy('purchase_synced_logs.created_at','desc')->get();
            return response()->json($logs);
        }
        return null;
    }

    public function import(Request $request)
    {
        // $request->request->add(['test' => true]); //加入test bypass 檔案匯入功能
        $request->request->add(['admin_id' => auth('gate')->user()->id]); //加入request
        $request->request->add(['import_no' => time()]); //加入request
        $request->cate == 'stockin' ? $cate = '倉庫入庫匯入' : '廠商直寄匯入';
        if($request->cate == 'stockin'){
            if ($request->hasFile('filename')) {
                $file = $request->file('filename');
                $uploadedFileMimeType = $file->getMimeType();
                $mimes = array('application/excel','application/vnd.ms-excel','application/vnd.msexcel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/CDFV2','application/octet-stream');
                if(in_array($uploadedFileMimeType, $mimes)){
                    //檔案不可以直接放入Job中使用dispatch去跑,只能使用dispatchNow
                    $result = PurchaseStockinFileImportJob::dispatchNow($request);
                    if(!empty($result)){
                        if($result == 'sheets error'){
                            $url = 'https://'.env('GATE_DOMAIN').'/sample/%E5%85%A5%E5%BA%AB%E5%A0%B1%E8%A1%A8%E7%AF%84%E6%9C%AC.xls';
                            $message = "該檔案 Sheet 數量不符規定，請參考 <a href='$url'>入庫報表範本</a> ，製作正確的檔案。";
                            Session::put('error', $message);
                        }elseif($result == 'no N value'){
                            $message = '檔案內資料O欄，至少有一筆不是 N 值。';
                            Session::put('error', $message);
                        }elseif($result == 'purhcase no error'){
                            $message = '檔案內資料缺少採購單號資料，請檢查第二Sheet是否有採購單號碼資料。';
                            Session::put('error', $message);
                        }elseif($result == 'rows error'){
                            $message = '檔案內資料欄位數錯誤，請檢查第一Sheet的欄位總數為16。';
                            Session::put('error', $message);
                        }elseif($result == 'no data'){
                            $message = '檔案內資料未被儲存，請檢查所有資料是否已經處理完成。';
                            Session::put('warning', $message);
                        }elseif(!empty($result['import_no'])){
                            if(!empty($request->test) && $request->test == true){
                                PurchaseStockinImportJob::dispatchNow($result);
                            }else{
                                if(env('APP_ENV') == 'local'){
                                    PurchaseStockinImportJob::dispatchNow($result);
                                }else{
                                    //背端處理檢查
                                    $chkQueue = 0;
                                    $delay = null;
                                    $minutes = 1;
                                    $jobName = 'PurchaseStockinImportJob';
                                    $countQueue = Redis::llen('queues:default');
                                    $allQueues = Redis::lrange('queues:default', 0, -1);
                                    $allDelayQueues = Redis::zrange('queues:default:delayed', 0, -1);
                                    if(count($allQueues) > 0){
                                        if(count($allDelayQueues) > 0){
                                            $allDelayQueues = array_reverse($allDelayQueues);
                                            for($i=0;$i<count($allDelayQueues);$i++){
                                                $job = json_decode($allDelayQueues[$i],true);
                                                if(strstr($job['displayName'],$jobName)){
                                                    $commandStr = $job['data']['command'];
                                                    if(strstr($commandStr,'s:26')){
                                                        $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                                                        $command = explode('____',$commandStr);
                                                        $time = $command[1];
                                                        $delay = Carbon::parse($time)->addminutes($minutes);
                                                    }else{
                                                        $delay = Carbon::now()->addminutes($minutes);
                                                    }
                                                    $chkQueue++;
                                                    break;
                                                }
                                            }
                                        }else{
                                            foreach($allQueues as $queue){
                                                $job = json_decode($queue,true);
                                                if(strstr($job['displayName'],$jobName)){
                                                    $delay = Carbon::now()->addminutes($minutes);
                                                    $chkQueue++;
                                                }
                                            }
                                        }
                                    }else{
                                        $queue = DB::table('jobs')->where('payload','like',"%$jobName%")->orderBy('id','desc')->first();
                                        if(!empty($queue)){
                                            $payload = $queue->payload;
                                            $job = json_decode($payload,true);
                                            $commandStr = $job['data']['command'];
                                            if(strstr($commandStr,'s:26')){
                                                $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                                                $command = explode('____',$commandStr);
                                                $time = $command[1];
                                                $delay = Carbon::parse($time)->addminutes($minutes);
                                            }else{
                                                $delay = Carbon::now()->addminutes($minutes);
                                            }
                                            $chkQueue++;
                                        }
                                    }
                                    if($chkQueue > 0){
                                        !empty($delay) ? PurchaseStockinImportJob::dispatch($result)->delay($delay) : PurchaseStockinImportJob::dispatch($result);
                                    }else{
                                        PurchaseStockinImportJob::dispatch($result);
                                    }
                                }
                            }
                            $message = "$cate 已於背端處理，請稍後一段時間，按F5重新整理頁面，確認是否已完成。";
                            Session::put('success', $message);
                        }
                    }
                    return redirect()->back();
                } else{
                    $message = '只接受 xls 或 xlsx 檔案格式';
                    Session::put('error', $message);
                    return redirect()->back();
                }
            }
        }
    }

    public function itemMemo(Request $request)
    {
        // return $request;
        $id = (INT)$request->id;
        $orderItem = PurchaseOrderItemDB::where('id', $id)->first();
        if ($orderItem) {
            $orderItem->update(['memo' => $request->memo]);
            $message = 'success';
        } else {
            $message = 'fail';
        }
        return $message;
    }

    public function qtyModify(Request $request)
    {
        $id = (INT)$request->id;
        $type = $request->type;
        $qty = $request->qty;
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchaseOrderItemPackageTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemPackageDB)->getTable();
        if($type == 'item'){
            $item = PurchaseOrderItemDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
            ->select([
                $purchaseOrderItemTable.'.*',
                $productModelTable.'.digiwin_no',
            ])->find($id);
            $poiId = $item->id;
            $poipId = null;
        }elseif ($type == 'package') {
            $item = PurchaseOrderItemPackageDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemPackageTable.'.product_model_id')
            ->select([
                $purchaseOrderItemPackageTable.'.*',
                $productModelTable.'.digiwin_no',
            ])->find($id);
            $poiId = $item->purchase_order_item_id;
            $poipId = $item->id;
        }else{
            return 'fail';
        }
        if (!empty($item) && $item->quantity >= $qty) {
            $erpPURTG = ErpPURTGDB::find($item->erp_stockin_no);
            $purchaseSingle = PurchaseOrderItemSingleDB::where([['poi_id',$poiId],['poip_id',$poipId],['is_del',0]])->first();
            $purchaseOrder = PurchaseOrderDB::where('purchase_no',$item->purchase_no)->first();
            $vendorId = $purchaseOrder->vendor_id;
            $erpPurchaseType = $purchaseOrder->type;
            $erpPurchaseNo = $purchaseSingle->erp_purchase_no;
            $erpPurchaseSno = $purchaseSingle->erp_purchase_sno;
            $erpStockinNo = $purchaseSingle->erp_stockin_no;
            $erpVendor = ErpVendorDB::find('A'.str_pad($vendorId,5,'0',STR_PAD_LEFT));
            $erpPURTH = ErpPURTHDB::where([['TH002',$erpStockinNo],['TH011',$erpPurchaseType],['TH012',$erpPurchaseNo],['TH013',$erpPurchaseSno]])->first();
            $TH003 = $erpPURTH->TH003;
            $TH016 = $erpPURTH->TH016;  //數量
            $TH018 = $erpPURTH->TH018;
            $TH045 = $erpPURTH->TH045;
            $TH046 = $erpPURTH->TH046;
            $TG017 = $erpPURTG->TG017;
            $TG019 = $erpPURTG->TG019;
            $TG026 = $erpPURTG->TG026;
            if($erpPURTG->TG013 == 'N'){
                //修改erp的進貨單商品
                $erpVendor->MA044 == 1 ? $newTH045 = ($qty * $TH018) / 1.05 : $newTH045 = $qty * $TH018;
                $erpVendor->MA044 == 1 ? $newTH046 = ($qty * $TH018) - $newTH045 : $newTH046 = ($qty * $TH018) * 0.05;
                $diffQty = $TH016 - $qty; //數量差
                $diffPrice = $TH045 - $newTH045;
                $diffTax = $TH046 - $newTH046;
                $digiWinNo = $item->digiwin_no;
                $item->update(['stockin_quantity' => $qty]);
                $temp = ErpPURTHDB::where([['TH004',$digiWinNo],['TH011',$erpPurchaseType],['TH012',$erpPurchaseNo]])->get();
                if(count($temp) > 1){
                    ErpPURTHDB::where([['TH002',$erpStockinNo],['TH003',$TH003]])
                    ->update([
                        'TH015' => $diffQty,
                        'TH016' => $diffQty,
                        'TH019' => $diffQty * $TH018,
                        'TH045' => round($diffPrice,2), //原幣未稅金額
                        'TH046' => round($diffTax,2), //原幣稅額
                        'TH047' => round($diffPrice,2), //本幣未稅金額
                        'TH048' => round($diffTax,2), //本幣稅額
                    ]);
                }else{
                    ErpPURTHDB::where([['TH002',$erpStockinNo],['TH003',$TH003]])
                    ->update([
                        'TH015' => $qty,
                        'TH016' => $qty,
                        'TH019' => $qty * $TH018,
                        'TH045' => round($newTH045,2), //原幣未稅金額
                        'TH046' => round($newTH046,2), //原幣稅額
                        'TH047' => round($newTH045,2), //本幣未稅金額
                        'TH048' => round($newTH046,2), //本幣稅額
                    ]);
                }
                $erpPURTG->update([
                    'TG017' => $TG017 - $diffPrice,
                    'TG019' => $TG019 - $diffTax,
                    'TG026' => $TG026 - $qty,
                    'TG028' => $TG017 - $diffPrice,
                    'TG031' => $TG017 - $diffPrice,
                    'TG032' => $TG019 - $diffTax,
                ]);
                //更新中繼的商品
                $purchaseSingle->update(['stockin_quantity' => $qty]);
                //更新 $item
                $item->update(['stockin_quantity' => $qty]);
                $message = 'success';
            }else{
                $message = 'fail';
            }
        } else {
            $message = 'fail';
        }
        return $message;
    }

    public function returnForm($id)
    {
        $menuCode = 'M28S1';
        $compact = [];
        $shippingMethods = [];
        $shippingVendors = [];
        $vendors = [];
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchase = PurchaseOrderDB::join($vendorTable,$vendorTable.'.id',$purchaseOrderTable.'.vendor_id')
            ->with('items','items.package','checkStockin')
            ->select([
                $purchaseOrderTable.'.*',
                DB::raw("(CASE WHEN $purchaseOrderTable.status = -1 THEN '已取消' WHEN $purchaseOrderTable.status = 0 THEN '尚未採購' WHEN $purchaseOrderTable.status = 1 THEN '已採購' WHEN $purchaseOrderTable.status = 2 THEN '已入庫' END) as status_text"),
                $vendorTable.'.name as vendor_name',
            ])->findOrFail($id);
        if(!empty($purchase->order_ids)){
            $purchase->orders = OrderDB::whereIn('id',explode(',',$purchase->order_ids))->orderBy('id','desc')->get();
        }
        foreach($purchase->items as $item){
            $item->stockinQty = 0;
            if(strstr($item->sku,'BOM')){
                foreach($item->package as $package){
                    if(count($package->returns) > 0){
                        foreach($package->returns as $return){
                            $package->returnQty += $return->quantity;
                        }
                    }
                    if(count($package->stockins) > 0){
                        $package->stockinQty;
                        foreach($package->stockins as $stockin){
                            $package->stockinQty += $stockin->stockin_quantity;
                        }
                    }
                }
            }else{
                if(count($item->stockins) > 0){
                    foreach($item->stockins as $stockin){
                        $item->stockinQty += $stockin->stockin_quantity;
                    }
                }
                foreach($item->returns as $return){
                    $item->returnQty += $return->quantity;
                }
            }
        }

        $compact = array_merge($compact, ['menuCode','purchase','shippingMethods','shippingVendors','vendors']);
        return view('gate.purchases.productReturn', compact($compact));

    }

    public function productReturn(Request $request, $id)
    {
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();

        $purchaseOrder = PurchaseOrderDB::with('exportItems','exportItems.stockins','exportItems.returns','exportItems.exportPackage','exportItems.exportPackage.stockins','exportItems.exportPackage.returns')->find($id);

        if(!empty($purchaseOrder)){
            // 先檢查是否超過入庫數量
            $error = 0;
            $message = '';
            foreach ($request->items as $ritem) {
                if (!empty($ritem['qty'])) { //有數量才進行檢查
                    foreach($purchaseOrder->exportItems as $item){
                        if($item->id == $ritem['id']){
                            $returnQty = $stockinQty = 0;
                            if(strstr($item->sku,'BOM')){ //組合品
                                foreach ($item->exportPackage as $package) {
                                    $usedQty = $package->quantity / $item->quantity;
                                    if(count($package->returns) > 0){
                                        foreach($package->returns as $return){
                                            $returnQty += $return->quantity;
                                        }
                                    }
                                    if(count($package->stockins) > 0){
                                        foreach($package->stockins as $stockin){
                                            $stockinQty += $stockin->stockin_quantity;
                                        }
                                    }
                                    if($ritem['qty'] * $usedQty > ($stockinQty - $returnQty) || (($stockinQty - $returnQty) < ($ritem['qty'] * $usedQty))){
                                        $error++;
                                        $message .= $item->product_name.', ';
                                    }
                                }
                            }else{ //單品
                                if(count($item->stockins) > 0){
                                    foreach($item->stockins as $stockin){
                                        $stockinQty += $stockin->stockin_quantity;
                                    }
                                }
                                if(count($item->returns) > 0){
                                    foreach($item->returns as $return){
                                        $returnQty += $return->quantity;
                                    }
                                }
                                if($ritem['qty'] > ($stockinQty - $returnQty)){
                                    $error++;
                                    $message .= $item->product_name.', ';
                                }
                            }
                        }
                    }
                }
            }
            if($error == 0){ //檢查OK
                $request->request->add(['purchaseOrderId' => $id]);
                ReturnDiscountJob::dispatchNow($request);
            }else{
                Session::put('error', $message."退貨數量不能大於進貨數量，請注意退貨數量已併入計算中");
            }
        }
        return redirect()->back();
    }

    public function productReturn_old(Request $request, $id)
    {
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();

        $purchaseOrder = PurchaseOrderDB::find($id);
        if(!empty($purchaseOrder)){
            // 先檢查是否超過入庫數量
            $error = 0;
            foreach ($request->items as $ritem) {
                if (!empty($ritem['qty'])) { //有數量才進行檢查
                    $purchaseItem = PurchaseOrderItemDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
                        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                        ->select([
                            $purchaseOrderItemTable.'.*',
                            $productModelTable.'.sku',
                            $productModelTable.'.digiwin_no',
                            $productModelTable.'.gtin13',
                        ])->find($ritem['id']);
                    $returnItem = ReturnDiscountItemDB::where('poi_id',$purchaseItem->id)->first();
                    !empty($returnItem) ? $returnQuantity = $returnItem->quantity : $returnQuantity = 0;
                    if (strstr($purchaseItem->sku, 'BOM')) {
                        $packages = PurchaseOrderItemPackageDB::where('purchase_order_item_id', $purchaseItem->id)->get();
                        foreach ($packages as $package) {
                            $returnItemPackage = ReturnDiscountItemPackageDB::where([['poi_id',$purchaseItem->id],['poip_id',$package->id]])->first();
                            !empty($returnItemPackage) ? $returnQuantity = $returnItemPackage->quantity : $returnQuantity = 0;
                             $package->usedQty = $package->quantity / $purchaseItem->quantity;
                            if($ritem['qty'] * $package->usedQty > ($package->stockin_quantity - $returnQuantity) || (($package->stockin_quantity - $returnQuantity) < ($ritem['qty'] * $package->usedQty))){
                                $error++;
                            }
                        }
                    } else {
                        if ($ritem['qty'] > $purchaseItem->stockin_quantity - $returnQuantity) {
                            $error++;
                        }
                    }
                }
            }
            if($error == 0){ //檢查OK
                $request->request->add(['purchaseOrderId' => $id]);
                ReturnDiscountJob::dispatchNow($request);
            }else{
                Session::put('error', "退貨數量不能大於進貨數量，請注意退貨數量已併入計算中");
            }
        }
        return redirect()->back();
    }

    public function removeOrder(Request $request)
    {
        $order = OrderDB::findOrFail($request->orderId);
        $purchaseOrder = PurchaseOrderDB::findOrFail($request->id);
        $orderItemIds = OrderItemDB::where('order_id',$order->id)->select('id')->get()->pluck('id')->all();
        $orderItemIdDiff = array_diff(explode(',',$purchaseOrder->order_item_ids),$orderItemIds);
        $orderIdDiff = array_diff(explode(',',$purchaseOrder->order_ids),[$order->id]);
        $purchaseOrder->update(['order_ids' => join(',',$orderIdDiff), 'order_item_ids' => $orderItemIdDiff]);
        return redirect()->back();
    }

    public function getStockin(Request $request)
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $stockinItemSingleTable = env('DB_DATABASE').'.'.(new StockinItemSingleDB)->getTable();
        if(!empty($request->poisId) && is_numeric($request->poisId)){
            $items = StockinItemSingleDB::join($productModelTable,$productModelTable.'.id',$stockinItemSingleTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->where($stockinItemSingleTable.'.pois_id',$request->poisId)
            ->where($stockinItemSingleTable.'.is_del',0)
            ->select([
                $stockinItemSingleTable.'.*',
                $productModelTable.'.sku',
                $productModelTable.'.digiwin_no',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            ])->orderBy('stockin_date','desc')->get();
            return response()->json($items);
        }
        return null;
    }

    public function stockinModify(Request $request)
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $stockinItemSingleTable = env('DB_DATABASE').'.'.(new StockinItemSingleDB)->getTable();
        $chkModify = 0;
        if(!empty($request->data)){
            $data = $request->data;
            for($i=0;$i<count($data);$i++){
                $item = StockinItemSingleDB::join($productModelTable,$productModelTable.'.id',$stockinItemSingleTable.'.product_model_id')
                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                ->select([
                    $stockinItemSingleTable.'.*',
                    $vendorTable.'.id as vendor_id',
                    $productModelTable.'.sku',
                    $productModelTable.'.digiwin_no',
                    DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                ])->find($data[$i]['id']);
                $purchaseNo = $item->purchase_no;
                $erpVendor = ErpVendorDB::find('A'.str_pad($item->vendor_id,5,0,STR_PAD_LEFT));
                if(!empty($item) && $item->stockin_quantity != $data[$i]['qty']){
                    $originQty = $item->stockin_quantity;
                    $diffQty = $originQty - $data[$i]['qty'];
                    $diffPrice = $diffQty * $item->purchase_price;
                    $diffTax = $diffPrice * 0.05;
                    $erpStockinItem = ErpPURTHDB::where([['TH002',$item->erp_stockin_no],['TH003',$item->erp_stockin_sno]])->first();
                    if(!empty($erpStockinItem) && $erpStockinItem->TH030 == 'N'){
                        $chkModify++;
                        ErpPURTHDB::where([['TH002',$item->erp_stockin_no],['TH003',$item->erp_stockin_sno]])
                        ->update([
                            'TH007' => $data[$i]['qty'], //進貨數量
                            'TH015' => $data[$i]['qty'], //驗收數量
                            'TH016' => $data[$i]['qty'], //計價數量
                            'TH019' => round($erpStockinItem->TH019 - $diffPrice,0), //原幣進貨金額
                            'TH045' => round($erpStockinItem->TH045 - $diffPrice,0), //原幣未稅金額
                            'TH046' => round($erpStockinItem->TH046 - $diffTax,0), //原幣稅額
                            'TH047' => round($erpStockinItem->TH047 - $diffPrice,0), //本幣未稅金額
                            'TH048' => round($erpStockinItem->TH048 - $diffTax,0), //本幣稅額
                        ]);
                        // 改用將進貨單資料拉出來, 重新統計後更新
                        $TH015 = $TH019 = $TH045 = $TH046 = 0;
                        $erpStockinItems = ErpPURTHDB::where('TH002',$item->erp_stockin_no)->get();
                        if(count($erpStockinItems) > 0){
                            foreach($erpStockinItems as $stockinItem){
                                $TH015 += $stockinItem->TH015;
                                $TH019 += $stockinItem->TH019;
                                $TH045 += $stockinItem->TH045;
                                $TH046 += $stockinItem->TH046;
                            }
                            ErpPURTGDB::where('TG002',$item->erp_stockin_no)->update([
                                'TG017' => round($TH019 ,0),
                                'TG019' => round($TH046 ,0),
                                'TG026' => round($TH015 ,0),
                                'TG028' => round($TH045 ,0),
                                'TG031' => round($TH045 ,0),
                                'TG032' => round($TH046 ,0),
                            ]);
                        }
                        $item->update(['stockin_quantity' => $data[$i]['qty']]);
                        $log = PurchaseOrderChangeLogDB::create([
                            'purchase_no' => $item->purchase_no,
                            'admin_id' => auth('gate')->user()->id,
                            'poi_id' => $item->id,
                            'sku' => $item->sku,
                            'digiwin_no' => $item->digiwin_no,
                            'product_name' => $item->product_name,
                            'status' => '修改',
                            'quantity' => $originQty.' => '.$data[$i]['qty'],
                            'price' => $item->purchase_price,
                            'date' => $item->stockin_date,
                            'memo' => '入庫單數量修改',
                        ]);
                    }else{
                        $log = PurchaseOrderChangeLogDB::create([
                            'purchase_no' => $item->purchase_no,
                            'admin_id' => auth('gate')->user()->id,
                            'poi_id' => $item->id,
                            'sku' => $item->sku,
                            'digiwin_no' => $item->digiwin_no,
                            'product_name' => $item->product_name,
                            'status' => '入庫修改',
                            'memo' => '鼎新入庫單已確認或不存在不可修改。',
                        ]);
                    }
                }
            }
            //有實際修改才做檢查入庫狀況
            if($chkModify > 0){
                $purchaseOrderItems = PurchaseOrderItemSingleDB::where('purchase_no',$purchaseNo)->where('is_del',0)->orderBy('stockin_date','asc')->get();
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
                    if($item->quantity <= $in){
                        $chk++;
                    }
                }
                if($counts == $chk){
                    $purchaseOrder = PurchaseOrderDB::where('purchase_no',$purchaseNo)->first();
                    $purchaseOrder->update(['status' => 2, 'stockin_finish_date' => date('Y-m-d')]);
                    $log = PurchaseOrderChangeLogDB::create([
                        'purchase_no' => $purchaseOrder->purchase_no,
                        'admin_id' => auth('gate')->user()->id,
                        'status' => '修改',
                        'memo' => '採購單商品已全部入庫 ('.date('Y-m-d').')',
                    ]);
                }else{
                    $purchaseOrder = PurchaseOrderDB::where('purchase_no',$purchaseNo)->first();
                    $purchaseOrder->update(['status' => 1, 'stockin_finish_date' => null]);
                    $log = PurchaseOrderChangeLogDB::create([
                        'purchase_no' => $purchaseOrder->purchase_no,
                        'admin_id' => auth('gate')->user()->id,
                        'status' => '修改',
                        'memo' => '入庫單數量修改，採購單商品未全部入庫',
                    ]);
                }
            }
        }
        if(!empty($request->url) && strstr($request->url,'https://gate.localhost/purchases')){
            return redirect()->to($request->url);
        }else{
            return redirect()->back();
        }
    }

    public function dateModify(Request $request)
    {
        $data = $request->all();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        if(!empty($data['id']) && !empty($data['date']) && !empty($data['orderNumber'])){
            $purchaseItem = PurchaseOrderItemDB::with('single','stockins','package','package.single','package.stockins')
            ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->where([
                [$purchaseOrderItemTable.'.is_del',0],
                [$purchaseOrderItemTable.'.is_close',0],
                [$purchaseOrderItemTable.'.is_lock',0]
            ])->select([
                $purchaseOrderItemTable.'.*',
                $productModelTable.'.sku',
                $productModelTable.'.gtin13',
                $productTable.'.package_data',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            ])->find($data['id']);
            if(!empty($purchaseItem)){
                $beforeQuantity = $purchaseItem->quantity;
                $beforeVendorArrivalDate = $purchaseItem->vendor_arrival_date;
                $productModelId = $purchaseItem->product_model_id;
                $packages = $purchaseItem->package; //原始的package
                $purchaseOrder = PurchaseOrderDB::with('items','items.single','items.package','items.package.single')->where('purchase_no',$purchaseItem->purchase_no)->first();
                $orderIds = explode(',',$purchaseOrder->order_ids);
                $orderItemIds = explode(',',$purchaseOrder->order_item_ids);
                $productModelIds = explode(',',$purchaseOrder->product_model_ids);
                $order = OrderDB::with('sellImports','sells','items','items.package','items.sells','items.package.sells')->where('order_number',$data['orderNumber'])->first();
                $order = $this->oneOrderItemTransfer($order);
                if(!empty($order)){
                    $removeOrderItemIds = [];
                    //找出該訂單的商品資料
                    $orderId = $order->id;
                    $orderItemId = null;
                    foreach($order->items as $item){
                        if($item->is_del == 0 && $item->product_model_id == $purchaseItem->product_model_id && $item->direct_shipment == 1 && $item->syncedOrderItem->vendor_arrival_date == $beforeVendorArrivalDate){
                            $orderItemId = $item->id;
                            $qty = $item->quantity;
                            $orderItem = $item;
                            break;
                        }
                    }
                    if(!empty($orderItemId)){
                        //剩餘數量
                        $remindQty = $purchaseItem->quantity - $qty;
                        if(!empty($purchaseOrder->erp_purchase_no)){ //已同步鼎新, 移除採購並修改數量
                            $chkStockin = 0;
                            if(strstr($purchaseItem->sku,'BOM')){
                                foreach($purchaseItem->package as $package){
                                    if($package->is_del == 0 && count($package->stockins) > 0){
                                        $chkStockin++;
                                    }
                                }
                            }else{
                                $chkStockin = count($purchaseItem->stockins);
                            }
                            //檢查訂單是否已經填寫出貨出貨或入庫
                            if(count($order->sells) > 0){
                                Session::put('error','該訂單已經完成出貨。');
                            }elseif($chkStockin > 0){
                                Session::put('error','該筆商品已有入庫資料，無法變更。');
                            }elseif(count($order->sellImports) > 0){
                                Session::put('error','廠商已填寫出貨資料，請與廠商聯繫確認。');
                            }else{
                                //清除訂單商品採購註記並變更廠商到貨日
                                $orders = OrderDB::with('sellImports','sells','items','items.package','items.sells','items.package.sells')->whereIn('id',$orderIds)->get();
                                foreach($orders as $order){
                                    foreach($order->items as $item){
                                        if($item->is_del == 0 && $item->product_model_id == $purchaseItem->product_model_id && $item->direct_shipment == 1 && $item->syncedOrderItem->vendor_arrival_date == $beforeVendorArrivalDate){
                                            $removeOrderItemIds[] = $item->id;
                                            if($item->id == $orderItemId){ //修改日期並移除採購註記
                                                if(strstr($item->sku,'BOM')){
                                                    foreach($item->package as $package){
                                                        if($package->is_del == 0){
                                                            $package->syncedOrderItemPackage->update(['purchase_no' => null, 'purchase_date' => null, 'erp_purchase_no' => null, 'vendor_arrival_date' => $data['date']]);
                                                        }
                                                    }
                                                }
                                                $item->syncedOrderItem->update(['purchase_no' => null, 'purchase_date' => null, 'erp_purchase_no' => null, 'vendor_arrival_date' => $data['date']]);
                                            }else{ //不修改日期, 移除採購註記
                                                if(strstr($item->sku,'BOM')){
                                                    foreach($item->package as $package){
                                                        if($package->is_del == 0){
                                                            $package->syncedOrderItemPackage->update(['purchase_no' => null, 'purchase_date' => null, 'erp_purchase_no' => null]);
                                                        }
                                                    }
                                                }
                                                $item->syncedOrderItem->update(['purchase_no' => null, 'purchase_date' => null, 'erp_purchase_no' => null]);
                                            }
                                            break;
                                        }
                                    }
                                }
                                //將該訂單資料移出採購單
                                for($i=0;$i<count($orderItemIds);$i++){
                                    for($j=0;$j<count($removeOrderItemIds);$j++){
                                        if($orderItemIds[$i] == $removeOrderItemIds[$j]){
                                            unset($orderItemIds[$i]);
                                            break;
                                        }
                                    }
                                }
                                sort($orderItemIds);
                                //檢查訂單商品是否還在採購單內
                                $removeOrderIds = [];
                                foreach($orders as $order){
                                    $chkOrder = 0;
                                    for($i=0;$i<count($orderItemIds);$i++){
                                        foreach($order->items as $item){
                                            if($item->id == $orderItemIds[$i]){
                                                $chkOrder++;
                                                break;
                                            }
                                        }
                                    }
                                    //若無則移除訂單id
                                    $chkOrder == 0 ? $removeOrderIds[] = $order->id : '';
                                }
                                if(count($removeOrderIds) > 0){
                                    for($i=0;$i<count($orderIds);$i++){
                                        for($j=0;$j<count($removeOrderIds);$j++){
                                            if($orderIds[$i] == $removeOrderIds[$j]){
                                                unset($orderIds[$i]);
                                                break;
                                            }
                                        }
                                    }
                                    sort($orderIds);
                                }
                                $purchaseOrder->update(['order_ids' => join(',',$orderIds), 'order_item_ids' => join(',',$orderItemIds)]);
                                //原始採購資料變動數量為0
                                $modifyData[0] = [
                                    'id' => $purchaseItem->id,
                                    'vendor_arrival_date' => $purchaseItem->vendor_arrival_date,
                                    'quantity' => 0, //剩餘的數量
                                    'purchase_price' => $purchaseItem->purchase_price,
                                ];
                                $this->updateModify($purchaseOrder, $modifyData, $purchaseOrder->purchase_date, $purchaseOrder->memo);
                                Session::put('success','已修改完成，並已重新同步鼎新與通知廠商，請單獨重建該商品採購單。');
                            }
                        }else{ //未同步鼎新, 修改數量增列一條新的
                            $newPurchaseItem = $purchaseItem->toArray();
                            $newPurchaseItem['quantity'] = $qty;
                            $newPurchaseItem['vendor_arrival_date'] = $data['date'];
                            $chkSameItem = 0;
                            foreach($purchaseOrder->items as $pItem){
                                if($pItem->product_model_id == $productModelId && $pItem->vendor_arrival_date == $data['date'] && $pItem->direct_shipment == 1 && $pItem->quantity > 0 && $pItem->is_del == 0 && $pItem->is_close == 0 && $pItem->is_lock == 0){
                                    //已有相同的商品列
                                    $chkSameItem++;
                                    $originQty = $pItem->quantity;
                                    $newQty = $pItem->quantity + $qty;
                                    if(strstr($pItem,'BOM')){
                                        $packageData = json_decode(str_replace('	','',$pItem->package_data));
                                        if(is_array($packageData) && count($packageData) > 0){
                                            foreach($packageData as $pp){
                                                if(isset($pp->is_del)){
                                                    if($pp->is_del == 0){
                                                        if($pItem->sku == $pp->bom){
                                                            foreach($pp->lists as $list) {
                                                                foreach($pItem->package as $package){
                                                                    if($package->is_del == 0 && $package->sku == $list->sku){
                                                                        $package->single->update(['quantity' => $list->quantity * $newQty]);
                                                                        $package->update(['quantity' => $list->quantity * $newQty]);
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }else{
                                                    if($pItem->sku == $pp->bom){
                                                        foreach($pp->lists as $list) {
                                                            foreach($pItem->package as $package){
                                                                if($package->is_del == 0 && $package->sku == $list->sku){
                                                                    $package->single->update(['quantity' => $list->quantity * $newQty]);
                                                                    $package->update(['quantity' => $list->quantity * $newQty]);
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }else{
                                        $pItem->single->update(['quantity' => $newQty]);
                                    }
                                    $pItem->update(['quantity' => $newQty]);
                                    $log = PurchaseOrderChangeLogDB::create([
                                        'purchase_no' => $pItem->purchase_no,
                                        'admin_id' => auth('gate')->user()->id,
                                        'poi_id' => $pItem->id,
                                        'sku' => $pItem->sku,
                                        'digiwin_no' => $pItem->digiwin_no,
                                        'product_name' => $pItem->product_name,
                                        'status' => '合併',
                                        'quantity' => $originQty.' => '.$newQty,
                                        'price' => $pItem->purchase_price.' => '.$pItem->purchase_price,
                                        'date' => $beforeVendorArrivalDate.' => '.$data['date'],
                                        'memo' => '數量 合併',
                                    ]);
                                    break;
                                }
                            }
                            if($chkSameItem == 0){
                                $sku = $newPurchaseItem['sku'];
                                $gtin13 = $newPurchaseItem['gtin13'];
                                $packageData = $newPurchaseItem['package_data'];
                                //建立新的採購商品
                                unset($newPurchaseItem['stockins']);
                                unset($newPurchaseItem['created_at']);
                                unset($newPurchaseItem['updated_at']);
                                unset($newPurchaseItem['package_data']);
                                unset($newPurchaseItem['digiwin_no']);
                                unset($newPurchaseItem['sku']);
                                unset($newPurchaseItem['gtin13']);
                                unset($newPurchaseItem['package']);
                                $purchaseOrderItem = PurchaseOrderItemDB::create($newPurchaseItem);
                                if(strstr($sku,'BOM')){
                                    $packageData = json_decode(str_replace('	','',$packageData));
                                    if(is_array($packageData) && count($packageData) > 0){
                                        foreach($packageData as $pp){
                                            if(isset($pp->is_del)){
                                                if($pp->is_del == 0){
                                                    foreach($pp->lists as $list) {
                                                        foreach($packages as $package){
                                                            if($list->sku == $package->sku){
                                                                // //建立中繼採購單組合商品
                                                                $purchaseOrderItemPackage = PurchaseOrderItemPackageDB::create([
                                                                    'purchase_no' => $package->purchase_no,
                                                                    'purchase_order_item_id' => $purchaseOrderItem->id,
                                                                    'product_model_id' => $package->product_model_id,
                                                                    'gtin13' => $package->gtin13,
                                                                    'vendor_id' => $package->vendor_id,
                                                                    'purchase_price' => $package->purchase_price,
                                                                    'quantity' => $purchaseOrderItem->quantity * $list->quantity,
                                                                    'vendor_arrival_date' => $purchaseOrderItem->vendor_arrival_date,
                                                                    'direct_shipment' => $purchaseOrderItem->direct_shipment,
                                                                ]);
                                                                //建立中繼採購單單品資料
                                                                $purchaseOrderItemSingle = PurchaseOrderItemSingleDB::create([
                                                                    'type' => 'A331',
                                                                    'purchase_no' => $package->purchase_no,
                                                                    'poi_id' => $purchaseOrderItem->id,
                                                                    'poip_id' => $purchaseOrderItemPackage->id,
                                                                    'product_model_id' => $package->product_model_id,
                                                                    'gtin13' => $package->gtin13,
                                                                    'vendor_id' => $package->vendor_id,
                                                                    'purchase_price' => $package->purchase_price,
                                                                    'quantity' => $purchaseOrderItem->quantity * $list->quantity,
                                                                    'vendor_arrival_date' => $purchaseOrderItem->vendor_arrival_date,
                                                                    'direct_shipment' => $purchaseOrderItem->direct_shipment,
                                                                ]);
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }else{
                                                if($pItem->sku == $pp->bom){
                                                    foreach($pp->lists as $list) {
                                                        foreach($packages as $package){
                                                            if($list->sku == $package->sku){
                                                                //建立中繼採購單組合商品
                                                                $purchaseOrderItemPackage = PurchaseOrderItemPackageDB::create([
                                                                    'purchase_no' => $package->purchase_no,
                                                                    'purchase_order_item_id' => $purchaseOrderItem->id,
                                                                    'product_model_id' => $package->product_model_id,
                                                                    'gtin13' => $package->gtin13,
                                                                    'vendor_id' => $package->vendor_id,
                                                                    'purchase_price' => $package->purchase_price,
                                                                    'quantity' => $purchaseOrderItem->quantity * $list->quantity,
                                                                    'vendor_arrival_date' => $purchaseOrderItem->vendor_arrival_date,
                                                                    'direct_shipment' => $purchaseOrderItem->direct_shipment,
                                                                ]);
                                                                //建立中繼採購單單品資料
                                                                $purchaseOrderItemSingle = PurchaseOrderItemSingleDB::create([
                                                                    'type' => 'A331',
                                                                    'purchase_no' => $package->purchase_no,
                                                                    'poi_id' => $purchaseOrderItem->id,
                                                                    'poip_id' => $purchaseOrderItemPackage->id,
                                                                    'product_model_id' => $package->product_model_id,
                                                                    'gtin13' => $package->gtin13,
                                                                    'vendor_id' => $package->vendor_id,
                                                                    'purchase_price' => $package->purchase_price,
                                                                    'quantity' => $purchaseOrderItem->quantity * $list->quantity,
                                                                    'vendor_arrival_date' => $purchaseOrderItem->vendor_arrival_date,
                                                                    'direct_shipment' => $purchaseOrderItem->direct_shipment,
                                                                ]);
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    //建立中繼採購單單品資料
                                    $purchaseOrderItemSingle = PurchaseOrderItemSingleDB::create([
                                        'type' => 'A331',
                                        'purchase_no' => $purchaseOrder->purchase_no,
                                        'poi_id' => $purchaseOrderItem->id,
                                        'poip_id' => null,
                                        'product_model_id' => $purchaseOrderItem->product_model_id,
                                        'gtin13' => $purchaseOrderItem->gtin13,
                                        'purchase_price' => $purchaseOrderItem->purchase_price,
                                        'quantity' => $purchaseOrderItem->quantity,
                                        'vendor_arrival_date' => $purchaseOrderItem->vendor_arrival_date,
                                        'direct_shipment' => $purchaseOrderItem->direct_shipment,
                                    ]);
                                }
                                $log = PurchaseOrderChangeLogDB::create([
                                    'purchase_no' => $purchaseOrderItem->purchase_no,
                                    'admin_id' => auth('gate')->user()->id,
                                    'poi_id' => $purchaseOrderItem->id,
                                    'sku' => $purchaseOrderItem->sku,
                                    'digiwin_no' => $purchaseOrderItem->digiwin_no,
                                    'product_name' => $purchaseOrderItem->product_name,
                                    'status' => '新增',
                                    'quantity' => '0 => '.$purchaseOrderItem->quantity,
                                    'price' => $purchaseOrderItem->purchase_price.' => '.$purchaseOrderItem->purchase_price,
                                    'date' => $beforeVendorArrivalDate.' => '.$data['date'],
                                    'memo' => '商品列 新增',
                                ]);
                            }
                            if($remindQty > 0){ //剩餘數量大於0
                                if(strstr($purchaseItem->sku,'BOM')){
                                    $packageData = json_decode(str_replace('	','',$purchaseItem->package_data));
                                    if(is_array($packageData) && count($packageData) > 0){
                                        foreach($packageData as $pp){
                                            if(isset($pp->is_del)){
                                                if($pp->is_del == 0){
                                                    if($pItem->sku == $pp->bom){
                                                        foreach($pp->lists as $list) {
                                                            foreach($pItem->package as $package){
                                                                if($package->is_del == 0 && $package->sku == $list->sku){
                                                                    $package->single->update(['quantity' => $list->quantity * $remindQty]);
                                                                    $package->update(['quantity' => $list->quantity * $remindQty]);
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }else{
                                                if($pItem->sku == $pp->bom){
                                                    foreach($pp->lists as $list) {
                                                        foreach($pItem->package as $package){
                                                            if($package->is_del == 0 && $package->sku == $list->sku){
                                                                $package->single->update(['quantity' => $list->quantity * $remindQty]);
                                                                $package->update(['quantity' => $list->quantity * $remindQty]);
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    $purchaseItem->single->update(['quantity' => $remindQty]);
                                }
                                $purchaseItem->update(['quantity' => $remindQty]);
                                $log = PurchaseOrderChangeLogDB::create([
                                    'purchase_no' => $purchaseItem->purchase_no,
                                    'admin_id' => auth('gate')->user()->id,
                                    'poi_id' => $purchaseItem->id,
                                    'sku' => $purchaseItem->sku,
                                    'digiwin_no' => $purchaseItem->digiwin_no,
                                    'product_name' => $purchaseItem->product_name,
                                    'status' => '修改',
                                    'quantity' => $beforeQuantity.' => '.$remindQty,
                                    'price' => $purchaseItem->purchase_price.' => '.$purchaseItem->purchase_price,
                                    'date' => $beforeVendorArrivalDate.' => '.$data['date'],
                                    'memo' => '數量 到貨日 修改',
                                ]);
                            }else{ //等於0 從採購單中移除該品項
                                if(strstr($purchaseItem->sku,'BOM')){
                                    foreach($purchaseItem->package as $package){
                                        $package->update(['quantity' => 0, 'is_del' => 1]);
                                        $package->single->update(['quantity' => 0, 'is_del' => 1]);
                                    }
                                }else{
                                    $purchaseItem->single->update(['quantity' => 0, 'is_del' => 1]);
                                }
                                $purchaseItem->update(['quantity' => 0, 'is_del' => 1]);
                                $log = PurchaseOrderChangeLogDB::create([
                                    'purchase_no' => $purchaseItem->purchase_no,
                                    'admin_id' => auth('gate')->user()->id,
                                    'poi_id' => $purchaseItem->id,
                                    'sku' => $purchaseItem->sku,
                                    'digiwin_no' => $purchaseItem->digiwin_no,
                                    'product_name' => $purchaseItem->product_name,
                                    'status' => '刪除',
                                    'quantity' => $beforeQuantity.' => '.$remindQty,
                                    'price' => $purchaseItem->purchase_price.' => '.$purchaseItem->purchase_price,
                                    'date' => $beforeVendorArrivalDate.' => '.$data['date'],
                                    'memo' => '數量 到貨日 修改',
                                ]);
                            }
                            Session::put('success','採購單尚未同步，已新增修改調整或整併刪除原有商品列，請重新檢視。');
                        }
                        //原始訂單商品修改廠商到貨日日期
                        if(strstr($orderItem->sku,'BOM')){
                            foreach($orderItem->package as $package){
                                $package->syncedOrderItemPackage->update(['vendor_arrival_date' => $data['date']]);
                            }
                        }
                        $orderItem->syncedOrderItem->update(['vendor_arrival_date' => $data['date']]);
                    }else{
                        Session::put('error','找不到訂單商品資料。');
                    }
                }else{
                    Session::put('error','找不到訂單資料。');
                }
            }else{
                Session::put('error','找不到採購單商品資料。');
            }
        }else{
            Session::put('error','輸入的資料有誤。');
        }
        return redirect()->back();
    }

    private function updateModify($order, $data, $purchaseDate, $orderMemo)
    {
        $chkChange = $arrivalDateChanged = 0;
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        for($i=0;$i<count($data);$i++){
            $item = PurchaseOrderItemDB::with('stockins','returns','exportPackage','exportPackage.stockins','exportPackage.returns')
                ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                ->select([
                    $purchaseOrderItemTable.'.*',
                    // $productTable.'.name as product_name',
                    DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                    $productTable.'.unit_name',
                    $productTable.'.vendor_price',
                    $productTable.'.price as product_price',
                    $productTable.'.serving_size',
                    $productTable.'.package_data',
                    $productModelTable.'.digiwin_no',
                    $productModelTable.'.sku',
                    $productModelTable.'.gtin13',
                    $vendorTable.'.id as vendor_id',
                    $vendorTable.'.name as vendor_name',
                    $vendorTable.'.service_fee',
                ])->find($data[$i]['id']);
            !isset($data[$i]['purchase_price']) ? $data[$i]['purchase_price'] = $item->purchase_price : '';
            !isset($data[$i]['quantity']) ? $data[$i]['quantity'] = $item->quantity : '';
            !isset($data[$i]['vendor_arrival_date']) ? $data[$i]['vendor_arrival_date'] = $item->vendor_arrival_date : '';
            //若有變更廠商到貨日
            if($data[$i]['vendor_arrival_date'] != $item->vendor_arrial_date){
                $arrivalDateChanged++;
                //將同步資料裡面的廠商到貨日變更成新的
                $syncedOrderItems = SyncedOrderItemDB::where([['product_model_id',$item->product_model_id],['vendor_arrival_date',$item->vendor_arrival_date],['purchase_no',$item->purchase_no]])->get();
                foreach($syncedOrderItems as $syncedItem){
                    $syncedItem->update(['vendor_arrival_date' => $data[$i]['vendor_arrival_date']]);
                }
            }
            $vendorShippingNo = $item->vendor_shipping_no;
            $beforeQuantity = $item->quantity;
            $beforePurchasePrice = $item->purchase_price;
            $beforeVendorArrivalDate = $item->vendor_arrival_date;
            empty($data[$i]['vendor_arrival_date']) ? $data[$i]['vendor_arrival_date'] = $item->vendor_arrival_date : '';
            if($item->vendor_arrival_date != $data[$i]['vendor_arrival_date'] || $item->quantity != $data[$i]['quantity'] || $item->purchase_price != $data[$i]['purchase_price']){
                //檢查進貨單是否為 N
                $checkStockinClose = $checkStockinStatus = $checkStockin = 0;
                if(strstr($item->sku,'BOM')){
                    foreach($item->exportPackage as $package){
                        if(count($package->stockins) > 0){
                            $checkStockin++;
                            foreach($package->stockins as $stockin){
                                $erpStockin = ErpPURTHDB::find($stockin->erp_stockin_no);
                                !empty($erpStockin) && $erpStockin->TH030 != 'N' ? $checkStockinStatus++ : '';
                                !empty($erpStockin) && $erpStockin->TH031 == 'Y' ? $checkStockinClose++ : '';
                            }
                        }
                    }
                }else{
                    if(count($item->stockins) > 0){
                        $checkStockin++;
                        foreach($item->stockins as $stockin){
                            $erpStockin = ErpPURTHDB::find($stockin->erp_stockin_no);
                            !empty($erpStockin) && $erpStockin->TH030 != 'N' ? $checkStockinStatus++ : '';
                            !empty($erpStockin) && $erpStockin->TH031 == 'Y' ? $checkStockinClose++ : '';
                        }
                    }
                }
                //未入庫可修改數量及金額
                if($checkStockin == 0){
                    $memo = '';
                    $oldVendorShippingNo = $vendorShippingNo;
                    if(strstr($item->sku,'BOM')){
                        $packageData = json_decode(str_replace('	','',$item->package_data));
                        $useQty = $totalPrice = 0;
                        foreach($item->exportPackage as $package){
                            if($beforeQuantity != $data[$i]['quantity']) {
                                //找出新的使用數量
                                foreach($packageData as $pp) {
                                    if($item->sku == $pp->bom) {
                                        if(!empty($pp->lists)) {
                                            foreach($pp->lists as $list) {
                                                if($package->is_del == 0 && $package->sku == $list->sku) {
                                                    $useQty = $list->quantity;
                                                    break 2;
                                                }
                                            }
                                        }
                                    }
                                }
                                $package->quantity = $useQty * $data[$i]['quantity'];
                            }
                            //找出組合品單價比例
                            if($package->vendor_price > 0 ){
                                $package->purchase_price = $package->vendor_price;
                            }else{
                                if(!empty($package->service_fee)){
                                    $package->service_fee = str_replace('"percent":}','"percent":0}',$package->service_fee);
                                    $tmp = json_decode($package->service_fee);
                                    foreach($tmp as $t){
                                        if ($t->name == 'iCarry') {
                                            $percent = $t->percent;
                                            break;
                                        }
                                    }
                                    $package->purchase_price = $package->product_price - $package->product_price * ( $percent / 100 );
                                }
                            }
                            $totalPrice += $package->purchase_price * $package->quantity;
                        }
                        $data[$i]['quantity'] == 0 ? $radio = 0: $radio = ($data[$i]['purchase_price'] * $data[$i]['quantity']) / $totalPrice;
                        foreach($item->exportPackage as $package){
                            $newQty = $package->quantity;
                            $newPrice = $radio * $package->purchase_price;
                            unset($package->service_fee);
                            $package->update([
                                'vendor_arrival_date' => $data[$i]['vendor_arrival_date'],
                                'quantity' => $newQty,
                                'purchase_price' => $newPrice,
                            ]);
                            PurchaseOrderItemSingleDB::where([['purchase_no',$order->purchase_no],['poi_id',$item->id],['poip_id',$package->id]])
                            ->update([
                                'purchase_price' => $newPrice,
                                'quantity' => $newQty,
                                'vendor_arrival_date' => $data[$i]['vendor_arrival_date'],
                            ]);
                        }
                    }else{
                        PurchaseOrderItemSingleDB::where([['purchase_no',$order->purchase_no],['poi_id',$item->id],['poip_id',null]])
                        ->update([
                            'purchase_price' => $data[$i]['purchase_price'],
                            'quantity' => $data[$i]['quantity'],
                            'vendor_arrival_date' => $data[$i]['vendor_arrival_date'],
                        ]);
                    }
                    $beforeQuantity != $data[$i]['quantity'] ? $memo .= '數量 ' : '';
                    $beforeQuantity != $data[$i]['quantity'] ? $vendorShippingNo = null : ''; //修改數量時, 同時取消出貨單
                    $beforePurchasePrice != $data[$i]['purchase_price'] ? $memo .= '金額 ' : '';
                    $beforePurchasePrice != $data[$i]['purchase_price'] ? $vendorShippingNo = $item->vendor_shipping_no : '';  //修改金額時, 出貨單號不變動
                    $beforeVendorArrivalDate != $data[$i]['vendor_arrival_date'] ? $memo .= '到貨日 ' : '';
                    $beforeVendorArrivalDate != $data[$i]['vendor_arrival_date'] ? $vendorShippingNo = null : ''; //修改到貨日時, 同時取消出貨單
                    $memo .= '修改';
                    $log = PurchaseOrderChangeLogDB::create([
                        'purchase_no' => $order->purchase_no,
                        'admin_id' => auth('gate')->user()->id,
                        'poi_id' => $item->id,
                        'sku' => $item->sku,
                        'digiwin_no' => $item->digiwin_no,
                        'product_name' => $item->product_name,
                        'status' => '修改',
                        'quantity' => $beforeQuantity.' => '.$data[$i]['quantity'],
                        'price' => $beforePurchasePrice.' => '.$data[$i]['purchase_price'],
                        'date' => $beforeVendorArrivalDate.' => '.$data[$i]['vendor_arrival_date'],
                        'memo' => $memo,
                    ]);
                    $item->update([
                        'vendor_arrival_date' => $data[$i]['vendor_arrival_date'],
                        'quantity' => $data[$i]['quantity'],
                        'purchase_price' => $data[$i]['purchase_price'],
                        'vendor_shipping_no' => $vendorShippingNo,
                    ]);
                    if(empty($vendorShippingNo) && !empty($oldVendorShippingNo)){
                        $shippingItems = VendorShippingItemDB::with('packages')->where([
                            ['shipping_no',$oldVendorShippingNo],
                            ['direct_shipment',$item->direct_shipment],
                            ['product_model_id',$item->product_model_id],
                            ['vendor_arrival_date',$beforeVendorArrivalDate],
                            ['purchase_no',$order->purchase_no],
                        ])->get();
                        if(count($shippingItems) > 0){
                            foreach($shippingItems as $shippingItem){
                                if(strstr($shippingItem->sku,'BOM')){
                                    foreach($shippingItem->packages as $package){
                                        $package->update(['is_del' => 1]);
                                    }
                                }
                                $shippingItem->update(['is_del' => 1]);
                            }
                        }
                        //檢查商家出貨單是否全部被取消, 若是則取消整張出貨單
                        $vendorShipping = VendorShippingDB::with('items')->where('shipping_no',$oldVendorShippingNo)->first();
                        if(!empty($vendorShipping)){
                            $chkVendorShipping = 0;
                            foreach($vendorShipping->items as $vendorItem){
                                $vendorItem->is_del == 1 ? $chkVendorShipping++ : '';
                            }
                            $chkVendorShipping == count($vendorShipping->items) ? $vendorShipping->update(['status' => -1, 'memo' => '已被iCarry系統取消。']) : '';
                        }
                    }
                    $chkChange++;
                }else{ //已有進貨單 且 全部進貨單 TH030 = N 且 全部進貨單未結帳 TH031 = N 才可修改金額
                    if($checkStockinStatus == 0 && $checkStockinClose == 0){
                        if(strstr($item->sku,'BOM')){
                            $packageData = json_decode(str_replace('	','',$item->package_data));
                            $totalPrice = 0;
                            //找出組合品單價比例
                            foreach($item->exportPackage as $package){
                                if($package->vendor_price > 0 ){
                                    $package->purchase_price = $package->vendor_price;
                                }else{
                                    if(!empty($package->service_fee)){
                                        $package->service_fee = str_replace('"percent":}','"percent":0}',$package->service_fee);
                                        $tmp = json_decode($package->service_fee);
                                        foreach($tmp as $t){
                                            if ($t->name == 'iCarry') {
                                                $percent = $t->percent;
                                                break;
                                            }
                                        }
                                        $package->purchase_price = $package->product_price - $package->product_price * ( $percent / 100 );
                                    }
                                }
                                $totalPrice += $package->purchase_price * $package->quantity;
                            }
                            //不可修改數量故使用原始的 $item->quantity
                            $radio = ($data[$i]['purchase_price'] * $item->quantity) / $totalPrice;
                            foreach($item->exportPackage as $package){
                                $newPrice = $radio * $package->purchase_price;
                                unset($package->service_fee);
                                $package->update([
                                    'vendor_arrival_date' => $data[$i]['vendor_arrival_date'],
                                    'purchase_price' => $newPrice,
                                ]);
                                PurchaseOrderItemSingleDB::where([['purchase_no',$order->purchase_no],['poi_id',$item->id],['poip_id',$package->id]])
                                ->update([
                                    'purchase_price' => $newPrice,
                                    'vendor_arrival_date' => $data[$i]['vendor_arrival_date'],
                                ]);
                                if(count($package->stockins) > 0){
                                    foreach($package->stockins as $stockin){
                                        $stockin->update(['purchase_price' => $newPrice]);
                                    }
                                }
                                if(count($package->returns) > 0){
                                    foreach($package->returns as $return){
                                        $returnDiscountItem = ReturnDiscountItemDB::find($return->return_discount_item_id);
                                        if(!empty($returnDiscountItem)){
                                            $returnDiscountItem->update(['purchase_price' => $data[$i]['purchase_price']]);
                                        }
                                        $return->update(['purchase_price' => $newPrice]);
                                    }
                                }
                            }
                        }else{
                            PurchaseOrderItemSingleDB::where([['purchase_no',$order->purchase_no],['poi_id',$item->id],['poip_id',null]])
                            ->update([
                                'purchase_price' => $data[$i]['purchase_price'],
                                'vendor_arrival_date' => $data[$i]['vendor_arrival_date'],
                            ]);
                            if(count($item->stockins) > 0){
                                foreach($item->stockins as $stockin){
                                    $stockin->update(['purchase_price' => $data[$i]['purchase_price']]);
                                }
                            }
                            if(count($item->returns) > 0){
                                foreach($item->returns as $return){
                                    $return->update(['purchase_price' => $data[$i]['purchase_price']]);
                                }
                            }
                        }
                        $item->update([
                            'vendor_arrival_date' => $data[$i]['vendor_arrival_date'],
                            'purchase_price' => $data[$i]['purchase_price']
                        ]);
                        $memo = '';
                        $beforeQuantity != $data[$i]['quantity'] ? $memo .= '數量 ' : '';
                        $beforePurchasePrice != $data[$i]['purchase_price'] ? $memo .= '金額 ' : '';
                        $beforeVendorArrivalDate != $data[$i]['vendor_arrival_date'] ? $memo .= '到貨日 ' : '';
                        $memo .= '修改';
                        $log = PurchaseOrderChangeLogDB::create([
                            'purchase_no' => $order->purchase_no,
                            'admin_id' => auth('gate')->user()->id,
                            'poi_id' => $item->id,
                            'sku' => $item->sku,
                            'digiwin_no' => $item->digiwin_no,
                            'product_name' => $item->product_name,
                            'status' => '修改',
                            'quantity' => $beforeQuantity.' => '.$data[$i]['quantity'],
                            'price' => $beforePurchasePrice.' => '.$data[$i]['purchase_price'],
                            'date' => $beforeVendorArrivalDate.' => '.$data[$i]['vendor_arrival_date'],
                            'memo' => $memo,
                        ]);
                        $chkChange++;
                    }
                }
            }
        }
        if($chkChange > 0){
            //重新計算金額及數量
            $order = PurchaseOrderDB::with('exportItems','exportItems.exportPackage')->find($order->id);
            $orderAmount = $orderQty = 0;
            foreach($order->exportItems as $item){
                $orderAmount += round($item->quantity * $item->purchase_price,0);
                if(strstr($item->sku,'BOM')){
                    foreach($item->exportPackage as $package){
                        $orderQty += $package->quantity;
                    }
                }else{
                    $orderQty += $item->quantity;
                }
            }
            //如果是1跟2的 要算稅額
            $tax = 0;
            $erpVendor = ErpVendorDB::find('A'.str_pad($order->vendor_id,5,0,STR_PAD_LEFT));
            if(!empty($erpVendor) && ($erpVendor->MA044 == 1 || $erpVendor->MA044 == 2)){
                $orderAmount = $orderAmount / 1.05;
                $tax = $orderAmount * 0.05;
            }
            $order->update([
                'amount' => round($orderAmount,0),
                'tax' => round($tax,0),
                'quantity' => $orderQty,
                'memo' => $orderMemo,
                'arrival_date_changed' => $arrivalDateChanged > 0 ? 1 : 0,
                'purchase_date' => $purchaseDate,
            ]);

            //檢查是否有同步過頂新
            if($order->status == 1){
                //加入request
                request()->request->add([
                    'id' => [$order->id],
                    'purchaseNo' => $order->purchase_no,
                    'adminId' => auth('gate')->user()->id,
                    'admin_id' => auth('gate')->user()->id
                ]);
                //採購單同步至鼎新
                PurchaseOrderSyncToDigiwin::dispatchNow(request()->all());
                $chkNotice = 0;
                $syncedLog = PurchaseSyncedLogDB::where('purchase_order_id',$order->id)
                ->groupBy('purchase_order_id')->having(DB::raw('count(notice_time)'), '>', 0)->first();
                //通知廠商, 先檢查是否曾經通知過廠商, 不曾通知過則不做通知, 由採購人員手動通知
                if(!empty($syncedLog)){
                    PurchaseOrderNoticeVendorModify::dispatchNow(request()->all());
                }
            }
        }
    }
}
