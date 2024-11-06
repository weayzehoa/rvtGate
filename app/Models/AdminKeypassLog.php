<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminKeypassLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'is_pass',
        'memo',
        'admin_id',
        'admin_name',
    ];
}
