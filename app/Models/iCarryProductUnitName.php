<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryProductUnitName extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'product_unit_name';
    public $timestamps = FALSE;
}
