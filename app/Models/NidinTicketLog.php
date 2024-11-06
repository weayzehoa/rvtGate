<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NidinTicketLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'nidin_order_no',
        'transaction_id',
        'platform_no',
        'key',
        'type',
        'from_nidin',
        'to_acpay',
        'from_acpay',
        'to_nidin',
        'rtnCode',
        'rtnMsg',
        'message',
        'ip',
    ];
}
