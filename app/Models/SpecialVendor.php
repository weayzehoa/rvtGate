<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialVendor extends Model
{
    use HasFactory;
    protected $fillable = [
        'vendor_id',
        'code',
        'company',
        'name',
    ];
}
