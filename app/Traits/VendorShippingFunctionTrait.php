<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\Sell as SellDB;
use App\Models\SellItemSingle as SellItemSingleDB;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseOrderItemPackage as PurchaseOrderItemPackageDB;
use App\Models\PurchaseOrderChangeLog as PurchaseOrderChangeLogDB;
use App\Models\VendorShipping as ShippingDB;
use App\Models\VendorShippingItem as ShippingItemDB;
use App\Models\VendorShippingExpress as ExpressDB;
use DB;

trait VendorShippingFunctionTrait
{
    protected function getVendorShippingData($request, $type = null, $name = null)
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchaseOrderItemPackageTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemPackageDB)->getTable();
        $purchaseOrderChangeLogTable = env('DB_DATABASE').'.'.(new PurchaseOrderChangeLogDB)->getTable();
        $shippingTable = env('DB_DATABASE').'.'.(new ShippingDB)->getTable();
        $shippingItemTable = env('DB_DATABASE').'.'.(new ShippingItemDB)->getTable();
        $expressTable = env('DB_DATABASE').'.'.(new ExpressDB)->getTable();

        auth('vendor')->user() ? $vendorId = auth('vendor')->user()->vendor_id : $vendorId = $request->vendorId;

        if(isset($request['id'])){ //指定選擇的訂單

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

        $shippings = ShippingDB::with('vendor','items','items.packages','items.packages.stockins','items.purchasePackages','items.orderPackages','items.express','items.stockins','nonDirectShip');
        $shippings = $shippings->rightJoin($shippingItemTable,$shippingItemTable.'.shipping_no',$shippingTable.'.shipping_no');

        isset($request['id']) && is_array($request['id']) ? $shippings = $shippings->whereIn($shippingTable.'.id',$request['id']) : '';

        isset($status) ? $shippings = $shippings->whereIn($shippingTable.'.status',explode(',',$status)) : $shippings = $shippings->whereIn($shippingTable.'.status',[0,1,2,3,4]);
        isset($shipping_no) ? $shippings = $shippings->where($shippingTable.'.shipping_no','like',"%$shipping_no%") : '';
        isset($vendor_arrival_date) ? $shippings = $shippings->where($shippingTable.'.vendor_arrival_date','>=',$vendor_arrival_date) : '';
        isset($vendor_arrival_date_end) ? $shippings = $shippings->where($shippingTable.'.vendor_arrival_date','<=',$vendor_arrival_date_end) : '';
        isset($purchase_no) ? $shippings = $shippings->where($shippingItemTable.'.purchase_no','like',"%$purchase_no%") : '';
        isset($order_number) ? $shippings = $shippings->where($shippingItemTable.'.order_numbers','like',"%$order_number%")->where($shippingItemTable.'.direct_shipment',1) : '';
        isset($digiwin_no) ? $shippings = $shippings->where($shippingItemTable.'.digiwin_no','like',"%$digiwin_no%") : '';
        isset($vendor_name) ? $shippings = $shippings->join($vendorTable,$vendorTable.'.id',$shippingTable.'.vendor_id')->where($vendorTable.'.name','like',"%$vendor_name%") : '';

        if(isset($shipping_date) || isset($shipping_date_end) || isset($express_way) || isset($express_no)){
            $shippings = $shippings->rightJoin($expressTable,$expressTable.'.vsi_id',$shippingItemTable.'.id');
            !empty($shipping_date) ? $shippings->where($expressTable.'.shipping_date','>=',$shipping_date) : '';
            !empty($shipping_date_end) ? $shippings->where($expressTable.'.shipping_date','<=',$shipping_date_end) : '';
            !empty($express_way) ? $shippings->where($expressTable.'.express_way','like',"%$express_way%") : '';
            !empty($express_no) ? $shippings->where($expressTable.'.express_no','like',"%$express_no%") : '';
            $shippings = $shippings->groupBy($expressTable.'.shipping_no');
        }else{
            $shippings = $shippings->groupBy($shippingTable.'.shipping_no');
        }
        if (!isset($list)) {
            $list = 50;
        }
        //使用rightJoin需選擇特定資料
        $shippings = $shippings->select([
            $shippingTable.'.*',
        ]);
        $shippings = $shippings->orderBy($shippingTable.'.status', 'asc')->distinct();

        if($type == 'index'){
            $shippings = $shippings->paginate($list);
        }elseif($type == 'show'){
            if(isset($request['product_id'])){
                $shippings = $shippings->findOrFail($request['product_id']);
            }else{
                $shippings = $shippings->findOrFail($request['id']);
            }
        }else{
            $shippings = $shippings->get();
        }
        return $shippings;
    }
}
