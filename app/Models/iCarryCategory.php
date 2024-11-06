<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryCategory extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'category';
    public $timestamps = FALSE;
}
