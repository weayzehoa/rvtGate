<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryShippingVendor extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'shipping_vendor';
    //不使用時間戳記
    public $timestamps = FALSE;
}
