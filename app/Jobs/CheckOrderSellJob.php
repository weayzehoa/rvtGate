<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\iCarryUser as UserDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryShipmentLog as ShipmentLogDB;
use App\Models\iCarryGroupBuyingOrder as GroupBuyOrderDB;

use App\Models\ErpOrder as ErpOrderDB;
use App\Models\ErpCOPTG as ErpCOPTGDB;
use App\Models\ErpCOPTH as ErpCOPTHDB;

use App\Models\Sell as SellDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use App\Models\SellAbnormal as SellAbnormalDB;
use App\Models\OrderShipping as OrderShippingDB;
use App\Models\MailTemplate as MailTemplateDB;
use App\Models\SerialNoRecord as SerialNoRecordDB;

use DB;
use Log;
use Exception;

use App\Traits\OrderFunctionTrait;

use App\Jobs\CheckErpSellTaxJob;
use App\Jobs\AdminSendEmail;
use App\Jobs\AdminExportJob;

class CheckOrderSellJob implements ShouldQueue
{
    use OrderFunctionTrait,Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->order;
        $sellAbnormals = SellAbnormalDB::where([['order_id',$order->id],['is_chk',0]])->get();
        //檢查訂單狀態是否為1,2及是否有異常
        if(!empty($order) && ($order->status == 1 || $order->status == 2) && count($sellAbnormals) == 0){
            //清除無法更新的變數
            unset($order->totalWeight);
            unset($order->totalPrice);
            unset($order->totalQty);
            unset($order->totalSellQty);
            unset($order->total_pay);
            unset($order->shipping_memo_vendor);
            $erpOrder = ErpOrderDB::with('items')->where('TC061',$order->order_number)->orWhere('TC061',$order->partner_order_number)->first();
            $shippings = $shipping = [];
            //清除所有相關物流資料
            OrderShippingDB::where('order_id',$order->id)->delete();
            //找出所有出貨資料
            $sellItems = SellItemSingleDB::where('order_number',$order->order_number)
            ->where('is_del',0)
            ->select([
                'express_way',
                'express_no',
            ])->groupBy('express_way','express_no')->get();
            if(count($sellItems) > 0){
                foreach($sellItems as $sellItem){
                    if(!empty($sellItem->express_way) && !empty($sellItem->express_no)){
                        if($sellItem->express_way == '廠商發貨'){
                            $sellItem->express_way = explode('_',$sellItem->express_no)[0];
                            $sellItem->express_no = explode('_',$sellItem->express_no)[1];
                        }
                        $shipping[] = [
                            'order_id' => $order->id,
                            'express_way' => $sellItem->express_way,
                            'express_no' => $sellItem->express_no,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    };
                }
                if(!empty($shipping)){
                    $shippingNumber = $shippingMemo = [];
                    OrderShippingDB::insert($shipping);
                    $orderShippings = OrderShippingDB::where('order_id',$order->id)->get();
                    foreach($orderShippings as $orderShipping){
                        $shippingMemo[] = [
                            'create_time' => str_replace('-','/',$orderShipping->created_at),
                            'express_way' => $orderShipping->express_way,
                            'express_no' => $orderShipping->express_no,
                        ];
                        $shippingNumber[] = $orderShipping->express_no;
                        $shippings[] = $orderShipping->express_way.'_'.$orderShipping->express_no;
                    }
                    $shippingNumber = array_unique($shippingNumber);
                    $shippingMemo = json_encode($shippingMemo);
                    $shippingNumber = mb_substr(join(',',$shippingNumber),0,58);
                    $order->update(['shipping_number' => $shippingNumber, 'shipping_memo' => $shippingMemo]);
                }
                //更新鼎新出貨單編號欄位
                ErpOrderDB::where('TC061',$order->order_number)->orWhere('TC061',$order->partner_order_number)->update(['TC200' => mb_substr(join(',',$shippings),0,58)]);

                //檢查訂單是否有票券
                //檢查訂單是否全部已出貨
                $chkTicket = $chkItemOut = $chkPackageOut = 0;
                $shippingTime = null;
                foreach($order->items as $item){
                    if($item->is_del == 0){ //排除掉被取消的商品
                        if(strstr($item,'BOM')){
                            foreach($item->package as $package){
                                $package->product_category_id == 17 ? $chkTicket++ : '';
                                $sellPackageQty = 0;
                                $sellItems = SellItemSingleDB::where([['order_item_id',$item->id],['order_item_package_id',$package->id],['is_del',0]])->get();
                                if(count($sellItems) > 0){
                                    foreach($sellItems as $sellItem){
                                        $sellPackageQty += $sellItem->sell_quantity;
                                        $shippingTime = $sellItem->sell_date.' '.explode(' ',$sellItem->created_at)[1];
                                    }
                                    if($package->quantity > $sellPackageQty){ //出貨數量小於需求
                                        $chkPackageOut++;
                                    }
                                }else{ //沒有出貨
                                    $chkPackageOut++;
                                }
                            }
                        }else{
                            $item->product_category_id == 17 ? $chkTicket++ : '';
                            $sellItemQty = 0;
                            $sellItems = SellItemSingleDB::where('order_item_id',$item->id)->where('is_del',0)->where(function($query){
                                $query->whereNull('order_item_package_id')->orWhere('order_item_package_id','');
                            })->get();
                            if(count($sellItems) > 0){
                                foreach($sellItems as $sellItem){
                                    $sellItemQty += $sellItem->sell_quantity;
                                    $shippingTime = $sellItem->sell_date.' '.explode(' ',$sellItem->created_at)[1];
                                }
                                if($item->quantity > $sellItemQty){ //出貨數量小於需求
                                    $chkItemOut++;
                                }
                            }else{ //沒有出貨
                                $chkItemOut++;
                            }
                        }
                    }
                }
                if($chkItemOut == 0 && $chkPackageOut == 0){
                    //這邊要建立訂單中含有 運費 折扣...等等的銷貨單
                    $return = $chkErpOrderItem = 0;
                    foreach($erpOrder->items as $erpOrderItem){
                        if($erpOrderItem->TD004 == 999000 || $erpOrderItem->TD004 == 999001 || $erpOrderItem->TD004 == 901001 || $erpOrderItem->TD004 == 901002){ //活動折扣
                            $chkErpOrderItem++;
                        }
                    }
                    $chkErpOrderItem > 0 ? $return = $this->createDigiwinSell() : '';
                    if($return === 0 || $return == 'success'){
                        //將訂單轉為已出貨
                        $order->update(['status' => 3]);
                        $order->shippingData = join(',',$shippings); //客路訂單的物流資料

                        //交流點數訂單更新狀態
                        !empty($order->acOrder) ? $order->acOrder->update(['is_sell' => 1]) : '';

                        //團購訂單須將相關的訂單轉為已出貨
                        if($order->create_type == 'groupbuy'){
                            $groupbuyOrders = GroupBuyOrderDB::where([['partner_order_number',$order->order_number],['status',2]])
                            ->update(['status' => 3, 'shipping_time' => $shippingTime]);
                        }

                        $shipmentLog = ShipmentLogDB::where([['order_id',$order->id],['send',1]])->first();
                        //下面客戶才需要發信件通知, 亞萬 031 改為憑證
                        $v = ['001','002','003','004','005','006','007','008','009','031','037','012','063','072','073'];
                        if(in_array($order->digiwin_payment_id,$v) && empty($shipmentLog)){
                            if($order->receiver_address == '桃園機場/第一航廈出境大廳門口'){
                                $order->airport_location = "第一航廈-台灣宅配通櫃檯：位於 1 樓出境大廳（近 12 號報到櫃檯）";
                            }elseif($order->receiver_address == '桃園機場/第二航廈出境大廳門口'){
                                $order->airport_location = "第二航廈-台灣宅配通櫃檯：位於 3 樓出境大廳（近 19 號報到櫃檯）";
                            }elseif($order->receiver_address == '松山機場/第一航廈台灣宅配通（E門旁）'){
                                $order->airport_location = "第一航廈-台灣宅配通櫃檯：位於 1 樓入境大廳內";
                            }elseif($order->receiver_address == '花蓮航空站/挪亞方舟旅遊'){
                                $order->airport_location = "諾亞方舟旅遊位於 1 樓國際線入境大廳出口處";
                            }

                            $order->receiver_time = str_replace("-","/",substr($order->receiver_key_time,5,5));
                            $order->am_print_link = "https://icarry.me/asiamiles-print.php?o=$order->am_md5";

                            //信件模板
                            if($order->shipping_method != 1){ //非機場提貨
                                $param['model'] = 'NormalOrderMailBody';
                                if(strtolower($order->create_type) == 'asiamiles'){
                                    $param['model'] = 'AsiamileOrderMailBody';
                                }
                            }else{ //機場提貨
                                $param['model'] = 'AirportPickupOrderMailBody';
                                if(strtolower($order->create_type) == 'asiamiles'){
                                    $param['model'] = 'AsiamileAirportPickupOrderMailBody';
                                }
                            }
                            if (strtolower($order->create_type) == 'klook' || $order->create_type == '客路') {
                                $param['model'] = 'KlookOrderMailBody';
                            }
                            if (strtolower($order->create_type) == 'groupbuy') {
                                $param['model'] = 'GroupBuyOrderSellMailBody';
                                //團購產生團主發貨清單
                                $export['con'] = [
                                    'partner_order_number' => $order->order_number,
                                    'status' => '1,2,3,4',
                                ];
                                $export['store'] = true;
                                $export['method'] = 'byQuery';
                                $export['cate'] = 'export';
                                $export['type'] = 'ShipList';
                                $export['model'] = 'groupbuyOrders';
                                $export['admin_id'] = 0;
                                $export['admin_name'] = '系統';
                                $export['export_no'] = time();
                                $export['name'] = '團主發貨清單_依查詢條件';
                                $export['filename'] = $export['name'].'_'.$export['export_no'].'.xlsx';
                                $exportResult = AdminExportJob::dispatchNow($export);
                                $exportResult == 'success' ? $param['files'][0] = $export['filename'] : '';
                            }
                            $param['from'] = 'icarry@icarry.me'; //寄件者
                            $param['name'] = 'iCarry伴手禮專家'; //寄件者名字
                            $param['replyTo'] = 'icarry@icarry.me'; //回信
                            $param['replyName'] = 'iCarry伴手禮專家'; //回信
                            $param['cc'] = 'backup@icarry.me'; //備份一份
                            $param['order'] = $order;
                            if(strtolower($order->create_type) == 'asiamiles'){
                                $mailTemplate = MailTemplateDB::find(6);
                                if($order->shipping_method == 1){
                                    $mailTemplate = MailTemplateDB::find(7);
                                }
                                if(!empty($mailTemplate)){
                                    env('APP_ENV') == 'local' ? $param['subject'] = $mailTemplate->subject.' (測試)' : $param['subject'] = $mailTemplate->subject;
                                }else{
                                    $param['subject'] = 'iCarry 亞洲萬里通 購買憑証通知';
                                }
                            }elseif(strtolower($order->create_type) == 'klook' || $order->create_type == '客路'){
                                $mailTemplate = MailTemplateDB::find(9);
                                if(!empty($mailTemplate)){
                                    env('APP_ENV') == 'local' ? $param['subject'] = $mailTemplate->subject.' (測試)' : $param['subject'] = $mailTemplate->subject;
                                }else{
                                    $param['subject'] = 'iCarry_Klook Order Shipment Notice 訂單出貨通知';
                                }
                            }elseif(strtolower($order->create_type) == 'groupbuy'){
                                $mailTemplate = MailTemplateDB::find(13);
                                if(!empty($mailTemplate)){
                                    env('APP_ENV') == 'local' ? $param['subject'] = $mailTemplate->subject.' (測試)' : $param['subject'] = $mailTemplate->subject;
                                }else{
                                    $param['subject'] = 'iCarry 團購訂單出貨通知';
                                }
                            }else{
                                $mailTemplate = MailTemplateDB::find(4);
                                if($order->shipping_method == 1){
                                    $mailTemplate = MailTemplateDB::find(5);
                                }
                                if(!empty($mailTemplate)){
                                    env('APP_ENV') == 'local' ? $param['subject'] = $mailTemplate->subject.' (測試)' : $param['subject'] = $mailTemplate->subject;
                                }else{
                                    $param['subject'] = 'iCarry 訂單出貨通知';
                                }
                            }
                            $user = UserDB::find($order->user_id);
                            !empty($user) ? $userMail = strtolower($user->email) : $userMail = null;
                            !empty($order->receiver_email) ? $receiverEmail = strtolower($order->receiver_email) : $receiverEmail = null;
                            $order->create_type == '客路' || strtolower($order->create_type) == 'klook' || strtolower($order->create_type) == 'asiamiles' ? $mailTo = $receiverEmail : $mailTo = $userMail;
                            $param['to'] = [];
                            if(env('APP_ENV') == 'local'){
                                $param['to'] = [env('TEST_MAIL_ACCOUNT')]; //收件者, 需使用陣列
                            }else{
                                $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                                if(!empty($mailTo) && preg_match($pattern,$mailTo)){
                                    $param['to'][] = $mailTo; //收件者, 需使用陣列
                                }
                            }

                            //發送mail
                            if(count($param['to']) > 0 && $chkTicket == 0){
                                $result = AdminSendEmail::dispatchNow($param); //馬上執行
                                ShipmentLogDB::create([
                                    'order_id' => $order->id,
                                    'order_number' => $order->order_number,
                                    'user_id' => $order->user_id,
                                    'shipping_method' => $order->shipping_method,
                                    'send' => 1,
                                    'create_time' => date('Y-m-d H:i:s')
                                ]);
                            }

                            //發送團購訂單通知給購買者
                            if(strtolower($order->create_type) == 'groupbuy') {
                                unset($param); //清除參數
                                $param['model'] = 'GroupBuyOrderMailBody';
                                $param['from'] = 'icarry@icarry.me'; //寄件者
                                $param['name'] = 'iCarry伴手禮專家'; //寄件者名字
                                $param['replyTo'] = 'icarry@icarry.me'; //回信
                                $param['replyName'] = 'iCarry伴手禮專家'; //回信
                                $param['cc'] = 'backup@icarry.me'; //備份一份
                                $mailTemplate = MailTemplateDB::find(14);
                                if(!empty($mailTemplate)){
                                    env('APP_ENV') == 'local' ? $param['subject'] = $mailTemplate->subject.' (測試)' : $param['subject'] = $mailTemplate->subject;
                                }else{
                                    $param['subject'] = 'iCarry 團購訂單出貨通知';
                                }
                                $groupbuyOrders = GroupBuyOrderDB::where([['partner_order_number',$order->order_number],['status','>=',2]])->get();
                                foreach($groupbuyOrders as $groupbuyOrder) {
                                    $param['order'] = $groupbuyOrder;
                                    $mailTo = $groupbuyOrder->receiver_email;
                                    $param['to'] = [];
                                    if(env('APP_ENV') == 'local'){
                                        $param['to'] = [env('TEST_MAIL_ACCOUNT')]; //收件者, 需使用陣列
                                    }else{
                                        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                                        if(!empty($mailTo) && preg_match($pattern,$mailTo)){
                                            $param['to'][] = $mailTo; //收件者, 需使用陣列
                                        }
                                    }
                                    if(count($param['to']) > 0){
                                        $result = AdminSendEmail::dispatchNow($param); //馬上執行
                                        ShipmentLogDB::create([
                                            'order_id' => $groupbuyOrder->id,
                                            'order_number' => $groupbuyOrder->order_number,
                                            'user_id' => null,
                                            'shipping_method' => $groupbuyOrder->shipping_method,
                                            'send' => 1,
                                            'create_time' => date('Y-m-d H:i:s')
                                        ]);
                                    }
                                }
                            }
                        }
                    }else{
                        if($return == 'Fail'){
                            Log::info("訂單單號 ".$order->order_number." 建立運費、購物金、折扣...銷貨單失敗。");
                        }elseif($return == 'getNoError'){
                            Log::info("檢查訂單執行程序取銷貨單單號重複。訂單單號 ".$order->order_number." 可能未完成銷貨。");
                        }
                    }
                }
            }
        }
    }

