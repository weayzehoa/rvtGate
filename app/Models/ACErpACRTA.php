<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryDigiwinPayment as iCarryCustomerDB;

class ACErpACRTA extends Model
{
    use HasFactory;
    protected $connection = 'ACSMERP';
    protected $table = 'dbo.ACRTA';
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
        'FLAG',
        'CREATE_TIME',
        'CREATE_AP',
        'CREATE_PRID',
        'TA001',
        'TA002',
        'TA003',
        'TA004',
        'TA006',
        'TA008',
        'TA009',
        'TA010',
        'TA011',
        'TA012',
        'TA013',
        'TA014',
        'TA015',
        'TA016',
        'TA017',
        'TA018',
        'TA019',
        'TA020',
        'TA022',
        'TA021',
        'TA023',
        'TA024',
        'TA025',
        'TA026',
        'TA027',
        'TA028',
        'TA029',
        'TA030',
        'TA031',
        'TA032',
        'TA034',
        'TA037',
        'TA038',
        'TA039',
        'TA040',
        'TA041',
        'TA042',
        'TA043',
        'TA044',
        'TA045',
        'TA046',
        'TA047',
        'TA048',
        'TA069',
        'TA074',
        'TA075',
        'TA076',
        'TA077',
        'TA078',
    ];
}
