<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarrySpgateway extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'spgateway';
    //變更 Laravel 預設 created_at 與 updated_at 欄位名稱
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    protected $fillable = [
        'order_number',
        'post_json',
        'get_json',
        'amount',
        'pay_status',
        'PaymentType',
        'memo',
        'result_json',
    ];
}
