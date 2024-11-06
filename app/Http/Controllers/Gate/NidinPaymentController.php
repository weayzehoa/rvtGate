<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\IpAddress as IpAddressDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\NidinOrder as NidinOrderDB;
use App\Models\NidinPayment as NidinPaymentDB;
use App\Models\NidinPaymentLog as NidinPaymentLogDB;
use Spatie\ArrayToXml\ArrayToXml;
use App\Traits\ApiResponser;
use App\Traits\ACpayPaymentFunctionTrait;

use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Http;

class NidinPaymentController extends Controller
{
    use ACpayPaymentFunctionTrait;
    // 先經過 middleware 檢查
    public function __construct()
    {
        // $this->middleware('auth:gate', ['except' => ['pay','query','capture','refund','callback','notify']]);
        //改走cloudflare需抓x-forwareded-for
        if(!empty(request()->header('x-forwarded-for'))){
            $this->loginIp = request()->header('x-forwarded-for');
        }else{
            $this->loginIp = request()->ip();
        }
        $this->allowIps = IpAddressDB::where([['admin_id',0],['is_on',1]])->select('ip')->get()->pluck('ip')->all();
    }

    public function pay(Request $request){
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $nonceStr = $toNidin = $message = null;
        $url = env('ACPAY_PAY_API_URL');
        if(!empty($request->getContent())){
            $getXml = $request->getContent();
            $log = NidinPaymentLogDB::create([
                'type' => 'pay',
                'from_nidin' => $getXml,
                'ip' => $this->loginIp,
            ]);
            libxml_use_internal_errors(true);
            $doc = simplexml_load_string($getXml);
            $errors = libxml_get_errors();
            $getXml = $this->icarry_replace_getxml($getXml); //localhost測試時轉換out_trade_no
            if ($doc !== false) {
                $json = $this->acpayXmlToJson($getXml);
                $getData = json_decode($json, true);
                $nidinPayment = NidinPaymentDB::create([
                    'from_nidin' => $json,
                    'ip' => $this->loginIp,
                ]);
                isset($getData['nonce_str']) ? $nonceStr = $getData['nonce_str'] : '';
                if(!empty($getData['callback_url']) && !empty($getData['notify_url'])){
                    $nidinPayment->update(['callback_url' => $getData['callback_url'], 'notify_url' => $getData['notify_url']]);
                    if(!empty($getData['merchant_no'])){
                        $nidinPayment->update(['merchant_no' => $getData['merchant_no']]);
                        $vendor = VendorDB::where('merchant_no',$getData['merchant_no'])->first();
                        if(!empty($vendor)){
                            $key = $vendor->payment_key;
                            if(!empty($getData['out_trade_no'])){
                                $log->update(['nidin_order_no' => $getData['out_trade_no']]);
                                if(!empty($getData['total_fee']) && $getData['total_fee'] > 0){
                                    !empty($getData['auto_settle']) && $getData['auto_settle'] == 'Y' ? $autoSettle = 1 : $autoSettle = 0;
                                    $icarryXml = $this->icarry_replace_url($getXml);
                                    $xml = $this->acpay_make_post_xml($icarryXml, $key);
                                    //送資料到ACPAY，然後存SQL
                                    $log->update(['to_acpay' => $xml]);
                                    $toNidin = $response = $this->acpay_post($url, $xml);
                                    $nidinPayment->update(['nidin_order_no' => $getData['out_trade_no'], 'to_acpay' => $xml, 'from_acpay' => $response]);
                                    $log->update(['from_acpay' => $response,'to_nidin' => $response]);
                                    $json = $this->acpay_xml_to_json($response);
                                    $result = json_decode($json, true);
                                    if($result['status'] != 0){
                                        $message = $result['message'];
                                    }else{
                                        $message = '金流發送成功。';
                                    }
                                    $nidinPayment->update(['amount' => $getData['total_fee'], 'to_nidin'=> $response, 'message' => $message, 'auto_settle' => $autoSettle]);
                                    $log->update(['message' => $message]);
                                    return response($response, 200)->header('Content-Type', 'xml');
                                }else{
                                    $code = 4;
                                    $message = 'total_fee 金額不存在或小於0。';
                                }
                            }else{
                                $code = 3;
                                $message = 'out_trade_no 特店訂單編號不存在。';
                            }
                        }else{
                            $code = 21;
                            $message = 'merchant_no 特店代號錯誤。';
                        }
                    }else{
                        $code = 2;
                        $message = 'merchant_no 特店代號不存在。';
                    }
                }else{
                    $code = 1;
                    $message = 'callback_url/notify_url 不存在。';
                }
            }else{
                $code = 500;
                $message = 'XML Data Check Fail。';
            }
            $log->update(['message' => $message]);
        }else{
            $code = 400;
            $data['message'] = $message = 'No Data Input。';
        }
        $nidinPayment->update(['to_nidin'=> $toNidin, 'message' => $message]);

        $response = "<xml>\n<charset><![CDATA[UTF-8]]></charset>\n<nonce_str><![CDATA[$nonceStr]]></nonce_str>\n<message><![CDATA[$message]]></message>\n<version><![CDATA[2.0]]></version>\n<status><![CDATA[$code]]></status>\n</xml>";
        return response($response, 200)->header('Content-Type', 'xml');
    }

