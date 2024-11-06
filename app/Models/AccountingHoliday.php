<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingHoliday extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'exclude_date',
        'memo',
    ];
}
