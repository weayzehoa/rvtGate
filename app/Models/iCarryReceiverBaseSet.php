<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryReceiverBaseSet extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'receiver_base_set';
    //變更 Laravel 預設 created_at 與 updated_at 欄位
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    protected $fillable = [
        'select_time',
        'is_call',
        'is_logistics',
        'is_out',
        'is_extract',
        'call_memo',
        'logistics_memo',
        'out_memo',
        'extract_memo',
        'admin_id',
        'update_admin_id',
    ];
}
