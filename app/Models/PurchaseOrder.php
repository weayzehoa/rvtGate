<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use App\Models\ReturnDiscount as ReturnDiscountDB;
use App\Models\PurchaseOrderChangeLog as PurchaseOrderChangeLogDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\ErpPURTD as ErpPURTDDB;
use App\Models\AcOrder as AcOrderDB;
use DB;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'purchase_no',
        'erp_purchase_no',
        'vendor_id',
        'quantity',
        'amount',
        'tax',
        'tax_type',
        'order_ids',
        'order_item_ids',
        'product_model_ids',
        'is_del',
        'status',
        'memo',
        'synced_time',
        'stockin_finish_date',
        'arrival_date_changed',
        'purchase_date',
        'ac_erp_order_no',
        'ac_erp_sell_no',
        'ac_erp_purchase_no',
        'ac_erp_stockin_no',
    ];

    public function items()
    {
        $syncedOrderItemTable = env('DB_DATABASE').'.'.(new SyncedOrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();

        return $this->hasMany(PurchaseOrderItemDB::class,'purchase_no','purchase_no')
            ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->select([
                $purchaseOrderItemTable.'.*',
                // $productTable.'.name as product_name',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable.'.unit_name',
                $productTable.'.category_id as product_category_id',
                $productTable.'.vendor_price',
                $productTable.'.price as product_price',
                $productTable.'.serving_size',
                $productTable.'.package_data',
                $productTable.'.model_name',
                $productModelTable.'.name as product_model_name',
                $productModelTable.'.digiwin_no',
                $productModelTable.'.sku',
                $productModelTable.'.gtin13',
                $productModelTable.'.vendor_product_model_id',
                $vendorTable.'.id as vendor_id',
                $vendorTable.'.name as vendor_name',
                $vendorTable.'.service_fee',
                $vendorTable.'.digiwin_vendor_no',
            ])->orderBy('vendor_arrival_date','asc');
    }

    public function exportItems()
    {
        $syncedOrderItemTable = env('DB_DATABASE').'.'.(new SyncedOrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();

        return $this->hasMany(PurchaseOrderItemDB::class,'purchase_no','purchase_no')->with('returns')
            ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->where($purchaseOrderItemTable.'.is_del',0)
            ->select([
                $purchaseOrderItemTable.'.*',
                // $productTable.'.name as product_name',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable.'.unit_name',
                $productTable.'.category_id as product_category_id',
                $productTable.'.vendor_price',
                $productTable.'.price as product_price',
                $productTable.'.serving_size',
                $productTable.'.model_name',
                $productTable.'.package_data',
                $productModelTable.'.name as product_model_name',
                $productModelTable.'.digiwin_no',
                $productModelTable.'.sku',
                $productModelTable.'.gtin13',
                $productModelTable.'.vendor_product_model_id',
                $vendorTable.'.id as vendor_id',
                $vendorTable.'.name as vendor_name',
                $vendorTable.'.service_fee',
            ])->orderBy($purchaseOrderItemTable.'.vendor_arrival_date','asc')
            ->orderBy($productTable.'.name','asc')
            ->orderBy($purchaseOrderItemTable.'.direct_shipment','asc');
    }

    public function syncedLog(){
        return $this->hasOne(PurchaseSyncedLogDB::class,'purchase_order_id','id')->orderBy('created_at','desc');
    }

    public function notice(){
        return $this->hasOne(PurchaseSyncedLogDB::class,'purchase_order_id','id')->orderBy('created_at','desc');
    }

    public function checkStockin(){
        return $this->hasMany(StockinItemSingleDB::class,'purchase_no','purchase_no')->whereNotNull('stockin_date')->where('is_del',0);
    }

    public function lastStockin(){
        return $this->hasOne(PurchaseOrderItemSingleDB::class,'purchase_no','purchase_no')->whereNotNull('stockin_date')->where('is_del',0)->orderBy('stockin_date','desc');
    }

    public function returns(){
        return $this->hasMany(ReturnDiscountDB::class,'purchase_no','purchase_no')->orderBy('created_at','desc');
    }

    public function changeLogs(){
        return $this->hasMany(PurchaseOrderChangeLogDB::class,'purchase_no','purchase_no')->orderBy('created_at','desc');
    }

    public function erpItems(){
        return $this->setConnection('iCarrySMERP')->hasMany(ErpPURTDDB::class,'TD002','erp_purchase_no');
    }

    public function acOrder(){
        return $this->hasOne(AcOrderDB::class,'purchase_no','purchase_no');
    }
}
