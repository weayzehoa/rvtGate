<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseOrderItemPackage as PurchaseOrderItemPackageDB;
use App\Models\PurchaseOrderChangeLog as PurchaseOrderChangeLogDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use DB;
use Carbon\Carbon;

trait PurchaseOrderFunctionTrait
{
    protected function getPurchaseOrderData($request, $type = null, $name = null)
    {
        $syncedOrderItemTable = env('DB_DATABASE').'.'.(new SyncedOrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchaseOrderItemPackageTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemPackageDB)->getTable();
        $purchaseOrderChangeLogTable = env('DB_DATABASE').'.'.(new PurchaseOrderChangeLogDB)->getTable();

        if (isset($request['cate']) && ($request['cate'] == 'Export' || $request['cate'] == 'Notice')) {
            $purchases = PurchaseOrderDB::join($vendorTable, $vendorTable.'.id', $purchaseOrderTable.'.vendor_id')
            ->with('notice','notice.files','exportItems','exportItems.exportPackage');
        }else{
            $purchases = PurchaseOrderDB::join($vendorTable, $vendorTable.'.id', $purchaseOrderTable.'.vendor_id')
            ->with('acOrder','notice','changeLogs','returns','syncedLog', 'items', 'items.package','items.stockins','items.returns','items.package.stockins','items.package.returns','checkStockin','lastStockin','returns');
        }

        if(isset($request['id'])){ //指定選擇的訂單
            is_array($request['id']) ? $purchases = $purchases->whereIn($purchaseOrderTable.'.id',$request['id']) : $purchases = $purchases->where($purchaseOrderTable.'.id',$request['id']);
        }elseif(isset($request['con'])){ //by條件
            //將進來的資料作參數轉換
            foreach ($request['con'] as $key => $value) {
                $$key = $value;
            }
        }else{
            //將進來的資料作參數轉換
            foreach ($request->all() as $key => $value) {
                $$key = $value;
            }
        }
        if (!isset($status)) {
            $status = '0,1,2,3';
        }
        // dd($status);
        //狀態預設為0
        if(isset($request['cate'])){
            if($request['cate'] == 'SyncToDigiwin'){ //同步鼎新時不帶預設狀態參數
                isset($status) ? $purchases = $purchases->whereIn($purchaseOrderTable.'.status',explode(',',$status)) : '';
            }elseif($request['cate'] == 'Export' || $request['cate'] == 'Notice' || $request['cate'] == 'NoticeVendor'){
                $purchases = $purchases->whereIn($purchaseOrderTable.'.status',[1,2]);
            }elseif($request['cate'] == 'CancelOrder'){ //排除已被取消的
                $purchases = $purchases->whereIn($purchaseOrderTable.'.status',[0,1,2]);
            }
        }else{
            isset($status) ? $purchases = $purchases->whereIn($purchaseOrderTable.'.status',explode(',',$status)) : (!empty($ng_return) || !empty($ng_stockin) ? '' : $purchases = $purchases->whereIn($purchaseOrderTable.'.status',[$status]));
        }
        if($type == 'notice'){
            $purchases = $purchases->whereNotNull($purchaseOrderTable.'.erp_purchase_no');
        }
        //查詢參數
        !empty($purchase_no) ? $purchases = $purchases->where($purchaseOrderTable.'.purchase_no', 'like', "%$purchase_no%") : '';
        !empty($erp_purchase_no) ? $purchases = $purchases->where($purchaseOrderTable.'.erp_purchase_no', 'like', "%$erp_purchase_no%") : '';
        !empty($vendor_name) ? $purchases = $purchases->whereIn($purchaseOrderTable.'.vendor_id', VendorDB::where('name','like',"%$vendor_name%")->select('id')->get()) : '';
        !empty($created_at) ? $purchases = $purchases->where($purchaseOrderTable.'.created_at', '>=', $created_at) : '';
        !empty($created_at_end) ? $purchases = $purchases->where($purchaseOrderTable.'.created_at', '<=', $created_at_end) : '';
        !empty($ng_stockin) ? $purchases = $purchases->whereIn($purchaseOrderTable.'.id',explode(',',$ng_stockin)) : '';
        !empty($ng_return) ? $purchases = $purchases->whereIn($purchaseOrderTable.'.id',explode(',',$ng_return)) : '';

        !empty($erp_stockin_no) ? $purchases = $purchases->whereIn($purchaseOrderTable.'.purchase_no',StockinItemSingleDB::where('erp_stockin_no','like',"%$erp_stockin_no%")->select(['purchase_no'])->get()) : '';

        if(!empty($notice_vendor)){
            if($notice_vendor == 'noticed'){
                // SELECT m.purchase_order_id, MAX(m.created_at),(SELECT notice_time FROM table WHERE purchase_order_id=m.purchase_order_id AND created_at=MAX(m.created_at)) notice_time FROM table m GROUP BY purchase_order_id HAVING notice_time IS NOT NULL
                $purchaseOrderIds = DB::table('purchase_synced_logs AS m')->select([
                    'm.purchase_order_id as purchase_order_id',
                    // DB::raw("MAX(m.created_at)"),
                    'notice_time' => PurchaseSyncedLogDB::whereColumn('purchase_synced_logs.purchase_order_id','m.purchase_order_id')->whereRaw(" created_at = MAX(m.created_at) ")->select('notice_time')->limit(1),
                ])->groupBy('purchase_order_id')->havingRaw('notice_time is not null')->get()->pluck('purchase_order_id')->all();
            }elseif($notice_vendor == 'noNotice'){
                $purchaseOrderIds = DB::table('purchase_synced_logs AS m')->select([
                    'm.purchase_order_id as purchase_order_id',
                    // DB::raw("MAX(m.created_at)"),
                    'notice_time' => PurchaseSyncedLogDB::whereColumn('purchase_synced_logs.purchase_order_id','m.purchase_order_id')->whereRaw(" created_at = MAX(m.created_at) ")->select('notice_time')->limit(1),
                ])->groupBy('purchase_order_id')->havingRaw('notice_time is null')->get()->pluck('purchase_order_id')->all();
            }
            $purchases = $purchases->whereIn($purchaseOrderTable.'.id',$purchaseOrderIds);
        }

        if(!empty($order_number)){
            $orderIds = OrderDB::where('order_number','like',"%$order_number%")->select('id')->get()->pluck('id')->all();
            $c = null;
            if(!empty($orderIds)){
                for($i=0;$i<count($orderIds);$i++){
                    $c .= " FIND_IN_SET('$orderIds[$i]',$purchaseOrderTable.order_ids) OR ";
                }
            }else{
                $c .= " FIND_IN_SET('null',$purchaseOrderTable.order_ids) ";
            }
            $c = rtrim($c,' OR ');
            $purchases = $purchases->where(function($query)use($c){
                $query->whereRaw($c);
            });
        }

        if(!empty($product_name)){
            $purchaseNos = PurchaseOrderItemDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->where($productTable.'.name','like',"%$product_name%")->select($purchaseOrderItemTable.'.purchase_no')->groupBy($purchaseOrderItemTable.'.purchase_no')->get();
            $purchases = $purchases->whereIn($purchaseOrderTable.'.purchase_no',$purchaseNos);
        }

        if(!empty($change_date) || !empty($change_date_end)){
            $purchases = $purchases->rightJoin($purchaseOrderChangeLogTable,$purchaseOrderChangeLogTable.'.purchase_no',$purchaseOrderTable.'.purchase_no');
            !empty($change_date) ? $purchases = $purchases->where($purchaseOrderChangeLogTable.'.created_at','>=',$change_date.' 00:00:00') : '';
            !empty($change_date_end) ? $purchases = $purchases->where($purchaseOrderChangeLogTable.'.created_at','<=',$change_date_end.' 23:59:59') : '';
            $purchases = $purchases->groupBy($purchaseOrderTable.'.purchase_no');
        }

        if(!empty($vendor_arrival_date)){
            $purchaseNos = PurchaseOrderItemDB::where('vendor_arrival_date','>=',$vendor_arrival_date);
            if(!empty($vendor_arrival_date_end)){
                $purchaseNos = $purchaseNos->where('vendor_arrival_date','<=',$vendor_arrival_date_end);
            }
            $purchaseNos = $purchaseNos->select('purchase_no')->groupBy('purchase_no')->get();
            $purchases = $purchases->whereIn($purchaseOrderTable.'.purchase_no',$purchaseNos);
        }

        if(!empty($vendor_arrival_date_end)){
            $purchaseNos = PurchaseOrderItemDB::where('vendor_arrival_date','<=',$vendor_arrival_date_end);
            if(!empty($vendor_arrival_date)){
                $purchaseNos = $purchaseNos->where('vendor_arrival_date','>=',$vendor_arrival_date);
            }
            $purchaseNos = $purchaseNos->select('purchase_no')->groupBy('purchase_no')->get();
            $purchases = $purchases->whereIn($purchaseOrderTable.'.purchase_no',$purchaseNos);
        }

        if(!empty($book_shipping_date)){
            $purchaseNos = PurchaseOrderItemDB::where('book_shipping_date','<=',$book_shipping_date_end);
            if(!empty($vendor_arrival_date)){
                $purchaseNos = $purchaseNos->where('book_shipping_date','>=',$book_shipping_date);
            }
            $purchaseNos = $purchaseNos->select('purchase_no')->groupBy('purchase_no')->get();
            $purchases = $purchases->whereIn($purchaseOrderTable.'.purchase_no',$purchaseNos);
        }

        if(!empty($book_shipping_date_end)){
            $purchaseNos = PurchaseOrderItemDB::where('book_shipping_date','<=',$book_shipping_date_end);
            if(!empty($book_shipping_date)){
                $purchaseNos = $purchaseNos->where('book_shipping_date','>=',$book_shipping_date);
            }
            $purchaseNos = $purchaseNos->select('purchase_no')->groupBy('purchase_no')->get();
            $purchases = $purchases->whereIn($purchaseOrderTable.'.purchase_no',$purchaseNos);
        }

        if(!empty($digiwin_no)){
            $purchaseOrderItemIds = PurchaseOrderItemPackageDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemPackageTable.'.product_model_id')
            ->where(function($query)use($digiwin_no,$productModelTable){
                $query->where($productModelTable.'.sku','like',"%$digiwin_no%")
                ->orWhere($productModelTable.'.digiwin_no','like',"%$digiwin_no%");
            })->select([
                $purchaseOrderItemPackageTable.'.purchase_order_item_id'
            ])->get()->pluck('purchase_order_item_id')->all();
            $purchases = $purchases->rightJoin($purchaseOrderItemTable,$purchaseOrderItemTable.'.purchase_no',$purchaseOrderTable.'.purchase_no')
                ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
                ->where(function($query)use($digiwin_no,$productModelTable,$purchaseOrderItemTable,$purchaseOrderItemIds){
                    $query = $query->where($productModelTable.'.digiwin_no','like',"%$digiwin_no%")
                    ->orWhere($productModelTable.'.sku','like',"%$digiwin_no%");
                    if(count($purchaseOrderItemIds) > 0){
                        $query = $query->orWhereIn($purchaseOrderItemTable.'.id',$purchaseOrderItemIds);
                    }
                });
            $purchases = $purchases->groupBy('purchase_no');
        }

