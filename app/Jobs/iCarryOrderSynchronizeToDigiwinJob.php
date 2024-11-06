<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\SyncedOrder as SyncedOrderDB;
use App\Models\ErpCustomer as CustomerDB;
use App\Models\ErpOrder as ErpOrderDB;
use App\Models\ErpProduct as ErpProductDB;
use App\Models\ErpOrderItem as ErpOrderItemDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\SyncedOrderItemPackage as SyncedOrderItemPackageDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\ErpACRTA as ErpACRTADB;
use App\Models\ErpACRTB as ErpACRTBDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\SyncedOrderError as SyncedOrderErrorDB;
use App\Models\iCarryTicket as TicketDB;
use App\Models\SerialNoRecord as SerialNoRecordDB;
use App\Models\AccountingHoliday as AccountingHolidayDB;
use DB;
use Carbon\Carbon;

use App\Traits\OrderFunctionTrait;

class iCarryOrderSynchronizeToDigiwinJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,OrderFunctionTrait;

    protected $param;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $iCarryWeb = ['001','002','003','004','005','006','007','008','009','037','063','072','073','086','AC0002','AC000201','AC000202'];
        //找出訂單資料, 包含分拆資料
        $orders = $this->orderItemSplit($this->itemTransfer($this->getOrderData($this->param,'Synchronize')));
        !empty(auth('gate')->user()->account) ? $creator = strtoupper(auth('gate')->user()->account) : $creator = strtoupper($this->param['admin_name']);
        !empty(auth('gate')->user()->id) ? $adminId = auth('gate')->user()->id : $adminId = $this->param['admin_id'];
        $createDate = date('Ymd');
        $createDate6 = date('ymd');
        $createTime = date('H:i:s');
        $orderData = [];
        $itemData = [];
        $c = 1;
        foreach($orders as $order){
            //預收結帳單單別設定
            in_array($order->digiwin_payment_id,['AC0002','AC000201','AC000202']) ? $checkOutTB001 = 'A645' : $checkOutTB001 = 'A641';
            //預收待抵單單別設定
            in_array($order->digiwin_payment_id,['AC0002','AC000201','AC000202']) ? $cancelOutTB001 = 'A655' : $cancelOutTB001 = 'A651';
            //扣款結帳單單別設定
            in_array($order->digiwin_payment_id,['AC0002','AC000201','AC000202']) ? $refundTB001 = 'A620' : $refundTB001 = 'A616';
            //單據號碼(付款當日)
            $orderDate = explode(' ',str_replace('-','',$order->pay_time))[0];
            $orderDate6 = substr(explode(' ',str_replace('-','',$order->pay_time))[0],2); //單據日期六碼

            //找出鼎新單據號碼當日最後一筆訂單號碼的流水號
            $chkTemp = SerialNoRecordDB::where([['type','ErpOrderNo'],['serial_no','like',"$orderDate6%"]])->orderBy('serial_no','desc')->first();
            !empty($chkTemp) ? $erpOrderNo = $chkTemp->serial_no + 1 : $erpOrderNo = $orderDate6.str_pad(1,5,0,STR_PAD_LEFT);
            $chkTemp = SerialNoRecordDB::create(['type' => 'ErpOrderNo','serial_no' => $erpOrderNo]);
            //檢查鼎新銷貨單有沒有這個號碼
            $tmp = ErpOrderDB::where('TC002','like',"%$orderDate6%")->select('TC002')->orderBy('TC002','desc')->first();
            if(!empty($tmp)){
                if($tmp->TC002 >= $erpOrderNo){
                    $erpOrderNo = $tmp->TC002+1;
                    $chkTemp = SerialNoRecordDB::create(['type' => 'ErpOrderNo','serial_no' => $erpOrderNo]);
                }
            }

            if(in_array($order->digiwin_payment_id,$iCarryWeb)){
                //找出鼎新單據號碼當日最後一筆預收結帳單單號
                $chkTemp = SerialNoRecordDB::where([['type','TB002No'],['serial_no','like',"$orderDate6%"]])->orderBy('serial_no','desc')->first();
                !empty($chkTemp) ? $TB002No = $chkTemp->serial_no + 1 : $TB002No = $orderDate6.str_pad(1,5,0,STR_PAD_LEFT);
                $chkTemp = SerialNoRecordDB::create(['type' => 'TB002No','serial_no' => $TB002No]);
                //檢查鼎新預收結帳單有沒有這個號碼
                $tmp = ErpACRTBDB::where('TB002','like',"%$orderDate6%")->select('TB002')->orderBy('TB002','desc')->first();
                if(!empty($tmp)){
                    if($tmp->TB002 >= $TB002No){
                        $TB002No = $tmp->TB002+1;
                        $chkTemp = SerialNoRecordDB::create(['type' => 'TB002No','serial_no' => $TB002No]);
                    }
                }

                //找出單據號碼當日最後一筆預收待底單單號(暫時用不到)
                $TB002_A651 = ErpACRTBDB::where([['TB002','like',"$orderDate6%"],['TB001',$cancelOutTB001]])->select('TB002')->orderBy('TB002','desc')->first();
                !empty($TB002_A651) ? $TB002A651No = $TB002_A651->TB002 + 1 : $TB002A651No = $orderDate6.str_pad(1,5,0,STR_PAD_LEFT);;

                //檢查鼎新扣款結帳單有沒有這個號碼
                $TB002_A616 = ErpACRTBDB::where([['TB002','like',"$createDate6%"],['TB001',$refundTB001]])->select('TB002')->orderBy('TB002','desc')->first();
                !empty($TB002_A616) ? $TB002A616No = $TB002_A616->TB002 + 1 : $TB002A616No = $createDate6.str_pad(1,5,0,STR_PAD_LEFT);
            }
            //以下客戶代號 TD001 = A222 其餘 A221
            in_array($order->digiwin_payment_id,$iCarryWeb) ? $TD001 = 'A222' : $TD001 = 'A221';
            $originTotalPrice = $totalItemQuantity = $totalPrice = $tax = $returnPrice = 0;
            $originTotalPrice = $order->amount + $order->shipping_fee + $order->parcel_tax - $order->discount - $order->spend_point;
            strstr($order->shipping_memo,'廠商發貨') ? $TD007 = 'W02' : $TD007 = 'W01';
            strstr($order->digiwin_payment_id,'089') ? $TD001 = 'A227' : '';
            if(!empty($order->customer)){
                $MA038 = $order->customer['MA038'];
                //統計直寄數量
                $totalItemQty = $chkItemPurchasePrice = $directShipQty = 0;
                foreach($order->items as $it){
                    //採購價若為空值則補
                    if(empty($it->purchase_price)){
                        $itemPurchasePrice = 0;
                        if($it->vendor_price > 0 ){
                            $itemPurchasePrice = $it->vendor_price;
                        }else{
                            if(!empty($it->service_fee)){
                                $it->service_fee = str_replace('"percent":}','"percent":0}',$it->service_fee);
                                $tmp = json_decode($it->service_fee);
                                foreach($tmp as $t){
                                    if ($t->name == 'iCarry') {
                                        $percent = $t->percent;
                                        break;
                                    }
                                }
                                $itemPurchasePrice = $it->price - $it->price * ( $percent / 100 );
                            }
                        }
                        $it->purchase_price = $itemPurchasePrice;
                        OrderItemDB::find($it->id)->update(['purchase_price' => $itemPurchasePrice]);
                    }
                    if($it->is_del==0){
                        $totalItemQty += $it->quantity;
                    }
                    if(count($order->syncedItems) > 0){ //檢查是否修改採購價
                        foreach($order->syncedItems as $si){
                            if($si->order_item_id == $it->id && $si->purchase_price != $it->purchase_price){
                                $chkItemPurchasePrice++;
                            }
                        }
                    }
                    if(strstr($it->sku,'BOM')){
                        foreach($it->package as $pp){
                            if($pp->direct_shipment == 1){
                                $directShipQty += $pp->quantity;
                            }
                        }
                    }else{
                        if($it->direct_shipment == 1){
                            $directShipQty += $it->quantity;
                        }
                    }
                }
                if (!empty($order->erpOrder)) {
                    $erpOrderNo = $order->erpOrder->TC002;
                    $balance = $order->syncedOrder['balance']; //上次餘額
                    //上次餘額不等於0 且不等於這次總金額 或者記錄狀態不等於-1且訂當狀態等於-1 或者紀錄與訂單的廠商預期到貨日不相等, 或者 $directShipQty 與 之前不同
                    if(($order->syncedOrder['balance'] != $originTotalPrice && $order->syncedOrder['balance'] != 0 ) || ($order->syncedOrder['status'] != -1 && $order->status == -1) || $order->syncedOrder['book_shipping_date'] != $order->book_shipping_date || $order->syncedOrder['vendor_arrival_date'] != $order->vendor_arrival_date || $directShipQty != $order->syncedOrder['direct_ship_quantity'] || $chkItemPurchasePrice != 0 || ($totalItemQty != $order->syncedOrder['total_item_quantity'] && $order->syncedOrder['total_item_quantity'] != null)){
                        if($order->status == -1){ //訂單整張取消時
                            //將鼎新商品全部取消
                            foreach($order->erpOrder->items as $erpItem){
                                $erpItem->update(['TD016' => 'y']);
                            }
                            $returnPrice = $originTotalPrice;
                            $balance = 0;
                            //取消尚未採買的商品 2022.10.12 改 不管有沒有採購都取消同步資料
                            foreach ($order->syncedItems as $item) {
                                if(count($item->package) > 0){
                                    foreach($item->package as $package){
                                        $package->update(['is_del'=>1]);
                                    }
                                }else{
                                    $item->update(['is_del'=>1]);
                                }
                            }
                        }else{ //部分變更
                            $returnPrice = $balance - $originTotalPrice;
                            $balance = $balance - $returnPrice;
                            //刪除鼎新訂單全部商品
                            foreach($order->erpOrder->forSyncItems as $erpItem){
                                if (!in_array($order->digiwin_payment_id, $iCarryWeb)) {
                                    $erpItem->delete();
                                }else{
                                    if($erpItem->TD016 != 'y'){
                                        $erpItem->delete();
                                    }
                                }
                            }
                            //重新建立訂單商品
                            $i = 0;
                            $itemData = $orderItems = $orderItemPackages = [];
                            foreach($order->items as $item){
                                $chk = SyncedOrderItemDB::where([['order_item_id',$item->id],['order_id',$order->id],['erp_order_no',$erpOrderNo]])->first();
                                //檢查item是否為票券
                                $chkTicket = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                ->where($productModelTable.'.digiwin_no',$item->digiwin_no)
                                ->where($productTable.'.category_id',17)->first();
                                if(!empty($chkTicket) || $item->category_id == 18){
                                    $TD007 = 'W14';
                                }else{
                                    $item->direct_shipment == 1 ? $TD007 = 'W02' : $TD007 = 'W01';
                                    $item->shipping_memo == 'momo-新莊' ? $TD007 = 'W15' : '';
                                    $item->shipping_memo == '順豐-新莊' ? $TD007 = 'W15' : '';
                                    $item->shipping_memo == '黑貓-新莊' ? $TD007 = 'W15' : '';
                                }
                                strstr($order->digiwin_payment_id,'AC') ? $TD007 = 'W16' : '';
                                strstr($order->digiwin_payment_id,'089') ? $TD007 = 'W17' : '';
                                //設定空變數
                                if(!empty($item->package) && count($item->package) > 0){
                                    foreach($item->package as $package){
                                        $snos[$package->id] = [];
                                    }
                                }
                                //拆分商品
                                if(!empty($item->split)){ //組合商品
                                        $package = $item->split;
                                        for($x=0;$x<count($package);$x++){
                                            $snos[$package[$x]['id']][] = str_pad(($i+1),4,0,STR_PAD_LEFT);
                                            $order->status >= 1 ? $item->is_del == 1 || $package[$x]['quantity'] == 0 ? $close = 'y' : $close = 'N' : $close = 'y';
                                            $totalItemQuantity += $package[$x]['quantity'];
                                            $itemData[] = [
                                                'COMPANY' => 'iCarry',
                                                'CREATOR' => $creator,
                                                'USR_GROUP' => 'DSC',
                                                'CREATE_DATE' => $createDate,
                                                'FLAG' => 1,
                                                'CREATE_TIME' => $createTime,
                                                'CREATE_AP' => 'iCarry',
                                                'CREATE_PRID' => 'COPI06',
                                                'TD001' => $TD001,
                                                'TD002' => $erpOrderNo,
                                                'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                                'TD004' => $package[$x]['digiwin_no'], //品號 product_model的digiwin_no
                                                'TD005' => mb_substr($package[$x]['product_name'],0,110,'utf8'), //品名 用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB002
                                                'TD006' => mb_substr($package[$x]['serving_size'],0,110,'utf8'), //用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB003
                                                'TD007' => $TD007,
                                                'TD008' => $package[$x]['quantity'], //訂單數量 依拆分後數量
                                                'TD009' => 0,
                                                'TD010' => $item->unit_name, //單位 product.unit_name
                                                'TD011' => round($package[$x]['price'],2), //單價 order_item.price
                                                'TD012' => intval($package[$x]['price'] * $package[$x]['quantity']), //金額 order_item.price * order_item.quantity
                                                'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                                'TD016' => $close, //結案碼 DEF 'N'
                                                'TD020' => $order->user_memo, //顧客備註
                                                'TD021' => 'Y', //確認碼 DEF 'Y'
                                                'TD022' => 0, //庫存數量 DEF '0'
                                                'TD024' => 0, //贈品量 DEF '0'
                                                'TD025' => 0, //贈品已交量 DEF '0'
                                                'TD026' => 1, //折扣率 DEF '0'
                                                'TD030' => null, //毛重(Kg) order_item.gross_weight
                                                'TD031' => 0,
                                                'TD032' => 1,
                                                'TD033' => 0,
                                                'TD034' => 0,
                                                'TD035' => 0,
                                                'TD036' => 0,
                                            ];
                                            $i++;
                                        }
                                }else{ //單品
                                        $order->status >= 1 ? $item->is_del == 1 || $item->quantity == 0 ? $close = 'y' : $close = 'N' : $close = 'y';
                                        $totalItemQuantity += $item->quantity;
                                        $itemData[] = [
                                            'COMPANY' => 'iCarry',
                                            'CREATOR' => $creator,
                                            'USR_GROUP' => 'DSC',
                                            'CREATE_DATE' => $createDate,
                                            'FLAG' => 1,
                                            'CREATE_TIME' => $createTime,
                                            'CREATE_AP' => 'iCarry',
                                            'CREATE_PRID' => 'COPI06',
                                            'TD001' => $TD001,
                                            'TD002' => $erpOrderNo,
                                            'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                            'TD004' => $item->digiwin_no, //品號 product_model的digiwin_no
                                            'TD005' => mb_substr($item->erpProduct['MB002'],0,110,'utf8'), //品名 用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB002
                                            'TD006' => mb_substr($item->erpProduct['MB003'],0,110,'utf8'), //用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB003
                                            'TD007' => $TD007,
                                            'TD008' => $item->quantity, //訂單數量 依拆分後數量
                                            'TD009' => 0,
                                            'TD010' => $item->unit_name, //單位 product.unit_name
                                            'TD011' => round($item->price,2), //單價 order_item.price
                                            'TD012' => intval($item->price * $item->quantity), //金額 order_item.price * order_item.quantity
                                            'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                            'TD016' => $close, //結案碼 DEF 'N'
                                            'TD020' => $order->user_memo, //顧客備註
                                            'TD021' => 'Y', //確認碼 DEF 'Y'
                                            'TD022' => 0, //庫存數量 DEF '0'
                                            'TD024' => 0, //贈品量 DEF '0'
                                            'TD025' => 0, //贈品已交量 DEF '0'
                                            'TD026' => 1, //折扣率 DEF '0'
                                            'TD030' => $item->gross_weight, //毛重(Kg) order_item.gross_weight
                                            'TD031' => 0,
                                            'TD032' => 1,
                                            'TD033' => 0,
                                            'TD034' => 0,
                                            'TD035' => 0,
                                            'TD036' => 0,
                                        ];
                                        if(!empty($chk)){
                                            $chk->update([
                                                'erp_order_sno' => str_pad(($i+1),4,0,STR_PAD_LEFT),
                                                'quantity' => $item->quantity,
                                                'direct_shipment' => $item->direct_shipment,
                                                'not_purchase' => $item->not_purchase,
                                                'is_del' => $item->is_del,
                                                'purchase_price' => $item->purchase_price,
                                                'vendor_arrival_date' => $order->vendor_arrival_date,
                                            ]);
                                        }else{
                                            $orderItems[] = [
                                                'order_item_id' => $item->id,
                                                'order_id' => $order->id,
                                                'erp_order_no' => $erpOrderNo,
                                                'erp_order_sno' => str_pad(($i+1),4,0,STR_PAD_LEFT),
                                                'product_model_id' => $item->product_model_id,
                                                'unit_name' => $item->unit_name,
                                                'gross_weight' => $item->gross_weight,
                                                'net_weight' => $item->net_weight,
                                                'purchase_price' => $item->purchase_price,
                                                'price' => $item->price,
                                                'quantity' => $item->quantity,
                                                'direct_shipment' => $item->direct_shipment,
                                                'not_purchase' => $item->not_purchase,
                                                'is_del' => $item->is_del,
                                                'created_at' => date('Y-m-d H:i:s'),
                                                'book_shipping_date' => $order->book_shipping_date,
                                                'vendor_arrival_date' => $order->vendor_arrival_date,
                                            ];
                                        }
                                        $i++;
                                }
                                if(strstr($item->sku,'BOM')){
                                    foreach($item->package as $package){
                                        $pp = SyncedOrderItemPackageDB::where([['order_id',$order->id],['order_item_id',$item->id],['order_item_package_id',$package->id]])->first();
                                        if(!empty($pp)){
                                            $pp->update([
                                                'erp_order_sno' => join(',',$snos[$package->id]),
                                                'quantity' => $package->quantity,
                                                'direct_shipment' => $item->direct_shipment,
                                                'is_del' => $item->is_del,
                                            ]);
                                        }else{
                                            $orderItemPackages[] = [
                                                'order_item_id' => $item->id,
                                                'order_item_package_id' => $package->id,
                                                'order_id' => $order->id,
                                                'erp_order_no' => $erpOrderNo,
                                                'erp_order_sno' => join(',',$snos[$package->id]),
                                                'product_model_id' => $package->product_model_id,
                                                'unit_name' => $package->unit_name,
                                                'gross_weight' => $package->gross_weight,
                                                'net_weight' => $package->net_weight,
                                                'price' => $package->price,
                                                'purchase_price' => $package->purchase_price,
                                                'quantity' => $package->quantity,
                                                'direct_shipment' => $item->direct_shipment,
                                                'is_del' => $item->is_del,
                                                'created_at' => date('Y-m-d H:i:s'),
                                                'vendor_arrival_date' => $order->vendor_arrival_date,
                                            ];
                                        }
                                    }
                                    if(!empty($chk)){
                                        $chk->update([
                                            'quantity' => $item->quantity,
                                            'direct_shipment' => $item->direct_shipment,
                                            'is_del' => $item->is_del,
                                            'purchase_price' => $item->purchase_price,
                                            'not_purchase' => $item->not_purchase,
                                            'vendor_arrival_date' => $order->vendor_arrival_date,
                                        ]);
                                    }else{
                                        $orderItems[] = [
                                            'order_item_id' => $item->id,
                                            'order_id' => $order->id,
                                            'erp_order_no' => $erpOrderNo,
                                            'erp_order_sno' => str_pad(($i+1),4,0,STR_PAD_LEFT),
                                            'product_model_id' => $item->product_model_id,
                                            'unit_name' => $item->unit_name,
                                            'gross_weight' => $item->gross_weight,
                                            'net_weight' => $item->net_weight,
                                            'purchase_price' => $item->purchase_price,
                                            'price' => $item->price,
                                            'quantity' => $item->quantity,
                                            'direct_shipment' => $item->direct_shipment,
                                            'not_purchase' => $item->not_purchase,
                                            'is_del' => $item->is_del,
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'book_shipping_date' => $order->book_shipping_date,
                                            'vendor_arrival_date' => $order->vendor_arrival_date,
                                        ];
                                    }
                                }
                            }
                            //iCarry訂單還未進鼎新時就已經被取消, 此時需要將此訂單內所有商品結案
                            $order->status == -1 ? $close = 'y' : $close = 'N';
                            //活動折扣
                            if($order->discount != 0){
                                $totalItemQuantity++;
                                $itemData[] = [
                                    'COMPANY' => 'iCarry',
                                    'CREATOR' => $creator,
                                    'USR_GROUP' => 'DSC',
                                    'CREATE_DATE' => $createDate,
                                    'FLAG' => 1,
                                    'CREATE_TIME' => $createTime,
                                    'CREATE_AP' => 'iCarry',
                                    'CREATE_PRID' => 'COPI06',
                                    'TD001' => $TD001,
                                    'TD002' => $erpOrderNo,
                                    'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                    'TD004' => '999000', //品號 product_model的digiwin_no
                                    'TD005' => '活動折扣', //品名
                                    'TD006' => '', //規格
                                    'TD007' => 'W07',
                                    'TD008' => 1,
                                    'TD009' => 0,
                                    'TD010' => '個', //單位
                                    'TD011' => -($order->discount), //單價
                                    'TD012' => -($order->discount), //金額
                                    'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                    'TD016' => $close, //結案碼 DEF 'N'
                                    'TD020' => $order->user_memo, //顧客備註
                                    'TD021' => 'Y', //確認碼 DEF 'Y'
                                    'TD022' => 0, //庫存數量 DEF '0'
                                    'TD024' => 0, //贈品量 DEF '0'
                                    'TD025' => 0, //贈品已交量 DEF '0'
                                    'TD026' => 1, //折扣率 DEF '0'
                                    'TD030' => null, //毛重(Kg) order_item.gross_weight
                                    'TD031' => 0,
                                    'TD032' => 1,
                                    'TD033' => 0,
                                    'TD034' => 0,
                                    'TD035' => 0,
                                    'TD036' => 0,
                                ];
                                $i++;
                            }
                            //購物金
                            if($order->spend_point > 0){
                                $totalItemQuantity++;
                                $itemData[] = [
                                    'COMPANY' => 'iCarry',
                                    'CREATOR' => $creator,
                                    'USR_GROUP' => 'DSC',
                                    'CREATE_DATE' => $createDate,
                                    'FLAG' => 1,
                                    'CREATE_TIME' => $createTime,
                                    'CREATE_AP' => 'iCarry',
                                    'CREATE_PRID' => 'COPI06',
                                    'TD001' => $TD001,
                                    'TD002' => $erpOrderNo,
                                    'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                    'TD004' => '999001', //品號 product_model的digiwin_no
                                    'TD005' => '購物金', //品名
                                    'TD006' => '', //規格
                                    'TD007' => 'W07',
                                    'TD008' => 1,
                                    'TD009' => 0,
                                    'TD010' => '個', //單位
                                    'TD011' => -($order->spend_point), //單價
                                    'TD012' => -($order->spend_point), //金額
                                    'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                    'TD016' => $close, //結案碼 DEF 'N'
                                    'TD020' => $order->user_memo, //顧客備註
                                    'TD021' => 'Y', //確認碼 DEF 'Y'
                                    'TD022' => 0, //庫存數量 DEF '0'
                                    'TD024' => 0, //贈品量 DEF '0'
                                    'TD025' => 0, //贈品已交量 DEF '0'
                                    'TD026' => 1, //折扣率 DEF '0'
                                    'TD030' => null, //毛重(Kg) order_item.gross_weight
                                    'TD031' => 0,
                                    'TD032' => 1,
                                    'TD033' => 0,
                                    'TD034' => 0,
                                    'TD035' => 0,
                                    'TD036' => 0,
                                ];
                                $i++;
                            }
                            //運費
                            if($order->shipping_fee > 0){
                                $totalItemQuantity++;
                                $itemData[] = [
                                    'COMPANY' => 'iCarry',
                                    'CREATOR' => $creator,
                                    'USR_GROUP' => 'DSC',
                                    'CREATE_DATE' => $createDate,
                                    'FLAG' => 1,
                                    'CREATE_TIME' => $createTime,
                                    'CREATE_AP' => 'iCarry',
                                    'CREATE_PRID' => 'COPI06',
                                    'TD001' => $TD001,
                                    'TD002' => $erpOrderNo,
                                    'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                    'TD004' => '901001', //品號 product_model的digiwin_no
                                    'TD005' => '運費', //品名
                                    'TD006' => '', //規格
                                    'TD007' => 'W07',
                                    'TD008' => 1,
                                    'TD009' => 0,
                                    'TD010' => '個', //單位
                                    'TD011' => $order->shipping_fee, //單價
                                    'TD012' => $order->shipping_fee, //金額
                                    'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                    'TD016' => $close, //結案碼 DEF 'N'
                                    'TD020' => $order->user_memo, //顧客備註
                                    'TD021' => 'Y', //確認碼 DEF 'Y'
                                    'TD022' => 0, //庫存數量 DEF '0'
                                    'TD024' => 0, //贈品量 DEF '0'
                                    'TD025' => 0, //贈品已交量 DEF '0'
                                    'TD026' => 1, //折扣率 DEF '0'
                                    'TD030' => null, //毛重(Kg) order_item.gross_weight
                                    'TD031' => 0,
                                    'TD032' => 1,
                                    'TD033' => 0,
                                    'TD034' => 0,
                                    'TD035' => 0,
                                    'TD036' => 0,
                                ];
                                $i++;
                            }
                            //行郵稅
                            if($order->parcel_tax > 0){
                                $totalItemQuantity++;
                                $itemData[] = [
                                    'COMPANY' => 'iCarry',
                                    'CREATOR' => $creator,
                                    'USR_GROUP' => 'DSC',
                                    'CREATE_DATE' => $createDate,
                                    'FLAG' => 1,
                                    'CREATE_TIME' => $createTime,
                                    'CREATE_AP' => 'iCarry',
                                    'CREATE_PRID' => 'COPI06',
                                    'TD001' => $TD001,
                                    'TD002' => $erpOrderNo,
                                    'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                    'TD004' => '901002', //品號 product_model的digiwin_no
                                    'TD005' => '行郵稅', //品名
                                    'TD006' => '', //規格
                                    'TD007' => 'W07',
                                    'TD008' => 1,
                                    'TD009' => 0,
                                    'TD010' => '個', //單位
                                    'TD011' => $order->parcel_tax, //單價
                                    'TD012' => $order->parcel_tax, //金額
                                    'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                    'TD016' => $close, //結案碼 DEF 'N'
                                    'TD020' => $order->user_memo, //顧客備註
                                    'TD021' => 'Y', //確認碼 DEF 'Y'
                                    'TD022' => 0, //庫存數量 DEF '0'
                                    'TD024' => 0, //贈品量 DEF '0'
                                    'TD025' => 0, //贈品已交量 DEF '0'
                                    'TD026' => 1, //折扣率 DEF '0'
                                    'TD030' => null, //毛重(Kg) order_item.gross_weight
                                    'TD031' => 0,
                                    'TD032' => 1,
                                    'TD033' => 0,
                                    'TD034' => 0,
                                    'TD035' => 0,
                                    'TD036' => 0,
                                ];
                                $i++;
                            }

                            //新增鼎新訂單商品資料至鼎新訂單商品資料表
                            //下面方法避開 Tried to bind parameter number 2101. SQL Server supports a maximum of 2100 parameters. 錯誤
                            if(!empty($itemData) && count($itemData) >= 20){
                                $items = array_chunk($itemData,20);
                                $syncedItems = array_chunk($orderItems,20);
                                for($i=0;$i<count($items);$i++){
                                    ErpOrderItemDB::insert($items[$i]);
                                }
                                for($i=0;$i<count($syncedItems);$i++){
                                    SyncedOrderItemDB::insert($syncedItems[$i]);
                                }
                            }elseif(!empty($itemData) && count($itemData) < 20){
                                $erpOrderItems = ErpOrderItemDB::insert($itemData);
                                $syncedOrderItems = SyncedOrderItemDB::insert($orderItems);
                            }
                            if(count($orderItemPackages) > 0){
                                if(count($orderItemPackages) >= 20){
                                    $syncedItemPackages = array_chunk($orderItemPackages,20);
                                    for($i=0;$i<count($syncedItemPackages);$i++){
                                        SyncedOrderItemPackageDB::insert($syncedItemPackages[$i]);
                                    }
                                }else{
                                    $syncedOrderItemPackages = SyncedOrderItemPackageDB::insert($orderItemPackages);
                                }
                            }
                        }

                        if(in_array($order->digiwin_payment_id,$iCarryWeb)){
                            //變更稅別
                            if($order->ship_to == '台灣'){
                                $MA038 = 1;
                            }elseif($order->ship_to != '台灣' && ($order->invoice_type == 3 || !empty($order->carrier_num))){
                                $MA038 = 1;
                            }else{
                                $MA038 = 3;
                            }
                            $A641 = ErpACRTBDB::where([['TB006',$erpOrderNo],['TB001',$checkOutTB001]])->select(['TB002','CREATE_DATE'])->orderBy('TB002', 'desc')->first();
                            ErpACRTADB::where([['TA001',$checkOutTB001],['TA002',$A641->TB002],['TA004',$order->digiwin_payment_id]])->update(['TA012' => $MA038]);
                            ErpACRTADB::where([['TA001',$cancelOutTB001],['TA002',$A641->TB002],['TA004',$order->digiwin_payment_id]])->update(['TA012' => $MA038]);

                            //建立扣款結帳單
                            if ($returnPrice > 0) {
                                $A641 = ErpACRTBDB::where([['TB006',$erpOrderNo],['TB001',$checkOutTB001]])->select(['TB002','CREATE_DATE'])->orderBy('TB002', 'desc')->first();
                                $A651H = ErpACRTADB::where([['TA001',$cancelOutTB001],['TA002',$A641->TB002],['TA004',$order->digiwin_payment_id]])->first();
                                $this->createA616($creator, $createDate, $createTime, $order, $A641, $TB002A616No, $returnPrice,$tax,$TD001,$MA038,$orderDate,$cancelOutTB001,$refundTB001);
                                if($order->status == -1){
                                    ErpACRTADB::where([['TA001',$cancelOutTB001],['TA002',$A641->TB002],['TA004',$order->digiwin_payment_id]])->update([
                                        'TA031' => $A651H->TA031 + $returnPrice,
                                        'TA044' => $A651H->TA031 + $returnPrice,
                                        'TA027' => 'Y',
                                        'TA012' => $MA038,
                                    ]);
                                }else{
                                    ErpACRTADB::where([['TA001',$cancelOutTB001],['TA002',$A641->TB002],['TA004',$order->digiwin_payment_id]])->update([
                                        'TA031' => $A651H->TA031 + $returnPrice,
                                        'TA044' => $A651H->TA031 + $returnPrice,
                                        'TA027' => 'N',
                                        'TA012' => $MA038,
                                    ]);
                                }
                            }

                            //稅金計算
                            if($order->ship_to == '台灣'){
                                $totalPrice = round($originTotalPrice / 1.05, 0);
                                $tax = round($originTotalPrice - $totalPrice, 0);
                                $MA038 = 1;
                            }elseif($order->ship_to != '台灣' && ($order->invoice_type == 3 || !empty($order->carrier_num))){
                                $totalPrice = round($originTotalPrice / 1.05, 0);
                                $tax = round($originTotalPrice - $totalPrice, 0);
                                $MA038 = 1;
                            }else{
                                $totalPrice = round($originTotalPrice, 0);
                                $MA038 = 3;
                            }
                        }else{
                            if($MA038 == 2){ //應稅外加, 除以1.05
                                $totalPrice = round($originTotalPrice, 0);
                                $tax = round($totalPrice * 0.05, 0);
                            }elseif($MA038 == 1){
                                $totalPrice = round($originTotalPrice / 1.05, 0);
                                $tax = round($originTotalPrice - $totalPrice, 0);
                            }else{
                                $totalPrice = round($originTotalPrice, 0);
                                $tax = 0;
                            }
                        }
                        $orderData = [
                            'TC003' => $orderDate, //訂單日期 CONVERT(VARCHAR(8),pay_time,112)
                            'TC004' => $order->digiwin_payment_id,
                            'TC005' => $order->customer['MA015'],
                            'TC006' => $order->customer['MA016'],
                            'TC010' => mb_substr($order->receiver_address,0,250),
                            'TC012' => $order->order_number,
                            'TC014' => $order->customer['MA031'], //付款條件 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA031
                            'TC015' => $order->user_memo,
                            'TC016' => $MA038, //課稅別 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA038
                            'TC018' => mb_substr($order->receiver_name,0,28),
                            'TC019' => $order->customer['MA048'], //運輸方式 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA048
                            'TC029' => $totalPrice, //訂單金額 各單品加總金額
                            'TC030' => $tax, //訂單稅額 各單品加總金額 * 0.05
                            'TC031' => $totalItemQuantity, //總數量 單品總數量
                            'TC035' => $order->ship_to,
                            'TC039' => $orderDate, //單據日期 CONVERT(VARCHAR(8),pay_time,112)
                            'TC040' => $creator,
                            'TC044' => $order->invoice_address,
                            'TC047' => mb_substr($order->receiver_tel,0,18),
                            'TC059' => $order->customer['MA083'],
                            'TC061' => $order->partner_order_number ?? $order->order_number,
                            'TC062' => mb_substr($order->receiver_name,0,28),
                            'TC063' => str_replace(['-','/'],['',''],$order->book_shipping_date),
                            'TC068' => $order->admin_memo,
                            'TC094' => mb_substr($order->receiver_phone_number,0,28),
                        ];
                        $order->erpOrder->update($orderData);
                        //紀錄SyncedOrder資料
                        SyncedOrderDB::create([
                            'order_id' => $order->id,
                            'erp_order_no' => $erpOrderNo,
                            'admin_id' => $adminId,
                            'amount' => $order->amount,
                            'discount' => $order->discount,
                            'shipping_fee' => $order->shipping_fee,
                            'spend_point' => $order->spend_point,
                            'parcel_tax' => $order->parcel_tax,
                            'status' => $order->status,
                            'direct_ship_quantity' => $directShipQty,
                            'orginal_money' => $order->syncedOrder['orginal_money'],
                            'return_money' => $returnPrice,
                            'balance' => $balance, //餘額
                            'total_item_quantity' => $totalItemQty,
                            'vendor_arrival_date' => $order->vendor_arrival_date,
                            'book_shipping_date' => $order->book_shipping_date,
                        ]);
                        //同步鼎新後,將訂單狀態改為集貨中
                        if($order->status == 1){
                            unset($order->totalWeight);
                            unset($order->totalPrice);
                            unset($order->totalQty);
                            unset($order->total_pay);
                            unset($order->totalSellQty);
                            $order->update(['status' => 2]);
                        }
                        //檢查訂單是否已經完成出貨
                        CheckOrderSellJob::dispatchNow($order);
                    }
                }else{
                    if(count($order->items) > 0){
                        //先清除錯誤紀錄
                        SyncedOrderErrorDB::where('order_id',$order->id)->delete();
                        //檢查訂單的預定出貨日與廠商出貨日
                        if(empty($order->book_shipping_date) || empty($order->vendor_arrival_date)) {
                            //建立錯誤訊息
                            SyncedOrderErrorDB::create([
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'error' => "預定出貨日與廠商出貨日不可空白。",
                            ]);
                        }
                        //商品資料
                        $i = $chkReturn = $returnPrice = $extraPrice = 0;
                        $itemData = $orderItems = $orderItemPackages = $returnDigiwinNo = [];
                        foreach($order->items as $item){
                            $chkExpress = 0;
                            empty($item->shipping_memo) ? $chkExpress++ : '';
                            //檢查item是否為票券
                            $chkTicket = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                            ->where($productModelTable.'.digiwin_no',$item->digiwin_no)
                            ->where($productTable.'.category_id',17)->first();
                            if(!empty($chkTicket) || $item->category_id == 18){
                                $TD007 = 'W14';
                            }else{
                                $item->direct_shipment == 1 ? $TD007 = 'W02' : $TD007 = 'W01';
                                $item->shipping_memo == 'momo-新莊' ? $TD007 = 'W15' : '';
                                $item->shipping_memo == '順豐-新莊' ? $TD007 = 'W15' : '';
                                $item->shipping_memo == '黑貓-新莊' ? $TD007 = 'W15' : '';
                            }
                            strstr($order->digiwin_payment_id,'AC') ? $TD007 = 'W16' : '';
                            strstr($order->digiwin_payment_id,'089') ? $TD007 = 'W17' : '';
                            //檢查商品是否存在, 或者被註記 is_del = 1 時跳異常
                            $itemError = null;
                            if(strstr($item->sku,'BOM') && $item->is_del == 0){
                                if(!empty($item->package_data)){
                                    $packageData = json_decode(str_replace('	','',$item->package_data));
                                    if(!empty($packageData) && count($packageData) > 0){
                                        $chk = 0;
                                        foreach($packageData as $package){
                                            if(isset($package->is_del)){
                                                if($package->is_del == 0){
                                                    if($item->sku == $package->bom){
                                                        $chk++;
                                                        break;
                                                    }
                                                }
                                            }else{
                                                if($item->sku == $package->bom){
                                                    $chk++;
                                                    break;
                                                }
                                            }
                                        }
                                        if($chk == 0){ //由於閃購沒有商家代號在鼎新中, 需用轉換貨號方式來重新檢查
                                            $newProduct = ProductModelDB::join('product','product.id','product_model.product_id')
                                                ->select('product.package_data')->find($item->product_model_id);
                                            $newPackageData = json_decode($newProduct->package_data);
                                            $reChk = 0;
                                            if(!empty($newPackageData) && count($newPackageData) > 0){
                                                foreach($newPackageData as $newPackage){
                                                    if(isset($newPackage->is_del)){
                                                        if($newPackage->is_del == 0){
                                                            if($item->sku == $newPackage->bom){
                                                                $reChk++;
                                                                break;
                                                            }
                                                        }
                                                    }else{
                                                        if($item->sku == $newPackage->bom){
                                                            $reChk++;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }else{
                                                $itemError = $item->digiwin_no.' 組合商品資料不存在';
                                            }
                                        }
                                    }else{
                                        $itemError = $item->digiwin_no.' 組合商品資料不存在';
                                    }
                                }else{
                                    $itemError = $item->digiwin_no.' 組合商品資料不存在';
                                }
                            }
                            $productModel = ProductModelDB::where('is_del',0)->find($item->product_model_id);
                            if(empty($productModel)){
                                $itemError = "iCarry商品 $item->digiwin_no 不存在。";
                            }else{
                                //檢查條碼
                                $chkChange = 0;
                                $repeatProduct = $productModels = [];
                                if(!empty($productModel->gtin13)){
                                    $productModels = ProductModelDB::where('gtin13',$productModel->gtin13)->get();
                                    if(count($productModels) > 1){
                                        foreach($productModels as $pm){
                                            if(empty($pm->origin_digiwin_no)){
                                                $repeatProduct[] = $pm->digiwin_no;
                                                $chkChange++;
                                            }
                                        }
                                    }
                                }
                                count($repeatProduct) > 0 ? $repeatProductSKU = join('、',$repeatProduct) : '';
                                $product = ProductDB::join('product_model','product_model.product_id','product.id')
                                ->join('vendor','vendor.id','product.vendor_id')
                                    ->select([
                                        'product.*',
                                        'product_model.sku',
                                        'vendor.name as vendor_name',
                                        'vendor.digiwin_vendor_no',
                                    ])->find($productModel->product_id);
                                !empty($product->digiwin_vendor_no) ? $erpVendor = ErpVendorDB::find($product->digiwin_vendor_no) : $erpVendor = ErpVendorDB::find('A'.str_pad($product->vendor_id,5,0,STR_PAD_LEFT));
                                if(empty($product) || $product->is_del == 1){
                                    $itemError = $product->is_del == 1 ? "iCarry商品 $item->digiwin_no 已被刪除。" : "iCarry商品 $item->digiwin_no 不存在。";
                                }elseif($product->price == 0 || $product->price == null){
                                    //排除使用報價單的檢查
                                    $useQuotation = $order->customer['use_quotation']; //是否使用報價單
                                    if($product->type != 3 && $useQuotation != 1){
                                        $itemError = "$item->digiwin_no 商品售價不可為 0";
                                    }
                                }elseif(empty($erpVendor)){
                                    strstr($product->sku,'EC') ? $itemError = "商家 $product->vendor_name 不存在於鼎新中。" : '';
                                }elseif(count($productModels) > 1 && $chkChange > 1){
                                    $itemError = "$item->digiwin_no 商品條碼重複且未全部轉換。重複商品： $repeatProductSKU ";
                                }
                            }
                            $erpProduct = ErpProductDB::find($item->digiwin_no);
                            if(empty($erpProduct)){
                                $itemError = "鼎新商品 $item->digiwin_no 不存在。";
                            }
                            if($item->is_del == 1){
                                $chkReturn++;
                                $returnDigiwinNo[] = $item->digiwin_no;
                                $returnPrice += $item->quantity * $item->price;
                            }
                            if(!empty($itemError)){
                                //建立錯誤訊息
                                SyncedOrderErrorDB::create([
                                    'order_id' => $item->order_id,
                                    'order_number' => $order->order_number,
                                    'product_model_id' => $item->product_model_id,
                                    'sku' => $item->sku,
                                    'digiwin_no' => $item->digiwin_no,
                                    'error' => $itemError,
                                ]);
                            }
                            //設定空變數
                            if(!empty($item->package) && count($item->package) > 0){
                                foreach($item->package as $package){
                                    $snos[$package->id] = [];
                                }
                            }
                            //拆分商品
                            if(!empty($item->split) && count($item->split) > 0){ //組合商品
                                $package = $item->split;
                                for($x=0;$x<count($package);$x++){
                                    $snos[$package[$x]['id']][] = str_pad(($i+1),4,0,STR_PAD_LEFT);
                                    //檢查商品是否存在, 或者被註記 is_del = 1 時跳異常
                                    $packageError = null;
                                    $productModel = ProductModelDB::find($package[$x]['product_model_id']);
                                    $packageDigiwinNo = $package[$x]['digiwin_no'];
                                    if(empty($productModel)){
                                        //建立錯誤訊息
                                        $packageError = "iCarry商品 $packageDigiwinNo 不存在。";
                                    }else{
                                        //檢查條碼
                                        $chkChange = 0;
                                        $repeatProduct = $productModels = [];
                                        if(!empty($productModel->gtin13)){
                                            $productModels = ProductModelDB::where('gtin13',$productModel->gtin13)->get();
                                            if(count($productModels) > 1){
                                                foreach($productModels as $pm){
                                                    if(empty($pm->origin_digiwin_no)){
                                                        $repeatProduct[] = $pm->sku;
                                                        $chkChange++;
                                                    }
                                                }
                                            }
                                        }
                                        count($repeatProduct) > 0 ? $repeatProductSKU = join('、',$repeatProduct) : '';
                                        $product = ProductDB::join('vendor','vendor.id','product.vendor_id')
                                        ->select([
                                            'product.*',
                                            'vendor.name as vendor_name',
                                            'vendor.digiwin_vendor_no',
                                        ])->find($package[$x]['product_id']);
                                        !empty($product->digiwin_vendor_no) ? $erpVendor = ErpVendorDB::find($product->digiwin_vendor_no) : $erpVendor = ErpVendorDB::find('A'.str_pad($product->vendor_id,5,0,STR_PAD_LEFT));
                                        if(empty($product) || $product->is_del == 1){
                                            $packageError = $product->is_del == 1 ? "iCarry商品 $packageDigiwinNo 已被刪除。" : "iCarry商品 $packageDigiwinNo 不存在。";
                                        }elseif($product->price == 0 || $product->price == null){
                                            //排除使用報價單的檢查
                                            $useQuotation = $order->customer['use_quotation']; //是否使用報價單
                                            if($product->type != 3 && $useQuotation != 1){
                                                $packageError = "$packageDigiwinNo 商品售價不可為 0";
                                            }
                                        }elseif(empty($erpVendor)){
                                            $packageError = "商家 $product->vendor_name 不存在於鼎新中。";
                                        }elseif(count($productModels) > 1 && $chkChange > 1){
                                            $packageError = "$packageDigiwinNo 商品條碼重複且未全部轉換。重複商品： $repeatProductSKU ";
                                        }
                                    }
                                    $erpProduct = ErpProductDB::find($package[$x]['digiwin_no']);
                                    if(empty($erpProduct)){
                                        //建立錯誤訊息
                                        $packageError = "鼎新商品 $packageDigiwinNo 不存在。";
                                    }
                                    if(!empty($packageError)){
                                        SyncedOrderErrorDB::create([
                                            'order_id' => $item->order_id,
                                            'order_number' => $order->order_number,
                                            'product_model_id' => $package[$x]['product_model_id'],
                                            'sku' => $package[$x]['sku'],
                                            'digiwin_no' => $package[$x]['digiwin_no'],
                                            'error' => $packageError,
                                        ]);
                                    }
                                    $order->status >= 1 ? $item->is_del == 1 ? $close = 'y' : $close = 'N' : $close = 'y';
                                    !empty($package[$x]['serving_size']) ? $servingSize = $package[$x]['serving_size'] : $servingSize = null;
                                    !empty($package[$x]['unit_name']) ? $unitName = $package[$x]['unit_name'] : $unitName = null;
                                    $totalItemQuantity += $package[$x]['quantity'];
                                    $itemData[] = [
                                        'COMPANY' => 'iCarry',
                                        'CREATOR' => $creator,
                                        'USR_GROUP' => 'DSC',
                                        'CREATE_DATE' => $createDate,
                                        'FLAG' => 1,
                                        'CREATE_TIME' => $createTime,
                                        'CREATE_AP' => 'iCarry',
                                        'CREATE_PRID' => 'COPI06',
                                        'TD001' => $TD001,
                                        'TD002' => $erpOrderNo,
                                        'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                        'TD004' => $package[$x]['digiwin_no'], //品號 product_model的digiwin_no
                                        'TD005' => mb_substr($package[$x]['product_name'],0,110,'utf8'), //品名 用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB002
                                        'TD006' => mb_substr($servingSize,0,110,'utf8'), //用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB003
                                        'TD007' => $TD007, //庫別
                                        'TD008' => $package[$x]['quantity'], //訂單數量 依拆分後數量
                                        'TD009' => 0,
                                        'TD010' => $unitName, //單位 product.unit_name
                                        'TD011' => round($package[$x]['price'],2), //單價 order_item.price
                                        'TD012' => intval($package[$x]['price'] * $package[$x]['quantity']), //金額 order_item.price * order_item.quantity
                                        'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                        'TD016' => $close, //結案碼 DEF 'N'
                                        'TD020' => $order->user_memo, //顧客備註
                                        'TD021' => 'Y', //確認碼 DEF 'Y'
                                        'TD022' => 0, //庫存數量 DEF '0'
                                        'TD024' => 0, //贈品量 DEF '0'
                                        'TD025' => 0, //贈品已交量 DEF '0'
                                        'TD026' => 1, //折扣率 DEF '0'
                                        'TD030' => null, //毛重(Kg) order_item.gross_weight
                                        'TD031' => 0,
                                        'TD032' => 1,
                                        'TD033' => 0,
                                        'TD034' => 0,
                                        'TD035' => 0,
                                        'TD036' => 0,
                                    ];
                                    $i++;
                                }
                                //紀錄同步商品資料.
                                $orderItems[] = [
                                    'order_item_id' => $item->id,
                                    'order_id' => $order->id,
                                    'erp_order_no' => $erpOrderNo,
                                    'erp_order_sno' => null,
                                    'product_model_id' => $item->product_model_id,
                                    'unit_name' => $item->unit_name,
                                    'gross_weight' => $item->gross_weight,
                                    'net_weight' => $item->net_weight,
                                    'price' => $item->price,
                                    'purchase_price' => $item->purchase_price,
                                    'quantity' => $item->quantity,
                                    'direct_shipment' => $item->direct_shipment,
                                    'is_del' => $item->is_del,
                                    'not_purchase' => $item->not_purchase,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'book_shipping_date' => $order->book_shipping_date,
                                    'vendor_arrival_date' => $order->vendor_arrival_date,
                                ];
                            }else{ //單品
                                $order->status >= 1 ? $item->is_del == 1 ? $close = 'y' : $close = 'N' : $close = 'y';
                                $totalItemQuantity += $item->quantity;
                                $itemData[] = [
                                    'COMPANY' => 'iCarry',
                                    'CREATOR' => $creator,
                                    'USR_GROUP' => 'DSC',
                                    'CREATE_DATE' => $createDate,
                                    'FLAG' => 1,
                                    'CREATE_TIME' => $createTime,
                                    'CREATE_AP' => 'iCarry',
                                    'CREATE_PRID' => 'COPI06',
                                    'TD001' => $TD001,
                                    'TD002' => $erpOrderNo,
                                    'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                    'TD004' => $item->digiwin_no, //品號 product_model的digiwin_no
                                    'TD005' => mb_substr($item->product_name,0,100,'utf8'), //品名 用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB002
                                    'TD006' => mb_substr($item->serving_size,0,110,'utf8'), //用COPTD.TD004去關聯PINVMB.MB001，帶入INVMB.MB003
                                    'TD007' => $TD007,
                                    'TD008' => $item->quantity, //訂單數量 依拆分後數量
                                    'TD009' => 0,
                                    'TD010' => $item->unit_name, //單位 product.unit_name
                                    'TD011' => round($item->price,2), //單價 order_item.price
                                    'TD012' => intval($item->price * $item->quantity), //金額 order_item.price * order_item.quantity
                                    'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                    'TD016' => $close, //結案碼 DEF 'N'
                                    'TD020' => $order->user_memo, //顧客備註
                                    'TD021' => 'Y', //確認碼 DEF 'Y'
                                    'TD022' => 0, //庫存數量 DEF '0'
                                    'TD024' => 0, //贈品量 DEF '0'
                                    'TD025' => 0, //贈品已交量 DEF '0'
                                    'TD026' => 1, //折扣率 DEF '0'
                                    'TD030' => $item->gross_weight, //毛重(Kg) order_item.gross_weight
                                    'TD031' => 0,
                                    'TD032' => 1,
                                    'TD033' => 0,
                                    'TD034' => 0,
                                    'TD035' => 0,
                                    'TD036' => 0,
                                ];
                                //紀錄同步商品資料.
                                $orderItems[] = [
                                    'order_item_id' => $item->id,
                                    'order_id' => $order->id,
                                    'erp_order_no' => $erpOrderNo,
                                    'erp_order_sno' => str_pad(($i+1),4,0,STR_PAD_LEFT),
                                    'product_model_id' => $item->product_model_id,
                                    'unit_name' => $item->unit_name,
                                    'gross_weight' => $item->gross_weight,
                                    'net_weight' => $item->net_weight,
                                    'price' => $item->price,
                                    'purchase_price' => $item->purchase_price,
                                    'quantity' => $item->quantity,
                                    'direct_shipment' => $item->direct_shipment,
                                    'not_purchase' => $item->not_purchase,
                                    'is_del' => $item->is_del,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'book_shipping_date' => $order->book_shipping_date,
                                    'vendor_arrival_date' => $order->vendor_arrival_date,
                                ];
                                $i++;
                            }
                            //紀錄同步組合商品資料.
                            if(!empty($item->package) && count($item->package) > 0){
                                foreach($item->package as $package){
                                    $orderItemPackages[] = [
                                        'order_item_id' => $item->id,
                                        'order_item_package_id' => $package->id,
                                        'order_id' => $order->id,
                                        'erp_order_no' => $erpOrderNo,
                                        'erp_order_sno' => join(',',$snos[$package->id]),
                                        'product_model_id' => $package->product_model_id,
                                        'unit_name' => $package->unit_name,
                                        'gross_weight' => $package->gross_weight,
                                        'net_weight' => $package->net_weight,
                                        'price' => $package->price,
                                        'purchase_price' => $package->purchase_price,
                                        'quantity' => $package->quantity,
                                        'direct_shipment' => $item->direct_shipment,
                                        'is_del' => $item->is_del,
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'vendor_arrival_date' => $order->vendor_arrival_date,
                                    ];
                                }
                            }
                        }
                        //物流未填寫建立錯誤訊息
                        if($chkExpress >= 1) {
                            //建立錯誤訊息
                            SyncedOrderErrorDB::create([
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'error' => "選擇物流未全部完成。",
                            ]);
                        }
                        // $order->amount 商品總計在icarry是會變動的, 也就是說若有退貨則只會算is_del=0的,
                        // 這樣$order->amount就不是真正實際收的金額. 所以要透過items來推算真正收的金額.
                        if($chkReturn > 0){
                            //找退貨商品是否有另外新增, 如果有要將此金額加回來
                            if(!empty($returnDigiwinNo)){
                                for($r=0;$r<count($returnDigiwinNo);$r++){
                                    foreach($order->items as $it){
                                        if($it->digiwin_no == $returnDigiwinNo[$r] && $it->is_del == 0){
                                            $extraPrice += $it->price * $it->quantity;
                                            break;
                                        }
                                    }
                                }
                                $returnPrice -= $extraPrice;
                            }
                            $originTotalPrice += $returnPrice;
                        }
                        //iCarry訂單還未進鼎新時就已經被取消, 此時需要將此訂單內所有商品結案
                        $order->status == -1 ? $close = 'y' : $close = 'N';
                        //活動折扣
                        if($order->discount != 0){
                            $totalItemQuantity++;
                            $itemData[] = [
                                'COMPANY' => 'iCarry',
                                'CREATOR' => $creator,
                                'USR_GROUP' => 'DSC',
                                'CREATE_DATE' => $createDate,
                                'FLAG' => 1,
                                'CREATE_TIME' => $createTime,
                                'CREATE_AP' => 'iCarry',
                                'CREATE_PRID' => 'COPI06',
                                'TD001' => $TD001,
                                'TD002' => $erpOrderNo,
                                'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                'TD004' => '999000', //品號 product_model的digiwin_no
                                'TD005' => '活動折扣', //品名
                                'TD006' => '', //規格
                                'TD007' => 'W07',
                                'TD008' => 1,
                                'TD009' => 0,
                                'TD010' => '個', //單位
                                'TD011' => -($order->discount), //單價
                                'TD012' => -($order->discount), //金額
                                'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                'TD016' => $close, //結案碼 DEF 'N'
                                'TD020' => $order->user_memo, //顧客備註
                                'TD021' => 'Y', //確認碼 DEF 'Y'
                                'TD022' => 0, //庫存數量 DEF '0'
                                'TD024' => 0, //贈品量 DEF '0'
                                'TD025' => 0, //贈品已交量 DEF '0'
                                'TD026' => 1, //折扣率 DEF '0'
                                'TD030' => null, //毛重(Kg) order_item.gross_weight
                                'TD031' => 0,
                                'TD032' => 1,
                                'TD033' => 0,
                                'TD034' => 0,
                                'TD035' => 0,
                                'TD036' => 0,
                            ];
                            $i++;
                        }
                        //購物金
                        if($order->spend_point > 0){
                            $totalItemQuantity++;
                            $itemData[] = [
                                'COMPANY' => 'iCarry',
                                'CREATOR' => $creator,
                                'USR_GROUP' => 'DSC',
                                'CREATE_DATE' => $createDate,
                                'FLAG' => 1,
                                'CREATE_TIME' => $createTime,
                                'CREATE_AP' => 'iCarry',
                                'CREATE_PRID' => 'COPI06',
                                'TD001' => $TD001,
                                'TD002' => $erpOrderNo,
                                'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                'TD004' => '999001', //品號 product_model的digiwin_no
                                'TD005' => '購物金', //品名
                                'TD006' => '', //規格
                                'TD007' => 'W07',
                                'TD008' => 1,
                                'TD009' => 0,
                                'TD010' => '個', //單位
                                'TD011' => -($order->spend_point), //單價
                                'TD012' => -($order->spend_point), //金額
                                'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                'TD016' => $close, //結案碼 DEF 'N'
                                'TD020' => $order->user_memo, //顧客備註
                                'TD021' => 'Y', //確認碼 DEF 'Y'
                                'TD022' => 0, //庫存數量 DEF '0'
                                'TD024' => 0, //贈品量 DEF '0'
                                'TD025' => 0, //贈品已交量 DEF '0'
                                'TD026' => 1, //折扣率 DEF '0'
                                'TD030' => null, //毛重(Kg) order_item.gross_weight
                                'TD031' => 0,
                                'TD032' => 1,
                                'TD033' => 0,
                                'TD034' => 0,
                                'TD035' => 0,
                                'TD036' => 0,
                            ];
                            $i++;
                        }
                        //運費
                        if($order->shipping_fee > 0){
                            $totalItemQuantity++;
                            $itemData[] = [
                                'COMPANY' => 'iCarry',
                                'CREATOR' => $creator,
                                'USR_GROUP' => 'DSC',
                                'CREATE_DATE' => $createDate,
                                'FLAG' => 1,
                                'CREATE_TIME' => $createTime,
                                'CREATE_AP' => 'iCarry',
                                'CREATE_PRID' => 'COPI06',
                                'TD001' => $TD001,
                                'TD002' => $erpOrderNo,
                                'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                'TD004' => '901001', //品號 product_model的digiwin_no
                                'TD005' => '運費', //品名
                                'TD006' => '', //規格
                                'TD007' => 'W07',
                                'TD008' => 1,
                                'TD009' => 0,
                                'TD010' => '個', //單位
                                'TD011' => $order->shipping_fee, //單價
                                'TD012' => $order->shipping_fee, //金額
                                'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                'TD016' => $close, //結案碼 DEF 'N'
                                'TD020' => $order->user_memo, //顧客備註
                                'TD021' => 'Y', //確認碼 DEF 'Y'
                                'TD022' => 0, //庫存數量 DEF '0'
                                'TD024' => 0, //贈品量 DEF '0'
                                'TD025' => 0, //贈品已交量 DEF '0'
                                'TD026' => 1, //折扣率 DEF '0'
                                'TD030' => null, //毛重(Kg) order_item.gross_weight
                                'TD031' => 0,
                                'TD032' => 1,
                                'TD033' => 0,
                                'TD034' => 0,
                                'TD035' => 0,
                                'TD036' => 0,
                            ];
                            $i++;
                        }
                        //行郵稅
                        if($order->parcel_tax > 0){
                            $totalItemQuantity++;
                            $itemData[] = [
                                'COMPANY' => 'iCarry',
                                'CREATOR' => $creator,
                                'USR_GROUP' => 'DSC',
                                'CREATE_DATE' => $createDate,
                                'FLAG' => 1,
                                'CREATE_TIME' => $createTime,
                                'CREATE_AP' => 'iCarry',
                                'CREATE_PRID' => 'COPI06',
                                'TD001' => $TD001,
                                'TD002' => $erpOrderNo,
                                'TD003' => str_pad(($i+1),4,0,STR_PAD_LEFT), //序號 依單別、單號依序加入四碼流水號
                                'TD004' => '901002', //品號 product_model的digiwin_no
                                'TD005' => '行郵稅', //品名
                                'TD006' => '', //規格
                                'TD007' => 'W07',
                                'TD008' => 1,
                                'TD009' => 0,
                                'TD010' => '個', //單位
                                'TD011' => $order->parcel_tax, //單價
                                'TD012' => $order->parcel_tax, //金額
                                'TD013' => str_replace(['-','/'],['',''],$order->book_shipping_date), //預交日 order.book_shipping_date
                                'TD016' => $close, //結案碼 DEF 'N'
                                'TD020' => $order->user_memo, //顧客備註
                                'TD021' => 'Y', //確認碼 DEF 'Y'
                                'TD022' => 0, //庫存數量 DEF '0'
                                'TD024' => 0, //贈品量 DEF '0'
                                'TD025' => 0, //贈品已交量 DEF '0'
                                'TD026' => 1, //折扣率 DEF '0'
                                'TD030' => null, //毛重(Kg) order_item.gross_weight
                                'TD031' => 0,
                                'TD032' => 1,
                                'TD033' => 0,
                                'TD034' => 0,
                                'TD035' => 0,
                                'TD036' => 0,
                            ];
                            $i++;
                        }
                        if(SyncedOrderErrorDB::where('order_id',$order->id)->count() == 0){
                            //新增鼎新訂單商品資料至鼎新訂單商品資料表及中繼站的同步訂單商品資料表
                            //下面方法避開 Tried to bind parameter number 2101. SQL Server supports a maximum of 2100 parameters. 錯誤
                            if(!empty($itemData) && count($itemData) >= 20){
                                $items = array_chunk($itemData,20);
                                $syncedItems = array_chunk($orderItems,20);
                                for($i=0;$i<count($items);$i++){
                                    ErpOrderItemDB::insert($items[$i]);
                                }
                                for($i=0;$i<count($syncedItems);$i++){
                                    SyncedOrderItemDB::insert($syncedItems[$i]);
                                }
                            }elseif(!empty($itemData) && count($itemData) < 20){
                                $erpOrderItems = ErpOrderItemDB::insert($itemData);
                                $syncedOrderItems = SyncedOrderItemDB::insert($orderItems);
                            }
                            if(count($orderItemPackages) > 0){
                                if(count($orderItemPackages) >= 20){
                                    $syncedItemPackages = array_chunk($orderItemPackages,20);
                                    for($i=0;$i<count($syncedItemPackages);$i++){
                                        SyncedOrderItemPackageDB::insert($syncedItemPackages[$i]);
                                    }
                                }else{
                                    $syncedOrderItemPackages = SyncedOrderItemPackageDB::insert($orderItemPackages);
                                }
                            }

                            //稅金計算
                            if(in_array($order->digiwin_payment_id,$iCarryWeb)){
                                if($order->ship_to == '台灣'){
                                    $totalPrice = round($originTotalPrice / 1.05, 0);
                                    $tax = round($originTotalPrice - $totalPrice, 0);
                                    $MA038 = 1;
                                }elseif($order->ship_to != '台灣' && ($order->invoice_type == 3 || !empty($order->carrier_num))){
                                    $totalPrice = round($originTotalPrice / 1.05, 0);
                                    $tax = round($originTotalPrice - $totalPrice, 0);
                                    $MA038 = 1;
                                }else{
                                    $totalPrice = round($originTotalPrice, 0);
                                    $MA038 = 3;
                                }
                            }else{
                                if($MA038 == 2){ //應稅外加, 除以1.05
                                    $totalPrice = round($originTotalPrice, 0);
                                    $tax = round($totalPrice * 0.05, 0);
                                }elseif($MA038 == 1){
                                    $totalPrice = round($originTotalPrice / 1.05, 0);
                                    $tax = round($originTotalPrice - $totalPrice, 0);
                                }else{
                                    $totalPrice = round($originTotalPrice, 0);
                                    $tax = 0;
                                }
                            }

                            //新增鼎新訂單資料至鼎新訂單資料表
                            $orderData = [
                                'COMPANY' => 'iCarry',
                                'CREATOR' => $creator,
                                'USR_GROUP' => 'DSC',
                                'CREATE_DATE' => $createDate,
                                'FLAG' => 1,
                                'CREATE_TIME' => $createTime,
                                'CREATE_AP' => 'iCarry',
                                'CREATE_PRID' => 'COPI06',
                                'TC001' => $TD001,
                                'TC002' => $erpOrderNo, //單號 年月日+五碼流水號 EX:22010100001
                                'TC003' => $orderDate, //訂單日期 CONVERT(VARCHAR(8),pay_time,112)
                                'TC004' => $order->digiwin_payment_id,
                                'TC005' => $order->customer['MA015'],
                                'TC006' => $order->customer['MA016'],
                                'TC007' => '001',
                                'TC008' => 'NTD',
                                'TC009' => 1,
                                'TC010' => mb_substr($order->receiver_address,0,250),
                                'TC012' => $order->order_number,
                                'TC014' => $order->customer['MA031'], //付款條件 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA031
                                'TC015' => $order->user_memo,
                                'TC016' => $MA038, //課稅別 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA038
                                'TC018' => mb_substr($order->receiver_name,0,28),
                                'TC019' => $order->customer['MA048'], //運輸方式 iCTest.COPTC.TC004關聯iCTest.COPMA.MA001，取iCTEST.COPMA.MA048
                                'TC026' => 0,
                                'TC027' => 'Y',
                                'TC028' => 0,
                                'TC029' => $totalPrice, //訂單金額 各單品加總金額
                                'TC030' => $tax, //訂單稅額 各單品加總金額 * 0.05
                                'TC031' => $totalItemQuantity, //總數量 單品總數量
                                'TC035' => $order->ship_to,
                                'TC039' => str_replace(['-','/'],['',''],explode(' ',$order->pay_time)[0]), //單據日期 CONVERT(VARCHAR(8),pay_time,112)
                                'TC040' => $creator,
                                'TC041' => '0.05',
                                'TC042' => 'N',
                                'TC043' => '其他',
                                'TC044' => $order->invoice_address,
                                'TC046' => '其他',
                                'TC047' => mb_substr($order->receiver_tel,0,18),
                                'TC050' => 1,
                                'TC055' => 1,
                                'TC057' => 0,
                                'TC058' => 1,
                                'TC059' => $order->customer['MA083'],
                                'TC061' => $order->partner_order_number ?? $order->order_number,
                                'TC062' => mb_substr($order->receiver_name,0,28),
                                'TC063' => str_replace(['-','/'],['',''],$order->book_shipping_date),
                                'TC064' => 1,
                                'TC068' => $order->admin_memo,
                                'TC074' => 'N',
                                'TC075' => 0,
                                'TC094' => mb_substr($order->receiver_phone_number,0,28),
                                'TC200' => 'N',
                                'TC201' => 'N',
                            ];
                            $newErpOrder = ErpOrderDB::create($orderData);
                            //紀錄SyncedOrder資料
                            SyncedOrderDB::create([
                                'order_id' => $order->id,
                                'erp_order_no' => $erpOrderNo,
                                'admin_id' => $adminId,
                                'amount' => $order->amount,
                                'discount' => $order->discount,
                                'shipping_fee' => $order->shipping_fee,
                                'spend_point' => $order->spend_point,
                                'parcel_tax' => $order->parcel_tax,
                                'status' => $order->status,
                                'orginal_money' => $originTotalPrice,
                                'return_money' => $returnPrice,
                                'total_item_quantity' => $totalItemQty,
                                'balance' => ($order->amount + $order->shipping_fee + $order->parcel_tax - $order->discount - $order->spend_point) - $returnPrice,
                                'vendor_arrival_date' => $order->vendor_arrival_date,
                                'book_shipping_date' => $order->book_shipping_date,
                            ]);
                            //建立預收結帳單及預收待抵單
                            //下方客戶代號才做預收
                            if(in_array($order->digiwin_payment_id,$iCarryWeb)){
                                $A641 = $this->createA641($creator,$createDate,$createTime,$order,$TB002No,$erpOrderNo,$totalPrice,$tax,$TD001,$MA038,$orderDate,$checkOutTB001,$cancelOutTB001);
                                $A651 = $this->createA651($creator,$createDate,$createTime,$order,$TB002No,$erpOrderNo,$totalPrice,$tax,$TD001,$MA038,$orderDate,$checkOutTB001,$cancelOutTB001);
                                $A651H = ErpACRTADB::where([['TA001',$cancelOutTB001],['TA002',"$TB002No"],['TA004',$order->digiwin_payment_id]])->first();
                                //當訂單狀態-1取消訂單, 建立扣款結帳單
                                if($order->status == -1 && $chkReturn == 0){
                                    $this->createA616($creator,$createDate,$createTime,$order,$A641,$TB002A616No,$totalPrice,$tax,$TD001,$MA038,$orderDate,$cancelOutTB001,$refundTB001);
                                    ErpACRTADB::where([['TA001',$cancelOutTB001],['TA002',"$TB002No"],['TA004',$order->digiwin_payment_id]])->update([
                                        'TA031' => $A651H->TA031 + $returnPrice,
                                        'TA044' => $A651H->TA031 + $returnPrice,
                                        'TA027' => 'Y',
                                        'TA012' => $MA038,
                                    ]);
                                }else{ //檢查商品中是否有被取消的.
                                    if($chkReturn > 0){
                                        $this->createA616($creator,$createDate,$createTime,$order,$A641,$TB002A616No,$returnPrice,$tax,$TD001,$MA038,$orderDate,$cancelOutTB001,$refundTB001);
                                        ErpACRTADB::where([['TA001',$cancelOutTB001],['TA002',"$TB002No"],['TA004',$order->digiwin_payment_id]])->update([
                                            'TA031' => $A651H->TA031 + $returnPrice,
                                            'TA044' => $A651H->TA031 + $returnPrice,
                                            'TA027' => 'N',
                                            'TA012' => $MA038,
                                        ]);
                                    }
                                }
                            }
                            //同步鼎新後,將訂單狀態改為集貨中
                            if($order->status == 1){
                                unset($order->totalWeight);
                                unset($order->totalPrice);
                                unset($order->totalQty);
                                unset($order->total_pay);
                                unset($order->shipping_memo_vendor);
                                unset($order->totalSellQty);
                                $order->update(['status' => 2]);
                            }
                        }
                    }
                }
                !empty($order->acOrder) ? $order->acOrder->update(['is_sync' => 1]) : '';
                !empty($order->nidinOrder) ? $order->nidinOrder->update(['is_sync' => 1]) : '';
                $c++;
            }
        }
    }

    private function createA641($creator,$createDate,$createTime,$order,$TB002No,$erpOrderNo,$totalPrice,$tax,$TD001,$MA038,$orderDate,$checkOutTB001,$cancelOutTB001)
    {
        $t = $TA020 = null;
        if(in_array($order->digiwin_payment_id,['001','002','005','008','009','037'])){
            $t = 7;
        }elseif(in_array($order->digiwin_payment_id,['003','004'])){ //智付通ATM, 智付通CVS, 下一個周三日
            for($i=1;$i<=7;$i++){
                $afterDay = Carbon::now()->addDays($i)->toDateString();
                $week = date('w',strtotime($afterDay));
                if($week == 3){
                    break;
                }
            }
        }elseif(in_array($order->digiwin_payment_id,['006','007'])){ //台新信用卡,台新銀聯卡
            $t = 6;
        }elseif(in_array($order->digiwin_payment_id,['063','073'])){ //ACPay 信用卡 T+4
            $t = 4;
        }elseif(in_array($order->digiwin_payment_id,['AC0002','AC000201','AC000202'])){ //你訂 T+3
            $t = 3;
        }elseif(in_array($order->digiwin_payment_id,['086'])){ //LinePay, 次月2號
            $afterDay = Carbon::now()->addMonth()->startOfMonth()->addDays(1)->toDateString();
        }
        !empty($t) ? $afterDay = Carbon::now()->addDays($t)->toDateString() : '';
        $week = date('w',strtotime($afterDay));
        //避開週六週日
        if($week == 6){ //往後延兩天
            $afterDay = Carbon::now()->addDays($t)->toDateString();
        }elseif($week == 0){ //往後延一天
            $afterDay = Carbon::now()->addDays($t)->toDateString();
        }
        //避開設定的休假日
        for($i=0;$i<=30;$i++){
            $exclude = AccountingHolidayDB::where([['type','erpACRTCProcess'],['exclude_date',$afterDay]])->first();
            $week = date('w',strtotime($afterDay));
            if(!empty($exclude)){
                if($week == 5){ //往後面再延兩天
                    $afterDay = date('Y-m-d',strtotime('+3 day',strtotime($afterDay)));
                }else{ //往後面再加一天
                    $afterDay = date('Y-m-d',strtotime('+1 day',strtotime($afterDay)));
                }
            }else{
                break;
            }
        }
        $TA020 = str_replace('-','',$afterDay);

        //建立預收結帳單 A641
        $A641 = ErpACRTBDB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => $creator,
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => $createDate,
            'FLAG' => 1,
            'CREATE_TIME' => $createTime,
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'ACRI02',
            'TB001' => $checkOutTB001,
            'TB002' => $TB002No,
            'TB003' => '0001',
            'TB004' => 6,
            'TB005' => $TD001,
            'TB006' => $erpOrderNo,
            'TB007' => '',
            'TB008' => $orderDate, //訂單日期 CONVERT(VARCHAR(8),pay_time,112),
            'TB009' => $totalPrice + $tax, //應收金額 各單品加總金額
            'TB010' => 0,
            'TB011' => $cancelOutTB001.'-'.$TB002No,
            'TB012' => 'Y',
            'TB013' => $checkOutTB001 == 'A641' ? 2131 : 213101,
            'TB014' => 0,
            'TB015' => 1,
            'TB017' => $checkOutTB001 == 'A641' ? $totalPrice + $tax : $totalPrice,
            'TB018' => $checkOutTB001 == 'A641' ? 0 : $tax,
            'TB019' => $checkOutTB001 == 'A641' ? $totalPrice + $tax : $totalPrice,
            'TB020'=> $checkOutTB001 == 'A641' ? 0 : $tax,
            'TB024' => 'N',
        ]);
        ErpACRTADB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => $creator,
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => $createDate,
            'FLAG' => 1,
            'CREATE_TIME' => $createTime,
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'COPI06',
            'TA001' => $checkOutTB001,
            'TA002' => $TB002No,
            'TA003' => $orderDate, //訂單日期 CONVERT(VARCHAR(8),pay_time,112)
            'TA004' => $order->digiwin_payment_id, //依訂單的客戶代號COPTC.TC004
            'TA006' => '001',
            'TA008' => $order->customer['customer_name'],
            'TA009' => 'NTD',
            'TA010' => 1,
            'TA011' => $order->customer['MA037'], //依客戶資料的發票聯數COPMA.MA037
            'TA012' => $MA038, //依訂單的課稅別COPTC.TC016
            'TA013' => 'N',
            'TA014' => 1,
            'TA015' => $order->is_invoice_no,
            'TA016' => $orderDate, //訂單日期 CONVERT(VARCHAR(8),pay_time,112)
            'TA017' => $checkOutTB001 == 'A641' ? $totalPrice + $tax : $totalPrice, //依訂單金額COPTC.TC029
            'TA018' => $checkOutTB001 == 'A641' ? 0 : $tax, //訂單稅額 COPTC.TC030
            'TA019' => 'N', //發票作廢
            'TA020' => $TA020,
            'TA021' => $TA020,
            'TA025' => 'Y',
            'TA026' => 'N',
            'TA027' => 'N', //結案碼
            'TA028' => 0,
            'TA029' => $checkOutTB001 == 'A641' ? $totalPrice + $tax : $totalPrice, //應收金額
            'TA030' => $checkOutTB001 == 'A641' ? 0 : $tax, //應收稅額
            'TA031' => 0,
            'TA032' => substr(str_replace(['-','/'],['',''],explode(' ',$order->pay_time)[0]),0,6), //單據日期取年月
            'TA034' => 0,
            'TA037' => 0,
            'TA038' => str_replace(['-','/'],['',''],explode(' ',$order->pay_time)[0]),
            'TA039' => $creator,
            'TA040' => '0.05',
            'TA041' => $checkOutTB001 == 'A641' ? $totalPrice + $tax : $totalPrice, //依訂單金額COPTC.TC029*訂單匯率COPTC.TC009
            'TA042' => $checkOutTB001 == 'A641' ? 0 : $tax, //依訂單稅額COPTC.TC030*訂單匯率COPTC.TC009
            'TA043' => 'N',
            'TA044' => 0,
            'TA045' => 0,
            'TA046' => 0,
            'TA047' => 0,
            'TA048' => $order->customer['MA083'], //依訂單付款條件COPTC.TC059
            'TA074' => 'Y',
            'TA075' => 0,
            'TA076' => 0,
            'TA077'=> 0,
            'TA078' => 0,
        ]);
        return $A641;
    }
    private function createA651($creator,$createDate,$createTime,$order,$TB002No,$erpOrderNo,$totalPrice,$tax,$TD001,$MA038,$orderDate,$checkOutTB001,$cancelOutTB001)
    {
        //建立預收待抵單 A651
        $A651 = ErpACRTBDB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => $creator,
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => $createDate,
            'FLAG' => 1,
            'CREATE_TIME' => $createTime,
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'ACRI02',
            'TB001' => $cancelOutTB001,
            'TB002' => $TB002No,
            'TB003' => '0001',
            'TB004' => 9,
            'TB005' => $checkOutTB001,
            'TB006' => $TB002No, //A641的單號
            'TB007' => '0001',
            'TB008' => $orderDate, //訂單日期 CONVERT(VARCHAR(8),pay_time,112),
            'TB009' => $totalPrice + $tax, //應收金額 各單品加總金額
            'TB010' => 0,
            'TB012' => 'Y',
            'TB013' => $cancelOutTB001 == 'A651' ? 2131 : 213101,
            'TB014' => 0,
            'TB015' => 1,
            'TB017' => $cancelOutTB001 == 'A651' ? $totalPrice + $tax : $totalPrice,
            'TB018' => $cancelOutTB001 == 'A651' ? 0 : $tax,
            'TB019' => $cancelOutTB001 == 'A651' ? $totalPrice + $tax : $totalPrice,
            'TB020' => $cancelOutTB001 == 'A651' ? 0 : $tax,
            'TB024' => 'N',
        ]);
        ErpACRTADB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => $creator,
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => $createDate,
            'FLAG' => 1,
            'CREATE_TIME' => $createTime,
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'COPI06',
            'TA001' => $cancelOutTB001,
            'TA002' => $TB002No,
            'TA003' => $orderDate, //訂單日期 CONVERT(VARCHAR(8),pay_time,112)
            'TA004' => $order->digiwin_payment_id, //依訂單的客戶代號COPTC.TC004
            'TA006' => '001',
            'TA008' => $order->customer['customer_name'],
            'TA009' => 'NTD',
            'TA010' => 1,
            'TA011' => $order->customer['MA037'], //依客戶資料的發票聯數COPMA.MA037
            'TA012' => $MA038, //依訂單的課稅別COPTC.TC016
            'TA013' => 'N',
            'TA014' => 1,
            'TA016' => $orderDate, //訂單日期 CONVERT(VARCHAR(8),pay_time,112)
            'TA017' => $cancelOutTB001 == 'A651' ? $totalPrice + $tax : $totalPrice, //依訂單金額COPTC.TC029
            'TA018' => $cancelOutTB001 == 'A651' ? 0 : $tax, //訂單稅額 COPTC.TC030
            'TA019' => 'N', //發票作廢
            'TA022' => $order->digiwin_payment_id, //依訂單的客戶代號COPTC.TC004
            'TA023' => $TD001, //依訂單單別COPTC.TC001
            'TA024' => $erpOrderNo, //依訂單單號COPTC.TC002
            'TA025' => 'Y',
            'TA026' => 'N',
            'TA027' => 'N', //結案碼
            'TA028' => 0,
            'TA029' => $cancelOutTB001 == 'A651' ? $totalPrice + $tax : $totalPrice, //應收金額
            'TA030' => $cancelOutTB001 == 'A651' ? 0 : $tax, //應收稅額
            'TA031' => 0,
            'TA032' => substr(str_replace(['-','/'],['',''],explode(' ',$order->pay_time)[0]),0,6), //單據日期取年月
            'TA034' => 0,
            'TA037' => 0,
            'TA038' => str_replace(['-','/'],['',''],explode(' ',$order->pay_time)[0]),
            'TA039' => $creator,
            'TA040' => '0.05',
            'TA041' => $cancelOutTB001 == 'A651' ? $totalPrice + $tax : $totalPrice, //依訂單金額COPTC.TC029*訂單匯率COPTC.TC009
            'TA042' => $cancelOutTB001 == 'A651' ? 0 : $tax, //依訂單稅額COPTC.TC030*訂單匯率COPTC.TC009
            'TA043' => 'N',
            'TA044' => 0,
            'TA045' => 0,
            'TA046' => 0,
            'TA047' => 0,
            'TA074' => 'Y',
        ]);
        return $A651;
    }
    private function createA616($creator,$createDate,$createTime,$order,$A641,$TB002A616No,$totalPrice,$tax,$TD001,$MA038,$orderDate,$cancelOutTB001,$refundTB001)
    {
        $A616 = ErpACRTBDB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => $creator,
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => $createDate,
            'FLAG' => 1,
            'CREATE_TIME' => $createTime,
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'ACRI02',
            'TB001' => $refundTB001,
            'TB002' => $TB002A616No,
            'TB003' => '0001',
            'TB004' => 5,
            'TB005' => $cancelOutTB001, //憑證單別, 填入預收待抵單別A651
            'TB006' => $A641->TB002, //憑證單號, 填入預收待抵單號
            'TB007' => '',
            'TB008' => $A641->CREATE_DATE, //憑證日期, 填入預收待抵單日期
            'TB009' => -($totalPrice + $tax), //應收金額, 扣款金額(含稅) 負數
            'TB011' => $order->status == -1 ? $order->order_number.' 訂單退款' : $order->order_number.' 訂單部分退款',
            'TB012' => 'Y', //確認碼
            'TB013' => $refundTB001 == 'A616' ? 2131 : 213101, //科目編號
            'TB014' => 0,
            'TB015' => 1,
            'TB017' => $refundTB001 == 'A616' ? -($totalPrice + $tax) : -round($totalPrice / 1.05,0), //原幣未稅金額
            'TB018' => $refundTB001 == 'A616' ? -(0) : -round($totalPrice - $totalPrice / 1.05,0), //原幣稅額, 未稅扣款金額*0.05
            'TB019' => $refundTB001 == 'A616' ? -($totalPrice + $tax) : -round($totalPrice / 1.05,0), //本幣未稅金額, 扣款金額(未稅)*1
            'TB020' => $refundTB001 == 'A616' ? -(0) : -round($totalPrice - $totalPrice / 1.05,0), //本幣稅額, 未稅扣款金額*0.05*1
            'TB024' => 'Y',
        ]);
        ErpACRTADB::create([
            'COMPANY' => 'iCarry',
            'CREATOR' => $creator,
            'USR_GROUP' => 'DSC',
            'CREATE_DATE' => $createDate,
            'FLAG' => 1,
            'CREATE_TIME' => $createTime,
            'CREATE_AP' => 'iCarry',
            'CREATE_PRID' => 'COPI06',
            'TA001' => $refundTB001,
            'TA002' => $TB002A616No,
            'TA003' => $createDate,
            'TA004' => $order->digiwin_payment_id,
            'TA006' => '001',
            'TA008' => $order->customer['customer_name'],
            'TA009' => 'NTD',
            'TA010' => 1,
            'TA011' => $order->customer['MA037'],
            'TA012' => $MA038,
            'TA013' => 'N',
            'TA014' => 1, //通關方式 1, 2
            'TA015' => $order->is_invoice_no, //發票號碼
            'TA016' => str_replace(['-','/'],['',''],substr($order->invoice_time,0,10)), //發票日期
            'TA019' => 'N', //發票作廢
            'TA025' => 'Y', //確認碼
            'TA026' => 'N', //更新碼
            'TA027' => 'N', //結案碼
            'TA028' => 0,
            'TA029' => $refundTB001 == 'A616' ? -($totalPrice + $tax) : -round($totalPrice / 1.05,0), //應收金額 未稅扣款金額(要有負號)
            'TA030' => $refundTB001 == 'A616' ? 0 : -round($totalPrice - $totalPrice / 1.05,0), //營業稅額
            'TA031' => 0, //已收金額
            'TA032' => substr(str_replace(['-','/'],['',''],explode(' ',$order->pay_time)[0]),0,6), //交易日期取年月
            'TA034' => 0,
            'TA037' => 0,
            'TA038' => $createDate,
            'TA039' => $creator,
            'TA040' => '0.05',
            'TA041' => $refundTB001 == 'A616' ? -($totalPrice + $tax) : -round($totalPrice / 1.05,0), //本幣應收金額, 扣款金額
            'TA042' => $refundTB001 == 'A616' ? 0 : -round($totalPrice - $totalPrice / 1.05,0), //營業稅額
            'TA043' => 'N',
            'TA044' => 0,
            'TA045' => 0,
            'TA046' => 0,
            'TA047' => 0,
            'TA048' => $order->customer['MA083'], //付款條件代號, 依訂單付款條件COPTC.TC059
            'TA068' => str_replace(['-','/',':'],['','',''],substr($order->invoice_time,11,10)), //發票開立時間
            'TA069' => $order->is_invoice_no,
            'TA074' => 'N',
        ]);
        return $A616;
    }
}

// ORM try chtch 參考
// try {
//     !empty($itemData) ? $erpOrderItems = ErpOrderItemDB::insert($itemData) : '';
// } catch (\Illuminate\Database\QueryException $exception) {
//     // You can check get the details of the error using `errorInfo`:
//     $errorInfo = $exception->errorInfo;
//     // Return the response to the client..
// }
// if(!empty($errorInfo)){
//     dd($errorInfo[2]);
// }
