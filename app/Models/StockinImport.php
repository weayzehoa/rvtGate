<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryVendor as VendorDB;

class StockinImport extends Model
{
    use HasFactory;
    protected $fillable = [
        'import_no',
        'warehouse_export_time',
        'warehouse_stockin_no',
        'vendor_id',
        'gtin13',
        'product_name',
        'expected_quantity',
        'stockin_quantity',
        'stockin_time',
        'purchase_nos',
        'row_no',
        'sell_no',
        'expiry_date',
        'type',
        'direct_shipment',
    ];
    public function vendor(){
        return $this->setConnection('icarry')->belongsTo(VendorDB::class,'vendor_id','id');
    }
}
