<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpACPTA extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.ACPTA';
    protected $primaryKey = 'TA002';
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
        'TA001', //憑單單別
        'TA002', //憑單單號
        'TA003', //憑單日期
        'TA004', //供應廠商
        'TA005', //廠別
        'TA006', //統一編號
        'TA008', //幣別
        'TA009', //匯率
        'TA010', //發票聯數
        'TA011', //課稅別
        'TA012', //扣抵區分
        'TA013', //煙酒註記
        'TA014', //發票號碼
        'TA015', //發票日期
        'TA016', //發票貨款
        'TA017', //發票稅額
        'TA018', //發票作廢
        'TA019', //預計付款日
        'TA020', //預計兌現日
        'TA021', //備註
        'TA022', //採購單別
        'TA023', //採購單號
        'TA024', //確認碼
        'TA025', //更新碼
        'TA026', //結案碼
        'TA027', //列印次數
        'TA028', //應付金額
        'TA029', //營業稅額
        'TA030', //已付金額
        'TA031', //產生分錄碼
        'TA032', //申報年月
        'TA033', //凍結付款碼
        'TA034', //單據日期
        'TA035', //確認者
        'TA036', //營業稅率
        'TA037', //本幣應付金額
        'TA038', //本幣營業稅額
        'TA039', //簽核狀態碼
        'TA040', //本幣已付金額
        'TA041', //已沖稅額
        'TA042', //傳送次數
        'TA043', //代徵營業稅
        'TA044', //本幣完稅價格
        'TA045', //付款條件代號
        'TA052', //訂金序號
        'TA056', //來源
        'TA071', //連絡人EMAIL
        'TA082', //作廢日期
        'TA083', //作廢時間
        'TA084', //作廢原因
        'TA085', //預留欄位
        'TA086', //預留欄位
        'TA087', //作廢折讓單號
        'TA088', //折讓證明單號碼
        'TA089', //折讓單簽回日期
        'TA090', //買方開立折讓單
    ];
}
