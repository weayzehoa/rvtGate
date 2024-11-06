<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncedOrderError extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'order_number',
        'product_model_id',
        'sku',
        'digiwin_no',
        'error',
    ];
}
