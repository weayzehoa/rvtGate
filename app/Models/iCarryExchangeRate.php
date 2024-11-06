<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryExchangeRate extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'exchange_rate';
    public $timestamps = FALSE;
}
