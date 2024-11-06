<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\iCarryOrder as OrderDB;
use App\Models\VendorShippingItem as VendorShippingItemDB;

class SellImport extends Model
{
    use HasFactory;
    protected $fillable = [
        'import_no',
        'type',
        'order_number',
        'shipping_number',
        'gtin13',
        'purchase_no',
        'digiwin_no',
        'product_name',
        'quantity',
        'sell_date',
        'stockin_time',
        'status',
        'memo',
        'vsi_id',
        'order_item_id',
    ];

    public function order()
    {
        return $this->hasOne(OrderDB::class,'order_number','order_number');
    }

    public function vendorShipping()
    {
        return $this->hasOne(VendorShippingItemDB::class,'id','vsi_id');
    }

}
