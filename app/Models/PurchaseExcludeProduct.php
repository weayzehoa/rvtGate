<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseExcludeProduct extends Model
{
    use HasFactory;
    public $timestamps = FALSE;
    protected $fillable = [
        'product_model_id',
    ];
}
