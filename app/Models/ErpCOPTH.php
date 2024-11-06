<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpCOPTH extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.COPTH';
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
        'TH001', //單別
        'TH002', //單號
        'TH003', //序號
        'TH004', //品號
        'TH005', //品名
        'TH006', //規格
        'TH007', //庫別
        'TH008', //數量
        'TH009', //單位
        'TH010', //庫存數量
        'TH011', //小單位
        'TH012', //單價
        'TH013', //金額
        'TH014', //訂單單別
        'TH015', //訂單單號
        'TH016', //訂單序號
        'TH017', //批號
        'TH018', //備註
        'TH019', //客戶品號
        'TH020', //確認碼
        'TH021', //更新碼
        'TH022', //保留欄位
        'TH023', //保留欄位
        'TH024', //贈/備品量
        'TH025', //折扣率
        'TH026', //結帳碼
        'TH027', //結帳單別
        'TH028', //結帳單號
        'TH029', //結帳序號
        'TH030', //專案代號
        'TH031', //類型
        'TH032', //暫出單別
        'TH033', //暫出單號
        'TH034', //暫出序號
        'TH035', //原幣未稅金額
        'TH036', //原幣稅額
        'TH037', //本幣未稅金額
        'TH038', //本幣稅額
        'TH039', //預留欄位
        'TH040', //預留欄位
        'TH041', //預留欄位
        'TH042', //包裝數量
        'TH043', //贈/備品包裝量
        'TH044', //包裝單位
        'TH045', //發票號碼
        'TH046', //生產加工包裝資訊
        'TH047', //網購訂單編號
        'TH057', //產品序號數量
        'TH074', //CRM來源
        'TH075', //CRM單別
        'TH076', //CRM單號
        'TH077', //CRM序號
        'TH099', //品號稅別
    ];
}

