<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryShippingSet extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'shipping_set';
    //不使用時間戳記
    public $timestamps = FALSE;
}
