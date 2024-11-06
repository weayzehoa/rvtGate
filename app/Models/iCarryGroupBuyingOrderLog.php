<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryGroupBuyingOrderLog extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'group_buying_log_order';
    //變更 Laravel 預設 created_at 與 updated_at 欄位名稱
    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;
    protected $fillable = [
        'column_name',
        'order_id',
        'order_item_id',
        'log',
        'editor',
    ];
}
