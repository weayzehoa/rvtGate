<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; //使用軟刪除

class iCarryOrderVendorShipping extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'order_with_vendor';
    //變更 Laravel 預設 created_at 與 updated_at 欄位名稱
    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;
}
