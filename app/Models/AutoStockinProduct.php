<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoStockinProduct extends Model
{
    use HasFactory;
    public $timestamps = FALSE;

    protected $fillable = [
        'digiwin_no',
    ];
}
