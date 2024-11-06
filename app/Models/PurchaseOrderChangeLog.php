<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;

class PurchaseOrderChangeLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'purchase_no',
        'admin_id',
        'poi_id',
        'poip_id',
        'sku',
        'digiwin_no',
        'product_name',
        'memo',
        'quantity',
        'price',
        'date',
        'status',
    ];
    public function order()
    {
        return $this->beLongsTo(PurchaseOrderDB::class,'purchase_no','purchase_no');
    }
    public function item()
    {
        return $this->beLongsTo(PurchaseOrderItemDB::class,'poi_id','id');
    }
}