    private function createDigiwinSell()
    {
        $order = $this->order;
        $erpOrder = ErpOrderDB::with('items')->where('TC061',$order->order_number)->orWhere('TC061',$order->partner_order_number)->first();
        $erpSell = $sell = $erpPURTG = $erpPURTH = [];
        $creator = 'iCarryGate';
        $createDate = date('Ymd');
        $createTime = date('H:i:s');
        $sell = SellDB::where([['order_number',$order->order_number],['is_del',0]])->orderBy('sell_date','desc')->first();
        if(!empty($sell)){
            $sellDate = $sell->sell_date;
            $sellDate6 = str_replace('-','',$sellDate);
            $sellDate6 = substr($sellDate6,2);
            try {
                //找出鼎新銷貨單的最後一筆單號
                $chkTemp = SerialNoRecordDB::where([['type','ErpSellNo'],['serial_no','like',"$sellDate6%"]])->orderBy('serial_no','desc')->first();
                !empty($chkTemp) ? $TG002 = $chkTemp->serial_no + 1 : $TG002 = $sellDate6.str_pad(1,5,0,STR_PAD_LEFT);
                $chkTemp = SerialNoRecordDB::create(['type' => 'ErpSellNo','serial_no' => $TG002]);
                //檢查鼎新銷貨單有沒有這個號碼
                $tmp = ErpCOPTHDB::where('TH002','like',"%$sellDate6%")->select('TH002')->orderBy('TH002','desc')->first();
                if(!empty($tmp)){
                    if($tmp->TH002 >= $TG002){
                        $TG002 = $tmp->TH002+1;
                        $chkTemp = SerialNoRecordDB::create(['type' => 'ErpSellNo','serial_no' => $TG002]);
                    }
                }

                //找出中繼今日最後一筆銷貨單號碼的流水號
                $chkTemp = SerialNoRecordDB::where([['type','SellNo'],['serial_no','>=',date('ymd').'00001']])->orderBy('serial_no','desc')->first();
                !empty($chkTemp) ? $sellNo = $chkTemp->serial_no + 1 : $sellNo = date('ymd').str_pad(1,5,0,STR_PAD_LEFT);
                $chkTemp = SerialNoRecordDB::create(['type' => 'SellNo','serial_no' => $sellNo]);
                //檢查中繼有沒有這個號碼
                $tmp = SellItemSingleDB::where('sell_no','>=',date('ymd').'00001')->select('sell_no')->orderBy('sell_no','desc')->first();
                if(!empty($tmp)){
                    if($tmp->sell_no >= $sellNo){
                        $sellNo = $tmp->sellNo+1;
                        $chkTemp = SerialNoRecordDB::create(['type' => 'ErpSellNo','serial_no' => $sellNo]);
                    }
                }
            } catch (Exception $exception) {
                return 'getNoError';
            }

            $totalQty = $amount = $tax = $itemTax = 0;
            $erpCustomer = $order->erpCustomer;
            $erpOrder->TC001 == 'A221' ? $TG001 = 'A231' : '';
            $erpOrder->TC001 == 'A222' ? $TG001 = 'A232' : '';
            $order->digiwin_payment_id == '025' ? $TG001 = 'A237' : '';
            $array1 = [
                'COMPANY' => 'iCarry',
                'CREATOR' => $creator,
                'USR_GROUP' => 'DSC',
                'CREATE_DATE' => $createDate,
                'MODIFIER' => '',
                'MODI_DATE' => '',
                'FLAG' => '1',
                'CREATE_TIME' => $createTime,
                'CREATE_AP' => 'iCarry',
                'CREATE_PRID' => 'COPI08',
                'MODI_TIME' => '',
                'MODI_AP' => '',
                'MODI_PRID' => '',
                'EF_ERPMA001' => '',
                'EF_ERPMA002' => '',
                'TH001' => $TG001, //單別
                'TH002' => $TG002, //單號
                'TH010' => 0, //庫存數量
                'TH011' => '', //小單位
                'TH017' => '', //批號
                'TH019' => '', //客戶品號
                'TH020' => 'N', //確認碼
                'TH021' => 'N', //更新碼
                'TH022' => '', //保留欄位
                'TH023' => '', //保留欄位
                'TH024' => 0, //贈/備品量
                'TH026' => 'N', //結帳碼
                'TH027' => '', //結帳單別
                'TH028' => '', //結帳單號
                'TH029' => '', //結帳序號
                'TH030' => '', //專案代號
                'TH031' => 1, //類型
                'TH032' => '', //暫出單別
                'TH033' => '', //暫出單號
                'TH034' => '', //暫出序號
                'TH039' => '', //預留欄位
                'TH040' => '', //預留欄位
                'TH041' => '', //預留欄位
                // 'TH042' => '', //包裝數量
                // 'TH043' => '', //贈/備品包裝量
                'TH044' => '', //包裝單位
                'TH045' => '', //發票號碼
                'TH046' => '', //生產加工包裝資訊
                // 'TH057' => '', //產品序號數量
                'TH074' => '', //CRM來源
                'TH075' => '', //CRM單別
                'TH076' => '', //CRM單號
                'TH077' => '', //CRM序號
                'TH099' => 1, //品號稅別
            ];
            $i = 1;
            foreach($erpOrder->items as $erpOrderItem){
                $array2 = $erpCOPTH = [];
                $itemPrice = $erpOrderItem->TD011;
                $price = $itemPrice;
                if($erpOrder->TC016 == 1){
                    $priceWithoutTax = round($price / 1.05,0);
                    $itemTax = round($priceWithoutTax * 0.05,0);
                }elseif($erpOrder->TC016 == 2){
                    $priceWithoutTax = $price;
                    $itemTax = round($price * 0.05,0);
                }else{
                    $priceWithoutTax = $price;
                    $itemTax = 0;
                }
                //建立運費、行郵稅、購物金、折扣...相關出貨單
                if($erpOrderItem->TD004 == 999000 || $erpOrderItem->TD004 == 999001 || $erpOrderItem->TD004 == 901001 || $erpOrderItem->TD004 == 901002){
                    $memo = null;
                    $erpOrderItem->TD004 == 999000 ? $memo = '活動折扣' : '';
                    $erpOrderItem->TD004 == 999001 ? $memo = '購物金' : '';
                    $erpOrderItem->TD004 == 901001 ? $memo = '運費' : '';
                    $erpOrderItem->TD004 == 901002 ? $memo = '行郵稅' : '';
                    if(!empty($memo)){
                        $sellItemSingle = SellItemSingleDB::where([['order_number',$order->order_number],['memo',$memo],['is_del',0]])->first();
                        if(empty($sellItemSingle)){
                            $amount += $priceWithoutTax;
                            $tax += $itemTax;
                            $totalQty++;
                            $sellItemSingle = [
                                'sell_no' => $sellNo,
                                'erp_sell_no' => $TG002,
                                'erp_sell_sno' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                                'erp_order_no' => $erpOrderItem->TD002,
                                'erp_order_sno' => $erpOrderItem->TD003,
                                'order_number' => $order->order_number,
                                'order_item_id' => null,
                                'order_item_package_id' => null,
                                'order_quantity' => 1,
                                'sell_quantity' => 1,
                                'sell_date' => $sellDate,
                                'sell_price' => round($itemPrice,2),
                                'product_model_id' => null,
                                'memo' => $memo,
                                'direct_shipment' => 0,
                                'express_way' => null,
                                'express_no' => null,
                                'created_at' => date('Y-m-d H:i:s'),
                            ];
                            SellItemSingleDB::create($sellItemSingle);
                            $array2 = [
                                'TH003' => str_pad($i,4,'0',STR_PAD_LEFT), //序號
                                'TH004' => $erpOrderItem->TD004, //品號
                                'TH005' => $erpOrderItem->TD005, //品名
                                'TH006' => $erpOrderItem->TD006, //規格
                                'TH007' => $erpOrderItem->TD007, //庫別
                                'TH008' => $erpOrderItem->TD008, //數量
                                'TH009' => $erpOrderItem->TD010, //單位
                                'TH012' => round($itemPrice,2), //單價
                                'TH013' => round($price,0), //金額
                                'TH014' => $erpOrderItem->TD001, //訂單單別
                                'TH015' => $erpOrderItem->TD002, //訂單單號
                                'TH016' => $erpOrderItem->TD003, //訂單序號
                                'TH018' => '', //備註
                                'TH025' => $erpOrderItem->TD026, //折扣率
                                'TH035' => round($priceWithoutTax,0), //原幣未稅金額
                                'TH036' => round($itemTax,0), //原幣稅額
                                'TH037' => round($priceWithoutTax,0), //本幣未稅金額
                                'TH038' => round($itemTax,0), //本幣稅額
                                'TH047' => $order->partner_order_number ?? $order->order_number, //網購訂單編號
                            ];
                            $erpCOPTH = array_merge($array1,$array2);
                            ErpCOPTHDB::create($erpCOPTH);
                            $i++;
                        }
                    }
                }
            }
            $erpSellItems = ErpCOPTHDB::where([['TH001',$TG001],['TH002',$TG002]])->get();
            if(!empty($erpSellItems) && count($erpSellItems) > 0){
                // 建立中繼銷貨單單頭
                $sell = [
                    'sell_no' => $sellNo,
                    'erp_sell_no' => $TG002,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'erp_order_number' => $erpOrder->TC002,
                    'quantity' => $totalQty,
                    'amount' => round($amount,0),
                    'tax' => round($tax,0),
                    'is_del' => 0,
                    'sell_date' => $sellDate,
                    'tax_type' => $erpOrder->TC016,
                    'memo' => null,
                ];
                SellDB::create($sell);
                //建立鼎新銷貨單單頭
                $erpSellData = [
                    'COMPANY' => 'iCarry',
                    'CREATOR' => $creator,
                    'USR_GROUP' => 'DSC',
                    'CREATE_DATE' => $createDate,
                    'MODIFIER' => '',
                    'MODI_DATE' => '',
                    'FLAG' => 1,
                    'CREATE_TIME' => $createTime,
                    'CREATE_AP' => 'iCarry',
                    'CREATE_PRID' => 'COPI08',
                    'MODI_TIME' => '',
                    'MODI_AP' => '',
                    'MODI_PRID' => '',
                    'EF_ERPMA001' => '',
                    'EF_ERPMA002' => '',
                    'TG001' => $TG001, //單別
                    'TG002' => $TG002, //單號
                    'TG003' => str_replace(['-','/'],['',''],$sellDate), //銷貨日期
                    'TG004' => $erpCustomer->MA001, //客戶代號
                    'TG005' => $erpOrder->TC005, //部門
                    'TG006' => $erpOrder->TC006, //業務員
                    'TG007' => $erpCustomer->MA003, //客戶全名
                    'TG008' => $erpOrder->TC010, //送貨地址一
                    'TG009' => $erpOrder->TC011, //送貨地址二
                    'TG010' => '001', //出貨廠別
                    'TG011' => 'NTD', //幣別
                    'TG012' => 1, //匯率
                    'TG013' => round($amount,0), //原幣銷貨金額
                    'TG014' => '', //發票號碼
                    'TG015' => $TG001 == 'A231' ? $erpCustomer->MA010 : $order->invoice_number, //統一編號
                    'TG016' => $erpCustomer->MA037, //發票聯數
                    'TG017' => $erpOrder->TC016, //課稅別
                    'TG018' => '', //發票地址一
                    'TG019' => '', //發票地址二
                    'TG020' => '', //備註
                    'TG021' => '', //發票日期
                    'TG022' => 0, //列印次數
                    'TG023' => 'N', //確認碼
                    'TG024' => 'N', //更新碼
                    'TG025' => round($tax,0), //原幣銷貨稅額
                    'TG026' => '', //收款業務員
                    'TG027' => '', //備註一
                    'TG028' => '', //備註二
                    'TG029' => '', //備註三
                    'TG030' => 'N', //發票作廢
                    'TG031' => 1, //通關方式
                    'TG032' => 0, //件數
                    'TG033' => $totalQty, //總數量
                    'TG034' => 'N', //現銷
                    'TG035' => '', //員工代號
                    'TG036' => 'N', //產生分錄碼(收入)
                    'TG037' => 'N', //產生分錄碼(成本)
                    'TG038' => substr(str_replace(['-','/'],['',''],$sellDate),0,6), //申報年月
                    'TG039' => '', //L/C_NO
                    'TG040' => '', //INVOICE_NO
                    'TG041' => 0, //發票列印次數
                    'TG042' => str_replace(['-','/'],['',''],$sellDate), //單據日期
                    'TG043' => '', //確認者
                    'TG044' => 0.05, //營業稅率
                    'TG045' => round($amount,0), //本幣銷貨金額
                    'TG046' => round($tax,0), //本幣銷貨稅額
                    'TG047' => 'N', //簽核狀態碼
                    'TG048' => '', //報單號碼
                    'TG049' => $erpOrder->TC046, //送貨客戶全名
                    'TG050' => $erpOrder->TC018, //連絡人
                    'TG051' => $erpOrder->TC047, //TEL_NO
                    'TG052' => $erpOrder->TC048, //FAX_NO
                    'TG053' => '', //出貨通知單別
                    'TG054' => '', //出貨通知單號
                    // 'TG055' => '', //預留欄位
                    'TG056' => 1, //交易條件
                    'TG057' => 0, //總包裝數量
                    'TG058' => 0, //傳送次數
                    'TG059' => '', //訂單單別
                    'TG060' => '', //訂單單號
                    'TG061' => '', //預收待抵單別
                    'TG062' => '', //預收待抵單號
                    'TG063' => 0, //沖抵金額
                    'TG064' => 0, //沖抵稅額
                    'TG065' => $erpOrder->TC059, //付款條件代號
                    'TG066' => $erpOrder->TC062, //收貨人
                    'TG067' => '', //指定日期
                    'TG068' => '', //配送時段
                    'TG069' => '', //貨運別
                    'TG070' => 0, //代收貨款
                    'TG071' => 0, //運費
                    'TG072' => 'N', //產生貨運文字檔
                    'TG073' => 0, //客戶描述
                    'TG074' => '', //作廢日期
                    'TG075' => '', //作廢時間
                    'TG076' => '', //專案作廢核准文號
                    'TG077' => '', //作廢原因
                    'TG078' => '', //發票開立時間
                    'TG079' => '', //載具顯碼ID
                    'TG080' => '', //載具類別號碼
                    'TG081' => '', //載具隱碼ID
                    'TG082' => '', //發票捐贈對象
                    'TG083' => '', //發票防偽隨機碼
                    'TG106' => '', //來源
                    'TG129' => $order->receiver_nation_number.$order->receiver_phone_number ? $order->receiver_nation_number.$order->receiver_phone_number : $order->receiver_tel, //行動電話
                    'TG130' => '', //信用卡末四碼
                    'TG131' => '', //連絡人EMAIL
                    'TG132' => '', //買受人適用零稅率註記
                    'TG200' => '', //載具行動電話
                    'TG134' => '', //貨運單號
                    'TG091' => 0, //原幣應稅銷售額
                    'TG092' => 0, //原幣免稅銷售額
                    'TG093' => 0, //本幣應稅銷售額
                    'TG094' => 0, //本幣免稅銷售額
                ];
                $erpSell = ErpCOPTGDB::create($erpSellData);
                //檢查Erp銷貨單稅額是否正確
                CheckErpSellTaxJob::dispatch($erpSell);
                return 'success';
            }
            return 'fail';
        }
    }

    protected    function randomString($length = 1, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        if (!is_int($length) || $length < 0) {
            return false;
        }
        $characters_length = strlen($characters) - 1;
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, $characters_length)];
        }
        return $string;
    }
}
