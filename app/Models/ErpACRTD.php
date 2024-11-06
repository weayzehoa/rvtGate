<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpACRTD extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.ACRTD';
    protected $primaryKey = 'TD002';
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
        'TD001',
        'TD002',
        'TD003',
        'TD004',
        'TD005',
        'TD006',
        'TD007',
        'TD008',
        'TD009',
        'TD010',
        'TD011',
        'TD012',
        'TD013',
        'TD014',
        'TD015',
        'TD016',
        'TD017',
        'TD018',
        'TD019',
        'TD020',
        'TD021',
        'TD022',
        'TD023',
    ];
}
