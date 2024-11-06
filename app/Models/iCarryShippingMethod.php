<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryShippingMethod extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'shipping_method';
    //不使用時間戳記
    public $timestamps = FALSE;
}
