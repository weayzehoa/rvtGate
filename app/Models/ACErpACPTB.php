<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ACErpACPTB extends Model
{
    use HasFactory;
    protected $connection = 'ACSMERP';
    protected $table = 'dbo.ACPTB';
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
        'TB001',  //憑單單別
        'TB002',  //憑單單號
        'TB003',  //憑單序號
        'TB004',  //來源
        'TB005',  //憑證單別
        'TB006',  //憑證單號
        'TB007',  //憑證序號
        'TB008',  //憑證日期
        'TB009',  //應付金額
        'TB010',  //差額
        'TB011',  //備註
        'TB012',  //確認碼
        'TB013',  //科目編號
        'TB014',  //費用部門
        'TB015',  //原幣未稅金額
        'TB016',  //原幣稅額
        'TB017',  //本幣未稅金額
        'TB018',  //本幣稅額
        'TB019',  //專案代號
        'TB020',  //營業稅稅基
        'TB021',  //訂金序號
    ];
}






