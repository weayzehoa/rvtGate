<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryDigiwinPayment as iCarryCustomerDB;

class ErpINVTB extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.INVTB';
    protected $primaryKey = 'TB002';
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
        'TB001',  //單別
        'TB002',  //單號
        'TB003',  //序號
        'TB004',  //品號
        'TB005',  //品名
        'TB006',  //規格
        'TB007',  //數量
        'TB008',  //單位
        'TB009',  //庫存數量
        'TB010',  //單位成本
        'TB011',  //金額
        'TB012',  //轉出庫
        'TB013',  //轉入庫
        'TB014',  //批號
        'TB015',  //有效日期
        'TB016',  //複檢日期
        'TB017',  //備註
        'TB018',  //確認碼
        'TB019',  //異動日期
        'TB020',  //小單位
        'TB021',  //專案代號
        'TB022',  //包裝數量
        'TB023',  //包裝單位
        'TB025',  //產品序號數量
    ];
}


