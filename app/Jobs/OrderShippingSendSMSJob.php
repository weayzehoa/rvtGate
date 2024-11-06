<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryUser as UserDB;
use App\Models\ShippingVendor as ShippingVendorDB;
use App\Models\SystemSetting as SystemSettingDB;
use App\Models\iCarryShopeeApiSet as ShopeeApiSetDB;

use App\Traits\ThirdPartyFunctionTrait;
use DB;

class OrderShippingSendSMSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,ThirdPartyFunctionTrait;
    protected $orderNumber;
    protected $shippings;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($orderNumber,$shippings = [])
    {
        $this->orderNumber = $orderNumber;
        $this->shippings = $shippings;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $key = env('APP_AESENCRYPT_KEY');
        $order = OrderDB::where('order_number',$this->orderNumber)
        ->select([
            'id',
            'order_number',
            'user_id',
            'ship_to',
            'receiver_name',
            'receiver_address',
            'receiver_key_time',
            'shipping_method',
            'partner_order_number',
            'create_type',
            'create_time',
            DB::raw("IF(receiver_tel IS NULL,'',AES_DECRYPT(receiver_tel,'$key')) as receiver_tel"),
        ])->first();
        $shippings = $this->shippings;
        $systemSetting = SystemSettingDB::first();
        count($shippings) > 0 ? $notify = true : $notify = false;
        if(count($shippings) > 0 && !empty($order)){
            $flyMessage = $flyMessageEn = $smsSchedule = $shippingNumbers = [];
            $expressWay = $expressWayEn = $expressNo = $message = $messageEn = null;
            //取貨時間及電話資料
            $receiverKeyTime5char=str_replace('-', '/', substr($order->receiver_key_time, 5, 5));
            $receiverKeyTimeHm=substr($order->receiver_key_time, 11, 5);
            $receiverTel=str_replace(array("o","-"," ","++"),array("+","","","+"),$order->receiver_tel);
            $secrtKey = env('APP_AESENCRYPT_KEY');
            $user = UserDB::select([
                'nation',
                DB::raw("IF(mobile IS NULL,'',AES_DECRYPT(mobile,'$secrtKey')) as mobile"),
            ])->find($order->user_id);
            !empty($user) ? $userTel = $user->nation.$user->mobile : $userTel = null;
            for($i=0;$i<count($shippings);$i++){
                $shippingNumbers[] = $shippings[$i]['express_no'];
            }
            if($order->receiver_name != '蝦皮台灣特選店'){ //狀態為已出貨發送簡訊
                if($order->shipping_method == 1){
                    for($i=0; $i<count($shippings); $i++){
                        $expressWay = $shippings[$i]['express_way'];
                        $expressNo = $shippings[$i]['express_no'];
                        if(($order->create_type=="klook"  || $order->create_type=="KKday") && $order->receiver_address=="松山機場/第一航廈台灣宅配通（E門旁）"){
                            $flyMessage[]="您好，您訂購的iCarry【訂單編號 / {$order->partner_order_number}】可在{$receiverKeyTime5char}於松山機場提貨。我司會將商品交寄至松山機場內的“台灣宅配通服務台”位於E門旁，您的商品取貨號：{$expressNo}，取件人:{$order->receiver_name} 。";
                            $flyMessageEn[]="Hi! Your order【{$order->partner_order_number}】 will be ready for pick up on {$receiverKeyTime5char} at Pelican Songshan Airport counter which is next to Door E. ✈Pickup No.: {$expressNo} Receiver: {$order->receiver_name} .";
                        }elseif($expressWay=="台灣宅配通"){
                            if($order->create_type=="klook" || $order->create_type=="KKday"){
                                if($order->receiver_address=="桃園機場/第一航廈出境大廳門口"){
                                    $flyMessage[]="您好，您訂購的iCarry【訂單編號 / {$order->partner_order_number}】可在{$receiverKeyTime5char}於桃園機場提貨。我司會將商品交寄至宅配通第一航廈（桃園機場航廈-營業站）即「行李寄存打包處」，位於出境大厅12號櫃台旁，您的商品取貨號：{$expressNo} 取件人: {$order->receiver_name}。";
                                    $flyMessageEn[]="Hi! Your order【{$order->partner_order_number}】 will be ready for pick up on {$receiverKeyTime5char} at Pelican Tayouan T1 counter which is next to No.12 check-in counter (north side of the 1st floor departure hall). ✈Pickup No.: {$expressNo} Receiver: {$order->receiver_name}";
                                }elseif($order->receiver_address=="桃園機場/第二航廈出境大廳門口"){
                                    $flyMessage[]="您好，您訂購的iCarry【訂單編號 / {$order->partner_order_number}】可在{$receiverKeyTime5char}於桃園機場提貨。我司會將商品交寄至宅配通第二航廈（桃園機場航廈-營業站）即「行李寄存打包處」，位於出境大厅19號櫃台旁，您的商品取貨號：{$expressNo} 取件人: {$order->receiver_name}。";
                                    $flyMessageEn[]="Hi! Your order【{$order->partner_order_number}】 will be ready for pick up on {$receiverKeyTime5char} at Pelican Tayouan T2 counter which is next to No.19 check-in counter (south side of the 3rd floor departure hall). ✈Pickup No.: {$expressNo} Receiver: {$order->receiver_name}";
                                }else if($order->receiver_address=="松山機場/第一航廈台灣宅配通（E門旁）"){
                                    $flyMessage[]="您好，您訂購的iCarry【訂單編號 / {$order->partner_order_number}】可在{$receiverKeyTime5char}於松山機場提貨。我司會將商品交寄至松山機場內的“台灣宅配通服務台”位於E門旁，您的商品取貨號：{$expressNo}，取件人:{$order->receiver_name}，黑猫松机服務櫃檯電話:02-25464772。";
                                    $flyMessageEn[]="Hi! Your order【{$order->partner_order_number}】 will be ready for pick up on {$receiverKeyTime5char} at Pelican Songshan Airport counter which is next to Door E. ✈Pickup No.: {$expressNo}  Receiver: {$order->receiver_name}, Counter Tel.: 02-25464772";
                                }elseif($order->receiver_address=="花蓮航空站/挪亞方舟旅遊"){
                                    $flyMessage[]="您好，您訂購的iCarry【訂單編號 / {$order->partner_order_number}】可在{$receiverKeyTime5char}於桃園機場提貨。我司會將商品交寄至諾亞方舟旅遊位於 1 樓國際線入境大廳出口處，您的商品取貨號：{$expressNo} 取件人: {$order->receiver_name}。";
                                    $flyMessageEn[]="Hi! Your order【{$order->partner_order_number}】 will be ready for pick up on {$receiverKeyTime5char} at Noah’s Ark Tour is located at the exit of the International Line Entry Hall on the 1st floor. ✈Pickup No.: {$expressNo} Receiver: {$order->receiver_name}";
                                }
                            }else{
                                if($order->receiver_address=="桃園機場/第一航廈出境大廳門口"){
                                    $flyMessage[]="您好，您訂購的iCarry【訂單編號 / {$order->order_number}】可在{$receiverKeyTime5char}於桃園機場提貨。我司會將商品交寄至宅配通第一航廈（桃園機場航廈-營業站）即「行李寄存打包處」，位於出境大厅12號櫃台旁，您的商品取貨號：{$expressNo} 取件人: {$order->receiver_name}。";
                                    $flyMessageEn[]="Hi! Your order【{$order->order_number}】 will be ready for pick up on {$receiverKeyTime5char} at Pelican Tayouan T1 counter which is next to No.12 check-in counter (north side of the 1st floor departure hall). ✈Pickup No.: {$expressNo} Receiver: {$order->receiver_name}";
                                }elseif($order->receiver_address=="桃園機場/第二航廈出境大廳門口"){
                                    $flyMessage[]="您好，您訂購的iCarry【訂單編號 / {$order->order_number}】可在{$receiverKeyTime5char}於桃園機場提貨。我司會將商品交寄至宅配通第二航廈（桃園機場航廈-營業站）即「行李寄存打包處」，位於出境大厅19號櫃台旁，您的商品取貨號：{$expressNo} 取件人: {$order->receiver_name}。";
                                    $flyMessageEn[]="Hi! Your order【{$order->order_number}】 will be ready for pick up on {$receiverKeyTime5char} at Pelican Tayouan T2 counter which is next to No.19 check-in counter (south side of the 3rd floor departure hall). ✈Pickup No.: {$expressNo} Receiver: {$order->receiver_name}";
                                }else if($order->receiver_address=="松山機場/第一航廈台灣宅配通（E門旁）"){
                                    $flyMessage[]="您好，您訂購的iCarry【訂單編號 / {$order->order_number}】可在{$receiverKeyTime5char}於松山機場提貨。我司會將商品交寄至松山機場內的“台灣宅配通服務台”位於E門旁，您的商品取貨號：{$expressNo}，取件人:{$order->receiver_name}，黑猫松机服務櫃檯電話:02-25464772。";
                                    $flyMessageEn[]="Hi! Your order【{$order->order_number}】 will be ready for pick up on {$receiverKeyTime5char} at Pelican Songshan Airport counter which is next to Door E. ✈Pickup No.: {$expressNo}  Receiver: {$order->receiver_name}, Counter Tel.: 02-25464772";
                                }elseif($order->receiver_address=="花蓮航空站/挪亞方舟旅遊"){
                                    $flyMessage[]="您好，您訂購的iCarry【訂單編號 / {$order->order_number}】可在{$receiverKeyTime5char}於桃園機場提貨。我司會將商品交寄至諾亞方舟旅遊位於 1 樓國際線入境大廳出口處，您的商品取貨號：{$expressNo} 取件人: {$order->receiver_name}。";
                                    $flyMessageEn[]="Hi! Your order【{$order->order_number}】 will be ready for pick up on {$receiverKeyTime5char} at Noah’s Ark Tour is located at the exit of the International Line Entry Hall on the 1st floor. ✈Pickup No.: {$expressNo} Receiver: {$order->receiver_name}";
                                }
                            }
                        }
                    }
                }else{
                    for($i=0; $i<count($shippings); $i++){
                        $expressNo .= ','.$shippings[$i]['express_no'];
                        $expressWay .= ','.$shippings[$i]['express_way'];
                        $tmp = ShippingVendorDB::where('name',$shippings[$i]['express_way'])->select('name_en')->first();
                        !empty($tmp) ? $expressWayEn .= ','.$tmp->name_en : '';
                    }

                    $expressNo = ltrim($expressNo,',');
                    $expressWay = ltrim($expressWay,',');
                    $expressWayEn = ltrim($expressWayEn,',');
                    if($order->create_type=="klook" || $order->create_type=="KKday" || $order->create_type=="Amazon"){
                        $message="您好，您的訂單【訂單編號 / {$order->partner_order_number}】已經為您出貨了，您可以至【物流業者 / {$expressWay}】查詢您的快遞單號【快遞單號 / {$expressNo}】，謝謝您。";
                        $messageEn="Hi! Your order【{$order->partner_order_number}】 has been shipped out. Your tracking number is {$expressNo} . You may check your parcel via {$expressWayEn}. Thank you!";
                    }else{
                        $message="您好，您的訂單【訂單編號 / {$order->order_number}】已經為您出貨了，您可以至【物流業者 / {$expressWay}】查詢您的快遞單號【快遞單號 / {$expressNo}】，謝謝您。";
                    }
                }

                if(!empty($message)){
                    $smsSchedule[] = [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'mobile' => $order->shipping_method == 1 ? $receiverTel : $userTel,
                        'message' => $message,
                        'vendor' => $systemSetting->sms_supplier,
                        'vendor_name' => $systemSetting->sms_supplier,
                        'create_time' => date('Y-m-d H:i:s'),
                    ];
                    if(!empty($messageEn)){
                        $smsSchedule[] = [
                            'order_id' => $order->id,
                            'user_id' => $order->user_id,
                            'mobile' => $order->shipping_method == 1 ? $receiverTel : $userTel,
                            'message' => $messageEn,
                            'vendor' => $systemSetting->sms_supplier,
                            'vendor_name' => $systemSetting->sms_supplier,
                            'create_time' => date('Y-m-d H:i:s'),
                        ];
                    }
                }else{
                    if(count($flyMessage) > 0){
                        foreach($flyMessage as $key => $fMessage){
                            if(!empty($fMessage)){ //中文訊息
                                $smsSchedule[] = [
                                    'order_id' => $order->id,
                                    'user_id' => $order->user_id,
                                    'mobile' => $order->shipping_method == 1 ? $receiverTel : $userTel,
                                    'message' => $fMessage,
                                    'vendor' => $systemSetting->sms_supplier,
                                    'vendor_name' => $systemSetting->sms_supplier,
                                    'create_time' => date('Y-m-d H:i:s'),
                                ];
                            }
                            if(!empty($flyMessageEn[$key])){ //英文訊息
                                $smsSchedule[] = [
                                    'order_id' => $order->id,
                                    'user_id' => $order->user_id,
                                    'mobile' => $order->shipping_method == 1 ? $receiverTel : $userTel,
                                    'message' => $flyMessageEn[$key],
                                    'vendor' => $systemSetting->sms_supplier,
                                    'vendor_name' => $systemSetting->sms_supplier,
                                    'create_time' => date('Y-m-d H:i:s'),
                                ];
                            }
                        }
                    }
                }
                //蝦皮訂單不發簡訊
                if($order->create_type == 'shopee'){
                    if(count($shippingNumbers) > 0){
                        foreach($shippingNumbers as $key => $shippingNumber){
                            if(!empty($shippingNumber)){
                                $country = null;
                                $order->ship_to == '新加坡' ? $country = strtoupper('SG') : '';
                                $order->ship_to == '台灣' ? $country = strtoupper('TW') : '';
                                if($country == 'TW' || $country == 'SG'){
                                    $shopee = [
                                        "SG" => [
                                            "partner_id" => env('SHOPEE_SG_PARTNER_ID'),
                                            "shop_id" => env('SHOPEE_SG_SHOP_ID'),
                                            "key" => env('SHOPEE_SG_KEY'),
                                        ],
                                        "TW" => [
                                            "partner_id" => env('SHOPEE_TW_PARTNER_ID'),
                                            "shop_id" => env('SHOPEE_TW_SHOP_ID'),
                                            "key" => env('SHOPEE_TW_KEY'),
                                        ]
                                    ];
                                    $shopeeApiSet = ShopeeApiSetDB::where([['region',$country],['partner_id',$shopee[$country]["partner_id"]],['shop_id',$shopee[$country]["shop_id"]],['partner_key',$shopee[$country]["key"]]])->first();
                                    if(!empty($shopeeApiSet)){
                                        $accessToken = $shopeeApiSet->access_token;
                                        $result = $this->shopeeSetTrackingNo($order->partner_order_number,$shippingNumber,$shopee[$country]["partner_id"],$shopee[$country]["shop_id"],$shopee[$country]["key"],$accessToken);
                                    }
                                }
                            }
                        }
                    }
                }elseif($order->create_type == 'web'){
                    if(count($smsSchedule) > 0){
                        for($i=0;$i<count($smsSchedule);$i++){
                            //發送SMS
                            $sms['user_id'] = $order->user_id;
                            $sms['supplier'] = $systemSetting->sms_supplier;
                            $sms['message'] = $smsSchedule[$i]['message'];
                            $sms['phone'] = $smsSchedule[$i]['mobile'];
                            AdminSendSMS::dispatch($sms); //放入隊列
                            // AdminSendSMS::dispatchNow($sms); //馬上執行
                        }
                    }
                }
            }
        }
    }
}
