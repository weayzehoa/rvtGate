<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPwdUpdateLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'ip',
        'password',
        'editor_id',
    ];
}
