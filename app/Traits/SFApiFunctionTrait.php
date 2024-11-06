<?php

namespace App\Traits;

use Illuminate\Support\Facades\Redis;
use Curl;
use App\Traits\SFClass\BizMsgCrypt;
use App\Models\GateSFShippingLog as SFShippingLogDB;

trait SFApiFunctionTrait
{
    protected function chkSFShippingNumber($sfShippingNos,$phoneNo)
    {
        $nonce = $timestamp = time();
        $appKey = env('SF_APP_KEY');
        $appSecret = env('SF_APP_SECRET');
        $appAesKey = env('SF_APP_AES');
        $msgType = 'GTS_QUERY_TRACK';
        $token = $this->acceccToken();
        $lang = 'zh_HK'; //語言編碼
        $url = env('SF_API_URL')."dispatch";
        $pc = new BizMsgCrypt($token, $appAesKey, $appKey);
        $data = [
            'sfWaybillNoList' => $sfShippingNos, //顺丰运单号(母单号),顺丰运单号与客户订单号二者填其一
            'phoneNo' => $phoneNo, //手机号后四位（下单时使用的手机号mobile）
        ];
        $dataToStr = json_encode($data,true); //字串
        $en = $pc->encryptMsg($dataToStr, $timestamp, $nonce);
        $signature = $en['signature']; //加密簽名
        $encrypt = $en['encrypt'];
        $headers= [
            "msgType:$msgType",
            "appKey:$appKey",
            "token:$token",
            "timestamp:$timestamp",
            "nonce:$nonce",
            "signature:$signature",
            "lang:$lang",
            'Content-Type:application/json',
            'charset:utf-8',
        ];

        $returnJson = Curl::to($url)->withHeaders($headers)->withData($encrypt)->post();
        $returnArray = json_decode($returnJson, true);

        if($returnArray['apiResultCode'] == 0){
            $returnData = $returnArray['apiResultData'];
            $resultArray = $pc->decryptMsg($timestamp, $nonce, $returnData);
            $resultJson = $resultArray[1];
            // $resultJson = '{"msg":"success","code":"0","data":[{"msg":"success","trackDetailItems":[{"gmt": "GMT+08:00","localTm": "2022-11-21 08:04:00","opCode": "31","reasonCode": "","sfWaybillNo": "SF1334456877398","trackAddr": "Dongguan International Small Bag Operation Center","trackCountry": "CN","trackOutRemark": "【Dongguan International Small Bag Operation Center,DongGuan,Guangdongsheng,CN】Shipmen has arrived at the station and unloading.","trackPostCode": "","trackRegionFirst": "Guangdongsheng","trackRegionSecond": "DongGuan"},{"gmt": "GMT+08:00","localTm": "2022-11-21 08:04:00","opCode": "31","reasonCode": "","sfWaybillNo": "SF1334456877398","trackAddr": "Dongguan International Small Bag Operation Center","trackCountry": "CN","trackOutRemark": "【Dongguan International Small Bag Operation Center,DongGuan,Guangdongsheng,CN】Shipmen has arrived at the station and unloading.","trackPostCode": "","trackRegionFirst": "Guangdongsheng","trackRegionSecond": "DongGuan"}],"code":"0","sfWaybillNo":"SF1334456877398"},{"msg":"success","trackDetailItems":[{"gmt": "GMT+08:00","localTm": "2022-11-21 08:04:00","opCode": "31","reasonCode": "","sfWaybillNo": "SF1334452950788","trackAddr": "Dongguan International Small Bag Operation Center","trackCountry": "CN","trackOutRemark": "【Dongguan International Small Bag Operation Center,DongGuan,Guangdongsheng,CN】Shipmen has arrived at the station and unloading.","trackPostCode": "","trackRegionFirst": "Guangdongsheng","trackRegionSecond": "DongGuan"}],"code":"0","sfWaybillNo":"SF1334452950788"},{"msg":"success","trackDetailItems":[{"gmt": "GMT+08:00","localTm": "2022-11-21 08:04:00","opCode": "80","reasonCode": "","sfWaybillNo": "SF1334456730261","trackAddr": "Dongguan International Small Bag Operation Center","trackCountry": "CN","trackOutRemark": "【Dongguan International Small Bag Operation Center,DongGuan,Guangdongsheng,CN】Shipmen has arrived at the station and unloading.","trackPostCode": "","trackRegionFirst": "Guangdongsheng","trackRegionSecond": "DongGuan"}],"code":"0","sfWaybillNo":"SF1334456730261"}],"success":true}';
            // $resultJson = '{"msg":"success","code":"0","data":[{"msg":"success","trackDetailItems":[{"gmt": "GMT+08:00","localTm": "2022-11-21 08:04:00","opCode": "31","reasonCode": "","sfWaybillNo": "SF1334452434204","trackAddr": "Dongguan International Small Bag Operation Center","trackCountry": "CN","trackOutRemark": "【Dongguan International Small Bag Operation Center,DongGuan,Guangdongsheng,CN】Shipmen has arrived at the station and unloading.","trackPostCode": "","trackRegionFirst": "Guangdongsheng","trackRegionSecond": "DongGuan"},{"gmt": "GMT+08:00","localTm": "2022-11-21 08:04:00","opCode": "31","reasonCode": "","sfWaybillNo": "SF1334452434204","trackAddr": "Dongguan International Small Bag Operation Center","trackCountry": "CN","trackOutRemark": "【Dongguan International Small Bag Operation Center,DongGuan,Guangdongsheng,CN】Shipment in transit. Thanks for your waiting.","trackPostCode": "","trackRegionFirst": "Guangdongsheng","trackRegionSecond": "DongGuan"}],"code":"0","sfWaybillNo":"SF1334452434204"}],"success":true}';
            $result = json_decode($resultJson,true);
            if($result['code'] == '0' && $result['msg'] == 'success'){
                return $result['data'];
            }
        }
        return null;
    }

