<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_no',
        'type',
        'digiwin_payment_id',
        'partner_order_number',
        'create_time',
        'pay_time',
        'receiver_address',
        'receiver_name',
        'receiver_tel',
        'receiver_email',
        'user_memo',
        'receiver_keyword',
        'receiver_key_time',
        'shipping_method',
        'sku',
        'quantity',
        'price',
        'book_shipping_date',
        'status',
        'invoice_type',
        'invoice_sub_type',
        'love_code',
        'carrier_num',
        'carrier_type',
        'invoice_title',
        'invoice_number',
        'buyer_name'
    ];
}
