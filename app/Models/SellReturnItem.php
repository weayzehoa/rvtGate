<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use DB;

class SellReturnItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'order_number',
        'return_no',
        'order_item_id',
        'order_item_package_id',
        'erp_order_type',
        'erp_order_no',
        'erp_order_sno',
        'erp_return_type',
        'erp_return_no',
        'erp_return_sno',
        'order_digiwin_no',
        'origin_digiwin_no',
        'quantity',
        'return_quantity',
        'price',
        'expiry_date',
        'is_stockin',
        'stockin_admin_id',
        'is_confirm',
        'erp_requisition_type',
        'erp_requisition_no',
        'erp_requisition_sno',
        'is_chk',
        'chk_date',
        'admin_id',
        'direct_shipment',
        'item_memo',
        'is_del',
    ];

    public function product(){
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        return $this->hasOne(ProductModelDB::class,'digiwin_no','origin_digiwin_no')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->select([
                $productModelTable.'.*',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name")
            ]);
    }
}
