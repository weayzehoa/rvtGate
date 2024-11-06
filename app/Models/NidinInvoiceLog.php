<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NidinInvoiceLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'nidin_order_no',
        'param',
        'is_success',
    ];
}
