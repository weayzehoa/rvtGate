<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NidinPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'from_nidin',
        'to_nidin',
        'to_acpay',
        'from_acpay',
        'callback_url',
        'notify_url',
        'nidin_order_no',
        'transaction_id',
        'amount',
        'pay_time',
        'message',
        'is_success',
        'ip',
        'callback_json',
        'notify_json',
        'is_capture',
        'is_refund',
        'auto_settle'
    ];
}
