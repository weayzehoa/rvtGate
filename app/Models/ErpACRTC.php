<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpACRTC extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.ACRTC';
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
        'EF_ERPMA001',
        'EF_ERPMA002',
        'TC001',
        'TC002',
        'TC003',
        'TC004',
        'TC005',
        'TC006',
        'TC007',
        'TC008',
        'TC009',
        'TC010',
        'TC011',
        'TC012',
        'TC013',
        'TC014',
        'TC015',
        'TC016',
        'TC017',
        'TC018',
        'TC019',
        'TC020',
        'TC021',
        'TC022',
        'TC028',
    ];
}
