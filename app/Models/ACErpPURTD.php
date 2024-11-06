<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ACErpPURTD extends Model
{
    use HasFactory;
    protected $connection = 'ACSMERP';
    protected $table = 'dbo.PURTD';
    protected $primaryKey = 'TD002';
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
        'TD001', //單別
        'TD002', //單號
        'TD003', //序號
        'TD004', //品號
        'TD005', //品名
        'TD006', //規格
        'TD007', //庫別
        'TD008', //採購數量
        'TD009', //單位
        'TD010', //單價
        'TD011', //金額
        'TD012', //預交日
        'TD013', //參考單別
        'TD014', //備註
        'TD015', //已交數量
        'TD016', //結案碼
        'TD018', //確認碼
        'TD019', //庫存數量
        'TD025', //急料
        'TD033', //計價數量
        'TD034', //計價單位
    ];
}
