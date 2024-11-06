<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ReturnDiscountItem as ReturnDiscountItemDB;
use DB;

class ReturnDiscount extends Model
{
    use HasFactory;
    protected $fillable = [
        'return_discount_no',
        'erp_return_discount_no',
        'type',
        'purchase_no',
        'vendor_id',
        'quantity',
        'amount',
        'tax',
        'memo',
        'return_date',
        'is_del',
        'is_lock',
    ];

    public function items()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $returnDiscountItemTable = env('DB_DATABASE').'.'.(new ReturnDiscountItemDB)->getTable();

        return $this->hasMany(ReturnDiscountItemDB::class,'return_discount_no','return_discount_no')
            ->join($productModelTable,$productModelTable.'.id',$returnDiscountItemTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->select([
                $returnDiscountItemTable.'.*',
                // $productTable.'.name as product_name',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable.'.unit_name',
                $productTable.'.serving_size',
                $productTable.'.direct_shipment',
                $productModelTable.'.digiwin_no',
                $productModelTable.'.sku',
                $productModelTable.'.gtin13',
                $vendorTable.'.id as vendor_id',
                $vendorTable.'.name as vendor_name',
            ]);
    }


}
