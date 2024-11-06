<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpINVMA extends Model
{
    use HasFactory;
    protected $connection = 'iCarrySMERP';
    protected $table = 'dbo.INVMA';
    protected $primaryKey = 'MB001';
    protected $keyType = 'string';
    //不使用時間戳記
    public $timestamps = FALSE;
}
