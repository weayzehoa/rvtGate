<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryDigiwinPayment as iCarryCustomerDB;

class ErpINVTA extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.INVTA';
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
        'TA001',  //單別
        'TA002',  //單號
        'TA003',  //異動日期
        'TA004',  //部門代號
        'TA005',  //備註
        'TA006',  //確認碼
        'TA007',  //列印次數
        'TA008',  //廠別代號
        'TA009',  //單據性質碼
        'TA010',  //件數
        'TA011',  //總數量
        'TA012',  //總金額
        'TA013',  //產生分錄碼
        'TA014',  //單據日期
        'TA015',  //確認者
        'TA016',  //簽核狀態碼
        'TA017',  //總包裝數量
        'TA018',  //傳送次數
        'TA025',  //來源
    ];
}
