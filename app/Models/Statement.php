<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statement extends Model
{
    use HasFactory;

    protected $fillable = [
        'statement_no',
        'vendor_id',
        'amount',
        'stockin_price',
        'return_price',
        'discount_price',
        'start_date',
        'end_date',
        'purchase_nos',
        'purchase_item_ids',
        'return_discount_ids',
        'return_order_ids',
        'return_order_item_ids',
        'set_item_ids',
        'filename',
        'is_del',
        'notice_time',
        'invoice_date',
    ];
}
