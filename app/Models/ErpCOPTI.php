<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpCOPTI extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.COPTI';
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
        'TI001', //單別
        'TI002', //單號
        'TI003', //銷退日
        'TI004', //客戶
        'TI005', //部門
        'TI006', //業務員
        'TI007', //廠別
        'TI008', //幣別
        'TI009', //匯率
        'TI010', //原幣銷退金額
        'TI011', //原幣銷退稅額
        'TI012', //發票聯數
        'TI013', //課稅別
        'TI014', //發票號碼
        'TI015', //統一編號
        'TI016', //列印次數
        'TI017', //發票日期
        'TI018', //更新碼
        'TI019', //確認碼
        'TI020', //備註
        'TI021', //客戶全名
        'TI022', //收款業務員
        'TI023', //備註一
        'TI024', //備註二
        'TI025', //折讓列印次數
        'TI026', //扣抵區分
        'TI027', //通關方式
        'TI028', //件數
        'TI029', //總數量
        'TI030', //員工代號
        'TI031', //產生分錄碼(收入)
        'TI032', //產生分錄碼(成本)
        'TI033', //申報年月
        'TI034', //單據日期
        'TI035', //確認者
        'TI036', //營業稅率
        'TI037', //本幣銷退金額
        'TI038', //本幣銷退稅額
        'TI039', //簽核狀態碼
        'TI040', //交易條件
        'TI041', //總包裝數量
        'TI042', //傳送次數
        'TI043', //付款條件代號
        'TI044', //客戶描述
        'TI045', //作廢日期
        'TI046', //作廢時間
        'TI047', //作廢原因
        'TI081', //原幣應稅銷售額
        'TI089', //賣方開立折讓單
        'TI090', //折讓單成立日
        'TI091', //連絡人EMAIL
        'TI092', //原幣免稅銷售額
        'TI093', //本幣應稅銷售額
        'TI094', //本幣免稅銷售額
    ];
}
