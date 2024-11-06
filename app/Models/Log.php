<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'db_name',
        'type',
        'sku',
        'digiwin_no',
        'old_data',
        'data',
    ];
}