    public function callback(){
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $url = $message = null;
        if(count(request()->all()) > 0){
            $data = request()->all();
            $log = NidinPaymentLogDB::create([
                'type' => 'callback',
                'from_acpay' => json_encode($data,true),
                'ip' => $this->loginIp,
            ]);
            foreach ($data as $keyName => $value) {
                $$keyName = $value;
            }
            isset($out_trade_no) ? $log->update(['nidin_order_no' => $out_trade_no]) : '';
            isset($transaction_id) ? $log->update(['transaction_id' => $transaction_id]) : '';
            $nidinPayment = NidinPaymentDB::where('nidin_order_no',$out_trade_no)->first();
            if(!empty($nidinPayment)){
                $url = $nidinPayment->callback_url;
                if($result_code == 0){ //付款成功
                    $nidinPayment->update(['transaction_id' => $transaction_id, 'is_success' => 1, 'callback_json' => json_encode($data,true), 'pay_time' => date('Y-m-d H:i:s'), 'message' => '付款成功']);
                    $message = '付款成功。';
                }else{
                    $nidinPayment->update(['transaction_id' => $transaction_id, 'is_success' => 0, 'callback_json' => json_encode($data,true), 'pay_time' => date('Y-m-d H:i:s'), 'message' => $err_msg]);
                    $message = $err_msg;
                }
            }else{
                $message = '查無金流交易。';
            }
            //返回你訂的callBackURL
            if(!empty($url)){
                if (strstr($url, "?")) {
                    $url = $url . "&" . http_build_query($data);
                } else {
                    $url = $url . "?" . http_build_query($data);
                }
                $log->update(['to_nidin' => $url, 'message' => 'callBack finished. '.$message]);
                return redirect()->to($url);
            }else{
                $log->update(['message' => 'Nidin Callback Url 不存在。']);
            }
        }
        return response(null)->header('Content-Type', 'text');
    }

