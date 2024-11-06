<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NidinPaymentLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'from_nidin',
        'to_nidin',
        'to_acpay',
        'from_acpay',
        'nidin_order_no',
        'transaction_id',
        'message',
        'ip',
    ];
}
