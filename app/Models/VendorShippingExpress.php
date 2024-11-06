<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorShippingExpress extends Model
{
    use HasFactory;
    protected $fillable = [
        'shipping_no',
        'vsi_id',
        'poi_id',
        'shipping_date',
        'express_way',
        'express_no',
    ];
}
