<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\NidinPayment as NidinPaymentDB;

class NidinOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'nidin_order_no',
        'merchant_no',
        'notify_url',
        'transaction_id',
        'get_json',
        'post_json',
        'message',
        'order_id',
        'order_number',
        'amount',
        'discount',
        'is_invoice',
        'is_ticket',
        'is_sync',
        'ip',
    ];

    public function order(){
        return $this->setConnection('icarry')->hasOne(OrderDB::class,'order_number','order_number');
    }

    public function vendor(){
        return $this->setConnection('icarry')->hasOne(VendorDB::class,'merchant_no','merchant_no');
    }

    public function payment(){
        return $this->setConnection('mysql')->hasOne(NidinPaymentDB::class,'transaction_id','transaction_id')->where('is_success',1);
    }
}
