<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\Admin as AdminDB;

class SellAbnormal extends Model
{
    use HasFactory;
    protected $fillable = [
        'import_no',
        'order_id',
        'order_number',
        'erp_order_number',
        'product_model_id',
        'product_name',
        'order_quantity',
        'quantity',
        'direct_shipment',
        'is_chk',
        'sell_date',
        'shipping_memo',
        'memo',
        'chk_date',
        'admin_id',
    ];

    public function order()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        return $this->hasOne(OrderDB::class,'order_number','order_number');
    }

    public function product()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        return $this->hasOne(ProductModelDB::class,'id','product_model_id')->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->select([
            $vendorTable.'.name as vendor_name',
            $productModelTable.'.sku',
        ]);
    }

    public function admin()
    {
        return $this->hasOne(AdminDB::class,'id','admin_id');
    }
}
