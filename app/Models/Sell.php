<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\SellItemSingle as SellItemSingleDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ErpCOPTG as ErpCOPTGDB;
use App\Models\ErpCOPTH as ErpCOPTHDB;
use DB;

class Sell extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'sell_no',
        'erp_sell_no',
        'order_number',
        'erp_order_number',
        'quantity',
        'amount',
        'tax',
        'is_del',
        'sell_date',
        'tax_type',
        'memo',
        'purchase_no',
    ];

    public function items()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellItemSingleTable = env('DB_DATABASE').'.'.(new SellItemSingleDB)->getTable();

        return $this->hasMany(SellItemSingleDB::class,'sell_no','sell_no')
        ->join($productModelTable,$productModelTable.'.id',$sellItemSingleTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->where($sellItemSingleTable.'.is_del',0)
        ->select([
            $sellItemSingleTable.'.*',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            $productTable.'.unit_name',
            $productModelTable.'.sku',
            $productModelTable.'.digiwin_no',
            $vendorTable.'.name as vendor_name',
        ]);
    }

    public function shipItems()
    {
        return $this->hasMany(SellItemSingleDB::class,'sell_no','sell_no')
        ->where('is_del',0)
        ->whereNull('product_model_id');
    }

    public function order()
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellItemSingleTable = env('DB_DATABASE').'.'.(new SellItemSingleDB)->getTable();

        return $this->hasOne(OrderDB::class,'order_number','order_number')->with('items');
    }

    public function erpSell()
    {
        return $this->hasOne(ErpCOPTGDB::class,'TG002','erp_sell_no')->with('items');
    }

    public function erpSellItems()
    {
        return $this->hasMany(ErpCOPTHDB::class,'TH002','erp_sell_no');
    }

    public function stockin()
    {
        return $this->hasOne(StockinItemSingleDB::class,'sell_no','sell_no')->groupBy('sell_no');
    }
}