        !isset($list) ? $list = 50 : '';

        //找出最終資料
        $purchases = $purchases->select([
            $purchaseOrderTable.'.*',
            $vendorTable.'.name as vendor_name',
            $vendorTable.'.digiwin_vendor_no',
        ]);

        if($type == 'index'){
            $purchases = $purchases->orderBy('status','asc')->orderBy('created_at','desc')->paginate($list);
        }elseif($type == 'getPurchaseNos'){
            $purchases = $purchases->select([
                $purchaseOrderTable.'.purchase_no',
            ])->get()->pluck('purchase_no')->all();
        }elseif(isset($request['cate']) && $request['cate']=='Notice'){
            $purchases = $purchases->addSelect([
                $vendorTable.'.company',
                $vendorTable.'.address',
                $vendorTable.'.contact_person',
                $vendorTable.'.tel',
                $vendorTable.'.fax',
            ])->orderBy('synced_time','desc')->orderBy('created_at','desc')->get();
        }elseif(isset($request['cate']) && $request['cate']=='Export'){
            if($request['type'] == 'WithSingle'){
                $purchases = $purchases->orderBy('synced_time','desc')->orderBy('created_at','desc')->get();
            }elseif($request['type']=='Stockin' || $request['type']=='OrderDetail' || $request['type']=='OrderChange'){
                $purchases = $purchases->select([
                    $purchaseOrderTable.'.purchase_no',
                ])->get()->pluck('purchase_no')->all();
            }else{
                return null;
            }
        }else{
            $purchases = $purchases->orderBy('synced_time','desc')->orderBy('created_at','desc')->get();
        }
        return $purchases;
    }

    protected function getPurchaseOrderItemData($purchaseNos, $type = null, $name = null)
    {
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        $purchaseOrderItems = PurchaseOrderItemDB::join($purchaseOrderTable,$purchaseOrderTable.'.purchase_no',$purchaseOrderItemTable.'.purchase_no')
        ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
        ->join($vendorTable,$vendorTable.'.id',$purchaseOrderTable.'.vendor_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->whereIn($purchaseOrderItemTable.'.purchase_no',$purchaseNos)
        ->select([
            $purchaseOrderItemTable.'.*',
            $vendorTable.'.name as vendor_name',
            $purchaseOrderTable.'.type',
            $purchaseOrderTable.'.erp_purchase_no',
            $purchaseOrderTable.'.order_ids',
            $purchaseOrderTable.'.created_at as orderDate',
            $productModelTable.'.digiwin_no',
            $productTable.'.unit_name',
            $productTable.'.serving_size',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
        ])->get();
        return $purchaseOrderItems;
    }

    protected function getPurchaseOrderChangeData($purchaseNos, $type = null, $name = null)
    {
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchaseOrderChangeLogTable = env('DB_DATABASE').'.'.(new PurchaseOrderChangeLogDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        $purchaseOrderItems = PurchaseOrderChangeLogDB::join($purchaseOrderTable,$purchaseOrderTable.'.purchase_no',$purchaseOrderChangeLogTable.'.purchase_no')
        ->join($productModelTable,$productModelTable.'.digiwin_no',$purchaseOrderChangeLogTable.'.digiwin_no')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$purchaseOrderTable.'.vendor_id')
        ->whereIn($purchaseOrderChangeLogTable.'.purchase_no',$purchaseNos)
        ->where($purchaseOrderChangeLogTable.'.status','修改')
        ->select([
            $purchaseOrderChangeLogTable.'.*',
            $vendorTable.'.id as vendor_id',
            $vendorTable.'.name as vendor_name',
            $purchaseOrderTable.'.type',
            $purchaseOrderTable.'.erp_purchase_no',
            $purchaseOrderTable.'.order_ids',
            $purchaseOrderTable.'.created_at as orderDate',
            $productTable.'.unit_name',
            $productTable.'.serving_size',
        ])->orderBy($purchaseOrderTable.'.purchase_no','desc')->orderBy($purchaseOrderChangeLogTable.'.digiwin_no','desc')->get();
        return $purchaseOrderItems;
    }
}
