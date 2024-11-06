<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryReceiverBaseSetting extends Model
{
    protected $connection = 'icarry';
    protected $table = 'receiver_base_settings';
    protected $fillable = [
        'select_date',
        'type',
        'is_ok',
        'memo',
        'admin_id',
    ];
}
