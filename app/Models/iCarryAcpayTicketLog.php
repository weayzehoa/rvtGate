<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryAcpayTicketLog extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'acpay_ticket_logs';
    protected $fillable = [
        'type',
        'user_id',
        'user_name',
        'admin_id',
        'admin_name',
        'post_json',
        'get_json',
        'rtnCode',
        'rtnMsg',
    ];
}
