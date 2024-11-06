<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NidinSetBalance extends Model
{
    use HasFactory;
    protected $fillable = [
        'set_no',
        'set_qty',
        'balance', //票券原價餘額
        'total_balance', //實際金額餘額
        'arrears', //折扣後抵銷後欠款
        'remain',
        'is_close',
        'close_date',
        'is_lock',
    ];
}
