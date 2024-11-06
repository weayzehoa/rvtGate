<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerialNoRecord extends Model
{
    use HasFactory;
    //不使用時間戳記
    public $timestamps = FALSE;
    protected $fillable = [
        'type',
        'serial_no',
    ];
}
