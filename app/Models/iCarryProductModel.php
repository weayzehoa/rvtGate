<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ACErpProduct as ACErpProductDB;
use App\Models\ErpProduct as ErpProductDB;
use App\Models\iCarryProduct as ProductDB;

class iCarryProductModel extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'product_model';
    //不使用時間戳記
    public $timestamps = FALSE;
    protected $fillable = [
        'quantity',
        'safe_quantity',
        'name',
        'is_del',
        'product_id',
        'gtin13',
        'sku',
        'digiwin_no',
        'origin_digiwin_no',
        'vendor_product_model_id'
    ];
    public function product(){
        return $this->belongsTo(ProductDB::class,'product_id','id');
    }
    public function erpProduct()
    {
        return $this->hasOne(ErpProductDB::class,'MB010','digiwin_no');
    }
    public function acErpProduct()
    {
        return $this->hasOne(ACErpProductDB::class,'MB010','digiwin_no');
    }
}
