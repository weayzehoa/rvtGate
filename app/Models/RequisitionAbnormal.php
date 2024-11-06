<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisitionAbnormal extends Model
{
    use HasFactory;

    protected $fillable = [
        'stockin_import_id',
        'import_no',
        'gtin13',
        'product_name',
        'quantity',
        'expiry_date',
        'stockin_date',
        'memo',
        'is_chk',
        'chk_date',
        'admin_id',
    ];
}
