<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ErpPURTH as ErpPURTHDB;

class ErpPURTG extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.PURTG';
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
        'FLAG',
        'CREATE_TIME',
        'CREATE_AP',
        'CREATE_PRID',
        'TG001', //單別
        'TG002', //單號
        'TG003', //進貨日期
        'TG004', //廠別
        'TG005', //供應廠商
        'TG007', //幣別
        'TG008', //匯率
        'TG009', //發票聯數
        'TG010', //課稅別
        'TG012', //列印次數
        'TG013', //確認碼
        'TG014', //單據日期
        'TG015', //更新碼
        'TG016', //備註
        'TG017', //進貨金額
        'TG018', //扣款金額
        'TG019', //原幣稅額
        'TG020', //進貨費用
        'TG021', //廠商全名
        'TG022', //統一編號
        'TG023', //扣抵區分
        'TG024', //菸酒註記
        'TG025', //件數
        'TG026', //數量合計
        'TG027', //發票日期
        'TG028', //原幣貨款金額
        'TG029', //申報年月
        'TG030', //營業稅率
        'TG031', //本幣貨款金額
        'TG032', //本幣稅額
        'TG033', //簽核狀態碼
        'TG038', //沖抵金額
        'TG039', //沖抵稅額
        'TG040', //預留欄位
        'TG041', //本幣沖自籌額
        'TG045', //預留欄位
        'TG046', //原幣沖自籌額
        'TG047', //包裝數量合計
        'TG048', //傳送次數
        'TG049', //付款條件代號
        'EF_ERPMA001',
        'EF_ERPMA002',
    ];

    public function items(){
        return $this->hasMany(ErpPURTHDB::class,'TH002','TG002');
    }
}
