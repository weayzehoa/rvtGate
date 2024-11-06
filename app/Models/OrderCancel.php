<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;

class OrderCancel extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'order_number',
        'sku',
        'order_digiwin_no',
        'purchase_digiwin_no',
        'quantity',
        'book_shipping_date',
        'vendor_arrival_date',
        'cancel_time',
        'cancel_person',
        'purchase_order_id',
        'purchase_no',
        'deduct_quantity',
        'memo',
        'is_chk',
        'chk_date',
        'admin_id',
        'direct_shipment',
        'vendor_shipping_no',
        'old_ori_id',
        'new_ori_id',
    ];

    public function order(){
        return $this->setConnection('icarry')->hasOne(OrderDB::class,'order_number','order_number');
    }

    public function oldOrderItem(){
        return $this->setConnection('icarry')->hasOne(OrderItemDB::class,'id','old_ori_id')->with('syncedOrderItem');
    }

    public function newOrderItem(){
        return $this->setConnection('icarry')->hasOne(OrderItemDB::class,'id','new_ori_id')->with('syncedOrderItem');
    }
}
