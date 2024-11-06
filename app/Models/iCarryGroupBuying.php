<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryGroupBuying extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'group_buying';
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    protected $fillable = [
        'master_user_id',
        'name',
        'master_percent',
        'is_on',
        'buyer_discount_percent',
        'description',
        'cover',
        'logo',
        'product_sold_country',
        'start_date',
        'end_date',
        'shipping_date',
        'allow_products',
        'over_weight',
    ];

}
