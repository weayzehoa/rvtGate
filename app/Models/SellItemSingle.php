<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellItemSingle extends Model
{
    use HasFactory;

    protected $fillable = [
        'sell_no',
        'erp_sell_no',
        'erp_sell_sno',
        'erp_order_no',
        'erp_order_sno',
        'order_number',
        'order_item_id',
        'order_item_package_id',
        'order_quantity',
        'sell_quantity',
        'sell_date',
        'sell_price',
        'product_model_id',
        'memo',
        'direct_shipment',
        'express_way',
        'express_no',
        'is_del',
        'purchase_no',
        'pois_id',
    ];
}
