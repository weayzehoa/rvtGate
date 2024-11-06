<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;

class AcOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'serial_no',
        'get_json',
        'order_id',
        'order_number',
        'vendor_order_no',
        'amount',
        'discount',
        'message',
        'is_invoice',
        'is_sync',
        'purchase_id',
        'purchase_no',
        'purchase_sync',
        'is_sell',
        'is_stockin',
        'ip',
        'sell_return',
        'stockin_return',
        'return_date'
    ];

    public function iCarryOrder(){
        return $this->setConnection('mysql')->hasOne(OrderDB::class,'id','order_id');
    }
}
