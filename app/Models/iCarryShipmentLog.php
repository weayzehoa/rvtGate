<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryShipmentLog extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'shipment_log';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;
    protected $fillable = [
        'order_id',
        'order_number',
        'user_id',
        'send',
        'shipping_method',
        'create_time',
    ];
}
