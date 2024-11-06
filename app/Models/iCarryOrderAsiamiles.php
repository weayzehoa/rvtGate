<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryOrderAsiamiles extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'order_asiamiles';
    protected $primaryKey = 'order_id';
    //變更 Laravel 預設 created_at 與 updated_at 欄位名稱
    const CREATED_AT = null;
    const UPDATED_AT = null;
    protected $fillable = [
        'order_id',
        'asiamiles_account',
        'asiamiles_name',
        'asiamiles_last_name',
    ];
}
