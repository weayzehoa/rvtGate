<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AcOrder as AcOrderDB;
use App\Models\NidinOrder as NidinOrderDB;
use App\Models\iCarryUser as UserDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\OrderShipping as OrderShippingDB;
use App\Models\iCarryOrderVendorShipping as OrderVendorShippingDB;
use App\Models\iCarryOrderAsiamiles as OrderAsiamilesDB;
use App\Models\iCarryShippingMethod as ShippingMethodDB;
use App\Models\iCarryDigiwinPayment as iCarryDigiwinPaymentDB;
use App\Models\iCarryShopcomOrder as ShopcomOrderDB;
use App\Models\iCarryTradevanOrder as TradevanOrderDB;
use App\Models\iCarryOrderLog as OrderLogDB;
use App\Models\iCarrySpgateway as SpgatewayDB;
use App\Models\ErpCustomer as ErpCustomerDB;
use App\Models\ErpOrder as ErpOrderDB;
use App\Models\ErpProduct as ErpProductDB;
use App\Models\SyncedOrder as SyncedOrderDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\SyncedOrderError as SyncedOrderErrorDB;
use App\Models\SellReturn as SellReturnDB;
use App\Models\Sell as SellDB;
use App\Models\iCarryPay2go as Pay2GoDB;
use App\Models\SellImport as SellImportDB;
use DB;

