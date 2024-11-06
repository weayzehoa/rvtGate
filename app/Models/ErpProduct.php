<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryProductModel as iCarryProductModelDB;

class ErpProduct extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.INVMB';
    protected $primaryKey = 'MB010';
    protected $keyType = 'string';
    //不使用時間戳記
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
        'MODIFIER',
        'MODI_DATE',
        'MODI_TIME',
        'MODI_AP',
        'MODI_PRID',
        'MB001',
        'MB002',
        'MB003',
        'MB004',
        'MB005',
        'MB010',
        'MB011',
        'MB013',
        'MB015',
        'MB017',
        'MB019',
        'MB020',
        'MB022',
        'MB023',
        'MB024',
        'MB025',
        'MB026',
        'MB032',
        'MB046',
        'MB049',
        'MB034',
        'MB042',
        'MB043',
        'MB044',
        'MB150',
        'MB151',
    ];
    public function iCarryProductModel()
    {
        return $this->hasOne(iCarryProductModelDB::class,'digiwin_no','MB010');
    }
}