    protected function cancelSFShippingNumber($sfShippingNo)
    {
        $nonce = $timestamp = time();
        $appKey = env('SF_APP_KEY');
        $appSecret = env('SF_APP_SECRET');
        $appAesKey = env('SF_APP_AES');
        $msgType = 'IUOP_CANCEL_ORDER';
        $token = $this->acceccToken();
        $lang = 'zh_HK'; //語言編碼
        $url = env('SF_API_URL')."dispatch";
        $pc = new BizMsgCrypt($token, $appAesKey, $appKey);
        $data = [
            'customerCode' => env('SF_CUSTOMER_CODE'), //客户编码
            'sfWaybillNo' => $sfShippingNo, //顺丰运单号(母单号),顺丰运单号与客户订单号二者填其一
        ];
        $dataToStr = json_encode($data,true); //字串
        $en = $pc->encryptMsg($dataToStr, $timestamp, $nonce);
        $signature = $en['signature']; //加密簽名
        $encrypt = $en['encrypt'];
        $headers= [
            "msgType:$msgType",
            "appKey:$appKey",
            "token:$token",
            "timestamp:$timestamp",
            "nonce:$nonce",
            "signature:$signature",
            "lang:$lang",
            'Content-Type:application/json',
            'charset:utf-8',
        ];
        //log
        $log = SFShippingLogDB::create([
            'type' => '取消',
            'headers' => json_encode($headers),
            'post_json' => $dataToStr,
        ]);
        $returnJson = Curl::to($url)->withHeaders($headers)->withData($encrypt)->post();
        $returnArray = json_decode($returnJson, true);
        if($returnArray['apiResultCode'] == 0){
            $returnData = $returnArray['apiResultData'];
            $resultArray = $pc->decryptMsg($timestamp, $nonce, $returnData);
            $resultJson = $resultArray[1];
            $result = json_decode($resultJson,true);
            // {"msg":"訂單信息不存在","code":"123002","success":false}
            // {"msg":"執行成功","code":"0","data":{"identityUploadUrl":"","childWaybillNoList":[],"invoiceUrl":"http://iuop-uat.sit.sf-express.com:9000/iuop-iuop-uat/api/print/printInvoice?sfWaybillNos=RQAWJJbC7pw2GYcLgmlwaQ%3D%3D","labelUrl":"http://iuop-uat.sit.sf-express.com:9000/iuop-iuop-uat/api/print/printLabel?sfWaybillNos=RQAWJJbC7pw2GYcLgmlwaQ%3D%3D","customerOrderNo":"202308010020001-5566","sfWaybillNo":"SF1334452172519"},"success":true}
            //log
            $log->update([
                'get_json' => $resultJson,
                'rtnCode' => $result['code'],
                'rtnMsg' => $result['msg'],
            ]);
            if($result['code'] == '0' && $result['msg'] == '執行成功'){
                return 'success';
            }
        }else{
            $log->update([
                'get_json' => $returnJson,
                'rtnCode' => $returnArray['apiResultCode'],
                'rtnMsg' => $returnArray['apiErrorMsg'],
            ]);
        }
        return null;
    }

