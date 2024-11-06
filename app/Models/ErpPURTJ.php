<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpPURTJ extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.PURTJ';
    protected $primaryKey = 'TJ002';
    protected $keyType = 'string';
    //不自動增加
    public $incrementing = false;
    public $timestamps = FALSE;

    protected $fillable = [
        'COMPANY',
        'CREATOR',
        'USR_GROUP',
        'CREATE_DATE',
        'FLAG',
        'CREATE_TIME',
        'CREATE_AP',
        'CREATE_PRID',
        'TJ001', //單別
        'TJ002', //單號
        'TJ003', //序號
        'TJ004', //品號
        'TJ005', //品名
        'TJ006', //規格
        'TJ007', //單位
        'TJ008', //單價
        'TJ009', //數量
        'TJ010', //金額
        'TJ011', //退貨庫別
        'TJ016', //原採購單別
        'TJ017', //原採購單號
        'TJ018', //原採購序號
        'TJ019', //備註
        'TJ020', //確認碼
        'TJ021', //結帳碼
        'TJ022', //庫存數量
        'TJ028', //更新碼
        'TJ030', //原幣未稅金額
        'TJ031', //原幣稅額
        'TJ032', //本幣未稅金額
        'TJ033', //本幣稅額
        'TJ034', //計價數量
        'TJ035', //計價單位
    ];
}
