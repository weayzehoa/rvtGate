<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryTicket as TicketDB;
use App\Models\iCarryOrderItemPackage as OrderItemPackageDB;
use App\Models\ErpQuotation as ErpQuotationDB;
use App\Models\Quotation as QuotationDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ErpProduct as ErpProductDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use App\Models\SellReturn as SellReturnDB;
use App\Models\SellReturnItem as SellReturnItemDB;
use DB;

class iCarryOrderItem extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'order_item';
    //變更 Laravel 預設 created_at 與 不使用 updated_at 欄位
    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;
    protected $fillable = [
        'order_id',
        'vendor_id',
        'product_id',
        'product_model_id',
        'digiwin_no',
        'digiwin_payment_id',
        'price',
        'purchase_price',
        'gross_weight',
        'net_weight',
        'is_tax_free',
        'parcel_tax_code',
        'parcel_tax',
        'vendor_service_fee_percent',
        'shipping_verdor_percent',
        'product_service_fee_percent',
        'quantity',
        'return_quantity',
        'is_del',
        'admin_memo',
        'create_time',
        'promotion_ids',
        'product_name',
        'is_call',
        'direct_shipment',
        'shipping_memo',
        'not_purchase',
        'discount',
        'set_no',
        'ticket_no',
        'writeoff_date',
        'purchase_no',
        'return_date',
        'return_amount',
        'return_service_fee',
        'ticket_start_date',
        'ticket_end_date',
        'is_statement',
    ];

    public function order()
    {
        return $this->belongsTo(OrderDB::class,'order_id','id')->with('itemData');
    }

    public function package()
    {
        //關聯組合商品-單品
        return $this->hasMany(OrderItemPackageDB::class,'order_item_id','id')->with('syncedOrderItemPackage','icarryQuotation')
        ->join('product_model','product_model.id','order_item_package.product_model_id')
        ->join('product','product.id','product_model.product_id')
        ->join('vendor','vendor.id','product.vendor_id')
        ->select([
            'order_item_package.*',
            'product_model.origin_digiwin_no',
            'product_model.digiwin_no',
            'product_model.sku',
            'product_model.gtin13',
            'product.unit_name',
            'product.id as product_id',
            'product.serving_size',
            'product.price',
            'product.price as product_price',
            'product.direct_shipment as directShip',
            'product.price as origin_price',
            'product.trans_start_date',
            'product.trans_end_date',
            'vendor.id as vendor_id',
            'vendor.name as vendor_name',
            DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
        ]);
    }
    public function purchaseOrder()
    {
        return $this->setConnection('mysql')->hasOne(PurchaseOrderDB::class,'id','order_item_id');
    }
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
            ->where('quotations.MB017','<=',date('Ymd',strtotime($this->create_time)))->orderBy('quotations.MB017','desc'); //生效日
    }
    //關聯icarry的報價單
    public function icarryQuotation()
    {
        return $this->hasOne(ProductModelDB::class,'id','product_model_id')
        ->join('product','product.id','product_model.product_id')
        ->select([
            'product_model.*',
            'product.name as product_name',
            'product.price',
        ]);
    }
    public function model(){
        return $this->belongsTo(ProductModelDB::class,'product_model_id','id');
    }

    public function erpProduct()
    {
        return $this->hasOne(ErpProductDB::class,'MB010','digiwin_no');
    }

    public function syncedOrderItem()
    {
        return $this->setConnection('mysql')->hasOne(SyncedOrderItemDB::class,'order_item_id','id')->with('purchaseOrder');
    }

    public function tickets()
    {
        $key = env('TICKET_ENCRYPT_KEY');
        return $this->setConnection('mysql')->hasMany(TicketDB::class,'order_item_id','id')->select([
            'id',
            'status',
            DB::raw("IF(ticket_no IS NULL,'',CONCAT('********',SUBSTRING(AES_DECRYPT(ticket_no,'$key'),10))) as ticket_no_mask"),
        ]);
    }

    public function returns()
    {
        $sellReturnTable = env('DB_DATABASE').'.'.(new SellReturnDB)->getTable();
        $sellReturnItemTable = env('DB_DATABASE').'.'.(new SellReturnItemDB)->getTable();

        return $this->setConnection('mysql')->hasMany(SellReturnItemDB::class,'order_item_id','id')
            ->join($sellReturnTable,$sellReturnTable.'.return_no',$sellReturnItemTable.'.return_no')
            ->where([[$sellReturnTable.'.type','銷退'],[$sellReturnTable.'.is_del',0],[$sellReturnItemTable.'.is_del',0]])
            ->where(function($query)use($sellReturnItemTable){ //排除運費及跨境稅
                $query->where($sellReturnItemTable.'.origin_digiwin_no','!=','901001')
                ->where($sellReturnItemTable.'.origin_digiwin_no','!=','901002');
            })->groupBy($sellReturnItemTable.'.order_item_id',$sellReturnItemTable.'.return_no');
    }
    public function sells()
    {
        $sellItemSingleTable = env('DB_DATABASE').'.'.(new SellItemSingleDB)->getTable();;
        return $this->setConnection('mysql')->hasMany(SellItemSingleDB::class,'order_item_id','id')
        ->where(function($query)use($sellItemSingleTable){
            $query->whereNull($sellItemSingleTable.'.order_item_package_id')
            ->orWhere($sellItemSingleTable.'.order_item_package_id','');
        })->where($sellItemSingleTable.'.is_del',0);
    }
}
