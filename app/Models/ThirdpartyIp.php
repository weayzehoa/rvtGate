<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThirdpartyIp extends Model
{
    use HasFactory;
    protected $fillable = [
        'ip',
        'memo',
        'disable',
        'is_on',
    ];
}
