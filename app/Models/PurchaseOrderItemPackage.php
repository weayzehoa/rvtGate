<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ReturnDiscount as ReturnDiscountDB;
use App\Models\ReturnDiscountItem as ReturnDiscountItemDB;
use App\Models\ReturnDiscountItemPackage as ReturnDiscountItemPackageDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;

class PurchaseOrderItemPackage extends Model
{
    use HasFactory;
    protected $fillable = [
        'purchase_no',
        'purchase_order_item_id',
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
    ];
    public function returns()
    {
        $returnDiscountTable = env('DB_DATABASE').'.'.(new ReturnDiscountDB)->getTable();
        $returnDiscountItemTable = env('DB_DATABASE').'.'.(new ReturnDiscountItemDB)->getTable();
        $returnDiscountItemPackageTable = env('DB_DATABASE').'.'.(new ReturnDiscountItemPackageDB)->getTable();
        return $this->hasMany(ReturnDiscountItemPackageDB::class,'poip_id','id')
            ->join($returnDiscountTable,$returnDiscountTable.'.return_discount_no',$returnDiscountItemPackageTable.'.return_discount_no')
            ->where($returnDiscountTable.'.is_del',0)
            ->select([
                $returnDiscountItemPackageTable.'.*',
                $returnDiscountTable.'.return_date',
            ]);
    }
    public function stockins()
    {
        return $this->hasMany(StockinItemSingleDB::class,'poip_id','id')->where('is_del',0)->orderBy('stockin_date','asc');
    }
    public function single()
    {
        return $this->hasOne(PurchaseOrderItemSingleDB::class,'poip_id','id')->where('is_del',0);
    }
}
