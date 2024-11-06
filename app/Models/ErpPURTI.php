<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpPURTI extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.PURTI';
    protected $primaryKey = 'TI002';
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
        'TI001', //單別
        'TI002', //單號
        'TI003', //退貨日期
        'TI004', //供應廠商
        'TI005', //廠別
        'TI006', //幣別
        'TI007', //匯率
        'TI008', //發票聯數
        'TI009', //課稅別
        'TI010', //列印次數
        'TI011', //原幣退貨金額
        'TI012', //備註
        'TI013', //確認碼
        'TI014', //單據日期
        'TI015', //原幣退貨稅額
        'TI016', //廠商全名
        'TI017', //統一編號
        'TI018', //發票號碼
        'TI019', //扣抵區分
        'TI020', //菸酒註記
        'TI021', //件數
        'TI022', //數量合計
        'TI023', //發票日期
        'TI024', //產生分錄碼
        'TI025', //申報年月
        'TI026', //確認者
        'TI027', //營業稅率
        'TI028', //本幣退貨金額
        'TI029', //本幣退貨稅額
        'TI030', //簽核狀態碼
        'TI031', //包裝數量合計
        'TI032', //傳送次數
        'TI033', //付款條件代號
    ];
}



