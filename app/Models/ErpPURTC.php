<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryDigiwinPayment as iCarryCustomerDB;
use App\Models\ErpPURTD as ErpPURTDDB;

class ErpPURTC extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.PURTC';
    protected $primaryKey = 'TC002';
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
        'TC001', //單別
        'TC002', //單號
        'TC003', //日期
        'TC004', //廠商代號
        'TC005', //幣別
        'TC006', //匯率
        'TC008', //付款條件名稱
        'TC009', //備註
        'TC010', //廠別
        'TC012', //列印格式
        'TC013', //列印次數
        'TC014', //確認碼
        'TC018', //課稅別
        'TC019', //採購金額
        'TC020', //稅額
        'TC021', //送貨地址
        'TC023', //數量合計
        'TC024', //單據日期
        'TC025', //確認者
        'TC026', //營業稅率
        'TC027', //簽核狀態碼
        'TC028', //郵遞區號
        'TC030', //傳送次數
        'TC031', //訂金比率
        'TC032', //付款條件代號
        'TC040', //訂金分批
    ];

    public function items(){
        return $this->hasMany(ErpPURTDDB::class,'TD002','TC002')
            ->select([
                'CREATE_DATE',
                'CREATE_TIME',
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
                'TD016', //結案碼
            ]);
    }

}
