<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ErpCOPTH as ErpCOPTHDB;

class ErpCOPTG extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.COPTG';
    protected $primaryKey = 'TG002';
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
        'TG001', //單別
        'TG002', //單號
        'TG003', //銷貨日期
        'TG004', //客戶代號
        'TG005', //部門
        'TG006', //業務員
        'TG007', //客戶全名
        'TG008', //送貨地址一
        'TG009', //送貨地址二
        'TG010', //出貨廠別
        'TG011', //幣別
        'TG012', //匯率
        'TG013', //原幣銷貨金額
        'TG014', //發票號碼
        'TG015', //統一編號
        'TG016', //發票聯數
        'TG017', //課稅別
        'TG018', //發票地址一
        'TG019', //發票地址二
        'TG020', //備註
        'TG021', //發票日期
        'TG022', //列印次數
        'TG023', //確認碼
        'TG024', //更新碼
        'TG025', //原幣銷貨稅額
        'TG026', //收款業務員
        'TG027', //備註一
        'TG028', //備註二
        'TG029', //備註三
        'TG030', //發票作廢
        'TG031', //通關方式
        'TG032', //件數
        'TG033', //總數量
        'TG034', //現銷
        'TG035', //員工代號
        'TG036', //產生分錄碼(收入)
        'TG037', //產生分錄碼(成本)
        'TG038', //申報年月
        'TG039', //L/C_NO
        'TG040', //INVOICE_NO
        'TG041', //發票列印次數
        'TG042', //單據日期
        'TG043', //確認者
        'TG044', //營業稅率
        'TG045', //本幣銷貨金額
        'TG046', //本幣銷貨稅額
        'TG047', //簽核狀態碼
        'TG048', //報單號碼
        'TG049', //送貨客戶全名
        'TG050', //連絡人
        'TG051', //TEL_NO
        'TG052', //FAX_NO
        'TG053', //出貨通知單別
        'TG054', //出貨通知單號
        'TG055', //預留欄位
        'TG056', //交易條件
        'TG057', //總包裝數量
        'TG058', //傳送次數
        'TG059', //訂單單別
        'TG060', //訂單單號
        'TG061', //預收待抵單別
        'TG062', //預收待抵單號
        'TG063', //沖抵金額
        'TG064', //沖抵稅額
        'TG065', //付款條件代號
        'TG066', //收貨人
        'TG067', //指定日期
        'TG068', //配送時段
        'TG069', //貨運別
        'TG070', //代收貨款
        'TG071', //運費
        'TG072', //產生貨運文字檔
        'TG073', //客戶描述
        'TG074', //作廢日期
        'TG075', //作廢時間
        'TG076', //專案作廢核准文號
        'TG077', //作廢原因
        'TG078', //發票開立時間
        'TG079', //載具顯碼ID
        'TG080', //載具類別號碼
        'TG081', //載具隱碼ID
        'TG082', //發票捐贈對象
        'TG083', //發票防偽隨機碼
        'TG106', //來源
        'TG129', //行動電話
        'TG130', //信用卡末四碼
        'TG131', //連絡人EMAIL
        'TG132', //買受人適用零稅率註記
        'TG200', //載具行動電話
        'TG134', //貨運單號
        'TG091', //原幣應稅銷售額
        'TG092', //原幣免稅銷售額
        'TG093', //本幣應稅銷售額
        'TG094', //本幣免稅銷售額
    ];

    public function items()
    {
        return $this->hasMany(ErpCOPTHDB::class,'TH002','TG002');
    }
}
