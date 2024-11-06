<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MomoOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_number', //momo訂單號碼
        'colE', //訂單編號
        'colF', //收件人姓名
        'colG', //收件人地址
        'colL', //轉單日
        'colM', //預計出貨日
        'colN', //商品原廠編號 sku
        'colP', //品名
        'colS', //數量
        'colU', //售價(含稅)
        'colW', //訂購人姓名
        'all_cols', //所有欄位
        'created_at',
    ];
}