    protected function getSFShippingNumber($param)
    {
        $shipping = $param['shipping'];
        $vendor = $param['vendor'];
        $nonce = $timestamp = time();
        // $nonce = "1637927849983"; //請求隨機碼
        $appKey = env('SF_APP_KEY');
        $appSecret = env('SF_APP_SECRET');
        $appAesKey = env('SF_APP_AES');
        $msgType = 'IUOP_CREATE_ORDER';
        $token = $this->acceccToken();
        $lang = 'zh_HK'; //語言編碼
        $url = env('SF_API_URL')."dispatch";
        if(!empty($token)){
            $customerOrderNo = $shipping->shipping_no.'-'.$param['sno'];
            $pc = new BizMsgCrypt($token, $appAesKey, $appKey);
            $paymentInfo = [
                'payMethod' => '2',
                'payMonthCard' => env('SF_ACCOUNT_NO'),
                'taxPayMethod' => '2',
            ];
            $senderInfo = [
                'company' => mb_substr($vendor->name,0,100),
                'contact' => mb_substr($vendor->contact_person,0,100),
                'country' => 'TW',
                'postCode' => mb_substr('N/A',0,20),
                'regionFirst' => mb_substr('台灣省',0,120),
                'regionSecond' => mb_substr('台灣省',0,120),
                'address' => mb_substr($vendor->factory_address,0,190),
                'telNo' => mb_substr($vendor->tel,0,20),
            ];
            $receiverInfo = [
                'contact' => mb_substr('iCarry-順豐倉儲進貨組#9',0,100),
                'country' => 'TW',
                'postCode' => mb_substr('33856',0,20),
                'regionFirst' => mb_substr('台灣省',0,120),
                'regionSecond' => mb_substr('桃園市',0,120),
                'address' => mb_substr('桃園市蘆竹區海湖北路309巷130號',0,190),
                'telNo' => mb_substr('032753161',0,20),
            ];
            $parcelInfoList = [
                [
                    'name' => '糕餅', //商品名称
                    'unit' => '箱', //货物单位
                    'amount' => '1000',
                    'quantity' => '1',
                    'originCountry' => 'TW',
                ],
            ];
            $data = [
                'customerCode' => env('SF_CUSTOMER_CODE'), //客户编码
                'customerOrderNo' => $customerOrderNo, //客户订单号
                'interProductCode' => 'INT0005', //国际产品映射码，即顺丰物流服务产品代码
                'parcelQuantity' => '1', //包裹总件数(固定为1)
                'declaredValue' => '1', //总商品申报价值，单件商品申报价值*数量获取
                'declaredCurrency' => 'NTD', //申报价值币种
                'paymentInfo' => $paymentInfo,
                'senderInfo' => $senderInfo,
                'receiverInfo' => $receiverInfo,
                'parcelInfoList' => $parcelInfoList,
                'orderOperateType' => '1',
                'remark' => "", //运单备注
            ];
            $dataToStr = json_encode($data,true); //字串

            $en = $pc->encryptMsg($dataToStr, $timestamp, $nonce);
            $signature = $en['signature']; //加密簽名
            $encrypt = $en['encrypt'];
            $headers= [
                "msgType:$msgType",
                "appKey:$appKey",
                "token:$token",
                "timestamp:$timestamp",
                "nonce:$nonce",
                "signature:$signature",
                "lang:$lang",
                'Content-Type:application/json',
                'charset:utf-8',
            ];
            //log
            $log = SFShippingLogDB::create([
                'type' => '取號',
                'headers' => json_encode($headers),
                'post_json' => $dataToStr,
            ]);
            $returnJson = Curl::to($url)->withHeaders($headers)->withData($encrypt)->post();
            $returnArray = json_decode($returnJson, true);
            if($returnArray['apiResultCode'] == 0){
                $returnData = $returnArray['apiResultData'];
                $resultArray = $pc->decryptMsg($timestamp, $nonce, $returnData);
                $resultJson = $resultArray[1];
                $result = json_decode($resultJson,true);
                // {"msg":"客戶編碼不存在或已失效","code":"100024","success":false}
                // {"msg":"執行成功","code":"0","data":{"identityUploadUrl":"","childWaybillNoList":[],"invoiceUrl":"http://iuop-uat.sit.sf-express.com:9000/iuop-iuop-uat/api/print/printInvoice?sfWaybillNos=RQAWJJbC7pw2GYcLgmlwaQ%3D%3D","labelUrl":"http://iuop-uat.sit.sf-express.com:9000/iuop-iuop-uat/api/print/printLabel?sfWaybillNos=RQAWJJbC7pw2GYcLgmlwaQ%3D%3D","customerOrderNo":"202308010020001-5566","sfWaybillNo":"SF1334452172519"},"success":true}
                //log
                $log->update([
                    'get_json' => $resultJson,
                    'rtnCode' => $result['code'],
                    'rtnMsg' => $result['msg'],
                ]);
                if($result['code'] == '0' && $result['msg'] == '執行成功'){
                    return $result['data'];
                }
            }else{
                $log->update([
                    'get_json' => $returnJson,
                    'rtnCode' => $returnArray['apiResultCode'],
                    'rtnMsg' => $returnArray['apiErrorMsg'],
                ]);
            }
        }
        return null;
    }


