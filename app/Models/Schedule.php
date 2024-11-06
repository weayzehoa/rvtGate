<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'code',
        'name',
        'is_on',
        'frequency',
        'last_update_time',
    ];
}
