<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryOrderPromotion extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'order_with_promotion';
    public $timestamps = FALSE;

}
