<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncedInvoiceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_number',
        'erp_order_no',
        'invoice_no',
        'invoice_time',
        'invoice_price',
        'invoice_tax',
        'create_type',
    ];

}
