<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes; //使用軟刪除

class OrderShipping extends Model
{
    use HasFactory;
    //使用軟刪除
    // use SoftDeletes;
    protected $fillable = [
        'order_id',
        'order_item_id',
        'express_way',
        'express_no',
    ];
}
