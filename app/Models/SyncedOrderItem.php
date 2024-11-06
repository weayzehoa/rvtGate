<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ErpPurchasePrice as ErpPurchasePriceDB;
use App\Models\SyncedOrderItemPackage as SyncedOrderItemPackageDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use DB;

class SyncedOrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'order_item_id',
        'erp_order_no',
        'erp_order_sno',
        'purchase_no',
        'erp_purchase_no',
        'purchase_date',
        'book_shipping_date',
        'vendor_arrival_date',
        'product_model_id',
        'unit_name',
        'gross_weight',
        'net_weight',
        'price',
        'purchase_price',
        'quantity',
        'admin_memo',
        'is_del',
        'direct_shipment',
        'not_purchase',
    ];

    public function erpPurchasePrice(){
        return $this->hasOne(ErpPurchasePriceDB::class,'MB001','digiwin_no')
            ->where('dbo.PURMB.MB014','<=',date('Ymd'))->orderBy('dbo.PURMB.MB014','desc');;
    }

    public function package()
    {
        $syncedOrderItemPackage = env('DB_DATABASE').'.'.(new SyncedOrderItemPackageDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        //關聯組合商品-單品
        return $this->hasMany(SyncedOrderItemPackageDB::class,'order_item_id','order_item_id')
        ->join($productModelTable,$productModelTable.'.id',$syncedOrderItemPackage.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->select([
            $syncedOrderItemPackage.'.*',
            $productModelTable.'.digiwin_no',
            $productModelTable.'.sku',
            $productModelTable.'.gtin13',
            $productTable.'.unit_name',
            $productTable.'.price as product_price',
            $productTable.'.vendor_price',
            $productTable.'.price as origin_price',
            $vendorTable.'.id as vendor_id',
            $vendorTable.'.name as vendor_name',
            $vendorTable.'.service_fee',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
        ]);
    }

    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrderDB::class,'purchase_no','purchase_no');
    }
    public function purchasePackage()
    {
        $syncedOrderItemPackage = env('DB_DATABASE').'.'.(new SyncedOrderItemPackageDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        //關聯組合商品-單品
        return $this->hasMany(SyncedOrderItemPackageDB::class,'order_item_id','order_item_id')
        ->join($productModelTable,$productModelTable.'.id',$syncedOrderItemPackage.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->whereNotNull($syncedOrderItemPackage.'.purchase_date')
        ->select([
            $syncedOrderItemPackage.'.*',
            $productModelTable.'.digiwin_no',
            $productModelTable.'.sku',
            $productModelTable.'.gtin13',
            $productTable.'.name as product_name',
            $productTable.'.unit_name',
            $productTable.'.price as product_price',
            $productTable.'.vendor_price',
            $productTable.'.price as origin_price',
            $vendorTable.'.id as vendor_id',
            $vendorTable.'.name as vendor_name',
            $vendorTable.'.service_fee',
        ]);
    }
}
