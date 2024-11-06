<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryProductLog extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'product_logs';
    protected $fillable = [
        'get_json',
        'post_json',
        'type',
        'digiwin_no',
        'vendor_product_no',
        'vendor_id',
        'product_id',
        'name',
        'price',
        'message',
        'ip',
    ];
}