class iCarryOrder extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'orders';
    //變更 Laravel 預設 created_at 與 updated_at 欄位名稱
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';
    protected $fillable = [
        'vendor_arrival_date',
        'shipping_memo',
        'shipping_number',
        'order_number',
        'user_id',
        'origin_country',
        'ship_to',
        'book_shipping_date',
        'receiver_name',
        'receiver_tel',
        'receiver_phone_number',
        'receiver_email',
        'receiver_address',
        'receiver_keyword',
        'receiver_key_time',
        'receiver_zip_code',
        'shipping_method',
        'invoice_type',
        'invoice_sub_type',
        'invoice_number',
        'invoice_rand',
        'is_invoice',
        'is_invoice_no',
        'invoice_time',
        'love_code',
        'invoice_title',
        'carrier_type',
        'carrier_num',
        'spend_point',
        'amount',
        'shipping_fee',
        'parcel_tax',
        'pay_method',
        'exchange_rate',
        'discount',
        'user_memo',
        'partner_order_number',
        'pay_time',
        'buyer_name',
        'buyer_email',
        'print_flag',
        'create_type',
        'status',
        'digiwin_payment_id',
        'is_call',
        'create_time',
        'admin_memo',
        'greeting_card',
        'shipping_kg_price',
        'shipping_base_price',
        'merge_order',
        'merged_order',
    ];


    public function acOrder(){
        return $this->setConnection('mysql')->hasOne(AcOrderDB::class,'order_id','id');
    }

    public function nidinOrder(){
        return $this->setConnection('mysql')->hasOne(NidinOrderDB::class,'order_id','id');
    }

    public function asiamiles(){
        return $this->hasOne(OrderAsiamilesDB::class,'order_id','id');
    }

    public function shopcom(){
        return $this->hasOne(ShopcomOrderDB::class,'order_id','id');
    }

    public function tradevan(){
        return $this->hasOne(TradevanOrderDB::class,'order_id','id');
    }

    public function items(){
        // return $this->hasMany(OrderItemDB::class,'order_id','id')->with('erpQuotation')
        // 不知道為何, 只要是跨mssql的資料庫若使用with則會找不到資料. 只能在迴圈中使用 item->erpQuotation 方式將資料拉出.
        return $this->hasMany(OrderItemDB::class,'order_id','id')->with('syncedOrderItem')
        ->join('orders','orders.id','order_item.order_id')
        ->join('product_model','product_model.id','order_item.product_model_id')
            ->join('product','product.id','product_model.product_id')
            ->join('vendor','vendor.id','product.vendor_id')
            ->select([
                'order_item.*',
                'orders.status',
                'vendor.id as vendor_id',
                'vendor.name as vendor_name',
                'vendor.service_fee',
                DB::raw("(CASE WHEN vendor.digiwin_vendor_no like '%AC%' THEN order_item.product_name ELSE (CONCAT(vendor.name,' ',product.name,'-',product_model.name)) END) as product_name"),
                'product.eng_name as product_eng_name',
                'product.unit_name',
                'product.direct_shipment as directShip',
                'product_model.vendor_product_model_id',
                'product_model.origin_digiwin_no',
                'product_model.digiwin_no',
                'product_model.sku',
                'product_model.gtin13',
                'product.serving_size',
                'product.unit_name',
                'product.id as product_id',
                'product.ticket_group',
                'product.ticket_merchant_no',
                'product.ticket_price',
                'product.ticket_memo',
                'product.package_data',
                'product.category_id as category_id',
                'product.category_id as product_category_id',
                'product.trans_start_date',
                'product.trans_end_date',
                'product.vendor_price',
            ]);
    }

    public function shippingMethod(){
        return $this->belongsTo(ShippingMethodDB::class,'shipping_method','id');
    }

    public function shippings(){
        return $this->setConnection('mysql')->hasMany(OrderShippingDB::class,'order_id','id');
    }

    public function vendorShippings(){
        return $this->hasMany(OrderVendorShippingDB::class,'order_id','id');
    }

    public function user(){
        $secrtKey = env('APP_AESENCRYPT_KEY');
        return $this->belongsTo(UserDB::class)->select([
            '*',
            DB::raw("IF(mobile IS NULL,'',AES_DECRYPT(mobile,'$secrtKey')) as mobile"),
        ]);
    }

    public function customer(){
        return $this->hasOne(iCarryDigiwinPaymentDB::class,'customer_no','digiwin_payment_id');
    }

    public function erpCustomer(){//不能使用belongsTo, 也不能用with, 否則找不到資料, 只能直接使用 order->customer
        return $this->hasOne(ErpCustomerDB::class,'MA001','digiwin_payment_id');
    }

    public function erpOrder(){
        return $this->hasOne(ErpOrderDB::class,'TC012','order_number')->with('customer','items','forSyncItems');
    }

    public function syncedOrder(){
        return $this->setConnection('mysql')->hasOne(SyncedOrderDB::class,'order_id','id')->orderBy('created_at','desc');
    }

    public function sell(){
        return $this->setConnection('mysql')->hasOne(SellDB::class,'order_id','id')->where('is_del',0)->orderBy('created_at','desc');
    }

    public function sells(){
        return $this->setConnection('mysql')->hasMany(SellDB::class,'order_number','order_number')->orderBy('created_at','desc');
    }

    public function sellImports(){
        return $this->setConnection('mysql')->hasMany(SellImportDB::class,'order_number','order_number')->orderBy('created_at','desc');
    }

    public function sellImport(){
        return $this->setConnection('mysql')->hasOne(SellImportDB::class,'order_number','order_number');
    }

    public function syncDate(){
        return $this->setConnection('mysql')->hasOne(SyncedOrderDB::class,'order_id','id')->orderBy('created_at','desc');
    }

    public function syncedItems(){
        return $this->setConnection('mysql')->hasMany(SyncedOrderItemDB::class,'order_id','id')->with('package');
    }

    public function syncedErrors(){
        return $this->setConnection('mysql')->hasMany(SyncedOrderErrorDB::class,'order_id','id');
    }

    public function spgateway(){
        return $this->hasOne(SpgatewayDB::class,'order_number','order_number')->orderBy('create_time', 'desc');
    }

    public function modifyLogs(){
        return $this->setConnection('mysql')->hasMany(OrderLogDB::class,'order_id','id')->orderBy('create_time');
    }

    public function unPurchaseItems()
    {
        return $this->setConnection('mysql')->hasMany(SyncedOrderSingleItemDB::class,'order_id','id')->whereNull('purchase_date')->where('is_del',0);
    }

    public function allowances(){
        return $this->setConnection('mysql')->hasMany(SellReturnDB::class,'order_id','id')
        ->with('items')->where('type','折讓')->where('is_del',0)->orderBy('created_at','desc');
    }

    public function sellReturns(){
        return $this->setConnection('mysql')->hasMany(SellReturnDB::class,'order_id','id')
        ->with('items')->where('type','銷退')->where('is_del',0)->orderBy('created_at','desc');
    }

    public function returns(){
        return $this->setConnection('mysql')->hasMany(SellReturnDB::class,'order_id','id')
        ->with('chkStockin','items')->where('is_del',0)->orderBy('created_at','desc');
    }

    public function invoiceAllowance(){
        return $this->hasOne(Pay2GoDB::class,'order_number','order_number')
        ->where('type','allowance')->orderBy('create_time','desc');
    }

    public function invoiceAllowances(){
        return $this->hasMany(Pay2GoDB::class,'order_number','order_number')
        ->where('type','allowance')->orderBy('create_time','desc');
    }

    //給acOrder API用
    public function acItems(){
        $key = env('APP_AESENCRYPT_KEY');
        return $this->hasMany(OrderItemDB::class,'order_id','id')
            ->join('orders', 'orders.id', 'order_item.order_id')
            ->join('product_model','product_model.id','order_item.product_model_id')
            ->join('product','product.id','product_model.product_id')
            ->join('vendor','vendor.id','product.vendor_id')
            ->where('order_item.is_del',0) //排除掉取消的
            ->select([
                'order_item.order_id',
                'product_model.vendor_product_model_id as vendor_product_no',
                'order_item.product_name',
                'order_item.quantity',
                'order_item.price',
                DB::raw("(order_item.quantity * order_item.price) as amount"),
                'order_item.discount',
                'order_item.set_no',
                DB::raw("((order_item.quantity * order_item.price) - order_item.discount) as total"),
            ])->orderBy('orders.id', 'desc');
    }

    //給鼎新匯出用
    public function itemData(){
        $key = env('APP_AESENCRYPT_KEY');
        return $this->hasMany(OrderItemDB::class,'order_id','id')->with('syncedOrderItem','sells','package','package.sells','package.syncedOrderItemPackage','returns')
            ->join('orders', 'orders.id', 'order_item.order_id')
            ->join('product_model','product_model.id','order_item.product_model_id')
            ->join('product','product.id','product_model.product_id')
            ->join('vendor','vendor.id','product.vendor_id')
            ->where('order_item.is_del',0) //排除掉取消的
            ->select([
                DB::raw("DATE_FORMAT(orders.pay_time,'%Y-%m-%d') as pay_time"),
                DB::raw("DATE_FORMAT(orders.pay_time,'%Y%m%d') as payTime"),
                'orders.ship_to',
                DB::raw("(IF(orders.pay_method='蝦皮', IF(orders.user_memo LIKE '%蝦皮訂單：(台灣)%',(SELECT CONCAT(customer_no,'@_@',customer_name,'@_@',set_deposit_ratio) FROM digiwin_payment WHERE customer_name='台灣蝦皮' limit 1),(SELECT CONCAT(customer_no,'@_@',customer_name,'@_@',set_deposit_ratio) FROM digiwin_payment WHERE customer_name='新加坡蝦皮' limit 1)) ,(SELECT CONCAT(customer_no,'@_@',customer_name,'@_@',set_deposit_ratio) FROM digiwin_payment WHERE customer_name=orders.pay_method limit 1))) as pay"),
                'is_shopcom' => ShopcomOrderDB::whereColumn('orders.id', 'shopcom_orders.order_id')->select(DB::raw('COUNT(id) as count'))->limit(1),
                'orders.promotion_code',
                'orders.create_type',
                'orders.shipping_method',
                'orders.status',
                'orders.book_shipping_date',
                'orders.user_memo',
                'orders.receiver_key_time',
                'orders.receiver_keyword',
                'orders.receiver_address',
                'orders.order_number',
                'orders.partner_order_number',
                'orders.receiver_name',
                DB::raw("IF(orders.receiver_phone_number IS NULL,'',AES_DECRYPT(orders.receiver_phone_number,'$key')) as receiver_phone_number"),
                DB::raw("IF(orders.receiver_tel IS NULL,'',AES_DECRYPT(orders.receiver_tel,'$key')) as receiver_tel"),
                'orders.discount',
                'orders.spend_point',
                'orders.shipping_fee',
                'orders.parcel_tax',
                'orders.shipping_memo',
                'product_model.id as product_model_id',
                'product_model.sku',
                'product_model.gtin13',
                'product_model.digiwin_no',
                'product_model.origin_digiwin_no',
                'product_model.name as product_model_name',
                'product_model.vendor_product_model_id',
                DB::raw("(SELECT name FROM vendor WHERE id IN(SELECT vendor_id FROM product WHERE id IN(SELECT product_id from product_model where digiwin_no IN((SELECT origin_digiwin_no FROM product_model WHERE product_model.id=order_item.product_model_id))))) as origin_vendor_name"),
                DB::raw("(SELECT name FROM product WHERE id IN(SELECT product_id from product_model where digiwin_no IN((SELECT origin_digiwin_no from product_model WHERE product_model.id=order_item.product_model_id)))) as origin_product_name"),
                DB::raw("(CASE WHEN vendor.digiwin_vendor_no like '%AC%' THEN order_item.product_name ELSE (CONCAT(vendor.name,' ',product.name,'-',product_model.name)) END) as product_name"),
                'vendor.id as vendor_id',
                'vendor.name as vendor_name',
                'vendor.service_fee',
                'order_item.not_purchase',
                'order_item.direct_shipment',
                'order_item.quantity',
                'order_item.return_quantity',
                'order_item.set_no',
                'order_item.discount',
                'order_item.price',
                'order_item.purchase_price',
                'order_item.gross_weight',
                'order_item.order_id',
                'order_item.id',
                'product.id as product_id',
                'product.category_id as category_id',
                'product.category_id as product_category_id',
                'product.direct_shipment as directShip',
                'product.vendor_earliest_delivery_date',
                'product.vendor_latest_delivery_date',
                'product.serving_size',
                'product.unit_name',
                'product.eng_name as product_eng_name',
                'product.name',
                'product.ticket_group',
                'product.ticket_merchant_no',
                'product.ticket_price',
                'product.ticket_memo',
                'product.package_data',
                'product.trans_start_date',
                'product.trans_end_date',
                'product.vendor_price',
            ])->orderBy('orders.id', 'desc');
    }
}