    public function notify(Request $request){
        if(!empty($request->getContent())){
            $getXml = $request->getContent();
            $log = NidinPaymentLogDB::create([
                'type' => 'notify',
                'from_acpay' => $getXml,
                'ip' => $this->loginIp,
            ]);
            libxml_use_internal_errors(true);
            $doc = simplexml_load_string($getXml);
            $errors = libxml_get_errors();
            $getXml = $this->icarry_replace_getxml($getXml); //localhost測試時轉換out_trade_no
            if ($doc !== false) {
                $json = $this->acpayXmlToJson($getXml);
                $getData = json_decode($json, true);
                if(!empty($getData['transaction_id'])){
                    $log->update(['transaction_id' => $getData['transaction_id'], 'nidin_order_no' => $getData['out_trade_no']]);
                    $nidinPayment = NidinPaymentDB::where('transaction_id',$getData['transaction_id'])->orWhere('nidin_order_no',$getData['out_trade_no'])->first();
                    if(!empty($nidinPayment)){
                        $notifyUrl = $nidinPayment->notify_url;
                        if($getData['pay_result'] == 0){
                            $nidinPayment->update(['is_success' => 1, 'notify_json' => json_encode($getData,true), 'pay_time' => date('Y-m-d H:i:s'), 'message' => '付款成功']);
                        }else{
                            $nidinPayment->update(['is_success' => 0, 'notify_json' => json_encode($getData,true), 'pay_time' => date('Y-m-d H:i:s'), 'message' => $getData['err_msg']]);
                        }
                        $log->update(['nidin_order_no' => $nidinPayment->nidin_order_no, 'to_nidin' => $getXml, 'message' => 'notify 轉發完成。']);
                        //將ACPAY的notify轉發過去到notify_url
                        $result = $this->acpay_post($notifyUrl, $getXml);
                        strtoupper($result) == 'SUCCESS' ? $log->update(['message' => 'nidin 接收完成。']) : '';
                    }else{
                        $log->update(['message' => '查無付款資料。']);
                    }
                }
                return response('SUCCESS')->header('Content-Type', 'text');
            }
        }
        return response(null)->header('Content-Type', 'text');
    }

    public function query(Request $request){
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $nonceStr = $message = null;
        $queryUrl = env('ACPAY_API_ROOT_URL').'/Query';
        if(!empty($request->getContent())){
            $getXml = $request->getContent();
            libxml_use_internal_errors(true);
            $doc = simplexml_load_string($getXml);
            $errors = libxml_get_errors();
            if ($doc !== false) {
                $json = $this->acpayXmlToJson($getXml);
                $getData = json_decode($json, true);
                isset($getData['nonce_str']) ? $nonceStr = $getData['nonce_str'] : '';
                if(!empty($getData['merchant_no'])){
                    $vendor = VendorDB::where('merchant_no',$getData['merchant_no'])->first();
                    if(!empty($vendor)){
                        $key = $vendor->payment_key;
                        if(!empty($getData['transaction_id']) || !empty($getData['out_trade_no'])){
                            $nidinPayment = NidinPaymentDB::where(function($query)use($getData){
                                if(!empty($getData['transaction_id'])){
                                    $query = $query->where('transaction_id',$getData['transaction_id']);
                                    if(!empty($getData['out_trade_no'])){
                                        $query = $query->orWhere('nidin_order_no',$getData['out_trade_no']);
                                    }
                                }else{
                                    if(!empty($getData['out_trade_no'])){
                                        $query = $query->where('nidin_order_no',$getData['out_trade_no']);
                                    }
                                }
                            })->first();
                            if(!empty($nidinPayment)){
                                $icarryXml = $this->acpay_xml_remove_sign($getXml); //移除sign
                                $xml = $this->acpay_make_post_xml($icarryXml, $key);
                                $response = $this->acpay_post($queryUrl, $xml);
                                return response($response, 200)->header('Content-Type', 'xml');
                            }else{
                                $code = 21;
                                $message = 'transaction_id / out_trade_no 金流交易不存在。';
                            }
                        }else{
                            $code = 2;
                            $message = 'transaction_id /out_trade_no 金流序號/特店訂單號不存在。';
                        }
                    }else{
                        $code = 11;
                        $message = 'merchant_no 特店不存在。';
                    }
                }else{
                    $code = 1;
                    $message = 'merchant_no 特店代號不存在。';
                }
            }else{
                $code = 500;
                $message = 'XML Data Check Fail。';
            }
        }else{
            $code = 400;
            $message = 'No Data Input。';
        }
        $response = "<xml>\n<charset><![CDATA[UTF-8]]></charset>\n<nonce_str><![CDATA[$nonceStr]]></nonce_str>\n<message><![CDATA[$message]]></message>\n<version><![CDATA[2.0]]></version>\n<status><![CDATA[$code]]></status>\n</xml>";
        return response($response, 200)->header('Content-Type', 'xml');
    }

