<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\AcOrder as AcOrderDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\NidinOrder as NidinOrderDB;
use DB;

class StockinItemSingle extends Model
{
    use HasFactory;
    protected $fillable = [
        'purchase_no',
        'erp_purchase_no',
        'erp_purchase_sno',
        'poi_id',
        'poip_id',
        'pois_id',
        'product_model_id',
        'erp_stockin_no',
        'erp_stockin_sno',
        'stockin_quantity',
        'quantity',
        'stockin_date',
        'return_quantity',
        'purchase_price',
        'quantity',
        'vendor_arrival_date',
        'direct_shipment',
        'is_del',
        'is_close',
        'is_lock',
        'sell_no',
        'statement_no',
    ];

    public function purchaseOrderItem()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $acOrderTable = env('DB_DATABASE').'.'.(new AcOrderDB)->getTable();

        return $this->belongsTo(PurchaseOrderItemDB::class,'poi_id','id')->with('returns')
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
            $productTable.'.model_name',
            $productTable.'.package_data',
            $productModelTable.'.name as product_model_name',
            $productModelTable.'.digiwin_no',
            $productModelTable.'.sku',
            $productModelTable.'.gtin13',
            $vendorTable.'.id as vendor_id',
            $vendorTable.'.name as vendor_name',
            $vendorTable.'.service_fee',
            'serial_no' => AcOrderDB::whereColumn($acOrderTable.'.purchase_no',$purchaseOrderItemTable.'.purchase_no')->select($acOrderTable.'.serial_no')->limit(1),
        ]);
    }

    public function product()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        return $this->belongsTo(ProductModelDB::class,'product_model_id','id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->select([
            $productModelTable.'.*',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            $productTable.'.unit_name',
            $productTable.'.vendor_price',
            $productTable.'.price as product_price',
            $productTable.'.serving_size',
            $productTable.'.model_name',
            $productModelTable.'.name as product_model_name',
            $productModelTable.'.digiwin_no',
            $productModelTable.'.sku',
            $productModelTable.'.gtin13',
            $vendorTable.'.id as vendor_id',
            $vendorTable.'.name as vendor_name',
            $vendorTable.'.service_fee',
        ]);
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
