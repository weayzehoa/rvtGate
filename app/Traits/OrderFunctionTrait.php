<?php

namespace App\Traits;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryOrderItemPackage as OrderItemPackageDB;
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
use App\Models\AcOrder as AcOrderDB;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use DB;

trait OrderFunctionTrait
{
    protected function getOrderData($request, $type = null, $name = null)
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
        $acOrderDigiwinPaymentId = DigiwinPaymentDB::where('customer_no','like',"AC0001%")->select('customer_no');
        $nidinOrderDigiwinPaymentId = DigiwinPaymentDB::where('customer_no','like',"AC0002%")->select('customer_no');
        $allowDigiwinPaymentIds = DigiwinPaymentDB::where('is_invoice',1)->get()->pluck('customer_no')->all();
        if($request == 'ticketOrderOpenInvoice') {
            $orders = OrderDB::with('items')
            ->where($orderTable.'.id',$type)
            ->whereIn($orderTable.'.digiwin_payment_id',$allowDigiwinPaymentIds)
            ->where($orderTable.'.create_time','>=','2022-12-31 23:59:59')
            ->where(function($query)use($orderTable){
                $query = $query->whereNull($orderTable.'.is_invoice_no')
                ->orWhere($orderTable.'.is_invoice_no','')
                ->orWhere($orderTable.'.is_invoice',0);
            })->where(function($query1)use($orderTable){
                $query1 = $query1->whereNull($orderTable.'.invoice_time')
                ->orWhere($orderTable.'.invoice_time','')
                ->orWhere($orderTable.'.invoice_time',0);
            });
        }elseif($request == 'OrderOpenInvoice') {
            $orders = OrderDB::with('items')
            ->whereIn($orderTable.'.status',[3,4])
            ->whereIn($orderTable.'.digiwin_payment_id',$allowDigiwinPaymentIds)
            ->where($orderTable.'.create_time','>=','2022-12-31 23:59:59')
            ->where(function($query)use($orderTable){
                $query = $query->whereNull($orderTable.'.is_invoice_no')
                ->orWhere($orderTable.'.is_invoice_no','')
                ->orWhere($orderTable.'.is_invoice',0);
            })->where(function($query1)use($orderTable){
                $query1 = $query1->whereNull($orderTable.'.invoice_time')
                ->orWhere($orderTable.'.invoice_time','')
                ->orWhere($orderTable.'.invoice_time',0);
            });
        }elseif($request == 'checkAcOrderInvoice') {
            $allowDigiwinPaymentIds = ['AC0001','AC000101','AC000102','AC000103'];
            $orders = OrderDB::with('acOrder','nidinOrder','items')
            ->whereIn($orderTable.'.status',[3,4])
            ->whereIn($orderTable.'.digiwin_payment_id',$allowDigiwinPaymentIds)
            ->where($orderTable.'.create_time','>=','2022-12-31 23:59:59')
            ->where(function($query)use($orderTable){
                $query = $query->whereNull($orderTable.'.is_invoice_no')
                ->orWhere($orderTable.'.is_invoice_no','')
                ->orWhere($orderTable.'.is_invoice',0);
            })->where(function($query1)use($orderTable){
                $query1 = $query1->whereNull($orderTable.'.invoice_time')
                ->orWhere($orderTable.'.invoice_time','')
                ->orWhere($orderTable.'.invoice_time',0);
            });
        }elseif(!empty($request['getExpress'])){
            if($request['getExpress'] != 'all'){
                $param = explode('&',urldecode($request['getExpress']));
                for($i=0;$i<count($param);$i++){
                    $tmp[explode('=',$param[$i])[0]] = explode('=',$param[$i])[1];
                }
                foreach ($tmp as $keyName => $value) {
                    $$keyName = $value;
                }
            }
            if($type == 'nonShipping' || $type == 'multiShipping'){
                $orders = (new OrderDB);
            }else{
                $orders = (new OrderDB);
                if(!empty($name)){
                    $orders = $orders->where('order_shippings.express_way',$name);
                }
            }
        }else{
            //找出訂單資料, 必須使用with將關聯資料一次拉出, 否則速度會很慢.
            //只要是跨mssql的資料庫若使用with則會找不到資料. 只能在迴圈中使用 item->erpQuotation 方式將資料拉出.
            if ($type == 'DigiwinExport' || $type == 'pickupShipping') {
                $orders = OrderDB::with('itemData');
            }elseif($type == 'acOrderOpenInvoice'){
                $orders = OrderDB::with('acOrder','nidinOrder','items');
            }elseif($type == 'nidinOrderOpenInvoice'){
                $orders = OrderDB::with('nidinOrder','items');
            }elseif($type == 'getOrderNumbers'){
                $orders = new OrderDB;
            }elseif($type == 'show' && $name == 'update'){
                $orders = OrderDB::with('acOrder','nidinOrder','asiamiles','sell','shippings','itemData','customer','shopcom','tradevan');
            }elseif($type == 'CheckOrder'){
                $orders = OrderDB::with('acOrder','nidinOrder','shippings','syncedOrder','items','items.sells','items.package','items.package.sells','items.syncedOrderItem','items.package.syncedOrderItemPackage');
            }elseif($type == 'getUnPurchaseOrders'){
                //找未採買的訂單
                $unPurchaeOrderIds = SyncedOrderItemDB::whereNull('purchase_date')->where('is_del',0)->select('order_id')->groupBy('order_id');
                $orders = OrderDB::whereIn($orderTable.'.id',$unPurchaeOrderIds);
            }elseif($type == 'airport'){
                $orders = OrderDB::with('items.sells','syncedErrors','syncedOrder','syncedItems','syncDate','shippings','vendorShippings','customer','items','items.model','items.package','items.package.sells','items.package.icarryQuotation')
                ->whereNotNull('receiver_key_time');
            }else{
                $orders = OrderDB::with('acOrder','nidinOrder','asiamiles','returns','sellReturns','allowances','invoiceAllowance','invoiceAllowances','modifyLogs','syncedErrors','syncedOrder','syncedItems','syncDate','shippings','vendorShippings','customer','items','items.returns','items.sells','items.model','items.package','items.package.sells','items.package.icarryQuotation');
                $name == 'acOrder' ? $orders = $orders->whereIn($orderTable.'.digiwin_payment_id',$acOrderDigiwinPaymentId) : '';
                $name == 'nidinOrder' ? $orders = $orders->whereIn($orderTable.'.digiwin_payment_id',$nidinOrderDigiwinPaymentId) : '';
            }

            if(isset($request['type']) && $request['type'] == 'PurchaseCall'){ //訂單狀態大於0的才能建立採購單(有付錢的)
                $orders = $orders->where($orderTable.'.status','>',0);
            }

            if(isset($request['id'])){ //指定選擇的訂單
                $type != 'show' && is_array($request['id']) ? $orders = $orders->whereIn($orderTable.'.id',$request['id']) : $orders = $orders->where($orderTable.'.id',$request['id']);
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
        }

        //錢街多筆查詢
        if(isset($multiSearch)){
            $serialNoStr = str_replace(',,',',',str_replace(["\r\n"],[','],$multiSearch));
            $serialNos = explode(',',$serialNoStr);
            $serialNos = array_unique($serialNos);
            sort($serialNos);
            $orders = $orders->whereIn($orderTable.'.id',AcOrderDB::whereIn('serial_no',$serialNos)->select('order_id'));
        }

        if(isset($type) && in_array($type,['show','Synchronize','modify','getInfo','Refund','allowance'])){
            //單一資料, 不預設狀態
        }elseif(isset($type) && ($type == 'pickupShipping')){
            $orders = $orders->where($orderTable.'.status','<=',2);
        }else{
            //狀態預設為1,2,3,4
            isset($status) ? $orders = $orders->whereIn($orderTable.'.status',explode(',',$status)) : ($name == 'acOrder' || $name == 'nidinOrder' ? '' : $orders = $orders->whereIn($orderTable.'.status',[1,2,3,4])->whereNotIn($orderTable.'.digiwin_payment_id',['AC0001','AC000101','AC000102','AC000103','AC0002','AC000201','AC000202']));
        }

        if(!empty($is_sync)){
            if($is_sync == '已同步'){
                $orders = $orders->whereIn($orderTable.'.id',SyncedOrderDB::select('order_id')->groupBy('order_id')->orderBy('order_id','asc'));
            }elseif($is_sync == '未同步'){
                $orders = $orders->whereNotIn($orderTable.'.id',SyncedOrderDB::select('order_id')->groupBy('order_id')->orderBy('order_id','asc'));
            }
        }

        if(!empty($is_purchase)){
            if($is_purchase == '有採購'){
                $orders = $orders->whereIn($orderTable.'.id',SyncedOrderItemDB::whereNotNull('purchase_date')->select('order_id')->groupBy('order_id')->orderBy('order_id','asc'));
            }elseif($is_purchase == '無採購'){
                $orders = $orders->whereIn($orderTable.'.id',SyncedOrderItemDB::where([['not_purchase',0],['is_del',0]])->whereNull('purchase_date')->select('order_id')->groupBy('order_id')->orderBy('order_id','asc'));
            }
        }

        if(!empty($digiwin_payment_id)){
            $digiwinPaymentId = explode(',',$digiwin_payment_id);
            $orders = $orders->whereIn($orderTable.'.digiwin_payment_id', $digiwinPaymentId);
        }

        if(!empty($digiwin_payment_ids)){
            $digiwinPaymentIds = explode(',',$digiwin_payment_ids);
            $orders = $orders->whereIn($orderTable.'.digiwin_payment_id', $digiwinPaymentIds);
        }

        if(!empty($source)) {
            $s = explode(',', $source);
            $chkWeb = 0; $skm=[];
            for($i = 0;$i < count($s);$i++) {
                if($s[$i] == 'iCarry') {
                    $chkWeb = 1;
                }
                if($s[$i] == 'skm') {
                    $skm = DigiwinPaymentDB::where('customer_no','like','065___')->get()->pluck('customer_no')->all();
                }
            }
            for($i=0;$i<count($s);$i++){
                if($s[$i] == 'iCarry' || $s[$i] == 'skm') {
                    unset($s[$i]);
                }
            }
            sort($s);
            $s = array_merge($s,$skm);

            $chkWeb == 1 ? $orders = $orders->where(function($query)use($s,$orderTable){$query->whereIn($orderTable.'.digiwin_payment_id',$s)->orWhere($orderTable.'.create_type','web');}) : $orders = $orders->whereIn($orderTable.'.digiwin_payment_id',$s);
        }

        //查詢參數
        isset($booking) && $booking ? $orders = $orders->where($orderTable.'.book_shipping_date', $booking) : '';
        isset($keytime) && $keytime ? $orders = $orders->where($orderTable.'.receiver_key_time', 'like', "%$keytime%") : '';
        isset($order_number) && $order_number ? $orders = $orders->where($orderTable.'.order_number', 'like', "%$order_number%") : '';
        isset($partner_order_number) && $partner_order_number ? $orders = $orders->where($orderTable.'.partner_order_number', 'like', "%$partner_order_number%") : '';
        isset($shipping_number) && $shipping_number ? $orders = $orders->where($orderTable.'.shipping_number', 'like', "%$shipping_number%") : '';
        isset($pay_time) && $pay_time ? $orders = $orders->where($orderTable.'.pay_time', '>=', $pay_time) : '';
        isset($pay_time_end) && $pay_time_end ? $orders = $orders->where($orderTable.'.pay_time', '<=', $pay_time_end) : '';
        isset($user_id) && $user_id ? $orders = $orders->where($orderTable.'.user_id',$user_id) : '';
        isset($buyer_name) && $buyer_name ? $orders = $orders->where($orderTable.'.buyer_name','like', "%$buyer_name%") : '';
        isset($receiver_name) && $receiver_name ? $orders = $orders->where($orderTable.'.receiver_name','like', "%$receiver_name%") : '';
        isset($receiver_address) && $receiver_address ? $orders = $orders->where($orderTable.'.receiver_address','like', "%$receiver_address%") : '';
        isset($created_at) && $created_at ? $orders = $orders->where($orderTable.'.create_time', '>=', $created_at) : '';
        isset($created_at_end) && $created_at_end ? $orders = $orders->where($orderTable.'.create_time', '<=', $created_at_end) : '';
        isset($is_invoice_no) && $is_invoice_no ? $orders = $orders->where($orderTable.'.is_invoice_no','like', "%$is_invoice_no%") : '';
        isset($invoice_type) && $invoice_type ? $orders = $orders->where($orderTable.'.invoice_type',$invoice_type) : '';
        isset($invoice_title) && $invoice_title ? $orders = $orders->where($orderTable.'.invoice_title','like', "%$invoice_title%") : '';
        isset($invoice_time) && $invoice_time ? $orders = $orders->where($orderTable.'.invoice_time', '>=', $invoice_time) : '';
        isset($invoice_time_end) && $invoice_time_end ? $orders = $orders->where('invoice_time', '<=', $invoice_time_end) : '';
        isset($domain) && $domain ? $orders = $orders->where($orderTable.'.domain',$domain) : '';
        isset($promotion_code) && $promotion_code ? $orders = $orders->where($orderTable.'.promotion_code',$promotion_code) : '';
        !empty($spend_point) ? strtoupper($spend_point) == 'X' ? $orders = $orders->where($orderTable.'.spend_point','<=', 0) : $orders = $orders->where('spend_point','>=', 1) : '';
        !empty($is_discount) ? strtoupper($is_discount) == 'X' ? $orders = $orders->where($orderTable.'.discount','=', 0) : $orders = $orders->where('discount','!=', 0) : '';
        isset($shipping_method) && $shipping_method != '1,2,3,4,5,6' ? $orders = $orders->whereIn($orderTable.'.shipping_method', explode(',', $shipping_method)) : '';
        isset($origin_country) && $origin_country ? $orders = $orders->whereIn($orderTable.'.origin_country', explode(',', $origin_country)) : '';
        !empty($is_asiamiles) ? $is_asiamiles == 1 ? $orders = $orders->whereIn($orderTable.'.id', OrderAsiamilesDB::select('order_id')->groupBy('order_id')) : $orders = $orders->whereNotIn('id', OrderAsiamilesDB::select('order_id')->groupBy('order_id')) : '';
        !empty($is_shopcom) ? $is_shopcom == 1 ? $orders = $orders->whereIn($orderTable.'.id', ShopcomOrderDB::select('order_id')->groupBy('order_id')) : $orders = $orders->whereNotIn('id', ShopcomOrderDB::select('order_id')->groupBy('order_id')) : '';

        if(!empty($shipping_time) || !empty($shipping_time_end)){
            if(!empty($shipping_time)){
                !empty($shipping_time) ? $sellDateStart = explode(' ',$shipping_time)[0] : '';
                $orderIdsTemp = SellDB::where('sell_date','>=',$sellDateStart);
                if(!empty($shipping_time_end)){
                    !empty($shipping_time_end) ? $sellDateEnd = explode(' ',$shipping_time_end)[0] : '';
                    $orderIdsTemp = $orderIdsTemp->where('sell_date','<=',$sellDateEnd);
                }
            }else{
                if(!empty($shipping_time_end)){
                    !empty($shipping_time_end) ? $sellDateEnd = explode(' ',$shipping_time_end)[0] : '';
                    $orderIdsTemp = SellDB::where('sell_date','<=',$sellDateEnd);
                }
            }
            $orderIdsTemp = $orderIdsTemp->where('is_del',0)->select('order_id');
            $orders = $orders->whereIn($orderTable.'.id',$orderIdsTemp);
        }

        if(!empty($pay_method) && $pay_method !='全部'){
            $payMethods = $digiwinPaymentIds = [];
            $payMethod = explode(',', $pay_method);
            for($i=0;$i<count($payMethod);$i++){
                if($payMethod[$i] == '台灣蝦皮'){
                    $digiwinPaymentIds[] = '010';
                }elseif($payMethod[$i] == '新加坡蝦皮'){
                    $digiwinPaymentIds[] = '011';
                }else{
                    $payMethods[] = $payMethod[$i];
                }
            }
            $orders = $orders->where(function($query)use($orderTable,$digiwinPaymentIds,$payMethods){
                if(count($digiwinPaymentIds) > 0){
                    $query = $query->whereIn($orderTable.'.digiwin_payment_id', $digiwinPaymentIds);
                    if(count($payMethods) > 0){
                        $query = $query->orWhereIn($orderTable.'.pay_method', $payMethods);
                    }
                }elseif(count($payMethods) > 0){
                    $query = $query->whereIn($orderTable.'.pay_method', $payMethods);
                }
            });
        }

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

        if(!empty($zero_tax)){
            if(strtoupper($zero_tax) == 'X'){
                //原始語法
                // $query.=" AND (`order_number` IN (SELECT DISTINCT order_number FROM {$_SiteGLOBAL['dbtable']}.pay2go WHERE tax_type=2) OR(origin_country='台灣' AND ship_to!='台灣' AND invoice_type!='3' AND (love_code='' OR love_code IS NULL) AND print_flag='Y' AND create_type IN('app','kiosk','admin','web','Amazon','vendor') )OR(origin_country='台灣' AND ship_to!='台灣' AND invoice_type!='3' AND (love_code='' OR love_code IS NULL) AND print_flag='Y' AND create_type='shopee' AND user_memo LIKE '%(新加坡)%' ) )";
                //轉換後
                // $orders = $orders->where(function ($query) use ($orderTable) {
                //     $query->whereRaw(" $orderTable.order_number not in (SELECT DISTINCT order_number FROM pay2go WHERE tax_type=2) ")
                //     ->orWhereRaw(" $orderTable.ship_to != '台灣' AND $orderTable.invoice_type != '3' AND ($orderTable.love_code = '' OR $orderTable.love_code IS NULL) AND $orderTable.print_flag = 'Y' AND $orderTable.create_type IN('app','kiosk','admin','web','Amazon','vendor') ")
                //     ->orWhereRaw(" $orderTable.ship_to != '台灣' AND $orderTable.invoice_type != '3' AND ($orderTable.love_code = '' OR $orderTable.love_code IS NULL) AND $orderTable.print_flag = 'Y' AND $orderTable.create_type = 'shopee' AND $orderTable.user_memo LIKE '%(新加坡)%' ");
                // });
                //修改成直接抓pay2go
                $orders = $orders->whereRaw(" $orderTable.order_number in (SELECT DISTINCT order_number FROM pay2go WHERE tax_type=1) ");

            }else{
                // 原始語法
                // $query.=" AND (`order_number` NOT IN (SELECT DISTINCT order_number FROM {$_SiteGLOBAL['dbtable']}.pay2go WHERE tax_type=2) AND `order_number` NOT IN (SELECT order_number FROM {$_SiteGLOBAL['dbtable']}.orders WHERE ( `order_number` IN (SELECT DISTINCT order_number FROM {$_SiteGLOBAL['dbtable']}.pay2go WHERE tax_type=2) OR (origin_country='台灣' AND ship_to!='台灣' AND invoice_type!='3' AND (love_code='' OR love_code IS NULL) AND print_flag='Y' AND create_type IN('app','kiosk','admin','web','Amazon','vendor') ) OR (origin_country='台灣' AND ship_to!='台灣' AND invoice_type!='3' AND (love_code='' OR love_code IS NULL) AND print_flag='Y' AND create_type='shopee' AND user_memo LIKE '%(新加坡)%' )) ))";
                // 轉換後
                // $orders = $orders->whereRaw(" order_number not in (SELECT DISTINCT order_number FROM pay2go WHERE tax_type = 1) ");
                // $orders = $orders->where(function ($query) use ($orderTable) {
                //     $query->where($orderTable.'.ship_to', '!=','台灣')
                //     ->where($orderTable.'.invoice_type','!=',3);
                // });
                //修改直接抓pay2go
                $orders = $orders->whereRaw(" order_number in (SELECT DISTINCT order_number FROM pay2go WHERE tax_type = 2) ");
            }
        }

        if(!empty($line_ecid)){
            if(strtoupper($line_ecid) == 'X'){
                $orders = $orders->where(function ($query) use ($orderTable) {
                    $query->whereNull($orderTable.'.line_ecid')
                    ->orWhere($orderTable.'.line_ecid', '');
                });
            }else{
                $orders = $orders->where(function ($query) use ($orderTable) {
                    $query->whereRaw(" line_ecid != '' or line_ecid != NULL ");
                });
            }
        }

        if(!empty($greeting_card)){
            if(strtoupper($greeting_card) == 'X'){
                $orders = $orders->where(function ($query) use ($orderTable) {
                    $query->whereNull($orderTable.'.greeting_card')
                    ->orWhere($orderTable.'.greeting_card', '');
                });
            }else{
                $orders = $orders->where(function ($query) use ($orderTable) {
                    $query->whereRaw(" greeting_card != '' or greeting_card != NULL ");
                });
            }
        }

        if(!empty($purchase_no)){
            $tmp = SyncedOrderItemDB::where('purchase_no',$purchase_no)->select('order_id')->groupBy('order_id');
            $orders = $orders->whereIn($orderTable.'.id', $tmp);
        }

        isset($all_item_is_call) ?  $all_item_is_call == 'ALL' ? $item_is_call = 'ALL' : '' : '';
        if(!empty($item_is_call) || isset($direct_shipment) || (isset($vendor_name) && $vendor_name) || (isset($product_name) && $product_name) || (isset($sku) && $sku) || (isset($shipping_vendor_name) && $shipping_vendor_name)){
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
                $orderItemIds = $orderItemIds->select($orderItemPackageTable.'.order_item_id');

                $orders = $orders->where(function($query)use($productTable,$orderItemTable,$productModelTable,$request,$orderItemIds){
                    if(!empty($request['getExpress'])){
                        if($request['getExpress'] != 'all'){
                            $param = explode('&',urldecode($request['getExpress']));
                            for($i=0;$i<count($param);$i++){
                                $tmp[explode('=',$param[$i])[0]] = explode('=',$param[$i])[1];
                            }
                            foreach ($tmp as $keyName => $value) {
                                $$keyName = $value;
                            }
                        }
                    }else{
                        if(isset($request['con'])){ //by條件
                            foreach ($request['con'] as $requestKeyName => $value) {
                                $$requestKeyName = $value;
                            }
                        }else{
                            foreach ($request->all() as $requestKeyName => $value) {
                                $$requestKeyName = $value;
                            }
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
                    $query = $query->orWhereIn($orderItemTable.'.id',$orderItemIds);
                });
            }

            if(isset($shipping_vendor_name) && $shipping_vendor_name){
                if($shipping_vendor_name == '含多筆運單之訂單'){
                    $tmp = DB::select( DB::raw("(select order_id from (SELECT order_id, shipping_memo FROM $orderItemTable WHERE shipping_memo is not null GROUP by shipping_memo, order_id ORDER BY `order_item`.`order_id` DESC) tmp where 1 group By order_id HAVING sum(1) >= 2)") );
                    foreach($tmp as $t){
                        $orderIds[] = $t->order_id;
                    }
                    $orders = $orders->whereIn($orderTable.'.id',$orderIds);
                }elseif($shipping_vendor_name == '未分類'){
                    $orders = $orders->whereNull($orderItemTable.'.shipping_memo');
                }else{
                    $orders = $orders->where($orderItemTable.'.shipping_memo',$shipping_vendor_name);
                }
            }

            if(!empty($direct_shipment)){
                if(strtoupper($direct_shipment) == 'X'){
                    $orders = $orders->where(function($query)use($orderItemTable){
                        $query->where($orderItemTable.'.direct_shipment',0)
                        ->orWhere($orderItemTable.'.direct_shipment','!=',1)
                        ->orWhere($orderItemTable.'.direct_shipment','')
                        ->orWhere($orderItemTable.'.direct_shipment',null);
                    });
                }elseif($direct_shipment == 1){
                    $orders = $orders->where($orderItemTable.'.direct_shipment',1);
                }
            }

            if (!empty($item_is_call)) {
                if (strtoupper($item_is_call) == 'ALL') {
                    $orders = $orders->where($orderItemTable.'.is_call','!=',null);
                } elseif (strtoupper($item_is_call) == 'X') {
                    $orders = $orders->where($orderItemTable.'.is_call');
                } else {
                    $orders = $orders->where($orderItemTable.'.is_call',$item_is_call);
                }
            }

            $orders = $orders->groupBy($orderTable.'.id');
        }

        if (isset($keyword) && $keyword) {
            $userIds = UserDB:: where('id', $keyword)
                ->orwhere('name','like', "%$keyword%")
                // ->orwhere('mobile', 'like', "%$keyword%")
                ->select('id')->distinct();
            $userIds == '' || $userIds == null ? $userIds = [] : '';
            $orders = $orders->where(function ($query) use ($keyword, $userIds) {
                $query->where($orderTable.'.order_number', 'like', "%$keyword%")
                ->orWhere($orderTable.'.receiver_name','like', "%$keyword%")
                // ->orWhere($orderTable.'.receiver_phone_number', 'like', "%$keyword%")
                // ->orWhere($orderTable.'.receiver_tel', 'like', "%$keyword%")
                ->orWhereIn($orderTable.'.user_id', $userIds);
            });
        }

        isset($all_is_call) ?  ($all_is_call == 'ALL' ? $is_call = 'ALL' : '') : '';
        if (!empty($is_call)) {
            if (strtoupper($is_call) == 'ALL') {
                $orders = $orders->where($orderTable.'.is_call', '!=', null);
            } elseif (strtoupper($is_call) == 'X') {
                $orders = $orders->whereNull($orderTable.'.is_call');
            } else {
                $orders = $orders->where($orderTable.'.is_call', $is_call);
            }
        }
        isset($all_is_print) ?  $all_is_print == 'ALL' ? $is_print = 'ALL' : '' : '';
        if (!empty($is_print)) {
            if (strtoupper($is_print) == 'ALL') {
                $orders = $orders->where(function ($query) use ($orderTable){
                    $query->whereNotNull($orderTable.'.is_print')
                    ->where($orderTable.'.is_print', '!=', '0');
                });
            } elseif (strtoupper($is_print) == 'X') {
                $orders = $orders->where(function ($query) use ($orderTable){
                    $query->whereNull($orderTable.'.is_print')
                    ->orWhere($orderTable.'.is_print', '0');
                });
            } else {
                $orders = $orders->where($orderTable.'.is_print', $is_print);
            }
        }

        if(isset($is_memo) && $is_memo == 1){
            $orders = $orders->where(function ($query) use ($orderTable) {
                $query->whereNotNull($orderTable.'.admin_memo')
                    ->orWhereNotNull($orderTable.'.user_memo');
            });
        }

        if(isset($memo) && $memo){
            $orders = $orders->where(function ($query) use ($memo,$orderTable) {
                $query->where($orderTable.'.admin_memo','like',"%$memo%")
                    ->orWhere($orderTable.'.user_memo','like',"%$memo%");
            });
        }

        if(isset($synced_date_not_fill) && ($synced_date_not_fill == 1 || strtolower($synced_date_not_fill) == 'on')){
            strtolower($synced_date_not_fill) == 'on' ? $synced_date_not_fill == 1 : '';
            $synced_date = null;
            $synced_date_end = null;
            $orders = $orders->whereNotIn($orderTable.'.id',SyncedOrderDB::select('order_id')->distinct('order_id'));
        }

        if(!empty($synced_date)){
            $syncedOrders = new SyncedOrderDB;
            $syncedOrders = $syncedOrders->where('created_at','>=',$synced_date.' 00:00:00');
            if(!empty($synced_date_end)){
                $syncedOrders = $syncedOrders->where('created_at','<=',$synced_date_end.' 23:59:59');
            }
            $syncedOrders = $syncedOrders->select('order_id')->distinct('order_id');
            $orders = $orders->whereIn($orderTable.'.id',$syncedOrders);
        }elseif(!empty($synced_date_end)){
            $syncedOrders = new SyncedOrderDB;
            $syncedOrders = $syncedOrders->where('created_at','<=',$synced_date_end.' 23:59:59');
            $syncedOrders = $syncedOrders->select('order_id')->distinct('order_id');
            $orders = $orders->whereIn($orderTable.'.id',$syncedOrders);
        }

        if(isset($book_shipping_date_not_fill) && $book_shipping_date_not_fill == 1){
            $book_shipping_date = '';
            $book_shipping_date_end = '';
            $orders = $orders->whereNull($orderTable.'.book_shipping_date');
        }

        !empty($book_shipping_date) ? $orders = $orders->where($orderTable.'.book_shipping_date', '>=', $book_shipping_date) : '';
        !empty($book_shipping_date_end) ? $orders = $orders->where($orderTable.'.book_shipping_date', '<=', $book_shipping_date_end) : '';

        if(isset($vendor_arrival_date_not_fill) && $vendor_arrival_date_not_fill == 1){
            $vendor_arrival_date = '';
            $vendor_arrival_date_end = '';
            $orders = $orders->where(function($query)use($orderTable){
                $query->whereNull($orderTable.'.vendor_arrival_date')
                ->orWhere($orderTable.'.vendor_arrival_date',null)
                ->orWhere($orderTable.'.vendor_arrival_date','');
            });
        }

        !empty($vendor_arrival_date) ? $orders = $orders->where($orderTable.'.vendor_arrival_date', '>=', $vendor_arrival_date) : '';
        !empty($vendor_arrival_date_end) ? $orders = $orders->where($orderTable.'.vendor_arrival_date', '<=', $vendor_arrival_date_end) : '';

        !empty($receiver_key_time) ? $orders = $orders->where($orderTable.'.receiver_key_time', '>=', $receiver_key_time.' 00:00:00') : '';
        !empty($receiver_key_time_end) ? $orders = $orders->where($orderTable.'.receiver_key_time', '<=', $receiver_key_time_end.' 23:59:59') : '';

        if(!empty($purchase_time) || !empty($purchase_time_end)){
            if(!empty($purchase_time)){
                if(!empty($purchase_time_end)){
                    $tmp = PurchaseOrderDB::whereBetween('created_at',[$purchase_time,$purchase_time_end])
                    ->select([
                        DB::raw("GROUP_CONCAT(order_ids) as order_ids")
                    ])->first();
                }else{
                    $tmp = PurchaseOrderDB::where('created_at','>=',$purchase_time)
                    ->select([
                        DB::raw("GROUP_CONCAT(order_ids) as order_ids")
                    ])->first();
                }
            }elseif(!empty($purchase_time_end)){
                $tmp = PurchaseOrderDB::where('created_at','<=',$purchase_time_end)
                ->select([
                    DB::raw("GROUP_CONCAT(order_ids) as order_ids")
                ])->first();
            }
            if(!empty($tmp)){
                $orderIds = explode(',',$tmp->order_ids);
                $orderIds = array_unique($orderIds);
                sort($orderIds);
                $orders = $orders->whereIn('id',$orderIds);
            }
        }

        if (!isset($list)) {
            $list = 50;
        }
        isset($request['method']) && $request['method'] == 'byQuery' ? $limit = 10000 : $limit = 0;
        //找出最終資料
        if(!empty($request['getExpress'])){
            $orders = $orders->select($orderTable.'.id')->orderBy($orderTable.'.create_time','desc');
            $limit == 10000 ? $orders = $orders->limit(10000) : '';
            $orders = $orders->get()->pluck('id')->all();
        }else{
            $getOrderIds = ['SFSpeedType','GoodMaji','Warehousing','WarehousingShipment','SFWarehousing','Asiamiles','Shopcom','WarehousingPreDelivery'];
            if(isset($request['type']) && in_array($request['type'],$getOrderIds)){
                $orders = $orders->select($orderTable.'.id')->orderBy($orderTable.'.create_time','desc');
                $limit == 10000 ? $orders = $orders->limit(10000) : '';
                $orders = $orders->get()->pluck('id')->all();
            }elseif(isset($request['type']) && $request['type'] == 'SF2'){ //只取訂單號碼及ID
                $orders = $orders->select([$orderTable.'.id',$orderTable.'.order_number']);
                $limit == 10000 ? $orders = $orders->limit(10000) : '';
                $orders = $orders->get();
            }elseif(isset($request['type']) && $request['type'] == 'DHL'){
                $orders = $orders->select([
                    $orderTable.'.id',
                    $orderTable.'.is_print',
                    $orderTable.'.vendor_arrival_date',
                    $orderTable.'.shipping_memo',
                    $orderTable.'.shipping_number',
                    $orderTable.'.order_number',
                    $orderTable.'.user_id',
                    $orderTable.'.origin_country',
                    $orderTable.'.ship_to',
                    $orderTable.'.book_shipping_date',
                    $orderTable.'.receiver_name',
                    $orderTable.'.receiver_email',
                    $orderTable.'.receiver_address',
                    $orderTable.'.receiver_city',
                    $orderTable.'.receiver_area',
                    $orderTable.'.receiver_province',
                    $orderTable.'.receiver_zip_code',
                    $orderTable.'.receiver_keyword',
                    $orderTable.'.receiver_key_time',
                    $orderTable.'.shipping_method',
                    $orderTable.'.invoice_type',
                    $orderTable.'.invoice_sub_type',
                    $orderTable.'.invoice_number',
                    $orderTable.'.is_invoice_no',
                    $orderTable.'.love_code',
                    $orderTable.'.invoice_title',
                    $orderTable.'.carrier_type',
                    $orderTable.'.carrier_num',
                    $orderTable.'.spend_point',
                    $orderTable.'.amount',
                    $orderTable.'.shipping_fee',
                    $orderTable.'.parcel_tax',
                    $orderTable.'.pay_method',
                    $orderTable.'.exchange_rate',
                    $orderTable.'.discount',
                    $orderTable.'.user_memo',
                    $orderTable.'.partner_order_number',
                    $orderTable.'.pay_time',
                    $orderTable.'.buyer_name',
                    $orderTable.'.buyer_email',
                    $orderTable.'.print_flag',
                    $orderTable.'.create_type',
                    $orderTable.'.status',
                    $orderTable.'.digiwin_payment_id',
                    $orderTable.'.is_call',
                    $orderTable.'.create_time',
                    $orderTable.'.admin_memo',
                    $orderTable.'.greeting_card',
                    $orderTable.'.shipping_kg_price',
                    $orderTable.'.shipping_base_price',
                    $orderTable.'.merge_order',
                    $orderTable.'.merged_order',
                    $orderTable.'.invoice_rand',
                    DB::raw("IF($orderTable.receiver_phone_number IS NULL,'',AES_DECRYPT($orderTable.receiver_phone_number,'$key')) as receiver_phone_number"),
                    DB::raw("IF($orderTable.receiver_tel IS NULL,'',AES_DECRYPT($orderTable.receiver_tel,'$key')) as receiver_tel"),
                    $orderTable.'.ship_to as receiver_country',
                    $orderTable.'.recevier_zip_code as zip_code',
                ])->orderBy($orderTable.'.create_time','desc');
                $limit == 10000 ? $orders = $orders->limit(10000) : '';
                $orders = $orders->get();
            }elseif($type == 'modify'){
                if(!empty($request->column_name)){
                    $columnName = $request->column_name;
                    if ($columnName == 'cancel') {
                        $orders = $orders->whereIn($orderTable.'.status',[1,2]);
                        $orders->update(['status' => -1, 'admin_memo' => $request->column_data]);
                    }elseif($columnName == 'merge_order'){
                        $request->column_data = str_replace([' ','　','，'],['','',','], $request->column_data);
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
                            $orders = $orders->whereIn($orderTable.'.status',[1,2]);
                        }
                        $orders->update([$request->column_name => $request->column_data]);
                    }
                }
                $orders = $this->getOrderData($request,'show');
            }elseif($type == 'getOrderNumbers'){
                $orders = $orders->select([$orderTable.'.id',$orderTable.'.order_number']);
                $limit == 10000 ? $orders = $orders->limit(10000) : '';
                $orders = $orders->get();
            }else{
                $orders = $orders->select([
                    $orderTable.'.id',
                    $orderTable.'.is_print',
                    $orderTable.'.vendor_arrival_date',
                    $orderTable.'.shipping_memo',
                    $orderTable.'.shipping_number',
                    $orderTable.'.order_number',
                    $orderTable.'.user_id',
                    $orderTable.'.origin_country',
                    $orderTable.'.ship_to',
                    $orderTable.'.book_shipping_date',
                    $orderTable.'.receiver_name',
                    $orderTable.'.receiver_email',
                    $orderTable.'.receiver_address',
                    $orderTable.'.receiver_city',
                    $orderTable.'.receiver_area',
                    $orderTable.'.receiver_province',
                    $orderTable.'.receiver_zip_code',
                    $orderTable.'.receiver_keyword',
                    $orderTable.'.receiver_key_time',
                    $orderTable.'.shipping_method',
                    $orderTable.'.invoice_type',
                    $orderTable.'.invoice_sub_type',
                    $orderTable.'.invoice_number',
                    $orderTable.'.invoice_time',
                    $orderTable.'.is_invoice_no',
                    $orderTable.'.love_code',
                    $orderTable.'.invoice_title',
                    $orderTable.'.carrier_type',
                    $orderTable.'.carrier_num',
                    $orderTable.'.spend_point',
                    $orderTable.'.amount',
                    $orderTable.'.shipping_fee',
                    $orderTable.'.parcel_tax',
                    $orderTable.'.pay_method',
                    $orderTable.'.exchange_rate',
                    $orderTable.'.discount',
                    $orderTable.'.user_memo',
                    $orderTable.'.partner_order_number',
                    $orderTable.'.pay_time',
                    $orderTable.'.buyer_name',
                    $orderTable.'.buyer_email',
                    $orderTable.'.print_flag',
                    $orderTable.'.create_type',
                    $orderTable.'.status',
                    $orderTable.'.digiwin_payment_id',
                    $orderTable.'.is_call',
                    $orderTable.'.create_time',
                    $orderTable.'.admin_memo',
                    $orderTable.'.greeting_card',
                    $orderTable.'.shipping_kg_price',
                    $orderTable.'.shipping_base_price',
                    $orderTable.'.merge_order',
                    $orderTable.'.merged_order',
                    $orderTable.'.invoice_rand',
                    DB::raw("DATE_FORMAT($orderTable.create_time,'%Y/%m/%d') as createTime"),
                ]);
                $getOrderTelType = ['getInfo','exportOrderDetail','orderReturnDetail','DigiwinExport','orderShipping','airport','Synchronize'];
                $getUserTelType = ['exportOrderDetail','Refund'];
                if(in_array($type,$getOrderTelType)){
                    $orders = $orders->addSelect([
                        'china_id_img1',
                        'china_id_img2',
                        'user_name' => UserDB::whereColumn($userTable.'.id','orders.user_id')->select($userTable.'.name')->limit(1),
                        DB::raw("IF($orderTable.receiver_phone_number IS NULL,'',AES_DECRYPT($orderTable.receiver_phone_number,'$key')) as receiver_phone_number"),
                        DB::raw("IF($orderTable.receiver_tel IS NULL,'',AES_DECRYPT($orderTable.receiver_tel,'$key')) as receiver_tel"),
                    ]);
                }
                if(in_array($type,$getUserTelType)){
                    $orders = $orders->addSelect([
                        'user_email' => UserDB::whereColumn($userTable.'.id','orders.user_id')->select($userTable.'.email')->limit(1),
                        'user_name' => UserDB::whereColumn($userTable.'.id','orders.user_id')->select($userTable.'.name')->limit(1),
                        'user_nation' => UserDB::whereColumn($userTable.'.id','orders.user_id')->select($userTable.'.nation')->limit(1),
                        // 'user_mobile' => UserDB::whereColumn($userTable.'.id','orders.user_id')->select(DB::raw("IF($userTable.mobile IS NULL,'',AES_DECRYPT($userTable.mobile,'$key')) as mobile"))->limit(1),
                    ]);
                }
                if($type == 'OrderInvoiceExport'){
                    $orders = $orders->addSelect([
                        'user_name' => UserDB::whereColumn($userTable.'.id','orders.user_id')->select($userTable.'.name')->limit(1),
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
                }elseif($type == 'getUnPurchaseOrders'){
                    $orders = $orders->whereNotIn($orderTable.'.status',[0,-1])->select($orderTable.'.id')->groupBy($orderTable.'.id');
                    $limit == 10000 ? $orders = $orders->limit(10000) : '';
                    $orders = $orders->get()->pluck('id')->all();
                }elseif($type == 'getPurchasedItems' || $type == 'pickupShipping'){
                    $orders = $orders->select($orderTable.'.id')->groupBy($orderTable.'.id');
                    $limit == 10000 ? $orders = $orders->limit(10000) : '';
                    $orders = $orders->get()->pluck('id')->all();
                }elseif($type == 'DigiwinExport'){
                        $orders = $orders->addSelect([
                            DB::raw("DATE_FORMAT(orders.pay_time,'%Y%m%d') as payTime"),
                        ])->orderBy($orderTable.'.id', 'desc');
                        $limit == 10000 ? $orders = $orders->limit(10000) : '';
                        $orders = $orders->get();
                }else{
                    $orders = $orders->orderBy($orderTable.'.id', 'desc');
                    $limit == 10000 ? $orders = $orders->limit(10000) : '';
                    $orders = $orders->get();
                }
            }
        }

        //檢查組合品是否沒分拆, 若沒分拆則補資料
        $type == 'index' ? $orders = $this->chkItemPackage($orders) : '';

        return $orders;
    }

    protected function chkItemPackage($orders, $type = null)
    {
        $grossWeightRate = SystemSettingDB::first()->gross_weight_rate;
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        foreach($orders as $order){
            foreach($order->items as $item){
                if(strstr($item->sku,'BOM')){
                    if(count($item->package) == 0){ //組合品資料未建立時
                        $packageData = json_decode(str_replace('	','',$item->package_data));
                        foreach($packageData as $package){
                            if(isset($package->is_del)){
                                if($package->bom == $item->sku && $package->is_del == 0){
                                    foreach($package->lists as $list){
                                        $useQty = $list->quantity;
                                        $pm = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                        ->where('sku',$list->sku)
                                        ->select([
                                            $productModelTable.'.id as product_model_id',
                                            $productModelTable.'.sku',
                                            $productModelTable.'.digiwin_no',
                                            $productTable.'.id as product_id',
                                            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                            $productTable.'.unit_name',
                                            $productTable.'.price',
                                            $productTable.'.gross_weight',
                                            $productTable.'.net_weight',
                                            $productTable.'.direct_shipment',
                                            $productTable.'.is_tax_free',
                                            $productTable.'.vendor_price',
                                            $productTable.'.service_fee_percent as product_service_fee_percent',
                                            $productTable.'.package_data',
                                            $vendorTable.'.id as vendor_id',
                                            $vendorTable.'.name as vendor_name',
                                            $vendorTable.'.service_fee as vendor_service_fee',
                                            $vendorTable.'.shipping_verdor_percent',
                                        ])->first();
                                        if(!empty($pm)){
                                            $packageItem = [
                                                'order_id' => $order->id,
                                                'order_item_id' => $item->id,
                                                'product_model_id' => $pm->product_model_id,
                                                'sku' => $pm->sku,
                                                'digiwin_no' => $pm->digiwin_no,
                                                'digiwin_payment_id' => $item->digiwin_payment_id,
                                                'gross_weight' => $grossWeightRate * $pm->gross_weight,
                                                'net_weight' => $pm->net_weight,
                                                'quantity' => $useQty * $item->quantity,
                                                'is_del' => 0,
                                                'create_time' => $order->pay_time,
                                                'product_name' => mb_substr($pm->product_name,0,250),
                                                'purchase_price' => 0,
                                                'direct_shipment' => $item->direct_shipment,
                                            ];
                                            OrderItemPackageDB::create($packageItem);
                                        }
                                    }
                                }
                            }else{
                                if($package->bom == $item->sku){
                                    foreach($package->lists as $list){
                                        $useQty = $list->quantity;
                                        $pm = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                        ->where('sku',$list->sku)
                                        ->select([
                                            $productModelTable.'.id as product_model_id',
                                            $productModelTable.'.sku',
                                            $productModelTable.'.digiwin_no',
                                            $productTable.'.id as product_id',
                                            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                            $productTable.'.unit_name',
                                            $productTable.'.price',
                                            $productTable.'.gross_weight',
                                            $productTable.'.net_weight',
                                            $productTable.'.direct_shipment',
                                            $productTable.'.is_tax_free',
                                            $productTable.'.vendor_price',
                                            $productTable.'.service_fee_percent as product_service_fee_percent',
                                            $productTable.'.package_data',
                                            $vendorTable.'.id as vendor_id',
                                            $vendorTable.'.name as vendor_name',
                                            $vendorTable.'.service_fee as vendor_service_fee',
                                            $vendorTable.'.shipping_verdor_percent',
                                        ])->first();
                                        if(!empty($pm)){
                                            $packageItem = [
                                                'order_id' => $order->id,
                                                'order_item_id' => $item->id,
                                                'product_model_id' => $pm->product_model_id,
                                                'sku' => $pm->sku,
                                                'digiwin_no' => $pm->digiwin_no,
                                                'digiwin_payment_id' => $item->digiwin_payment_id,
                                                'gross_weight' => $grossWeightRate * $pm->gross_weight,
                                                'net_weight' => $pm->net_weight,
                                                'quantity' => $useQty * $item->quantity,
                                                'is_del' => 0,
                                                'create_time' => $order->pay_time,
                                                'product_name' => mb_substr($pm->product_name,0,250),
                                                'purchase_price' => 0,
                                                'direct_shipment' => $item->direct_shipment,
                                            ];
                                            OrderItemPackageDB::create($packageItem);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $orders;
    }

    protected function orderItemSplit($orders, $type = null)
    {
        //計算及item資料分拆
        if($type == 'single'){
            $orders = $this->itemSplit($orders);
        }else{
            foreach ($orders as $order) {
                $order = $this->itemSplit($order);
            }
        }
        return $orders;
    }

    public function itemTransfer($orders)
    {
        foreach($orders as $order){
            foreach($order->items as $item){
                if(!empty($item->origin_digiwin_no)){
                    $newProductModel = ProductModelDB::join('product','product.id','product_model.product_id')
                        ->join('vendor','vendor.id','product.vendor_id')
                        ->where('product_model.digiwin_no',$item->origin_digiwin_no)
                        ->select([
                            'product_model.*',
                            'vendor.id as vendor_id',
                            'vendor.name as vendor_name',
                            DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                            'product.unit_name',
                            'product.direct_shipment',
                            'product.serving_size',
                            'product.unit_name',
                            'product.id as product_id',
                        ])->first();
                    if(!empty($newProductModel)){
                        $item->order_digiwin_no = $item->digiwin_no;
                        $item->product_model_id = $newProductModel->id;
                        $item->digiwin_no = $newProductModel->digiwin_no;
                        $item->sku = $newProductModel->sku;
                        $item->gtin13 = $newProductModel->gtin13;
                        $item->product_id = $newProductModel->product_id;
                        $item->product_name = $newProductModel->product_name;
                        $item->vendor_id = $newProductModel->vendor_id;
                        $item->vendor_name = $newProductModel->vendor_name;
                        $item->unit_name = $newProductModel->unit_name;
                        !empty($item->direct_shipment) ? $item->direct_shipment : $item->direct_shipment = 0;
                    }
                }
                if(strstr($item->sku,'BOM')){
                    foreach($item->package as $package){
                        if(!empty($package->origin_digiwin_no)){
                            $newProductModel = ProductModelDB::join('product','product.id','product_model.product_id')
                                ->join('vendor','vendor.id','product.vendor_id')
                                ->where('product_model.digiwin_no',$package->origin_digiwin_no)
                                ->select([
                                    'product_model.*',
                                    'vendor.id as vendor_id',
                                    'vendor.name as vendor_name',
                                    DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                                    'product.unit_name',
                                    'product.direct_shipment',
                                    'product.serving_size',
                                    'product.unit_name',
                                    'product.id as product_id',
                                ])->first();
                            if(!empty($newProductModel)){
                                $package->order_digiwin_no = $package->digiwin_no;
                                $package->product_model_id = $newProductModel->id;
                                $package->digiwin_no = $newProductModel->digiwin_no;
                                $package->sku = $newProductModel->sku;
                                $package->gtin13 = $newProductModel->gtin13;
                                $package->product_id = $newProductModel->product_id;
                                $package->product_name = $newProductModel->product_name;
                                $package->vendor_id = $newProductModel->vendor_id;
                                $package->vendor_name = $newProductModel->vendor_name;
                                $package->unit_name = $newProductModel->unit_name;
                                $package->direct_shipment = $item->direct_shipment;
                            }
                        }
                    }
                }
            }
        }
        return $orders;
    }

    public function oneOrderItemTransfer($order)
    {
        if(!empty($order)){
            foreach($order->items as $item){
                if(!empty($item->origin_digiwin_no)){
                    $newProductModel = ProductModelDB::join('product','product.id','product_model.product_id')
                        ->join('vendor','vendor.id','product.vendor_id')
                        ->where('product_model.digiwin_no',$item->origin_digiwin_no)
                        ->select([
                            'product_model.*',
                            'vendor.id as vendor_id',
                            'vendor.name as vendor_name',
                            // 'product.name as product_name',
                            DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                            'product.unit_name',
                            'product.direct_shipment',
                            'product.serving_size',
                            'product.unit_name',
                            'product.id as product_id',
                        ])->first();
                    if(!empty($newProductModel)){
                        $item->order_digiwin_no = $item->digiwin_no;
                        $item->product_model_id = $newProductModel->id;
                        $item->digiwin_no = $newProductModel->digiwin_no;
                        $item->sku = $newProductModel->sku;
                        $item->gtin13 = $newProductModel->gtin13;
                        $item->product_id = $newProductModel->product_id;
                        $item->product_name = $newProductModel->product_name;
                        $item->vendor_id = $newProductModel->vendor_id;
                        $item->vendor_name = $newProductModel->vendor_name;
                        $item->unit_name = $newProductModel->unit_name;
                        !empty($item->direct_shipment) ? $item->direct_shipment : $item->direct_shipment = 0;
                    }
                }
                if(strstr($item->sku,'BOM')){
                    foreach($item->package as $package){
                        if(!empty($package->origin_digiwin_no)){
                            $newProductModel = ProductModelDB::join('product','product.id','product_model.product_id')
                                ->join('vendor','vendor.id','product.vendor_id')
                                ->where('product_model.digiwin_no',$package->origin_digiwin_no)
                                ->select([
                                    'product_model.*',
                                    'vendor.id as vendor_id',
                                    'vendor.name as vendor_name',
                                    // 'product.name as product_name',
                                    DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                                    'product.unit_name',
                                    'product.direct_shipment',
                                    'product.serving_size',
                                    'product.unit_name',
                                    'product.id as product_id',
                                ])->first();
                            if(!empty($newProductModel)){
                                $package->order_digiwin_no = $package->digiwin_no;
                                $package->product_model_id = $newProductModel->id;
                                $package->digiwin_no = $newProductModel->digiwin_no;
                                $package->sku = $newProductModel->sku;
                                $package->gtin13 = $newProductModel->gtin13;
                                $package->product_id = $newProductModel->product_id;
                                $package->product_name = $newProductModel->product_name;
                                $package->vendor_id = $newProductModel->vendor_id;
                                $package->vendor_name = $newProductModel->vendor_name;
                                $package->unit_name = $newProductModel->unit_name;
                                $package->direct_shipment = $newProductModel->direct_shipment;
                            }
                        }
                    }
                }
            }
        }
        return $order;
    }

    public function oneItemTransfer($item)
    {
        if(!empty($item->origin_digiwin_no)){
            $newProductModel = ProductModelDB::join('product','product.id','product_model.product_id')
                ->join('vendor','vendor.id','product.vendor_id')
                ->where('product_model.digiwin_no',$item->origin_digiwin_no)
                ->select([
                    'product_model.*',
                    'vendor.id as vendor_id',
                    'vendor.name as vendor_name',
                    // 'product.name as product_name',
                    DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                    'product.unit_name',
                    'product.direct_shipment',
                    'product.serving_size',
                    'product.unit_name',
                    'product.id as product_id',
                ])->first();
            if(!empty($newProductModel)){
                $item->order_digiwin_no = $item->digiwin_no;
                $item->product_model_id = $newProductModel->id;
                $item->digiwin_no = $newProductModel->digiwin_no;
                $item->sku = $newProductModel->sku;
                $item->gtin13 = $newProductModel->gtin13;
                $item->product_id = $newProductModel->product_id;
                $item->product_name = $newProductModel->product_name;
                $item->vendor_id = $newProductModel->vendor_id;
                $item->vendor_name = $newProductModel->vendor_name;
                $item->unit_name = $newProductModel->unit_name;
                !empty($item->direct_shipment) ? $item->direct_shipment : $item->direct_shipment = 0;
            }
        }
        if(strstr($item->sku,'BOM')){
            foreach($item->package as $package){
                if(!empty($package->origin_digiwin_no)){
                    $newProductModel = ProductModelDB::join('product','product.id','product_model.product_id')
                        ->join('vendor','vendor.id','product.vendor_id')
                        ->where('product_model.digiwin_no',$package->origin_digiwin_no)
                        ->select([
                            'product_model.*',
                            'vendor.id as vendor_id',
                            'vendor.name as vendor_name',
                            // 'product.name as product_name',
                            DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                            'product.unit_name',
                            'product.direct_shipment',
                            'product.serving_size',
                            'product.unit_name',
                            'product.id as product_id',
                        ])->first();
                    if(!empty($newProductModel)){
                        $package->order_digiwin_no = $package->digiwin_no;
                        $package->product_model_id = $newProductModel->id;
                        $package->digiwin_no = $newProductModel->digiwin_no;
                        $package->sku = $newProductModel->sku;
                        $package->gtin13 = $newProductModel->gtin13;
                        $package->product_id = $newProductModel->product_id;
                        $package->product_name = $newProductModel->product_name;
                        $package->vendor_id = $newProductModel->vendor_id;
                        $package->vendor_name = $newProductModel->vendor_name;
                        $package->unit_name = $newProductModel->unit_name;
                        $package->direct_shipment = $newProductModel->direct_shipment;
                    }
                }
            }
        }
        return $item;
    }

    public function oneOrderItemDataTransfer($order)
    {
        if(!empty($order)){
            foreach($order->itemData as $item){
                if(!empty($item->origin_digiwin_no)){
                    $newProductModel = ProductModelDB::join('product','product.id','product_model.product_id')
                        ->join('vendor','vendor.id','product.vendor_id')
                        ->where('product_model.digiwin_no',$item->origin_digiwin_no)
                        ->select([
                            'product_model.*',
                            'vendor.id as vendor_id',
                            'vendor.name as vendor_name',
                            // 'product.name as product_name',
                            DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                            'product.unit_name',
                            'product.direct_shipment',
                            'product.serving_size',
                            'product.unit_name',
                            'product.id as product_id',
                        ])->first();
                    if(!empty($newProductModel)){
                        $item->order_digiwin_no = $item->digiwin_no;
                        $item->product_model_id = $newProductModel->id;
                        $item->digiwin_no = $newProductModel->digiwin_no;
                        $item->sku = $newProductModel->sku;
                        $item->gtin13 = $newProductModel->gtin13;
                        $item->product_id = $newProductModel->product_id;
                        $item->product_name = $newProductModel->product_name;
                        $item->vendor_id = $newProductModel->vendor_id;
                        $item->vendor_name = $newProductModel->vendor_name;
                        $item->unit_name = $newProductModel->unit_name;
                        !empty($item->direct_shipment) ? $item->direct_shipment : $item->direct_shipment = 0;
                    }
                }
                if(strstr($item->sku,'BOM')){
                    foreach($item->package as $package){
                        if(!empty($package->origin_digiwin_no)){
                            $newProductModel = ProductModelDB::join('product','product.id','product_model.product_id')
                                ->join('vendor','vendor.id','product.vendor_id')
                                ->where('product_model.digiwin_no',$package->origin_digiwin_no)
                                ->select([
                                    'product_model.*',
                                    'vendor.id as vendor_id',
                                    'vendor.name as vendor_name',
                                    // 'product.name as product_name',
                                    DB::raw("CONCAT(vendor.name,' ',product.name,'-',product_model.name) as product_name"),
                                    'product.unit_name',
                                    'product.direct_shipment',
                                    'product.serving_size',
                                    'product.unit_name',
                                    'product.id as product_id',
                                ])->first();
                            if(!empty($newProductModel)){
                                $package->order_digiwin_no = $package->digiwin_no;
                                $package->product_model_id = $newProductModel->id;
                                $package->digiwin_no = $newProductModel->digiwin_no;
                                $package->sku = $newProductModel->sku;
                                $package->gtin13 = $newProductModel->gtin13;
                                $package->product_id = $newProductModel->product_id;
                                $package->product_name = $newProductModel->product_name;
                                $package->vendor_id = $newProductModel->vendor_id;
                                $package->vendor_name = $newProductModel->vendor_name;
                                $package->unit_name = $newProductModel->unit_name;
                                $package->direct_shipment = $newProductModel->direct_shipment;
                            }
                        }
                    }
                }
            }
        }
        return $order;
    }


    public function itemSplit($order){
        $order->totalQty = $order->totalPrice = $order->totalWeight = 0;
        if($order->shipping_memo){
            if( json_decode( $order->shipping_memo , true ) ){
                $shippingMemo = collect(json_decode($order->shipping_memo));
                foreach($shippingMemo as $sm){
                    $order->shipping_memo_vendor = $sm->express_way;
                }
            }else{
                $order->shipping_memo_vendor = $order->shipping_memo;
            }
        }
        $totalSellQty = $totalItemPrice = $totalQty = $totalPrice = $totalWeight = 0;
        // $useQuotation = $order->customer->MA030; //是否使用報價單
        !empty($order->customer) ? $useQuotation = $order->customer['use_quotation'] : $useQuotation = 0; //是否使用報價單
        /*
            如果需要使用報價單時，所有商品資料(單品與組合)，皆以鼎新報價單為準，如果沒有缺少則填0，
            若是組合商品分拆時，皆以iCarry報價為基準做分拆。
            若不需使用報價單時，所有商品資料全部以iCarry報價單為準。
        */
        foreach ($order->items as $item) {
            $erpPrice = 0;
            //如果需要使用報價單時，所有商品資料(單品與組合)，皆以鼎新報價單為準，如果沒有缺少則填0, 操作者須去鼎新建立報價單.
            if($useQuotation == 1){
                !empty($item->erpQuotation) ? $erpPrice = $item->erpQuotation->MB008 : ''; //erp組合商品報價
                $item->price = $erpPrice;
            }
            $itemTotalPrice = $item->price * $item->quantity; //組合商品總價
            //組合商品分拆皆以iCarry報價單為基準
            if(strstr($item->sku,'BOM') && !empty($item->package)){
                $sellQty = $iCarryTotalPrice = 0;
                foreach($item->package as $package){ //找出所有組合商品總金額
                    !empty($package->icarryQuotation) ? $icarryItemPrice = $package->icarryQuotation->price : $icarryItemPrice = 0; //iCarry單品報價單
                    $iCarryTotalPrice += $package->quantity * $icarryItemPrice;
                }
                $remain = $itemTotalPrice;
                $sellQty = $packageTotal = 0;
                $split = [];
                foreach($item->package as $package){ //算出所有商品拆分比例
                    !empty($package->icarryQuotation) ? $icarryItemPrice = $package->icarryQuotation->price : $icarryItemPrice = 0; //iCarry單品報價單                    //icarry商品價格小於1時,拆分比例即為0.
                    if($iCarryTotalPrice > 0){
                        $icarryItemPrice < 1 ? $package->ratio = 0 : $package->ratio = $icarryItemPrice / $iCarryTotalPrice;
                    }else{
                        $package->ratio = 0;
                    }
                    $package->price = intval($itemTotalPrice * $package->ratio);
                    $package->total = $package->price * $package->quantity;
                    $c = $package->quantity;
                    $erpOrderSnos = [];
                    if(!empty($package->syncedOrderItemPackage)){
                        $erpOrderSnos = explode(',',$package->syncedOrderItemPackage->erp_order_sno);
                    }
                    if($c > 1){ //將所有商品數量拆成1
                        for($x=1; $x <= $c; $x++){
                            $sellQuantity = $sellNo = $erpOrderSno = null;
                            if(count($erpOrderSnos) > 0){
                                for($y=0;$y<count($erpOrderSnos);$y++){
                                    if($y+1 == $x){
                                        $erpOrderSno = $erpOrderSnos[$y];
                                        break;
                                    }
                                }
                            }
                            if(!empty($erpOrderSno)){
                                $sellItem = SellItemSingleDB::where([['order_item_id',$item->id],['order_item_package_id',$package->id],['erp_order_sno',$erpOrderSno],['is_del',0]])->first();
                                if(!empty($sellItem)){
                                    $sellNo = $sellItem->sell_no;
                                    $sellQuantity = $sellItem->sell_quantity;
                                    $sellQty += $sellItem->sell_quantity;
                                }
                            }
                            $split[] = [ //由於相同的商品拆分時計算最後一筆價格時, 物件操作時會變成全部都一樣. 所以只好用陣列來轉.
                                'id' => $package->id,
                                'order_item_id' => $package->order_item_id,
                                'product_model_id' => $package->product_model_id,
                                'product_id' => $package->product_id,
                                'serving_size' => $package->serving_size,
                                'sku' => $package->sku,
                                'digiwin_no' => $package->digiwin_no,
                                'digiwin_payment_id' => $package->digiwin_payment_id,
                                'gross_weight' => $package->gross_weight,
                                'net_weight' => $package->net_weight,
                                'quantity' => 1,
                                'is_del' => $item->is_del,
                                'admin_memo' => $package->admin_memo,
                                'create_time' => $package->create_time,
                                'product_name' => $package->product_name,
                                'is_call' => $package->is_call,
                                'unit_name' => $package->unit_name,
                                'price' => $package->price,
                                'origin_price' => $package->origin_price,
                                'ratio' => $package->ratio,
                                'total' => $package->price * 1,
                                'sell_no' => $sellNo,
                                'erp_order_sno' => $erpOrderSno,
                                'sell_quantity' => $sellQuantity,
                            ];
                            $package->total = $package->price;
                            $remain -= $package->price;
                            $packageTotal += $package->total;
                        }
                    }else{
                        $sellQuantity = $sellNo = $erpOrderSno = null;
                        if(count($erpOrderSnos) > 0){
                            $erpOrderSno = $erpOrderSnos[0];
                        }
                        if(!empty($erpOrderSno)){
                            $sellItem = SellItemSingleDB::where([['order_item_id',$item->id],['order_item_package_id',$package->id],['erp_order_sno',$erpOrderSno],['is_del',0]])->first();
                            if(!empty($sellItem)){
                                $sellNo = $sellItem->sell_no;
                                $sellQuantity = $sellItem->sell_quantity;
                                $sellQty = 1;
                            }
                        }
                        $remain -= $package->price;
                        $packageTotal += $package->total;
                        $split[] = [
                            'id' => $package->id,
                            'order_item_id' => $package->order_item_id,
                            'product_model_id' => $package->product_model_id,
                            'product_id' => $package->product_id,
                            'serving_size' => $package->serving_size,
                            'sku' => $package->sku,
                            'digiwin_no' => $package->digiwin_no,
                            'digiwin_payment_id' => $package->digiwin_payment_id,
                            'gross_weight' => $package->gross_weight,
                            'net_weight' => $package->net_weight,
                            'quantity' => $package->quantity,
                            'is_del' => $item->is_del,
                            'admin_memo' => $package->admin_memo,
                            'create_time' => $package->create_time,
                            'product_name' => $package->product_name,
                            'is_call' => $package->is_call,
                            'unit_name' => $package->unit_name,
                            'price' => $package->price,
                            'origin_price' => $package->origin_price,
                            'ratio' => $package->ratio,
                            'total' => $package->price * $package->quantity,
                            'sell_no' => $sellNo,
                            'erp_order_sno' => $erpOrderSno,
                            'sell_quantity' => $sellQuantity,
                        ];
                    }
                    $item->is_del == 0 ? $order->totalQty += $package->quantity : '';
                }
                $item->is_del == 0 ? $order->totalSellQty += $sellQty : '';
                for($i=1;$i<=count($split);$i++){
                    if($i == count($split)){
                        $split[$i-1]['total'] = $split[$i-1]['price'] += $itemTotalPrice - $packageTotal;
                    }
                }
                $item->split = $split;
            }else{
                $item->is_del == 0 ? $order->totalQty += $item->quantity : '';
                if(count($item->sells) > 0){
                    foreach($item->sells as $sell){
                        $item->is_del == 0 ? $order->totalSellQty += $sell->sell_quantity : '';
                    }
                }
            }
            $item->is_del == 0 ? $order->totalPrice += $item->price * $item->quantity : '';
            $item->is_del == 0 ? $order->totalWeight += $item->gross_weight * $item->quantity : '';
        }
        //金流支付 (付款金額 = 商品費+跨境稅+運費-使用購物金-折扣)
        $order->total_pay = $order->amount + $order->shipping_fee + $order->parcel_tax - $order->spend_point - $order->discount;

        return $order;
    }

    protected function getUnPurchaseSyncedOrderItemData($orderIds)
    {
        //這邊要將閃購對應商品資料轉換成實際廠商資料
        //vendor id = 239 or product name like 短效品
        $items = [];
        if(!empty($orderIds)){
            $syncedOrderItemTable = env('DB_DATABASE').'.'.(new SyncedOrderItemDB)->getTable();
            $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
            $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
            $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
            $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
            $items = SyncedOrderItemDB::join($orderTable,$orderTable.'.id',$syncedOrderItemTable.'.order_id')
                ->join($productModelTable,$productModelTable.'.id',$syncedOrderItemTable.'.product_model_id')
                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                ->whereNotIn($vendorTable.'.id',[723,729,730]) //排除你訂全部商品不顯示
                ->whereIn($syncedOrderItemTable.'.order_id',$orderIds)
                ->whereNull($syncedOrderItemTable.'.purchase_date')
                ->where($syncedOrderItemTable.'.is_del',0)
                ->where($syncedOrderItemTable.'.not_purchase',0)
                ->select([
                    $syncedOrderItemTable.'.purchase_price',
                    DB::raw("(CASE WHEN $syncedOrderItemTable.direct_shipment = 1 THEN '是' ELSE '否' END) as direct_shipment"),
                    $productTable.'.category_id as product_category_id',
                    $syncedOrderItemTable.'.vendor_arrival_date',
                    $productModelTable.'.id as product_model_id',
                    $productModelTable.'.vendor_product_model_id',
                    $productModelTable.'.sku',
                    DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                    $productTable.'.serving_size',
                    $vendorTable.'.id as vendor_id',
                    $vendorTable.'.name as vendor_name',
                    DB::raw("(CASE WHEN $vendorTable.id = 293 OR $productTable.name like '%短效品%' THEN (SELECT digiwin_no from $productModelTable where $productModelTable.id = vendor_product_model_id limit 1) ELSE $productModelTable.digiwin_no END) as digiwin_no"),
                    DB::raw("SUM($syncedOrderItemTable.quantity) as quantity"),
                    DB::raw("GROUP_CONCAT($orderTable.id) as orderIds"),
                    DB::raw("GROUP_CONCAT($syncedOrderItemTable.order_item_id) as orderItemIds"),
                    DB::raw("GROUP_CONCAT($syncedOrderItemTable.id) as syncedOrderItemIds"),
                ])->groupBy('vendor_arrival_date','digiwin_no','direct_shipment','purchase_price')
                ->orderBy('vendor_arrival_date','asc')->orderBy($vendorTable.'.id','asc')->get();
        }
        return $items;
    }

    protected function getPurchaseSyncedOrderItemData($orderIds)
    {
        $items = [];
        if(!empty($orderIds)){
            $syncedOrderItemTable = env('DB_DATABASE').'.'.(new SyncedOrderItemDB)->getTable();
            $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
            $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
            $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
            $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
            $items = SyncedOrderItemDB::join($orderTable,$orderTable.'.id',$syncedOrderItemTable.'.order_id')
                ->join($productModelTable,$productModelTable.'.id',$syncedOrderItemTable.'.product_model_id')
                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                ->whereIn($syncedOrderItemTable.'.order_id',$orderIds)
                ->whereNotNull($syncedOrderItemTable.'.purchase_date')
                ->where($syncedOrderItemTable.'.is_del',0)
                ->select([
                    $syncedOrderItemTable.'.*',
                    $productModelTable.'.sku',
                    $productModelTable.'.digiwin_no',
                    // $productTable.'.name as product_name',
                    DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                    $orderTable.'.order_number',
                    $orderTable.'.book_shipping_date',
                    $vendorTable.'.name as vendor_name',
                ])->orderBy($syncedOrderItemTable.'.purchase_no','asc')->get();
        }
        return $items;
    }

    protected function statusText($str){
        switch($str){
            case -1:return "後台取消訂單";break;
            case 0:return "尚未付款";break;
            case 1:return "已付款待出貨";break;
            case 2:return "訂單集貨中";break;
            case 3:return "訂單已出貨";break;
            case 4:return "訂單已完成";break;
        }
    }

    protected function checkNation($address){
        $blocks=[
            "中國","中国","北京","天津","上海","重慶","河北","山西","蒙古","遼寧","吉林",
            "黑龍江","江蘇","浙江","安徽","福建","江西","山東","河南","湖北","湖南","廣東",
            "廣西","海南","四川","貴州","雲南","陝西","甘肅","青海","西藏","寧夏","新疆",
            "重庆","辽宁","黑龙江","江苏","山东","广东","广西","贵州","云南","陕西","甘肃",
            "宁夏","台灣","澳門","新加坡","馬來西亞","香港","台湾","澳门","马来西亚",
            "HONG KONG","MACAU","Singapore","MALAYSIA"
        ];
        foreach($blocks as $b){
            if(stristr($address,$b)){
                return $b;
                break;
            }
        }
        return "台灣";
    }

    protected function country($receiver_address,$shipping_method,$receiver_tel){
        if(strstr($receiver_address,"中國")){
            return "中國";
        }else if(strstr($receiver_address,"香港") || strstr($receiver_address,"HK")){
            return "香港";
        }else if(strstr($receiver_address,"新加坡") || strstr($receiver_address,"SG")){
            return "新加坡";
        }else if(strstr($receiver_address,"馬來西亞")){
            return "馬來西亞";
        }else if(strstr($receiver_address,"台灣") || strstr($receiver_address,"7-11")){
            return "馬來西亞"; //其實是台灣公司叫的
        }
        if($shipping_method == "寄送台灣" || $shipping_method == "現場提貨"){
            return "馬來西亞"; //其實是台灣公司叫的
        }
        $receiver_tel = str_replace("+","",$receiver_tel);//先去掉+
        $receiver_tel = str_replace("-","",$receiver_tel);//先去掉-
        $receiver_tel_tmp = substr($receiver_tel,0,2);//判斷是否韓國(據說韓國人很多)
        if($receiver_tel_tmp == "82"){
            return "韓國";
        }else if($receiver_tel_tmp == "81"){
            return "日本";
        }else if($receiver_tel_tmp == "84"){
            return "越南";
        }else if($receiver_tel_tmp == "60"){
            return "馬來西亞";
        }else if($receiver_tel_tmp == "66"){
            return "泰國";
        }else if($receiver_tel_tmp == "62"){
            return "印尼";
        }else if($receiver_tel_tmp == "63"){
            return "菲律賓";
        }else if($receiver_tel_tmp == "91"){
            return "印度";
        }else if($receiver_tel_tmp == "86"){
            return "中國";
        }
        $receiver_tel_tmp = substr($receiver_tel,0,3);//判斷是否韓國(據說韓國人很多)
        if($receiver_tel_tmp == "852"){
            return "香港";
        }else if($receiver_tel_tmp == "853"){
            return "澳門";
        }else if($receiver_tel_tmp == "886"){
            return "馬來西亞"; //其實是台灣公司叫的
        }
        return "馬來西亞";//未知 直接丟馬來西亞
    }

    protected function phoneChange($phone)
    {
        //好巴這邊為何不用 str_replace 來直接取代掉886 因為你不知道是頭吃886
        //還是手機號碼吃886為了保險就只檢查表頭3碼再取表頭三碼以外的  這樣做法比較好
        //這邊由於格式超級不固定... 要去掉+ 886但有時候 886後面還有空白也要去掉
        $receiver_tel  = str_replace('+','',$phone);//先去掉+
        $receiver_tel_lengh = strlen($receiver_tel);//先取得長度(擷取使用)用這是保險
        $receiver_tel_tmp = '';
        $str_tmp = $receiver_tel_lengh-3;//台灣是三碼
            $receiver_tel_tmp = substr($receiver_tel,0,$str_tmp);
            if($receiver_tel_tmp=='886'){//就是香港
                $receiver_tel = substr($receiver_tel,3);//擷取完畢
            }
        $receiver_tel  = str_replace(' ','',$receiver_tel);//去除有空格的(有些+XXX 的問題)
        return $receiver_tel;
    }

    protected function receiverCountry($address,$memo){
        if(strstr($memo,'蝦皮訂單：(新加坡)')){
            return 'SG';
        }else if(strstr($address,'日本') || strstr($address,'JP')){
            return 'JP';
        }else if(strstr($address,'馬來西亞') || strstr($address,'MY')){
            return 'MY';
        }else if(strstr($address,'新加坡') || strstr($address,'SG')){
            return 'SG';
        }else{
            return '';
        }
    }

    protected function serverCode($address,$memo){
        if(strstr($memo,'蝦皮訂單：(新加坡)')){
            return 'SGETKSG';
        }else if(strstr($address,'日本') || strstr($address,'JP')){
            return 'JPEMSEP';
        }else if(strstr($address,'馬來西亞') || strstr($address,'MY')){
            return 'MYETKMY';
        }else if(strstr($address,'新加坡') || strstr($address,'SG')){
            return 'SGETKSG';
        }else{
            return '';
        }
    }

    protected function getDayWithWeek($str){
        $d=strtotime($str);
        $w=date("w",$d);
        $ary=explode(" ","日 一 二 三 四 五 六");
        $str=date("n/j",$d)."(".$ary[$w].")";
        return $str;
    }

    protected function checkNation2($str){
        $blocks = [
            '中國',
            '中国',
            '台灣',
            '澳門',
            '新加坡',
            '馬來西亞',
            '香港',
            '台湾',
            '澳门',
            '马来西亚',
            'HONG KONG',
            'MACAU',
            'Singapore',
            'MALAYSI',
        ];
        foreach($blocks as $b){
            if(stristr($str,$b)){
                return $b;
                break;
            }
        }
        return '台灣';
    }


    protected function priceCalculate($price,$type,$zeroFeeModify = 0) {
        $calculate = [];
        if($zeroFeeModify == 1){ //整張訂單金額為0
            $calculate['tax'] = $calculate['priceWithoutTax'] = $calculate['priceWithTax'] = 0;
        }else{
            if($type == 1){
                $calculate['tax'] = round(round($price / 1.05,0) * 0.05,0);
                $calculate['priceWithoutTax'] = round($price - $calculate['tax'],0);
                $calculate['priceWithTax'] = round($price,0);
            }elseif($type == 2){
                $calculate['tax'] = round($price * 0.05,0);
                $calculate['priceWithoutTax'] = round($price,0);
                $calculate['priceWithTax'] = round($price + $calculate['tax'],0);
            }elseif($type == 3){
                $calculate['tax'] = 0;
                $calculate['priceWithoutTax'] = round($price,0);
                $calculate['priceWithTax'] = round($price,0);
            }elseif($type == 9){
                $calculate['tax'] = 0;
                $calculate['priceWithoutTax'] = round($price,0);
                $calculate['priceWithTax'] = round($price,0);
            }
        }
        return $calculate;
    }

    //挑選物流
    protected function return_shipping_vendor($row){
        $shipping_vendor = null;
        $faraway = array("三星鄉","大同鄉","南澳鄉","光復鄉","玉里鎮","鳳林鎮","豐濱鄉","瑞穗鄉","富里鄉","萬榮鄉","卓溪鄉","壽豐鄉","秀林鄉","萬里區","金山區","坪林區","烏來區","平溪區","雙溪區","貢寮區","石碇區","石門區","三芝區","復興區","新竹縣","北埔鄉","峨嵋鄉","峨眉鄉","尖石鄉","五峰鄉","橫山鄉","寶山鄉","關西鎮","南庄鄉","獅潭鄉","大湖鄉","泰安鄉","卓蘭鎮","東勢區","和平區","新社區","集集鎮","中寮鄉","國姓鄉","信義鄉","仁愛鄉","鹿谷鄉","魚池鄉","水里鄉","褒忠鄉","東勢鄉","台西鄉","麥寮鄉","水林鄉","四湖鄉","口湖鄉","溪州鄉","竹塘鄉","二林鄉","大城鄉","番路鄉","梅山鄉","阿里山鄉","大埔鄉","東石鄉","義竹鄉","布袋鎮","左鎮區","南化區","龍崎區","布袋鎮","七股區","將軍區","白河區","東山區","北門區","玉井區","楠西區","大內區","田寮區","六龜區","甲仙區","杉林區","內門區","那瑪夏區","茂林區","桃源區","滿州鄉","霧臺鄉","瑪家鄉","泰武鄉","來義鄉","春日鄉","獅子鄉","牡丹鄉","三地門鄉","萬巒鄉","林邊鄉","佳冬鄉","枋山鄉","車城鄉","里港鄉","高樹鄉","麟洛鄉","崁頂鄉","新埤鄉","南州鄉","東港鎮","新園鄉","枋寮鄉","恆春鎮","竹田鄉","成功鎮","關山鎮","大武鄉","東河鄉","長濱鄉","鹿野鄉","池上鄉","延平鄉","海端鄉","達仁鄉","金峰鄉","太麻里鄉");;
        $row['receiver_address']=str_replace("鄕","鄉",$row['receiver_address']);//因為Asiamiles的鄉是這個鄕，FUCK YO
        //$row["shipping_method"],$row["receiver_address"],$row["ship_to"],$row["user_memo"],$row["create_type"]
        if($row["shipping_method"]==1){
            $shipping_vendor="台灣宅配通";
        }elseif($row["shipping_method"]==2){
            $shipping_vendor="黑貓宅急便";
            foreach($faraway as $value){
                if(strstr($row["receiver_address"],$value)){
                    $shipping_vendor="黑貓宅急便";
                    break;
                }
            }
        }elseif($row["shipping_method"]==4){
            if($row["ship_to"]=="台灣" || ($row["ship_to"]=="" && strstr($row["receiver_address"],"台灣"))){
                $shipping_vendor="順豐-台灣";
            }elseif($row["ship_to"]=="香港" || ($row["ship_to"]=="" && strstr($row["receiver_address"],"香港"))){
                $shipping_vendor="順豐-香港";
            }elseif($row["ship_to"]=="澳門" || ($row["ship_to"]=="" && strstr($row["receiver_address"],"澳門"))){
                $shipping_vendor="順豐-澳門";
            }elseif($row["ship_to"]=="新加坡" || ($row["ship_to"]=="" && strstr($row["receiver_address"],"新加坡"))){
                $shipping_vendor="順豐-新加坡";
            }elseif($row["ship_to"]=="馬來西亞" || ($row["ship_to"]=="" && strstr($row["receiver_address"],"馬來西亞"))){
                $shipping_vendor="順豐-馬來西亞";
            }elseif($row["ship_to"]=="中國" || ($row["ship_to"]=="" && strstr($row["receiver_address"],"中國"))){
                $shipping_vendor="順豐速運";
            }elseif($row["ship_to"]=="美國" || $row["ship_to"]=="加拿大" || $row["ship_to"]=="澳洲" || $row["ship_to"]=="紐西蘭"|| ($row["ship_to"]=="" && (strstr($row["receiver_address"],"美國") || strstr($row["receiver_address"],"加拿大") || strstr($row["receiver_address"],"澳洲") || strstr($row["receiver_address"],"紐西蘭")))){
                $shipping_vendor="DHL";
            }elseif($row["ship_to"]=="英國" || $row["ship_to"]=="法國" || $row["ship_to"]=="越南" || ($row["ship_to"]=="" && (strstr($row["receiver_address"],"英國") || strstr($row["receiver_address"],"法國") || strstr($row["receiver_address"],"越南")))){
                if(empty($row["ship_to"])){
                    if(strstr($row["receiver_address"],"英國")){
                        $row["ship_to"]="英國";
                    }elseif(strstr($row["receiver_address"],"法國")){
                        $row["ship_to"]="法國";
                    }elseif(strstr($row["receiver_address"],"越南")){
                        $row["ship_to"]="越南";
                    }
                }
                $shipping_vendor="LINEX-{$row["ship_to"]}";
            }elseif($row["ship_to"]=="泰國" || $row["ship_to"]=="泰國-曼谷" || ($row["ship_to"]=="" && (strstr($row["receiver_address"],"泰國") || strstr($row["receiver_address"],"泰國-曼谷")))){
                $shipping_vendor="LINEX-泰國";
            }elseif($row["ship_to"]=="南韓"	|| ($row["ship_to"]=="" && strstr($row["receiver_address"],"南韓"))){
                $row["ship_to"]="南韓";
                // $shipping_vendor="順豐-南韓";
                $shipping_vendor="DHL";
            }elseif($row["ship_to"]=="日本"	|| ($row["ship_to"]=="" && strstr($row["receiver_address"],"日本"))){
                $row["ship_to"]="日本";
                $shipping_vendor="順豐-日本";
            }

            if($row["create_type"]=="momo" || $row["create_type"]=="Momo"){
                $shipping_vendor="momo-宅急便";
            }
            if($row["ship_to"]=="中國" || (strstr($row["receiver_address"],"中國") && $row['ship_to'] != '澳門' && $row['ship_to'] != '香港')){
                $shipping_vendor="順豐-中國";
            }

        }elseif($row["shipping_method"]==6){
            $shipping_vendor="順豐-台灣";
            foreach($faraway as $value){
                if(strstr($row["receiver_address"],$value)){
                    $shipping_vendor="黑貓宅急便";
                    break;
                }
            }

            if($row["create_type"]=="shopee" && $row["ship_to"]=="台灣" && strstr($row["user_memo"],"台灣") && strstr($row["receiver_address"],"7-*")){
                $shipping_vendor="7-11 大智通";
            }elseif($row["create_type"]=="松果" && strstr($row["receiver_address"],"台灣 7-11")){
                $shipping_vendor="7-11 大智通";
            }elseif($row["create_type"]=="shopee" && $row["ship_to"]=="台灣" && strstr($row["user_memo"],"台灣") && strstr($row["receiver_address"],"全家")){
                $shipping_vendor="全家 日翊";
            }elseif($row["create_type"]=="shopee" && $row["ship_to"]=="台灣" && strstr($row["user_memo"],"台灣") && strstr($row["receiver_address"],"萊爾")){
                $shipping_vendor="萊爾富";
            }
            if($row["create_type"]=="momo" && $row["ship_to"]=="台灣"){
                $shipping_vendor="momo-宅急便";
            }elseif($row["create_type"]=="Momo" && $row["ship_to"]=="台灣"){
                $shipping_vendor="momo-宅急便";
            }
        }elseif($row["shipping_method"]==5){
            $shipping_vendor="順豐-台灣";
            foreach($faraway as $value){
                if(strstr($row["receiver_address"],$value)){
                    $shipping_vendor="黑貓宅急便";
                    break;
                }
            }
            if($row["create_type"]=="shopee" && strstr($row["user_memo"],"台灣") && strstr($row["receiver_address"],"7-")){
                $shipping_vendor="7-11 大智通";
            }elseif($row["create_type"]=="松果" && strstr($row["receiver_address"],"台灣 7-11")){
                $shipping_vendor="7-11 大智通";
            }elseif($row["create_type"]=="shopee" && strstr($row["user_memo"],"台灣") && strstr($row["receiver_address"],"全家")){
                $shipping_vendor="全家 日翊";
            }elseif($row["create_type"]=="松果" && strstr($row["receiver_address"],"全家")){
                $shipping_vendor="全家 日翊";
            }elseif($row["create_type"]=="shopee" && strstr($row["user_memo"],"台灣") && strstr($row["receiver_address"],"萊爾")){
                $shipping_vendor="萊爾富";
            }
            strtolower($row["create_type"]) =="momo" ? $shipping_vendor="momo-宅急便" : '';
        }
        $row["create_type"] == '酷澎' ? $shipping_vendor="黑貓宅急便" : '';
        return $shipping_vendor;
    }
}
