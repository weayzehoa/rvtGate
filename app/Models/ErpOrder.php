<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ErpCustomer as ErpCustomerDB;
use App\Models\ErpOrderItem as ErpOrderItemDB;

class ErpOrder extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.COPTC';
    protected $primaryKey = 'TC002';
    //非數字型別
    protected $keyType = 'string';
    //不自動增加
    public $incrementing = false;
    //不使用時間戳記
    public $timestamps = FALSE;
    protected $fillable = [
        'COMPANY', //公司代號 DEF 'iCarry'
        'CREATOR', //建立者 DEF 'DS'
        'USR_GROUP', //群組 DEF 'DSC'
        'CREATE_DATE', //建立日期
        'FLAG', //修改旗桿 DEF '1'
        'CREATE_TIME', //建立時間
        'CREATE_AP', //建立工作站 DEF 'iCarry'
        'CREATE_PRID', //建立程式 DEF 'COPI06'
        'TC001', //單別 DEF 'A222'
        'TC002', //單號 年月日+五碼流水號 EX:22010100001
        'TC003', //訂單日期 CONVERT(VARCHAR(8),pay_time,112)
        'TC004', //digiwin_payment_id
        'TC005', //業務人員
        'TC006', //部門
        'TC007', //出貨廠別 DEF '001'
        'TC008', //交易幣別 DEF 'NTD'
        'TC009', //匯率 DEF '1'
        'TC010', //送貨地址(一) receiver_address
        'TC012', //客戶單號 partner_order_number
        'TC014', //付款條件 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA031
        'TC016', //課稅別 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA038
        'TC018', //連絡人 receiver_name
        'TC019', //運輸方式 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA048
        'TC026', //佣金比率 DEF '0'
        'TC027', //確認碼 DEF 'Y'
        'TC028', //列印次數 DEF '0'
        'TC029', //訂單金額 各單品加總金額
        'TC030', //訂單稅額 各單品加總金額 * 0.05
        'TC031', //總數量 單品總數量
        'TC035', //目的地 ship_to
        'TC039', //單據日期 CONVERT(VARCHAR(8),pay_time,112)
        'TC040', //確認者 抓管理者account轉大寫
        'TC041', //營業稅率 DEF '0.05'
        'TC042', //簽核狀態碼 DEF 'N'
        'TC043', //客戶全名 DEF '其他'
        'TC044', //發票地址(一) invoice_address
        'TC046', //送貨客戶全名 DEF '其他'
        'TC047', //TEL_NO receiver_tel
        'TC050', //材積單位 DEF '1'
        'TC055', //交易條件 DEF '1'
        'TC057', //傳送次數 DEF '0'
        'TC058', //訂金比率 DEF '1'
        'TC059', //付款條件代號 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA083
        'TC061', //網購訂單編號 order_number
        'TC062', //收貨人 receiver_name
        'TC063', //指定日期 CONVERT(VARCHAR(8),book_shipping_date,112)
        'TC064', //配送時段 DEF '1'
        'TC068', //客戶描述
        'TC074', //訂金分批 DEF 'N'
        'TC075', //收入遞延天數 DEF '0'
        'TC094', //行動電話 receiver_phone_number
        'TC200', //貨運單號 DEF 'N'
        'TC201', //廠商出貨 DEF 'N'
    ];

    public function items(){
        return $this->hasMany(ErpOrderItemDB::class,'TD002','TC002')->where('TD016','!=','y');
    }

    public function forSyncItems(){ //為了訂單已取消後要轉回集貨中, 避免發生錯誤, 新增此項如 items 取消限制條件.
        return $this->hasMany(ErpOrderItemDB::class,'TD002','TC002');
    }

    public function customer(){//不能使用belongsTo, 也不能用with, 否則找不到資料, 只能直接使用 order->customer
        return $this->hasOne(ErpCustomerDB::class,'MA001','TC004');
    }
}
