<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ErpQuotation as ErpQuotationDB;
use App\Models\Quotation as QuotationDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\SyncedOrderItemPackage as SyncedOrderItemPackageDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use DB;

class iCarryOrderItemPackage extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'order_item_package';
    //變更 Laravel 預設 created_at 與 不使用 updated_at 欄位
    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;
    protected $fillable = [
        'order_id',
        'order_item_id',
        'product_model_id',
        'sku',
        'digiwin_no',
        'gross_weight',
        'net_weight',
        'quantity',
        'purchase_price',
        'is_del',
        'admin_memo',
        'create_time',
        'product_name',
        'is_call',
        'direct_shipment',
        'digiwin_payment_id',
    ];
    // //關聯erp的報價單
    // public function erpQuotation()
    // {
    //     return $this->hasOne(ErpQuotationDB::class,'MB002','digiwin_no') //品號
    //         ->where('dbo.COPMB.MB001',$this->digiwin_payment_id) //客戶編號
    //         ->where('dbo.COPMB.MB017','<=',date('Ymd',strtotime($this->create_time))); //生效日
    // }

    //關聯erp的報價單, 由於透過防火牆抓鼎新資料太慢, 所以將資料同步到iCarry的DB伺服器, 抓取速度較快.
    public function erpQuotation()
    {
        return $this->setConnection('mysql')->hasOne(QuotationDB::class,'MB002','digiwin_no') //品號
            ->where('quotations.MB001',$this->digiwin_payment_id) //客戶編號
            ->where('quotations.MB017','<=',date('Ymd',strtotime($this->create_time))); //生效日
    }

    //關聯icarry的報價單
    public function icarryQuotation()
    {
        return $this->hasOne(ProductModelDB::class,'id','product_model_id')
        ->join('product','product.id','product_model.product_id')
        ->join('vendor','vendor.id','product.vendor_id')
        ->select([
            'product_model.*',
            // 'product.name as product_name',
            DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
            'product.price',
        ]);
    }

    public function syncedOrderItemPackage()
    {
        return $this->setConnection('mysql')->hasOne(SyncedOrderItemPackageDB::class,'order_item_package_id','id');
    }

    public function sells()
    {
        $sellItemSingleTable = env('DB_DATABASE').'.'.(new SellItemSingleDB)->getTable();;
        return $this->setConnection('mysql')->hasMany(SellItemSingleDB::class,'order_item_package_id','id')->where($sellItemSingleTable.'.is_del',0);
    }
}
