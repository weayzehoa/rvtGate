<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryGroupBuyingOrderItemPackage extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'group_buying_order_item_package';
    //變更 Laravel 預設 created_at 與 不使用 updated_at 欄位
    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;
    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_model_id',
        'sku',
        'digiwin_no',
        'gross_weight',
        'net_weight',
        'quantity',
        'purchase_price',
        'is_del',
        'admin_memo',
        'create_time',
        'product_name',
        'is_call',
        'direct_shipment',
        'digiwin_payment_id',
    ];
}
