<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; //使用軟刪除

class iCarryCountry extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $connection = 'icarry';
    protected $table = 'countries';
    protected $fillable = [
        'name',
        'name_en',
        'name_jp',
        'name_kr',
        'name_th',
        'lang',
        'code',
        'sms_vendor',
        'sort',
    ];
}
