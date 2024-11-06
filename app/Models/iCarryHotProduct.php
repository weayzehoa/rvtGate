<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryHotProduct extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'hot_product';
    public $timestamps = FALSE;
    protected $fillable = [
        'product_model_id',
        'product_id',
        'hits',
        'vendor_id',
        'category_id',
    ];
}