    public function capture(Request $request){
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $nonceStr = $message = null;
        $queryUrl = env('ACPAY_API_ROOT_URL').'/Capture';
        if(!empty($request->getContent())){
            $getXml = $request->getContent();
            $log = NidinPaymentLogDB::create([
                'type' => 'capture',
                'from_nidin' => $getXml,
                'ip' => $this->loginIp,
            ]);
            libxml_use_internal_errors(true);
            $doc = simplexml_load_string($getXml);
            $errors = libxml_get_errors();
            if ($doc !== false) {
                $json = $this->acpayXmlToJson($getXml);
                $getData = json_decode($json, true);
                isset($getData['nonce_str']) ? $nonceStr = $getData['nonce_str'] : '';
                if(!empty($getData['merchant_no'])){
                    $vendor = VendorDB::where('merchant_no',$getData['merchant_no'])->first();
                    if(!empty($vendor)){
                        $key = $vendor->payment_key;
                        if(!empty($getData['transaction_id']) || !empty($getData['out_trade_no'])){
                            $nidinPayment = NidinPaymentDB::where(function($query)use($getData){
                                if(!empty($getData['transaction_id'])){
                                    $query = $query->where('transaction_id',$getData['transaction_id']);
                                    if(!empty($getData['out_trade_no'])){
                                        $query = $query->orWhere('nidin_order_no',$getData['out_trade_no']);
                                    }
                                }else{
                                    if(!empty($getData['out_trade_no'])){
                                        $query = $query->where('nidin_order_no',$getData['out_trade_no']);
                                    }
                                }
                            })->first();
                            if(!empty($nidinPayment)){
                                $log->update(['transaction_id' => $nidinPayment->transaction_id, 'nidin_order_no' => $nidinPayment->nidin_order_no ]);
                                $icarryXml = $this->acpay_xml_remove_sign($getXml); //移除sign
                                $xml = $this->acpay_make_post_xml($icarryXml, $key);
                                $log->update(['to_acpay' => $xml]);
                                $response = $this->acpay_post($queryUrl, $xml);
                                // 成功
                                // $response = "<xml>\n<transaction_id><![CDATA[Aa240722aIx89Lh0wu]]></transaction_id>\n<charset><![CDATA[UTF-8]]></charset>\n<merchant_no><![CDATA[090400000000856]]></merchant_no>\n<nonce_str><![CDATA[6e7bf8fc936c468a95c8007cd01709b5]]></nonce_str>\n<fee_type><![CDATA[TWD]]></fee_type>\n<version><![CDATA[2.0]]></version>\n<settle_fee><![CDATA[2]]></settle_fee>\n<out_trade_no><![CDATA[1721619532]]></out_trade_no>\n<local_fee_type><![CDATA[TWD]]></local_fee_type>\n<total_fee><![CDATA[2]]></total_fee>\n<order_fee><![CDATA[2]]></order_fee>\n<local_total_fee><![CDATA[2]]></local_total_fee>\n<trade_type><![CDATA[pay.ctbc.card]]></trade_type>\n<result_code><![CDATA[0]]></result_code>\n<sign_type><![CDATA[SHA-256]]></sign_type>\n<status><![CDATA[0]]></status>\n<sign><![CDATA[DE2675246BF89C32BF6DDA60008FE7EA9D5AAFE8AA46EC7AC4A2C46A58608745]]></sign>\n</xml>";
                                // 失敗
                                // $response = "<xml>\n<charset><![CDATA[UTF-8]]></charset>\n<merchant_no><![CDATA[090400000000856]]></merchant_no>\n<nonce_str><![CDATA[17e5af7591034c3ea8b29a2e9c4e92e9]]></nonce_str>\n<err_msg><![CDATA[(3:5)違反交易法則，P.G.系統無法判讀交易完成狀態]]></err_msg>\n<err_code><![CDATA[PG_REJ_BAD_COMPSTATE]]></err_code>\n<result_code><![CDATA[-9100]]></result_code>\n<message><![CDATA[後端交易失敗(errCode=PG_REJ_BAD_COMPSTATE,errMsg=(3:5)違反交易法則，P.G.系統無法判讀交易完成狀態,message=null,status=0)]]></message>\n<version><![CDATA[2.0]]></version>\n<sign_type><![CDATA[SHA-256]]></sign_type>\n<status><![CDATA[0]]></status>\n<sign><![CDATA[0E6D3DD1D52AB89BE8D1274A4DA34EF1D97FE9FB4CEE555E711CF746F0B24CEB]]></sign>\n</xml>";
                                $json = $this->acpay_xml_to_json($response);
                                $data = json_decode($json, true);
                                if($data['result_code'] == 0){
                                    $nidinPayment->update(['is_capture' => 1]);
                                    $message = 'Capture 處理成功。';
                                }else{
                                    $message = str_replace('\n','',$data['err_msg']);
                                }
                                $log->update(['from_acpay'=> $response,'to_nidin' => $response, 'message'=> $message]);
                                return response()->json($response);
                            }else{
                                $code = 21;
                                $message = 'transaction_id / out_trade_no 金流交易不存在。';
                            }
                        }else{
                            $code = 2;
                            $message = 'transaction_id /out_trade_no 金流序號/特店訂單號不存在。';
                        }
                    }else{
                        $code = 11;
                        $message = 'merchant_no 特店不存在。';
                    }
                }else{
                    $code = 1;
                    $message = 'merchant_no 特店代號不存在。';
                }
            }else{
                $code = 500;
                $message = 'XML Data Check Fail。';
            }
            $log->update(['message' => $message]);
        }else{
            $code = 400;
            $message = 'No Data Input。';
        }
        $response = "<xml>\n<charset><![CDATA[UTF-8]]></charset>\n<nonce_str><![CDATA[$nonceStr]]></nonce_str>\n<message><![CDATA[$message]]></message>\n<version><![CDATA[2.0]]></version>\n<status><![CDATA[$code]]></status>\n</xml>";
        return response($response, 200)->header('Content-Type', 'xml');
    }

