<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ErpPURTC as ErpPURTCDB;
use App\Models\ErpPURTD as ErpPURTDDB;

class PurchaseOrderItemSingle extends Model
{
    use HasFactory;
    protected $fillable = [
        'type',
        'purchase_no',
        'erp_purchase_no',
        'erp_purchase_sno',
        'poi_id',
        'poip_id',
        'product_model_id',
        'gtin13',
        'erp_stockin_no',
        'stockin_quantity',
        'stockin_date',
        'return_quantity',
        'purchase_price',
        'quantity',
        'vendor_arrival_date',
        'direct_shipment',
        'is_del',
        'is_close',
    ];

    public function erpPurchase()
    {
        return $this->setConnection('iCarrySMERP')->hasOne(ErpPURTCDB::class,'TC002','erp_purchase_no');
    }

    public function erpPurchaseItem()
    {
        return $this->setConnection('iCarrySMERP')->hasOne(ErpPURTDDB::class,'TD002','erp_purchase_no')->where('TD001',$this->type)->where('TD003',$this->erp_purchase_sno);
    }
}
