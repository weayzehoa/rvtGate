<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpPURTH extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.PURTH';
    protected $primaryKey = 'TH002';
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
        'TH001', //單別
        'TH002', //單號
        'TH031', //結帳碼
        'TH003', //序號
        'TH004', //品號
        'TH005', //品名
        'TH006', //規格
        'TH007', //進貨數量
        'TH008', //單位
        'TH009', //庫別
        'TH011', //採購單別
        'TH012', //採購單號
        'TH013', //採購序號
        'TH014', //驗收日期
        'TH015', //驗收數量
        'TH016', //計價數量
        'TH017', //驗退數量
        'TH018', //原幣單位進價
        'TH019', //原幣進貨金額
        'TH020', //原幣扣款金額
        'TH024', //進貨費用
        'TH026', //暫不付款
        'TH027', //逾期碼
        'TH028', //檢驗狀態
        'TH029', //驗退碼
        'TH030', //確認碼
        'TH032', //更新碼
        'TH033', //備註
        'TH034', //庫存數量
        'TH038', //確認者
        'TH039', //應付憑單別
        'TH040', //應付憑單號
        'TH041', //應付憑單序號
        'TH042', //專案代號
        'TH043', //產生分錄碼
        'TH044', //沖自籌額碼
        'TH045', //原幣未稅金額
        'TH046', //原幣稅額
        'TH047', //本幣未稅金額
        'TH048', //本幣稅額
        'TH049', //計價單位
        'TH050', //簽核狀態碼
        'TH051', //原幣沖自籌額
        'TH052', //本幣沖自籌額
        'TH054', //抽樣數量
        'TH055', //不良數量
        'TH058', //缺點數
        'TH059', //進貨包裝數量
        'TH060', //驗收包裝數量
        'TH061', //驗退包裝數量
        'TH064', //產品序號數量
        'EF_ERPMA001',
        'EF_ERPMA002',
    ];
}
