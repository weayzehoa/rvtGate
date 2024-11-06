<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpPurchasePrice extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.PURMB';
    protected $primaryKey = 'MB001';
    protected $keyType = 'string';
    public $timestamps = FALSE;
}
