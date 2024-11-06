<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpQuotation extends Model
{
    use HasFactory;
    //指定連線名稱
    protected $connection = 'iCarrySMERP';
    //指定資料表
    protected $table = 'dbo.COPMB';
    // if your key name is not 'id'
    // you can also set this to null if you don't have a primary key
    protected $primaryKey = 'MB002';
    // In Laravel 6.0+ make sure to also set $keyType
    public $incrementing = false;
    protected $keyType = 'string';
    //不使用時間戳記
    public $timestamps = FALSE;
}
