<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SellReturnItem as SellReturnItemDB;

class SellReturn extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'order_id',
        'order_number',
        'return_no',
        'erp_return_type',
        'erp_return_no',
        'price',
        'tax',
        'memo',
        'return_date',
        'return_admin_id',
        'is_del',
    ];

    public function items(){
        return $this->hasMany(SellReturnItemDB::class,'return_no','return_no')->orderBy('erp_return_sno','asc');
    }

    public function chkStockin(){
        return $this->hasMany(SellReturnItemDB::class,'return_no','return_no')->where('is_stockin',1);
    }
}
