<?php

namespace App\Traits;

use App\Models\iCarryGroupBuyingOrder as OrderDB;
use App\Models\iCarryGroupBuyingOrderItem as OrderItemDB;
use App\Models\iCarryGroupBuyingOrderItemPackage as OrderItemPackageDB;
use App\Models\iCarryUser as UserDB;
use App\Models\iCarryUserAddress as UserAddressDB;
use App\Models\iCarryTradevanOrder as TradevanOrderDB;
use App\Models\iCarryShopcomOrder as ShopcomOrderDB;
use App\Models\iCarryOrderAsiamiles as OrderAsiamilesDB;
use App\Models\SyncedOrder as SyncedOrderDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use App\Models\SystemSetting as SystemSettingDB;
use App\Models\Sell as SellDB;
use DB;

trait GroupBuyingOrderFunctionTrait
{
    protected function getGroupBuyingOrderData($request, $type = null, $name = null)
    {
        $key = env('APP_AESENCRYPT_KEY');
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $orderItemPackageTable = env('DB_ICARRY').'.'.(new OrderItemPackageDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $userAddresstable = env('DB_ICARRY').'.'.(new UserAddressDB)->getTable();
        $userTable = env('DB_ICARRY').'.'.(new UserDB)->getTable();

        //找出訂單資料, 必須使用with將關聯資料一次拉出, 否則速度會很慢.
        $orders = OrderDB::with('items','itemData','items.package','itemData.packageData');

        if(isset($request['id'])){ //指定選擇的訂單
            $type != 'show' && is_array($request['id']) ? $orders = $orders->whereIn($orderTable.'.id',$request['id']) : '';
        }elseif(isset($request['con'])){ //by條件
            //將進來的資料作參數轉換
            foreach ($request['con'] as $requestKeyName => $value) {
                $$requestKeyName = $value;
            }
        }else{
            //將進來的資料作參數轉換
            foreach ($request->all() as $requestKeyName => $value) {
                $$requestKeyName = $value;
            }
        }

        $type == 'openInvoice' ? $orders = $orders->whereNull('is_invoice_no')->whereNull('invoice_time')->where('is_invoice',0) : '';
        //查詢參數
        isset($status) ? $orders = $orders->whereIn($orderTable.'.status',explode(',',$status)) : '';
        isset($group_buying_id) && $group_buying_id ? $orders = $orders->where($orderTable.'.group_buying_id', 'like', "%$group_buying_id%") : '';
        isset($order_number) && $order_number ? $orders = $orders->where($orderTable.'.order_number', 'like', "%$order_number%") : '';
        isset($pay_time) && $pay_time ? $orders = $orders->where($orderTable.'.pay_time', '>=', $pay_time) : '';
        isset($pay_time_end) && $pay_time_end ? $orders = $orders->where($orderTable.'.pay_time', '<=', $pay_time_end) : '';
        isset($shipping_time) && $shipping_time ? $orders = $orders->where($orderTable.'.shipping_time', '>=', $shipping_time) : '';
        isset($shipping_time_end) && $shipping_time_end ? $orders = $orders->where($orderTable.'.shipping_time', '<=', $shipping_time_end) : '';
        isset($receiver_name) && $receiver_name ? $orders = $orders->where($orderTable.'.receiver_name','like', "%$receiver_name%") : '';
        isset($receiver_address) && $receiver_address ? $orders = $orders->where($orderTable.'.receiver_address','like', "%$receiver_address%") : '';
        isset($partner_order_number) && $partner_order_number ? $orders = $orders->where($orderTable.'.partner_order_number', 'like', "%$partner_order_number%") : '';
        isset($receiver_tel) && $receiver_tel ? $orders = $orders->whereRaw(" AES_DECRYPT($orderTable.receiver_tel, '$key') like '%$receiver_tel%' ") : '';

        if(!empty($invoice_number)){
            if(strtoupper($invoice_number) == 'X'){
                $orders = $orders->where(function ($query) use ($orderTable) {
                    $query->whereNull($orderTable.'.invoice_number')
                    ->orWhere($orderTable.'.invoice_number', '');
                });
            }else{
                $orders = $orders->where($orderTable.'.invoice_number','!=','');
                // $orders = $orders->where(function ($query) use ($orderTable) {
                //     $query->whereRaw(" $orderTable.invoice_number != '' or $orderTable.invoice_number != NULL ")->orWhereNotNull($orderTable.'.invoice_number');
                // });
            }
        }

        if(!empty($invoice_address)){
            if(strtoupper($invoice_address) == 'X'){
                $orders = $orders->where(function ($query) use ($orderTable) {
                    $query->whereRaw(" $orderTable.invoice_address != '' or $orderTable.invoice_address != NULL ")->orWhereNotNull($orderTable.'.invoice_address');
                });
            }else{
                $orders = $orders->where(function ($query) use ($orderTable) {
                    $query->whereNull($orderTable.'.invoice_address')
                    ->orWhere($orderTable.'.invoice_address', '');
                });
            }
        }

        if(!empty($invoice_no_empty)){
            if(strtoupper($invoice_no_empty) == 'X'){
                $orders = $orders->where(function ($query) use ($orderTable) {
                    $query->whereRaw(" $orderTable.is_invoice_no != '' or $orderTable.is_invoice_no != NULL ")->orWhereNotNull($orderTable.'.is_invoice_no');
                });
            }else{
                $orders = $orders->where(function ($query) use ($orderTable) {
                    $query->whereNull($orderTable.'.is_invoice_no')
                    ->orWhere($orderTable.'.is_invoice_no', '');
                });
            }
        }

        if((isset($vendor_name) && $vendor_name) || (isset($product_name) && $product_name) || (isset($sku) && $sku) || (isset($shipping_vendor_name) && $shipping_vendor_name)){
            $orders = $orders->rightJoin($orderItemTable,$orderItemTable.'.order_id',$orderTable.'.id')
                ->join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id');

            if(isset($vendor_name) && $vendor_name){
                $orders = $orders->whereIn($orderItemTable.'.vendor_id',VendorDB::where('name','like',"%$vendor_name%")->select('id'));
            }

            if((isset($product_name) && $product_name) || (isset($sku) && $sku)){
                $orderItemIds = OrderItemPackageDB::join($productModelTable,$productModelTable.'.id',$orderItemPackageTable.'.product_model_id')
                    ->join($productTable,$productTable.'.id',$productModelTable.'.product_id');
                if(isset($product_name) && $product_name){
                    $orderItemIds = $orderItemIds->where($productTable.'.name','like',"%$product_name%");
                }
                if(isset($sku) && $sku){
                    $orderItemIds = $orderItemIds->where(function($query)use($productModelTable,$sku){
                        $query->where($productModelTable.'.sku',$sku)
                        ->orWhere($productModelTable.'.digiwin_no',$sku);
                    });
                }
                $orderItemIds = $orderItemIds->get()->pluck('order_item_id')->all();

                $orders = $orders->where(function($query)use($productTable,$orderItemTable,$productModelTable,$request,$orderItemIds){
                    if(isset($request['con'])){ //by條件
                        foreach ($request['con'] as $requestKeyName => $value) {
                            $$requestKeyName = $value;
                        }
                    }else{
                        foreach ($request->all() as $requestKeyName => $value) {
                            $$requestKeyName = $value;
                        }
                    }
                    if(isset($product_name) && $product_name){
                        $query = $query->where($productTable.'.name','like',"%$product_name%");
                    }
                    if(isset($sku) && $sku){
                        $query = $query->where(function($q)use($productModelTable,$sku){
                            $q->where($productModelTable.'.sku',$sku)
                            ->orWhere($productModelTable.'.digiwin_no',$sku);
                        });
                    }
                    if(count($orderItemIds) > 0){
                        $query = $query->orWhereIn($orderItemTable.'.id',$orderItemIds);
                    }
                });
            }

            $orders = $orders->groupBy($orderTable.'.id');
        }

        // !empty($book_shipping_date) ? $orders = $orders->where($orderTable.'.book_shipping_date', '>=', $book_shipping_date) : '';
        // !empty($book_shipping_date_end) ? $orders = $orders->where($orderTable.'.book_shipping_date', '<=', $book_shipping_date_end) : '';

        if (!isset($list)) {
            $list = 50;
        }
        isset($request['method']) && $request['method'] == 'byQuery' ? $limit = 10000 : $limit = 0;

        //找出最終資料
        $orders = $orders->select([
                $orderTable . '.id',
                $orderTable . '.is_print',
                $orderTable . '.vendor_arrival_date',
                $orderTable . '.shipping_memo',
                $orderTable . '.shipping_number',
                $orderTable . '.order_number',
                $orderTable . '.group_buying_id',
                $orderTable . '.origin_country',
                $orderTable . '.ship_to',
                $orderTable . '.book_shipping_date',
                $orderTable . '.receiver_name',
                $orderTable . '.receiver_email',
                $orderTable . '.receiver_address',
                $orderTable . '.receiver_city',
                $orderTable . '.receiver_area',
                $orderTable . '.receiver_province',
                $orderTable . '.receiver_zip_code',
                $orderTable . '.receiver_keyword',
                $orderTable . '.receiver_key_time',
                $orderTable . '.shipping_method',
                $orderTable . '.invoice_type',
                $orderTable . '.invoice_sub_type',
                $orderTable . '.invoice_number',
                $orderTable . '.invoice_time',
                $orderTable . '.is_invoice_no',
                $orderTable . '.love_code',
                $orderTable . '.invoice_title',
                $orderTable . '.carrier_type',
                $orderTable . '.carrier_num',
                $orderTable . '.spend_point',
                $orderTable . '.amount',
                $orderTable . '.shipping_fee',
                $orderTable . '.parcel_tax',
                $orderTable . '.pay_method',
                $orderTable . '.exchange_rate',
                $orderTable . '.discount',
                $orderTable . '.user_memo',
                $orderTable . '.partner_order_number',
                $orderTable . '.pay_time',
                $orderTable . '.buyer_name',
                $orderTable . '.buyer_email',
                $orderTable . '.print_flag',
                $orderTable . '.create_type',
                $orderTable . '.status',
                $orderTable . '.digiwin_payment_id',
                $orderTable . '.is_call',
                $orderTable . '.create_time',
                $orderTable . '.admin_memo',
                $orderTable . '.greeting_card',
                $orderTable . '.shipping_kg_price',
                $orderTable . '.shipping_base_price',
                $orderTable . '.shipping_time',
                DB::raw("DATE_FORMAT($orderTable.create_time,'%Y/%m/%d') as createTime"),
        ]);
        $getOrderTelType = ['getInfo','export'];
        if(in_array($type, $getOrderTelType)) {
            $orders = $orders->addSelect([
                'china_id_img1',
                'china_id_img2',
                DB::raw("IF($orderTable.receiver_phone_number IS NULL,'',AES_DECRYPT($orderTable.receiver_phone_number,'$key')) as receiver_phone_number"),
                DB::raw("IF($orderTable.receiver_tel IS NULL,'',AES_DECRYPT($orderTable.receiver_tel,'$key')) as receiver_tel"),
            ]);
        }

        if ($type == 'index') {
            if($list == 'all'){
                $orders = $orders->orderBy($orderTable.'.id', 'desc')->get();
            }else{
                $orders = $orders->orderBy($orderTable.'.id', 'desc')->paginate($list);
            }
        }elseif($type == 'show' || $type == 'getInfo'){
            $orders = $orders->orderBy($orderTable.'.id', 'desc')->findOrFail($request['id']);
        }elseif($type == 'modify'){
            if(!empty($request->column_name)){
                $columnName = $request->column_name;
                if ($columnName == 'cancel') {
                    $orders = $orders->where('status',1);
                    $orders->update(['status' => -1, 'admin_memo' => $request->column_data]);
                }elseif($columnName == 'order_item_modify' || $columnName == 'item_is_call_clear'){
                    $itemIds = $request->item_ids;
                    $columnData = $request->column_data;
                    for($i=0;$i<count($itemIds);$i++){
                        $items[$i] = OrderItemDB::whereIn('id',$itemIds[$i])->update(['is_call'  => $columnData]);
                    }
                }elseif($columnName == 'shipping_memo_vendor'){
                    $orders->update(['shipping_memo' => $request->column_data]);
                }else{
                    $checkColumnName = ['is_call','is_print','receiver_key_time','shipping_time','book_shipping_date'];
                    if(in_array($columnName,$checkColumnName)){
                        $orders = $orders->whereIn('status',[1,2]);
                    }
                    $orders->update([$request->column_name => $request->column_data]);
                }
            }
            $orders = $this->getGroupBuyingOrderData($request,'show');
        }else{
            $orders = $orders->orderBy($orderTable.'.id', 'desc');
            $limit == 10000 ? $orders = $orders->limit(10000) : '';
            $orders = $orders->get();
        }
        return $orders;
    }
}
