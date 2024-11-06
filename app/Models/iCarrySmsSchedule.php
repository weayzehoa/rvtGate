<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarrySmsSchedule extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'sms_schedule';
    //變更 Laravel 預設 created_at 與 updated_at 欄位名稱
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    protected $fillable = [
        'mobile',
        'message',
        'vendor',
        'vendor_name',
        'create_time',
        'user_id',
        'is_send',
        'order_id',
    ];
}
