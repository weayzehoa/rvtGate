<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryProductUpdateRecord extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'product_update_record';
    //變更 Laravel 預設 created_at 與 updated_at 欄位名稱
    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;
    protected $fillable = [
        'product_id',
        'admin_id',
        'vendor_id',
        'column',
        'before_value',
        'after_value',
    ];
}