    public function refund(Request $request){
        $code = 0;
        $status = 'Error';
        $httpCode = 200;
        $nonceStr = $message = null;
        $refundUrl = env('ACPAY_API_REFUND_URL').'/Refund';
        if(!empty($request->getContent())){
            $getXml = $request->getContent();
            $log = NidinPaymentLogDB::create([
                'type' => 'refund',
                'from_nidin' => $getXml,
                'ip' => $this->loginIp,
            ]);
            libxml_use_internal_errors(true);
            $doc = simplexml_load_string($getXml);
            $errors = libxml_get_errors();
            if ($doc !== false) {
                $json = $this->acpayXmlToJson($getXml);
                $getData = json_decode($json, true);
                isset($getData['nonce_str']) ? $nonceStr = $getData['nonce_str'] : '';
                if(!empty($getData['merchant_no'])){
                    $vendor = VendorDB::where('merchant_no',$getData['merchant_no'])->first();
                    if(!empty($vendor)){
                        $key = $vendor->payment_key;
                        if(empty($getData['transaction_id']) && empty($getData['out_trade_no'])){
                            $code = 2;
                            $data['message'] = $message = 'transaction_id /out_trade_no 金流序號/特店訂單號不存在。';
                        }else{
                            !empty($getData['transaction_id']) ? $nidinPayment = NidinPaymentDB::where('transaction_id',$getData['transaction_id'])->orderBy('id','desc')->first() : $nidinPayment = null;
                            empty($nidinPayment) && !empty($getData['out_trade_no']) ? $nidinPayment = NidinPaymentDB::where('nidin_order_no',$getData['out_trade_no'])->orderBy('id','desc')->first() : '';
                            if(!empty($nidinPayment)){
                                $log->update(['transaction_id' => $nidinPayment->transaction_id,'nidin_order_no' => $nidinPayment->nidin_order_no]);
                                if($nidinPayment->is_success == 1){
                                    if($nidinPayment->is_refund == 0){
                                        $nidinOrder = NidinOrderDB::with('order')->whereNotNull('order_id')->where('transaction_id',$nidinPayment->transaction_id)->first();
                                        !empty($nidinOrder) ? ($nidinOrder->order->status == 5 ? $orderStatus = 5 : $orderStatus = 0) : $orderStatus = -1;
                                        if($orderStatus == 5 || $orderStatus == -1){
                                            $icarryXml = $this->acpay_xml_remove_sign($getXml); //移除sign
                                            $xml = $this->acpay_make_post_xml($icarryXml, $key);
                                            $log->update(['to_acpay' => $xml]);
                                            $response = $this->acpay_post($refundUrl, $xml);
                                            $log->update(['from_acpay' => $response, 'to_nidin' => $response]);
                                            //成功
                                            // $response = "<xml>\n<transaction_id><![CDATA[Aa2407220LpXeNHSGC]]></transaction_id>\n<charset><![CDATA[UTF-8]]></charset>\n<merchant_no><![CDATA[090400000000856]]></merchant_no>\n<nonce_str><![CDATA[0d80a6d935c24c75b329135ab2e62ba1]]></nonce_str>\n<out_refund_no><![CDATA[17220825661]]></out_refund_no>\n<fee_type><![CDATA[TWD]]></fee_type>\n<version><![CDATA[2.0]]></version>\n<refund_id><![CDATA[Aa2407220LpXeNHSGC]]></refund_id>\n<out_trade_no><![CDATA[46452701005600000000017210758714]]></out_trade_no>\n<local_fee_type><![CDATA[TWD]]></local_fee_type>\n<refund_fee><![CDATA[2]]></refund_fee>\n<total_fee><![CDATA[2]]></total_fee>\n<order_fee><![CDATA[2]]></order_fee>\n<local_total_fee><![CDATA[2]]></local_total_fee>\n<trade_type><![CDATA[pay.CTBC.card]]></trade_type>\n<result_code><![CDATA[0]]></result_code>\n<sign_type><![CDATA[SHA-256]]></sign_type>\n<status><![CDATA[0]]></status>\n<sign><![CDATA[9ADF8EDE09C380C250B66DBDB4EA8DEAF99082D4D4BD5001FEA676F50699B3F5]]></sign>\n</xml>";
                                            // 失敗
                                            // $response = "<xml>\n<charset><![CDATA[UTF-8]]></charset>\n<nonce_str><![CDATA[fa3d32d02b644aa1ac2d84dc488639a7]]></nonce_str>\n<message><![CDATA[退款單號已存在，此筆交易無法進行退款，請更換退款訂單編號]]></message>\n<version><![CDATA[2.0]]></version>\n<status><![CDATA[-9000]]></status>\n</xml>";
                                            $json = $this->acpay_xml_to_json($response);
                                            $data = json_decode($json, true);
                                            if(isset($data['result_code']) && $data['result_code'] == 0){
                                                $message = '退款成功。';
                                                $nidinPayment->update(['is_refund' => 1, 'message' => $message]);
                                            }else{
                                                $message = $data['message'];
                                            }
                                            $log->update(['message' => $message]);
                                            return response($response, 200)->header('Content-Type', 'xml');
                                        }else{
                                            $code = 5;
                                            $message = '訂單已開立票券成功，無法退款。';
                                        }
                                    }else{
                                        $code = 4;
                                        $message = '該交易已退款。';
                                    }
                                }else{
                                    $code = 3;
                                    $message = '金流交易未成功，無法退款。';
                                }
                            }else{
                                $code = 2;
                                $message = 'transaction_id / out_trade_no 金流交易不存在。';
                            }
                        }
                    }else{
                        $code = 11;
                        $message = 'merchant_no 特店不存在。';
                    }
                }else{
                    $code = 1;
                    $message = 'merchant_no 特店代號不存在。';
                }
            }else{
                $code = 500;
                $message = 'XML Data Check Fail。';
            }
            $log->update(['message' => $message]);
        }else{
            $code = 400;
            $message = 'No Data Input。';
        }
        $response = "<xml>\n<charset><![CDATA[UTF-8]]></charset>\n<nonce_str><![CDATA[$nonceStr]]></nonce_str>\n<message><![CDATA[$message]]></message>\n<version><![CDATA[2.0]]></version>\n<status><![CDATA[$code]]></status>\n</xml>";
        return response($response, 200)->header('Content-Type', 'xml');
    }
}
