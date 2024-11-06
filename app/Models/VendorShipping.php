<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\VendorShippingItem as ShippingItemDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use DB;

class VendorShipping extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_no',
        'vendor_id',
        'vendor_arrival_date',
        'shipping_finish_date',
        'stockin_finish_date',
        'status',
        'memo',
        'method',
    ];

    public function vendor(){
        return $this->beLongsTo(VendorDB::class,'vendor_id','id');
    }

    public function items(){
        return $this->hasMany(ShippingItemDB::class,'shipping_no','shipping_no')->orderBy('direct_shipment','asc');
    }

    public function nonDirectShip(){
        return $this->hasMany(ShippingItemDB::class,'shipping_no','shipping_no')->where('is_del',0)->where('direct_shipment',0);
    }

    public function directShip(){
        $key = env('APP_AESENCRYPT_KEY');
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $vendorShippingItemTable = env('DB_DATABASE').'.'.(new ShippingItemDB)->getTable();

        return $this->hasMany(ShippingItemDB::class,'shipping_no','shipping_no')
        ->join($orderTable,$orderTable.'.order_number',$vendorShippingItemTable.'.order_numbers')
        ->join($productModelTable,$productModelTable.'.id',$vendorShippingItemTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->where($vendorShippingItemTable.'.is_del',0)->where($vendorShippingItemTable.'.direct_shipment',1)
        ->select([
            DB::raw("DATE_FORMAT(orders.pay_time,'%Y-%m-%d') as pay_time"),
            DB::raw("DATE_FORMAT(orders.pay_time,'%Y%m%d') as payTime"),
            $vendorShippingItemTable.'.*',
            $orderTable.'.status',
            $orderTable.'.user_memo',
            $orderTable.'.receiver_key_time',
            $orderTable.'.receiver_keyword',
            $orderTable.'.receiver_address',
            $orderTable.'.receiver_name',
            $orderTable.'.partner_order_number',
            DB::raw("IF($orderTable.receiver_phone_number IS NULL,'',AES_DECRYPT($orderTable.receiver_phone_number,'$key')) as receiver_phone_number"),
            DB::raw("IF($orderTable.receiver_tel IS NULL,'',AES_DECRYPT($orderTable.receiver_tel,'$key')) as receiver_tel"),
            $productTable.'.serving_size',
            $productTable.'.unit_name',
        ]);
    }

}
