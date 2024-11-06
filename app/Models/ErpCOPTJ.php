<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpCOPTJ extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.COPTJ';
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
        'MODIFIER',
        'MODI_DATE',
        'FLAG',
        'CREATE_TIME',
        'CREATE_AP',
        'CREATE_PRID',
        'MODI_TIME',
        'MODI_AP',
        'MODI_PRID',
        'EF_ERPMA001',
        'EF_ERPMA002',
        'TJ001', //單別
        'TJ002', //單號
        'TJ003', //序號
        'TJ004', //品號
        'TJ005', //品名
        'TJ006', //規格
        'TJ007', //數量
        'TJ008', //單位
        'TJ009', //庫存數量
        'TJ010', //小單位
        'TJ011', //單價
        'TJ012', //金額
        'TJ013', //退貨庫別
        'TJ014', //批號
        'TJ015', //銷貨單別
        'TJ016', //銷貨單號
        'TJ017', //銷貨序號
        'TJ018', //訂單單別
        'TJ019', //訂單單號
        'TJ020', //訂單序號
        'TJ021', //確認碼
        'TJ022', //更新碼
        'TJ023', //備註
        'TJ024', //結帳碼
        'TJ025', //結帳單別
        'TJ026', //結帳單號
        'TJ027', //結帳序號
        'TJ028', //專案代號
        'TJ029', //客戶品號
        'TJ030', //類型
        'TJ031', //原幣未稅金額
        'TJ032', //原幣稅額
        'TJ033', //本幣未稅金額
        'TJ034', //本幣稅額
        'TJ035', //包裝數量
        'TJ036', //包裝單位
        'TJ041', //數量類型
        'TJ042', //贈/備品量
        'TJ043', //贈/備品包裝量
        'TJ044', //發票號碼
        'TJ045', //網購訂單編號
        'TJ047', //產品序號數量
        'TJ052', //銷退原因代號
        'TJ099', //品號稅別
    ];
}

