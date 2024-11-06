<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;
    protected $fillable = [
        'MB001',
        'MB002',
        'MB003',
        'MB004',
        'MB008',
        'MB017',
        'MB018',
    ];

}
