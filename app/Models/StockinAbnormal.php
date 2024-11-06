<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockinAbnormal extends Model
{
    use HasFactory;
    protected $fillable = [
        'stockin_import_id',
        'import_no',
        'gtin13',
        'product_name',
        'purchase_quantity',
        'need_quantity',
        'stockin_quantity',
        'quantity',
        'direct_shipment',
        'is_chk',
        'stockin_date',
        'memo',
        'chk_date',
        'admin_id',
    ];
}
