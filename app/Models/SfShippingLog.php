<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SfShippingLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'headers',
        'post_json',
        'get_json',
        'rtnCode',
        'rtnMsg',
    ];
}
