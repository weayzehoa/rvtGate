<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryGroupBuyingOrder as OrderDB;
use App\Models\iCarryGroupBuyingOrderItem as OrderItemDB;
use App\Models\iCarryGroupBuyingOrderItemPackage as OrderItemPackageDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use DB;

class iCarryGroupBuyingOrderItem extends Model
{
    use HasFactory;

    protected $connection = 'icarry';
    protected $table = 'group_buying_order_item';
    //變更 Laravel 預設 created_at 與 不使用 updated_at 欄位
    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;
    protected $fillable = [
        'order_id',
        'vendor_id',
        'product_id',
        'product_model_id',
        'digiwin_no',
        'digiwin_payment_id',
        'price',
        'purchase_price',
        'gross_weight',
        'net_weight',
        'is_tax_free',
        'parcel_tax_code',
        'parcel_tax',
        'vendor_service_fee_percent',
        'shipping_verdor_percent',
        'product_service_fee_percent',
        'quantity',
        'return_quantity',
        'is_del',
        'admin_memo',
        'create_time',
        'promotion_ids',
        'product_name',
        'is_call',
        'direct_shipment',
        'shipping_memo',
        'not_purchase',
        'discount',
    ];

    public function order()
    {
        return $this->belongsTo(OrderDB::class,'order_id','id');
    }

    public function package()
    {
        $orderItemPackageTable = env('DB_ICARRY').'.'.(new OrderItemPackageDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        //關聯組合商品-單品
        return $this->hasMany(OrderItemPackageDB::class,'order_item_id','id')
        ->join($productModelTable,$productModelTable.'.id',$orderItemPackageTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$this->product.'.vendor_id')
        ->select([
            $orderItemPackageTable.'.*',
            $productModelTable.'.origin_digiwin_no',
            $productModelTable.'.digiwin_no',
            $productModelTable.'.sku',
            $productModelTable.'.gtin13',
            $productTable.'.unit_name',
            $productTable.'.id as product_id',
            $productTable.'.serving_size',
            $productTable.'.price',
            $productTable.'.price as product_price',
            $productTable.'.direct_shipment as directShip',
            $productTable.'.price as origin_price',
            $vendorTable.'.id as vendor_id',
            $vendorTable.'.name as vendor_name',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
        ]);
    }

    //匯出用
    public function packageData()
    {
        $orderItemPackageTable = env('DB_ICARRY').'.'.(new OrderItemPackageDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        //關聯組合商品-單品
        return $this->hasMany(OrderItemPackageDB::class,'order_item_id','id')
        ->join($productModelTable,$productModelTable.'.id',$orderItemPackageTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$this->product.'.vendor_id')
        ->where($orderItemPackageTable.'.is_del',0)
        ->select([
            $orderItemPackageTable.'.*',
            $productModelTable.'.origin_digiwin_no',
            $productModelTable.'.digiwin_no',
            $productModelTable.'.sku',
            $productModelTable.'.gtin13',
            $productTable.'.unit_name',
            $productTable.'.id as product_id',
            $productTable.'.serving_size',
            $productTable.'.price',
            $productTable.'.price as product_price',
            $productTable.'.direct_shipment as directShip',
            $productTable.'.price as origin_price',
            $vendorTable.'.id as vendor_id',
            $vendorTable.'.name as vendor_name',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
        ]);
    }
}
