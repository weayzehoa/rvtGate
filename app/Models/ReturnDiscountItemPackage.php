<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnDiscountItemPackage extends Model
{
    use HasFactory;
    protected $fillable = [
        'return_discount_item_id',
        'return_discount_no',
        'erp_return_discount_no',
        'erp_return_discount_sno',
        'purchase_no',
        'erp_purchase_type',
        'erp_purchase_no',
        'erp_purchase_sno',
        'poi_id', //purchase_order_item_id
        'poip_id', //purchase_order_item_package_id
        'product_model_id',
        'purchase_price',
        'quantity',
        'is_del',
        'direct_shipment',
    ];
}
