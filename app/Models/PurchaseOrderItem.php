<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseOrderItemPackage as PurchaseOrderItemPackageDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\ReturnDiscount as ReturnDiscountDB;
use App\Models\ReturnDiscountItem as ReturnDiscountItemDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\AcOrder as AcOrderDB;
use App\Models\NidinOrder as NidinOrderDB;
use DB;

class PurchaseOrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'purchase_no',
        'product_model_id',
        'gtin13',
        'erp_stockin_no',
        'stockin_quantity',
        'stockin_date',
        'return_quantity',
        'purchase_price',
        'quantity',
        'vendor_arrival_date',
        'direct_shipment',
        'memo',
        'is_del',
        'is_close',
        'is_lock',
        'vendor_shipping_no',
    ];

    public function package()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchaseOrderItemPackageTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemPackageDB)->getTable();

        //關聯組合商品-單品
        return $this->hasMany(PurchaseOrderItemPackageDB::class)
        ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemPackageTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->select([
            $purchaseOrderItemPackageTable.'.*',
            $productModelTable.'.digiwin_no',
            $productModelTable.'.sku',
            $productModelTable.'.vendor_product_model_id',
            // $productModelTable.'.gtin13',
            DB::raw("(CASE WHEN $productModelTable.gtin13 is not null THEN $productModelTable.gtin13 ELSE $productModelTable.sku END) as gtin13"),
            // $productTable.'.name as product_name',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            $productTable.'.unit_name',
            $productTable.'.category_id as product_category_id',
            $productTable.'.price as product_price',
            $productTable.'.vendor_price',
            $productTable.'.price as origin_price',
            $productTable.'.serving_size',
            $vendorTable.'.id as vendor_id',
            $vendorTable.'.name as vendor_name',
            $vendorTable.'.service_fee',
        ])->orderBy('vendor_arrival_date','asc');
    }

    public function exportPackage()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchaseOrderItemPackageTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemPackageDB)->getTable();

        //關聯組合商品-單品
        return $this->hasMany(PurchaseOrderItemPackageDB::class)
        ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemPackageTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->where($purchaseOrderItemPackageTable.'.is_del',0)
        ->select([
            $purchaseOrderItemPackageTable.'.*',
            $productModelTable.'.digiwin_no',
            $productModelTable.'.sku',
            $productModelTable.'.gtin13',
            $productModelTable.'.name as product_model_name',
            $productModelTable.'.vendor_product_model_id',
            $productTable.'.model_name',
            // $productTable.'.name as product_name',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            $productTable.'.unit_name',
            $productTable.'.category_id as product_category_id',
            $productTable.'.price as product_price',
            $productTable.'.vendor_price',
            $productTable.'.price as origin_price',
            $productTable.'.serving_size',
            $productTable.'.package_data',
            $vendorTable.'.id as vendor_id',
            $vendorTable.'.name as vendor_name',
            $vendorTable.'.service_fee',
        ])->orderBy($purchaseOrderItemPackageTable.'.vendor_arrival_date','asc')
        ->orderBy($productTable.'.name','asc')
        ->orderBy($purchaseOrderItemPackageTable.'.direct_shipment','asc');
    }

    public function returns()
    {
        $returnDiscountTable = env('DB_DATABASE').'.'.(new ReturnDiscountDB)->getTable();
        $returnDiscountItemTable = env('DB_DATABASE').'.'.(new ReturnDiscountItemDB)->getTable();
        $acOrderTable = env('DB_DATABASE').'.'.(new AcOrderDB)->getTable();
        return $this->hasMany(ReturnDiscountItemDB::class,'poi_id','id')
            ->join($returnDiscountTable,$returnDiscountTable.'.return_discount_no',$returnDiscountItemTable.'.return_discount_no')
            ->where($returnDiscountTable.'.is_del',0)
            ->select([
                $returnDiscountItemTable.'.*',
                $returnDiscountTable.'.return_date',
                'serial_no' => AcOrderDB::whereColumn($acOrderTable.'.purchase_no',$returnDiscountItemTable.'.purchase_no')->select($acOrderTable.'.serial_no')->limit(1),
            ]);
            // ->where([[$returnDiscountTable.'.is_del',0],[$returnDiscountItemTable.'.is_del',0]]);
    }

    public function stockins()
    {
        return $this->hasMany(StockinItemSingleDB::class,'poi_id','id')->whereNull('poip_id')->where('is_del',0)->orderBy('stockin_date','asc');
    }

    public function single()
    {
        return $this->hasOne(PurchaseOrderItemSingleDB::class,'poi_id','id')->whereNull('poip_id')->where('is_del',0);
    }

    public function nidinItems()
    {
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $nidinOrderTable = env('DB_DATABASE').'.'.(new NidinOrderDB)->getTable();

        return $this->setConnection('mysql')->hasMany(OrderItemDB::class,'purchase_no','purchase_no')
        ->join($orderTable,$orderTable.'.id',$orderItemTable.'.order_id')
        ->join($nidinOrderTable,$nidinOrderTable.'.order_number',$orderTable.'.order_number')
        ->where($orderItemTable.'.product_model_id',$this->product_model_id) //使用這種方式不能放在with裡面會找不到資料一般使用 $xxx->orderItems 可以找到資料
        ->select([
            $orderItemTable.'.*',
            $orderTable.'.order_number',
            $nidinOrderTable.'.nidin_order_no',
            $nidinOrderTable.'.transaction_id',
        ]);
    }
}
