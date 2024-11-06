<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use DB;

class SyncedOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'erp_order_no',
        'admin_id',
        'amount',
        'discount',
        'shipping_fee',
        'spend_point',
        'parcel_tax',
        'orginal_money',
        'return_money',
        'balance',
        'total_item_quantity',
        'direct_ship_quantity',
        'vendor_arrival_date',
        'book_shipping_date',
        'status',
    ];

    public function items()
    {
        $syncedOrderItemTable = env('DB_DATABASE').'.'.(new SyncedOrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        return $this->hasMany(SyncedOrderItemDB::class,'order_id','order_id')->with('package','purchaseOrder')
            ->join($productModelTable,$productModelTable.'.id',$syncedOrderItemTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->select([
                $syncedOrderItemTable.'.*',
                // $productTable.'.name as product_name',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable.'.unit_name',
                $productTable.'.vendor_price',
                $productTable.'.price as product_price',
                $productModelTable.'.digiwin_no',
                $productModelTable.'.sku',
                $productModelTable.'.gtin13',
                $vendorTable.'.id as vendor_id',
                $vendorTable.'.name as vendor_name',
                $vendorTable.'.service_fee',
            ]);
    }
}
