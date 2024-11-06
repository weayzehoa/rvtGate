<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpTAXMC extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.TAXMC';
    protected $primaryKey = 'MC006';
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
        'MC001', //申報公司
        'MC002', //申報年月
        'MC003', //預留欄位
        'MC004', //格式代號
        'MC005', //稅籍編號
        'MC006', //流水號
        'MC007', //開立日期
        'MC008', //買方統一編號
        'MC009', //賣方統一編號
        'MC010', //發票號碼
        'MC011', //銷售金額
        'MC012', //課稅別
        'MC013', //營業稅額
        'MC014', //扣抵代號
        'MC015', //空白欄位
        'MC016', //彙加註記
        'MC017', //洋菸酒註記
        'MC018', //備註
        'MC019', //來源方式
        'MC020', //來源單別
        'MC021', //來源單號
        'MC022', //買受人代號
        'MC023', //買受人簡稱
        'MC024', //貨物名稱
        'MC025', //數量
        'MC026', //外銷方式
        'MC027', //通關方式
        'MC028', //證明文件名稱
        'MC029', //出口報單類別
        'MC030', //證明文件號碼
        'MC031', //輸出或結匯日期
        'MC032', //混稅
        'MC033', //預留欄位
        'MC034', //免稅銷售額
        'MC035', //資料來源
        'MC038', //用途
        'MC042', //賣方名稱
        'MC043', //賣方地址
        'MC044', //賣方負責人姓名
        'MC045', //賣方電話
        'MC046', //賣方傳真
        'MC047', //電子郵件地址
        'MC048', //買方名稱
        'MC049', //買方地址
        'MC050', //買方負責人姓名
        'MC051', //買方電話號碼
        'MC052', //買方傳真號碼
        'MC053', //買方電子郵件
        'MC054', //發票檢查碼
        'MC055', //稅捐稽徵處名稱
        'MC056', //核准日
        'MC057', //核准文
        'MC058', //核准號
        'MC059', //發票類別
        'MC060', //捐贈註記
        'MC061', //稅率
        'MC062', //總計
        'MC063', //扣抵金額
        'MC064', //原幣金額
        'MC065', //匯率
        'MC066', //幣別
        'MC067', //作廢日期
        'MC068', //作廢時間
        'MC069', //專案作廢核准文號
        'MC070', //作廢原因
        'MC071', //接收日期
        'MC072', //接收時間
        'MC073', //狀態碼
        'MC074', //賣方營業人角色註記
        'MC075', //買方營業人角色註記
        'MC076', //沖帳別
        'MC077', //相關號碼
        'MC078', //總備註
        'MC079', //開立日期
        'MC080', //信用卡末四碼
        'MC081', //預留欄位
        'MC082', //發票開立時間
        'MC083', //上傳方式
        'MC084', //載具顯碼ID
        'MC085', //載具類別號碼
        'MC086', //載具隱碼ID
        'MC087', //發票捐贈對象
        'MC088', //發票防偽隨機碼
        'MC089', //紙本電子發票已列印註記
    ];
}



