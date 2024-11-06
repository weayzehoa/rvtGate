<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryGroupBuying as GroupBuyingDB;
use App\Models\iCarryGroupBuyingOrder as OrderDB;
use App\Models\iCarryGroupBuyingOrderItem as OrderItemDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;

use DB;

class iCarryGroupBuyingOrder extends Model
{
    use HasFactory;

    protected $connection = 'icarry';
    protected $table = 'group_buying_orders';
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
        'shipping_time',
    ];

    public function items(){
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        return $this->hasMany(OrderItemDB::class,'order_id','id')
        ->join($orderTable,$orderTable.'.id',$orderItemTable.'.order_id')
        ->join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->select([
            $orderItemTable.'.*',
            $orderTable.'.status',
            $vendorTable.'.id as vendor_id',
            $vendorTable.'.name as vendor_name',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            $productTable.'.eng_name as product_eng_name',
            $productTable.'.unit_name',
            $productTable.'.direct_shipment as directShip',
            $productModelTable.'.origin_digiwin_no',
            $productModelTable.'.digiwin_no',
            $productModelTable.'.sku',
            $productModelTable.'.gtin13',
            $productTable.'.serving_size',
            $productTable.'.unit_name',
            $productTable.'.id as product_id',
            $productTable.'.package_data',
            $productTable.'.category_id as category_id',
            $productTable.'.category_id as product_category_id',
        ]);
    }

    public function groupBuying(){
        return $this->belongsTo(GroupBuyingDB::class,'group_buying_id','id');
    }

    //給匯出用
    public function itemData(){
        $key = env('APP_AESENCRYPT_KEY');
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        return $this->hasMany(OrderItemDB::class,'order_id','id')
            ->join($orderTable,$orderTable.'.id',$orderItemTable.'.order_id')
            ->join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->where($orderItemTable.'.is_del',0) //排除掉取消的
            ->select([
                DB::raw("DATE_FORMAT($orderTable.pay_time,'%Y-%m-%d') as pay_time"),
                DB::raw("DATE_FORMAT($orderTable.pay_time,'%Y%m%d') as payTime"),
                $orderTable.'.ship_to',
                $orderTable.'.promotion_code',
                $orderTable.'.create_type',
                $orderTable.'.shipping_method',
                $orderTable.'.status',
                $orderTable.'.book_shipping_date',
                $orderTable.'.user_memo',
                $orderTable.'.receiver_key_time',
                $orderTable.'.receiver_keyword',
                $orderTable.'.receiver_address',
                $orderTable.'.order_number',
                $orderTable.'.partner_order_number',
                $orderTable.'.receiver_name',
                $orderTable.'.discount',
                $orderTable.'.spend_point',
                $orderTable.'.shipping_fee',
                $orderTable.'.parcel_tax',
                $orderTable.'.shipping_memo',
                $productModelTable.'.id as product_model_id',
                $productModelTable.'.sku',
                $productModelTable.'.gtin13',
                $productModelTable.'.digiwin_no',
                $productModelTable.'.origin_digiwin_no',
                $productModelTable.'.name as product_model_name',
                $vendorTable.'.id as vendor_id',
                $vendorTable.'.name as vendor_name',
                $orderItemTable.'.direct_shipment',
                $orderItemTable.'.quantity',
                $orderItemTable.'.return_quantity',
                $orderItemTable.'.price',
                $orderItemTable.'.purchase_price',
                $orderItemTable.'.gross_weight',
                $orderItemTable.'.order_id',
                $orderItemTable.'.id',
                $productTable.'.id as product_id',
                $productTable.'.category_id as category_id',
                $productTable.'.category_id as product_category_id',
                $productTable.'.direct_shipment as directShip',
                $productTable.'.vendor_earliest_delivery_date',
                $productTable.'.serving_size',
                $productTable.'.unit_name',
                $productTable.'.eng_name as product_eng_name',
                $productTable.'.name',
                $productTable.'.ticket_group',
                $productTable.'.ticket_price',
                $productTable.'.ticket_memo',
                $productTable.'.package_data',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                DB::raw("IF($orderTable.receiver_phone_number IS NULL,'',AES_DECRYPT($orderTable.receiver_phone_number,'$key')) as receiver_phone_number"),
                DB::raw("IF($orderTable.receiver_tel IS NULL,'',AES_DECRYPT($orderTable.receiver_tel,'$key')) as receiver_tel"),
            ])->orderBy($orderTable.'.id', 'desc');
    }
}
