<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; //使用軟刪除

class ShippingVendor extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'name',
        'name_en',
        'tel',
        'api_url',
        'is_foreign',
        'sort',
        'is_on',
    ];
}
