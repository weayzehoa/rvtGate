<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\VendorShipping as VendorShippingDB;
use App\Models\iCarryVendor as VendorDB;

class SfShipping extends Model
{
    use HasFactory;
    protected $fillable = [
        'vendor_shipping_no',
        'sf_express_no',
        'vendor_id',
        'sno',
        'vendor_arrival_date',
        'shipping_date',
        'stockin_date',
        'status',
        'label_url',
        'invoice_url',
        'phone',
        'trace_address',
    ];

    public function vendorShipping(){
        return $this->belongsTo(VendorShippingDB::class,'vendor_shipping_no','shipping_no');
    }

    public function vendor(){
        return $this->belongsTo(VendorDB::class,'vendor_id','id');
    }
}
