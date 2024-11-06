<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ACErpVendor extends Model
{
    use HasFactory;
    protected $connection = 'ACSMERP';
    protected $table = 'dbo.PURMA';
    protected $primaryKey = 'MA001';
    protected $keyType = 'string';
    //不自動增加
    public $incrementing = false;
    public $timestamps = FALSE;
}
