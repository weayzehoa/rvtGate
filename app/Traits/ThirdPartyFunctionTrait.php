<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Curl;

trait ThirdPartyFunctionTrait
{
    protected function cancelSendToShopcom($order_number,$order_time,$Refund_Amount=0,$RID,$Click_ID){
        if(empty($RID)){
            return false;
        }
        $Refund_Amount=($Refund_Amount>0)?$Refund_Amount*-1:$Refund_Amount;
        //Refund_Amount=消費者實際針對商品所支付的金額，也就是扣除運費以及折價券後的金額。
        /*
        <Offer_ID> 是固定字串,我們會於完成第一階段的「雙方網站連結」測試後提供給您 = 3445
        <Advertiser_ID>是固定字串,我們會於完成第一階段的「雙方網站連結」測試後提供給您 = 3523
        <Commission_Amount>請輸入佣金金額，負數，計算方式為退款的金額乘上店家夥伴商店欲撥給美安公司的佣金百分比。 4%
        <Refund_Amount>請輸入退款金額，負數
        <Origingal_Order_ID>請輸入此筆訂單原本的訂單編號
        <RID>請輸入此筆訂單原本所記錄的RID號碼  [不用]
        <Click_ID>請輸入此筆訂單原本所記錄的Click_ID號碼 [不用]
        <yyyy-mm-dd> 請輸入訂單日期	 [order_time]
        */
        $Advertiser_ID=3523;
        $Offer_Id=3445;
        $Commission_Amount=$Refund_Amount*0.04;
        $url="https://api.hasoffers.com/Api?Format=json&Target=Conversion&Method=create&Service=HasOffers&Version=2&NetworkId=marktamerica&NetworkToken=NETPYKNAYOswzsboApxaL6GPQRiY2s&data[offer_id]={$Offer_Id}&data[advertiser_id]={$Advertiser_ID}&data[sale_amount]={$Refund_Amount}&data[affiliate_id]=3&data[payout]={$Commission_Amount}&data[revenue]={$Commission_Amount}&data[advertiser_info]={$order_number}&data[affiliate_info1]={$RID}&data[ad_id]={$Click_ID}&data[is_adjustment]=1&data[session_datetime]={$order_time}";

        $Advertiser_ID=3535;
        $Offer_Id=3455;
        $Commission_Amount=$Refund_Amount*0.06;
        $url="https://api.hasoffers.com/Api?Format=json&Target=Conversion&Method=create&Service=HasOffers&Version=2&NetworkId=marktamerica&NetworkToken=NETPYKNAYOswzsboApxaL6GPQRiY2s&data[offer_id]={$Offer_Id}&data[advertiser_id]={$Advertiser_ID}&data[sale_amount]={$Refund_Amount}&data[affiliate_id]=3&data[payout]={$Commission_Amount}&data[revenue]={$Commission_Amount}&data[advertiser_info]={$order_number}&data[affiliate_info1]={$RID}&data[ad_id]={$Click_ID}&data[is_adjustment]=1&data[session_datetime]={$order_time}";
        $response = Curl::to($url)->withHeaders(['Content-Type:text/html','charset:utf-8','Accept:text/html'])->get();
        return $response;
    }

    protected function cancelSendToTradevan($order_number,$order_time,$Refund_Amount=0,$RID,$Click_ID){
        if(empty($RID)){
            return false;
        }
        $Refund_Amount=($Refund_Amount>0)?$Refund_Amount*-1:$Refund_Amount;
        //Refund_Amount=消費者實際針對商品所支付的金額，也就是扣除運費以及折價券後的金額。
        $TL_Offer_ID="jZ4fa+yxTn8yohBAjj2Kpqe+F5yltyHtdhoTtQmCdjE=";
        $TL_Advertiser_ID="SDmCkqOZqrIvDOAfpucjD6MXvSfLDIrK9ZKdlzD5bks=";
        $Commission_Amount=$Refund_Amount*0.06;
        $url="https://likeytw.tradevan.com.tw/aptry/likeytw/Api?Method=delete&Service=HasOffers&TL_Offer_ID={$TL_Offer_ID}&TL_Advertiser_ID={$TL_Advertiser_ID}&TL_Refund_Amount={$Refund_Amount}&TL_Commission_Amount={$Commission_Amount}&TL_Order_Id={$order_number}&TL_Rid={$RID}&TL_Click_ID={$Click_ID}&Date_Time={$order_time}";

        $response = Curl::to($url)->withHeaders(['Content-Type:text/html','charset:utf-8','Accept:text/html'])->get();
        return $response;
    }

    protected function writeShopComLog($api,$data,$key){

    }

    protected function shopeeSetTrackingNo($orderSn,$trackingNumber,$partnerId,$shopId,$key,$accessToken){
        $partnerId = intval($partnerId);
        $shopId = intval($shopId);
        $apiHost = env('SHOPEE_API_HOST');
        $apiPath = "/api/v2/logistics/ship_order";
        $api = $apiHost.$apiPath;
        $timestamp = time();
        $signString = "{$partnerId}{$apiPath}{$timestamp}{$accessToken}{$shopId}";
        $sign = hash_hmac('sha256', $signString, $key);
        $data = array(
            "access_token" => $accessToken,
            "timestamp" => $timestamp,
            "partner_id" => $partnerId,
            "shop_id" => $shopId,
            "sign" => $sign
        );
        $para=$this->shopeeGetShippingParameter($orderSn, $partnerId, $shopId, $key, $accessToken);
        $shipping = json_decode($para,true);
        if(isset($shipping["response"]["info_needed"]["dropoff"])){
            return false;
        }elseif(isset($shipping["response"]["info_needed"]["pickup"])){
            //不在ICARRY需要處理的業務
            return false;
        }elseif(isset($shipping["response"]["info_needed"]["non_integrated"])){
            $package_number="";
            $array = array(
                "order_sn" => $orderSn,
                "non_integrated" => array(
                    "tracking_number" => $trackingNumber
                )
            );
            $json=json_encode($array, JSON_UNESCAPED_UNICODE);
            return $this->shopeeApiPost($api, $data, $json);
        }else{
            return false;
        }
    }

    protected function shopeeGetShippingParameter($orderSn,$partnerId,$shopId,$key,$accessToken){
        $partnerId = intval($partnerId);
        $shopId = intval($shopId);
        $apiHost = env('SHOPEE_API_HOST');
        $apiPath = "/api/v2/logistics/get_shipping_parameter";
        $api = $apiHost.$apiPath;
        $timestamp = time();
        $signString = "{$partnerId}{$apiPath}{$timestamp}{$accessToken}{$shopId}";
        $sign = hash_hmac('sha256', $signString, $key);
        $data = array(
            "access_token" => $accessToken,
            "timestamp" => $timestamp,
            "partner_id" => $partnerId,
            "shop_id" => $shopId,
            "sign" => $sign,
            "order_sn"=>$orderSn
        );
        return $this->shopeeApiGet($api, $data);
    }

    protected function shopeeApiGet($api,$data){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api . "?" . http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    protected function shopeeApiPost($api,$data,$json){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api . "?" . http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
