<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\VendorShippingItemPackage as ShippingItemPackageDB;

class VendorShippingItemPackage extends Model
{
    use HasFactory;
    protected $fillable = [
        'vsi_id',
        'poi_id',
        'poip_id',
        'product_model_id',
        'product_name',
        'digiwin_no',
        'quantity',
        'is_del',
    ];


    public function stockins()
    {
        $stockinItemSingleTable = env('DB_DATABASE').'.'.(new StockinItemSingleDB)->getTable();
        $vendorShippingItemPackageTable = env('DB_DATABASE').'.'.(new ShippingItemPackageDB)->getTable();

        return $this->hasMany(StockinItemSingleDB::class,'poip_id','poip_id')->orderBy($stockinItemSingleTable.'.stockin_date','asc');
    }
}
