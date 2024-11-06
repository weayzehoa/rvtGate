<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryDigiwinPayment as iCarryCustomerDB;

class ErpACRTB extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.ACRTB';
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
        'FLAG',
        'CREATE_TIME',
        'CREATE_AP',
        'CREATE_PRID',
        'TB001',
        'TB002',
        'TB003',
        'TB004',
        'TB005',
        'TB006',
        'TB007',
        'TB008',
        'TB009',
        'TB010',
        'TB011',
        'TB012',
        'TB013',
        'TB014',
        'TB015',
        'TB017',
        'TB018',
        'TB019',
        'TB020',
        'TB024',
    ];
}
