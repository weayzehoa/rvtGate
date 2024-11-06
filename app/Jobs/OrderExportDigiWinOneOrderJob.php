<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryShopcomOrder as ShopcomOrderDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryOrderLog as OrderLogDB;
use App\Models\iCarrySiteSetup as SiteSetupDB;
use App\Models\iCarryReceiverBaseSet as ReceiverBaseSetDB;
use App\Models\SyncedOrderError as SyncedOrderErrorDB;
use DB;
use DateTime;
use App\Jobs\AdminExportJob;
use App\Traits\ProductAvailableDate;
use App\Traits\OrderFunctionTrait;

class OrderExportDigiWinOneOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,ProductAvailableDate,OrderFunctionTrait;

    protected $param;
    protected $orderNumber;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param, $orderNumber)
    {
        $this->param = $param;
        $this->orderNumber = $orderNumber;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = OrderDB::with('itemData')->where('order_number',$this->orderNumber)->select(['*',DB::raw("DATE_FORMAT(orders.pay_time,'%Y%m%d') as payTime")])->first();
        $siteSetup = SiteSetupDB::first();
        $specialDateStart = str_replace(array("/","-"),array("",""),$siteSetup->pre_order_start_date);
        $specialDateEnd = str_replace(array("/","-"),array("",""),$siteSetup->pre_order_end_date);
        if (count($order->itemData) > 0) {
            //先清除錯誤紀錄
            SyncedOrderErrorDB::where('order_id',$order->id)->delete();
            $vendorLatestDeliveryDate = $vedd = $vendorEarliestDeliveryDate = $bookShippingDate = $vendorArrivalDate = null;
            $data = $tmp = $tmp2 = $is_call = $is_out = [];
            $todayNumber=intval(date("Ymd"));
            //找出廠商最快發貨日
            $isTicket = 0;
            foreach($order->itemData as $t){
                $t->category_id == 17 ? $isTicket++ : 0;
                if($t->is_del != 1){ //未被取消的商品
                    if(!empty($order->book_shipping_date)){
                        if(strtotime($order->book_shipping_date) < strtotime($t->vendor_earliest_delivery_date)){ //超過預定出貨日則忽略
                            $tmp[] = $t->vendor_earliest_delivery_date;
                        }
                        if(strtotime($order->book_shipping_date) < strtotime($t->vendor_latest_delivery_date)){ //超過預定出貨日則忽略
                            $tmp2[] = $t->vendor_latest_delivery_date;
                        }
                    }else{
                        $vd = new \DateTime($order->pay_time);
                        if(strtotime($vd->format('Y-m-d')) < strtotime($t->vendor_earliest_delivery_date)){ //超過今日則忽略
                            $tmp[] = $t->vendor_earliest_delivery_date;
                        }
                        if(strtotime($vd->format('Y-m-d')) < strtotime($t->vendor_latest_delivery_date)){ //超過今日則忽略
                            $tmp2[] = $t->vendor_latest_delivery_date;
                        }
                    }
                }
            }
            rsort($tmp);
            sort($tmp2);
            count($tmp) > 0 ? $vendorEarliestDeliveryDate = $tmp[0] : '';
            count($tmp2) > 0 ? $vendorLatestDeliveryDate = $tmp2[0] : '';
            !empty($vendorLatestDeliveryDate) && (strtotime($vendorEarliestDeliveryDate) > strtotime($vendorLatestDeliveryDate)) ? $chkDate = 1 : $chkDate = 0;

            $max = OrderItemDB::join('orders','orders.id','order_item.order_id')
            ->where('orders.id',$order->id)
            ->where('order_item.is_del',0)
            ->select([
                DB::raw("MAX(IF(orders.shipping_method = 1, (SELECT MAX(airplane_days) FROM product WHERE id IN(SELECT product_id FROM product_model WHERE product_model.id IN(SELECT product_model_id FROM order_item WHERE order_item.order_id = orders.id ))), (SELECT MAX(hotel_days) FROM product WHERE id IN(SELECT product_id FROM .product_model WHERE product_model.id IN(SELECT product_model_id FROM order_item WHERE order_item.order_id = orders.id ))))) as max_days"),
                DB::raw("MAX(DATE_FORMAT(orders.receiver_key_time,'%Y-%m-%d')) as max_receiver_key_time"),
                DB::raw("MAX(DATE_FORMAT(orders.pay_time,'%Y-%m-%d')) as max_pay_time"),
                DB::raw("MIN(DATE_FORMAT(orders.pay_time,'%Y-%m-%d')) as min_pay_time"),
                DB::raw("DATE_FORMAT(orders.pay_time,'%Y%m%d') as payTime"),
            ])->first();

            $findReceiverBase = $this->findReceiverBase($max);
            $is_call = $findReceiverBase['is_call'];
            $is_out = $findReceiverBase['is_out'];
            //標準計算
            if(empty($order->book_shipping_date)){
                $vendorArrivalDate=$this->getPreDeliveryDate($is_call,$is_out,$max->max_days,$max->payTime);
                if(!empty($vendorEarliestDeliveryDate)){
                    $vedd = new \DateTime($vendorEarliestDeliveryDate);
                    if($todayNumber < intval($vedd->format('Ymd'))){//操作日期 < 【廠商最快出貨日】時，【廠商最快出貨日】+ 一天可出
                        $vendorArrivalDate=$this->whichDateIsGreater($vendorArrivalDate,$this->checkDatePlusOneDayCanDelivery($vendorEarliestDeliveryDate));
                    }
                }
            }else{
                $vendorArrivalDate=str_replace(array("-","/"),array("",""),$order->book_shipping_date);
                if($vendorArrivalDate=="00000000"){
                    $vendorArrivalDate=$this->getPreDeliveryDate($is_call,$is_out,$max->max_days,$max->payTime);
                    if(!empty($vendorEarliestDeliveryDate)){
                        $vedd = new \DateTime($vendorEarliestDeliveryDate);
                        if($todayNumber < intval($vedd->format('Ymd'))){//操作日期 < 【廠商最快出貨日】時，【廠商最快出貨日】+ 一天可出
                            $vendorArrivalDate=$this->whichDateIsGreater($vendorArrivalDate,$this->checkDatePlusOneDayCanDelivery($vendorEarliestDeliveryDate));
                        }
                    }
                }
            }
            $bookShippingDate=$vendorArrivalDate;
            !empty($vendorArrivalDate) ? $vendorArrivalDate=str_replace(array('-','/'), array('',''), $vendorArrivalDate) : '';
            if($order->payTime >= $specialDateStart && $order->payTime <= $specialDateEnd){
                if(strtolower($order->create_type) == 'momo'){
                    //momo活動檔期內, 廠商到貨日為預定出貨日減一天
                    $data = $this->findMomoIsOut($bookShippingDate);
                    $is_out = $data['is_out'];
                    $vendorArrivalDate=$this->getPreDeliveryDateInSpecialDateMinusOneDay($bookShippingDate,$is_out);
                }else{
                    //有預定出貨日
                    if(!empty($order->book_shipping_date)){
                        $bookShippingDate=str_replace(array("-","/"),array("",""),$order->book_shipping_date);
                        $vendorArrivalDate=$this->checkDateMinusOneDayCanDelivery($is_out,$bookShippingDate);
                    }else{
                        //沒預定出貨日則廠商到貨後+一天為預定出貨日
                        $vedd = new \DateTime($vendorArrivalDate);
                        $bookShippingDate =  $this->whichDateIsGreater($bookShippingDate,$this->checkDatePlusOneDayCanDelivery($vedd->format('Y-m-d')));
                    }
                }
            }else{
                //非活動檔期、有廠商最快出貨日, 上面已經算過
                if(!empty($vendorEarliestDeliveryDate)){
                    //momo訂單不管廠商出貨日
                    if(strtolower($order->create_type) != 'momo'){
                        if(empty($order->book_shipping_date)) {
                            $vedd = new \DateTime($vendorEarliestDeliveryDate);
                            if(intval(date("Ymd")) < intval($vedd->format('Ymd'))){
                                $vendorArrivalDate=$this->whichDateIsGreater($vendorArrivalDate,$this->checkDatePlusOneDayCanDelivery($vedd->format('Y-m-d')));
                            }
                        }
                    }
                }else{
                    //非活動檔期、有預定出貨日
                    if(!empty($order->book_shipping_date)){
                        $bookShippingDate=str_replace(array("-","/"),array("",""),$order->book_shipping_date);
                        $vendorArrivalDate=str_replace(array("-","/"),array("",""),$order->book_shipping_date);
                    }
                }
            }
            // dd("bookShippingDate => $bookShippingDate && vendorArrivalDate = $vendorArrivalDate");
            //商品資料
            foreach ($order->itemData as $item) {
                //更新產品直寄資料到item中
                if(strstr($order->shipping_memo,'廠商發貨')){ //訂單物流若為廠商發貨則最高優先
                    $item->update(['direct_shipment' => 1 ]);
                    if(strstr($item->sku,'BOM')){
                        foreach ($item->package as $package) {
                            $package->update(['direct_shipment' => 1]);
                        }
                    }
                }else{
                    if(empty($item->direct_shipment)){
                        $item->update(['direct_shipment' => $item->directShip ]);
                    }
                    if(count($item->package) > 0){
                        foreach ($item->package as $package) {
                            if (empty($package->direct_shipment)) {
                                $package->update(['direct_shipment' => $item->directShip]);
                            }
                        }
                    }
                }
            }
            //回寫book_shipping_date 及 vendor_arrival_date 資料到orders
            unset($order->max_days);
            unset($order->max_receiver_key_time);
            unset($order->max_pay_time);
            unset($order->min_pay_time);
            unset($order->payTime);
            if($chkDate == 1){
                foreach($order->itemData as $it) {
                    if($it->vendor_earliest_delivery_date == $vendorEarliestDeliveryDate){
                        //建立錯誤訊息
                        SyncedOrderErrorDB::create([
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'product_model_id' => $it->product_model_id,
                            'sku' => $it->sku,
                            'digiwin_no' => $it->digiwin_no,
                            'error' => $it->digiwin_no. " 商品最快出貨日異常。",
                        ]);
                    }
                }
            }else{
                $isTicket >= 1 ? $bookShippingDate = '20731231' : '';
                $isTicket >= 1 ? $vendorArrivalDate = '20731231' : '';
                $this->updateOrder($order,$vendorArrivalDate,$bookShippingDate);
            }
        }
    }

    public function updateOrder($order,$vendorArrivalDate,$bookShippingDate)
    {
        if ($order->status != -1) {
            $vendorArrivalDate = substr($vendorArrivalDate, 0, 4).'-'.substr($vendorArrivalDate, 4, 2).'-'.substr($vendorArrivalDate, -2);
            $bookShippingDate = substr($bookShippingDate, 0, 4).'-'.substr($bookShippingDate, 4, 2).'-'.substr($bookShippingDate, -2);
            if(empty($order->book_shipping_date)){
                $order->update(['book_shipping_date' => $bookShippingDate, 'vendor_arrival_date' => $vendorArrivalDate]);
                $log[] = [
                    'order_id' => $order->id,
                    'editor' => $this->param['admin_id'],
                    'column_name' => 'book_shipping_date',
                    'log' => $bookShippingDate,
                    'create_time' => date('Y-m-d H:i:s')
                ];
                $log[] = [
                    'order_id' => $order->id,
                    'editor' => $this->param['admin_id'],
                    'column_name' => 'vendor_arrival_date',
                    'log' => $vendorArrivalDate,
                    'create_time' => date('Y-m-d H:i:s')
                ];
            }else{
                $order->update(['vendor_arrival_date' => $vendorArrivalDate]);
                $log[] = [
                    'order_id' => $order->id,
                    'editor' => $this->param['admin_id'],
                    'column_name' => 'vendor_arrival_date',
                    'log' => $vendorArrivalDate,
                    'create_time' => date('Y-m-d H:i:s')
                ];
                if($bookShippingDate != $order->book_shipping_date){
                    $order->update(['book_shipping_date' => $bookShippingDate]);
                    $log[] = [
                        'order_id' => $order->id,
                        'editor' => $this->param['admin_id'],
                        'column_name' => 'book_shipping_date',
                        'log' => $bookShippingDate,
                        'create_time' => date('Y-m-d H:i:s')
                    ];
                }
            }
            $logs = OrderLogDB::insert($log);
        }
    }
}
