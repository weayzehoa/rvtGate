<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ReturnDiscountItemPackage as ReturnDiscountItemPackageDB;
use DB;

class ReturnDiscountItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'return_discount_no',
        'erp_return_discount_no',
        'erp_return_discount_sno',
        'purchase_no',
        'erp_purchase_type',
        'erp_purchase_no',
        'erp_purchase_sno',
        'poi_id', //purchase_order_item_id
        'product_model_id',
        'purchase_price',
        'quantity',
        'is_del',
        'direct_shipment',
        'is_lock',
    ];

    public function packages()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $returnDiscountItemPackageTable = env('DB_DATABASE').'.'.(new ReturnDiscountItemPackageDB)->getTable();

        return $this->hasMany(ReturnDiscountItemPackageDB::class,'return_discount_item_id','id')
            ->join($productModelTable,$productModelTable.'.id',$returnDiscountItemPackageTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->select([
                $returnDiscountItemPackageTable.'.*',
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
