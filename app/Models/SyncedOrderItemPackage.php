<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ErpPurchasePrice as ErpPurchasePriceDB;

class SyncedOrderItemPackage extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'order_item_id',
        'order_item_package_id',
        'erp_order_no',
        'erp_order_sno',
        'erp_purchase_no',
        'purchase_no',
        'purchase_date',
        'book_shipping_date',
        'vendor_arrival_date',
        'product_model_id',
        'unit_name',
        'gross_weight',
        'net_weight',
        'price',
        'purchase_price',
        'quantity',
        'admin_memo',
        'is_del',
        'direct_shipment',
    ];

    public function erpPurchasePrice(){
        return $this->hasOne(ErpPurchasePriceDB::class,'MB001','digiwin_no')
            ->where('dbo.PURMB.MB014','<=',date('Ymd'))->orderBy('dbo.PURMB.MB014','desc');;
    }
}
