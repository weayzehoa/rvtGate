<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryProductModel as iCarryProductModelDB;

class ErpOrderItem extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.COPTD';
    protected $primaryKey = 'TD002';
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
        'TD001', //單別 DEF 'A222'
        'TD002', //單號 年月日+五碼流水號 EX:22010100001
        'TD003', //序號 依單別、單號依序加入四碼流水號
        'TD004', //品號 product_model的digiwin_no
        'TD005', //品名 用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB002
        'TD006', //用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB003
        'TD007', //庫別 待討論，理論上廠商直送是W02，倉庫出貨是W01
        'TD008', //訂單數量 依拆分後數量
        'TD009', //已交數量 DEF '0'
        'TD010', //單位 product.unit_name
        'TD011', //單價 order_item.price
        'TD012', //金額 order_item.price * order_item.quantity
        'TD013', //預交日 order.book_shipping_date
        'TD016', //結案碼 DEF 'N'
        'TD021', //確認碼 DEF 'Y'
        'TD022', //庫存數量 DEF '0'
        'TD024', //贈品量 DEF '0'
        'TD025', //贈品已交量 DEF '0'
        'TD026', //折扣率
        'TD030', //毛重(Kg) order_item.gross_weight
        'TD031', //材積(CUFT) DEF '0'
        'TD032', //來源 DEF '1'
        'TD033', //訂單包裝數量 DEF '0'
        'TD034', //已交包裝數量 DEF '0'
        'TD035', //贈品包裝量 DEF '0'
        'TD036', //贈品已交包裝量 DEF '0'
    ];
}
