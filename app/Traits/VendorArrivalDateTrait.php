<?php

namespace App\Traits;

use App\Models\iCarrySiteSetup as SiteSetupDB;
use App\Models\iCarryOrderLog as OrderLogDB;
use DB;
use Carbon\Carbon;
use Session;

use App\Traits\ProductAvailableDate;

trait VendorArrivalDateTrait
{
    use OrderFunctionTrait,ProductAvailableDate;

    protected function vendorArrivalDate($orders,$max)
    {
        $siteSetup = SiteSetupDB::first();
        $specialDateStart = str_replace(array("/","-"),array("",""),$siteSetup->pre_order_start_date);
        $specialDateEnd = str_replace(array("/","-"),array("",""),$siteSetup->pre_order_end_date);

        //找出is_call與is_out
        $receiverBase = $this->findReceiverBase($max);
        $is_call = $receiverBase['is_call'];
        $is_out = $receiverBase['is_out'];

        if (!empty($orders)) {
            $i = 0;
            foreach($orders as $order){
                if (count($order->itemData) > 0) {
                    $todayNumber=intval(date("Ymd"));
                    //找出廠商最快發貨日
                    foreach($order->itemData as $t){
                        if($t->is_del != 1){ //未被取消的商品
                            $tmp[] = $t->vendor_earliest_delivery_date;
                            $orderItemIDs[] = $t->product_id;
                        }
                    }
                    rsort($tmp);
                    $vendorEarliestDeliveryDate = $tmp[0];

                    if(empty($order->book_shipping_date)){
                        $colT=$this->getPreDeliveryDate($is_call,$is_out,$order->max_days,$order->payTime);
                        if(!empty($vendorEarliestDeliveryDate)){
                            $vedd = new \DateTime($vendorEarliestDeliveryDate);
                            if($todayNumber < intval($vedd->format('Ymd'))){//操作日期 < 【廠商最快出貨日】時，【廠商最快出貨日】+ 一天可出
                                $colT=$this->whichDateIsGreater($colT,checkDatePlusOneDayCanDelivery($vendorEarliestDeliveryDate));
                            }
                        }
                    }else{
                        $colT=str_replace(array("-","/"),array("",""),$order->book_shipping_date);
                        if($colT=="00000000"){
                            $colT=$this->getPreDeliveryDate($is_call,$is_out,$order->max_days,$order->payTime);
                            if(!empty($vendorEarliestDeliveryDate)){
                                $vedd = new \DateTime($vendorEarliestDeliveryDate);
                                if($todayNumber < intval($vedd->format('Ymd'))){//操作日期 < 【廠商最快出貨日】時，【廠商最快出貨日】+ 一天可出
                                    $colT=$this->whichDateIsGreater($colT,checkDatePlusOneDayCanDelivery($vendorEarliestDeliveryDate));
                                }
                            }
                        }
                    }
                    $colX=$colT;
                    !empty($colT) ? $colT=str_replace(array('-','/'), array('',''), $colT) : '';
                    if($order->payTime>=$specialDateStart && $order->payTime<=$specialDateEnd){
                        $colX=$this->getPreDeliveryDateInSpecialDate($is_call,$is_out,$order->max_days,$order->payTime);
                    }
                    if(!empty($vendorEarliestDeliveryDate)){
                        $vedd = new DateTime($vendorEarliestDeliveryDate);
                        if($today_number < intval($vedd->format('Ymd'))){//操作日期 < 【廠商最快出貨日】時，【廠商最快出貨日】+ 一天可出
                            $colX=$this->whichDateIsGreater($colX,$this->checkDatePlusOneDayCanDelivery($vendorEarliestDeliveryDate));
                        }
                    }
                    if($order->payTime>=$specialDateStart && $order->payTime<=$specialDateEnd){
                        if(!empty($order->book_shipping_date)){
                            $colX=str_replace(array("-","/"),array("",""),$order->book_shipping_date);
                            $colT=$this->checkDateMinusOneDayCanDelivery($is_out,$colX);
                        }
                    }else{
                        if(!empty($order->book_shipping_date)){
                            $colX=str_replace(array("-","/"),array("",""),$order->book_shipping_date);
                            $colT=str_replace(array("-","/"),array("",""),$order->book_shipping_date);
                        }
                    }

                    //回寫book_shipping_date 及 vendor_arrival_date 資料到orders
                    if ($order->status!=-1) {
                        $vendorArrivalDate = substr($colT, 0, 4).'-'.substr($colT, 4, 2).'-'.substr($colT, -2);
                        if(empty($order->book_shipping_date)){
                            $bookShippingDate = substr($colX, 0, 4).'-'.substr($colX, 4, 2).'-'.substr($colX, -2);
                            $order->update(['book_shipping_date' => $bookShippingDate, 'vendor_arrival_date' => $vendorArrivalDate]);
                            OrderLogDB::create([
                                'order_id' => $order->id,
                                'editor' => auth()->user()->id,
                                'column_name' => 'book_shipping_date',
                                'log' => $bookShippingDate,
                                'create_time' => date('Y-m-d H:i:s')
                            ]);
                        }else{
                            $order->update(['vendor_arrival_date' => $vendorArrivalDate]);
                        }
                    }
                }
            }
        }
    }
}