    protected function acceccToken()
    {
        $timestamp = time();
        $SFaccessToken = Redis::get('SFaccessToken');
        if(empty($SFaccessToken)){
            $SFaccessToken = $this->getSFAcceccToken();
        }else{
            $SFaccessToken = json_decode($SFaccessToken,true);
            if($timestamp > strtotime($SFaccessToken['exprieTime'])){ //重新取token
                $SFaccessToken = $this->getSFAcceccToken();
            }
        }
        if(!empty($SFaccessToken)){
            return $SFaccessToken['accessToken'];
        }else{
            return null;
        }
    }

    protected function getSFAcceccToken()
    {
        $SFaccessToken = [];
        $timestamp = time();
        $appKey = env('SF_APP_KEY');
        $appSecret = env('SF_APP_SECRET');
        $url = env('SF_API_URL')."token?appKey=$appKey&appSecret=$appSecret";
        $response = Curl::to($url)->withHeaders(['Content-Type:text/html','charset:utf-8','Accept:text/html'])->get();
        if(!empty($response)){
            $result = json_decode($response,true);
            if(isset($result['apiResultCode']) && $result['apiResultCode'] == 0){
                $exprieTime = date("Y-m-d H:i:s", $timestamp + $result['apiResultData']['expireIn'] - 200);
                $accessToken = $result['apiResultData']['accessToken'];
                $SFaccessToken = ['exprieTime' => $exprieTime, 'accessToken' => $accessToken];
                Redis::set('SFaccessToken',json_encode($SFaccessToken,true));
            }
        }
        return $SFaccessToken;
    }
}
