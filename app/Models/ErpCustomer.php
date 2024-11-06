<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryDigiwinPayment as iCarryCustomerDB;

class ErpCustomer extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.COPMA';
    protected $primaryKey = 'MA001';
    protected $keyType = 'string';
    public $timestamps = FALSE;

    public function orders(){
        return $this->hasMany(OrderDB::class,'digiwin_payment_id','MA001');
    }
}
