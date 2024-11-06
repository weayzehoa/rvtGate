<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NidinTicket extends Model
{
    use HasFactory;
    protected $fillable = [
        'import_no',
        'type',
        'merchant_no',
        'transaction_id',
        'product_num',
        'set_no',
        'description',
        'ticket_no',
        'writeoff_time',
        'is_chk',
        'memo',
    ];
}
