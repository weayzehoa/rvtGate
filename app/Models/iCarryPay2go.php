<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryPay2go extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'pay2go';
    //變更 Laravel 預設 created_at 與 updated_at 欄位名稱
    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;
    protected $fillable = [
        'type',
        'order_number',
        'post_json',
        'get_json',
        'tax_type',
        'total_amt',
        'buyer_name',
        'buyer_UBN',
        'invoice_no',
        'allowance_no',
        'allowance_amt',
        'remain_amt',
        'canceled_order_number',
        'random_num',
    ];
}
