<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ACErpCustomer extends Model
{
    use HasFactory;
    protected $connection = 'ACSMERP';
    protected $table = 'dbo.COPMA';
    protected $primaryKey = 'MA001';
    protected $keyType = 'string';
    public $timestamps = FALSE;
}
