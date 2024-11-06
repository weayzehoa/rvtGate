<?php

namespace App\Traits;
use App\Models\Admin as AdminDB;
use App\Models\iCarryTicket as TicketDB;
use App\Models\iCarryAcpayTicketLog as AcpayTicketLogDB;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use Curl;
use DB;
use Validator;

trait ACpayTicketFunctionTrait
{
    protected function resendTicket($ticket)
    {
        if(!empty($ticket) && !empty($ticket->order)){
            env('APP_ENV') == 'local' ? $url = env('ACPAY_TICKET_TEST_URL').'/Resend' : $url = env('ACPAY_TICKET_URL').'/Resend';
            $timestamp = date('Y').date('m').date('d').date('H').date('i').date('s');
            $order = $ticket->order;
            $platformNo = $ticket->platform_no;
            $platformNo == '090400000000005' ? $key = env('ACPAY_TICKET_QUERY_TEST_KEY') : '';
            $platformNo == '090400000000001' ? $key = env('ACPAY_TICKET_QUERY_0904_KEY') : '';
            $orderNo = $order->order_number;
            $consumer = $order->user_id;
            $ticketNo = $ticket->ticket_no;
            env('APP_ENV') == 'local' ? $consumerEmail = env('TEST_MAIL_ACCOUNT') : $consumerEmail = $order->receiver_email;
            env('APP_ENV') == 'local' ? $consumerPhone = str_replace('+','',env('TEST_SMS_PHONE')) : $consumerPhone = str_replace('+','',$order->receiver_tel);
            $data = [
                'platformNo' => "$platformNo",
                'ticketNo' => "$ticketNo",
                'consumerEmail' => "$consumerEmail",
                'consumerPhone' => "$consumerPhone",
                'sendType' => "1,2",
                'handler' => !empty(auth('gate')->user()) ? auth('gate')->user()->name : 'iCarry Gate',
                'timestamp' => "$timestamp",
            ];
            ksort($data);
            $queryString = urldecode(http_build_query($data));
            $data['sign'] = strtoupper(hash('sha256', $queryString.'&key='.$key));
            $data = json_encode($data,true);
            $log = $this->log('post',$data,'補寄');
            $result = Curl::to($url)->withHeaders(['Content-Type:application/json','charset:utf-8'])
                        ->withTimeout(5)->withData( $data )->post();
            $log = $this->log('get',$result,null,$log);
            // $result = '{"itemName":"胡同燒肉夜食【500元現金抵用卷】","orderNo":"23031480000909","ticketNo":"224230000d9d22","sendType":"1,2","sign":"29FD802B9B5EF0CBBF3FFDFF29A3E6DA8FB1CB9ADBC09BFDB984C94CDF4735CE","consumerPhone":"886928589779","itemNo":"5TWA006900001","rtnMsg":"成功","consumerEmail":"roger@icarry.me","consumer":"2020","rtnCode":0}';
            $result = json_decode($result,true);
            return $result;
        }
        return null;
    }

    protected function ticketSettle($tickets, $adminId = 0)
    {
        env('APP_ENV') == 'local' ? $url = env('ACPAY_TICKET_TEST_URL').'/Clearing' : $url = env('ACPAY_TICKET_URL').'/Clearing';
        env('APP_ENV') == 'local' ? $platformNo = '090400000000005' : $platformNo = '090400000000001';
        env('APP_ENV') == 'local' ? $key = env('ACPAY_TICKET_QUERY_TEST_KEY') : $key = env('ACPAY_TICKET_QUERY_0904_KEY');
        $timestamp = date('Y').date('m').date('d').date('H').date('i').date('s');
        if(count($tickets) > 0){
            $data = [
                'platformNo' => "$platformNo",
                'clearingTickets' => $tickets,
                'timestamp' => "$timestamp",
            ];
            $ticketsString=json_encode($tickets,JSON_UNESCAPED_UNICODE);
            $ticketsString=str_replace(['"',',',':'], ['',', ','='], $ticketsString);
            $queryString = "clearingTickets=$ticketsString&platformNo=$platformNo&timestamp=$timestamp";
            ksort($data);
            $data['sign'] = strtoupper(hash('sha256', $queryString.'&key='.$key));
            $postData = json_encode($data,true);
            if(!empty($postData)){
                $log = $this->log('post',$postData,'結帳',null,$adminId);
                $result = Curl::to($url)->withHeaders(['Content-Type:application/json','charset:utf-8'])->withTimeout(5)->withData( $postData )->post();
                if(!empty($result)){
                    $log = $this->log('get',$result,null,$log,$adminId);
                    $result = json_decode($result,true);
                    // $result = '{"tickets":[{"amount":500,"writeOffMerchantNo":"090400000000005","itemName":"胡同燒肉夜食【500元現金抵用卷】","clearingDatetime":"20230310180018","ticketNo":"187e30000b2ab3","createTime":"20230310164136","sendType":"","expStartDate":"20230310","verify":"N","expEndDate":"21230310","writeOffTime":"20230310175816","status":"1","merchantNo":null}],"sign":"DA14B0AAC5C43A440571509A5546F570ABCD0D8CE0E931B87DA9B6BAAC12D1ED","rtnMsg":"成功","rtnCode":0}';
                    return $result;
                }
            }
        }
        return null;
    }

    protected function ticketNotify()
    {
        $key = env('TICKET_ENCRYPT_KEY');
        $request = request()->getContent();
        // $request = [
        //     'version' => '1.0',
        //     'charset' => 'UTF-8',
        //     'signType' => 'SHA-256',
        //     'rtnCode' => 0,
        //     'rtnMsg' => '成功',
        //     'sign' => '',
        //     'type' => 'writeOff',
        //     'ticketNo' => '160d30000b28d9',
        //     'orderNo' => '2023030900009',
        //     'transactionId' => '123455',
        //     'operateTime' => date('YmdHis'),
        // ];
        if(is_array(json_decode($request,true))){
            $validator = Validator::make(json_decode($request,true), [
                'rtnCode' => 'required',
                'type' => 'required',
                'ticketNo' => 'required',
                'orderNo' => 'required',
                'operateTime' => 'required',
            ]);
            if ($validator->fails()) {
                return 'fail';
            }else{
                $log = $this->log('get',$request,'回傳');
                $request = json_decode($request,true);
                $type = $request['type'];
                $ticketNo = $request['ticketNo'];
                $ticketOrderNo = $request['orderNo'];
                $usedTime = null;
                $operateTime = substr($request['operateTime'],0,4).'-'.substr($request['operateTime'],4,2).'-'.substr($request['operateTime'],6,2).' '.substr($request['operateTime'],8,2).':'.substr($request['operateTime'],10,2).':'.substr($request['operateTime'],12,2);
                $type == 'invalid' ? $status = -1 : '';
                $type == 'cancel' ? $status = 1 : '';
                $type == 'settle' ? $status = 2 : '';
                $type == 'writeOff' ? $status = 9 : '';
                $type == 'writeOff' ? $usedTime = $operateTime : $usedTime = null;
                if(!empty($ticketOrderNo) && !empty($ticketNo) && !empty($type)){
                    $ticket = TicketDB::whereRaw(" ticket_no = AES_ENCRYPT('$ticketNo', '$key') ")
                    ->where('ticket_order_no',$ticketOrderNo)
                    ->where('status','!=',2)->first();
                    if(!empty($ticket)){
                        if($type == 'settle'){
                            $ticket->update([
                                'status' => $status,
                            ]);
                        }else{
                            $ticket->update([
                                'status' => $status,
                                'used_time' => $usedTime,
                            ]);
                        }
                    }
                    return 'success';
                }
            }
        }
        return null;
    }

    protected function openTicket($createType, $items, $order = null)
    {
        $notifyUrl = env('ACPAY_TICKET_NOTIFY_URL');
        env('APP_ENV') == 'local' ? $url = env('ACPAY_TICKET_TEST_URL').'/Create' : $url = env('ACPAY_TICKET_URL').'/Create';
        env('APP_ENV') == 'local' ? $platformNo = '090400000000005' : $platformNo = '090400000000001';
        env('APP_ENV') == 'local' ? $key = env('ACPAY_TICKET_QUERY_TEST_KEY') : $key = env('ACPAY_TICKET_QUERY_0904_KEY');
        $purchaseDate = date('Ymd');
        $timestamp = date('Y').date('m').date('d').date('H').date('i').date('s');
        if(!empty($order)){
            $orderNo = $order->order_number;
            $consumer = mb_substr($order->receiver_name,0,38);
            env('APP_ENV') == 'local' ? $consumerEmail = env('TEST_MAIL_ACCOUNT') : $consumerEmail = $order->receiver_email;
            env('APP_ENV') == 'local' ? $consumerPhone = str_replace('+','',env('TEST_SMS_PHONE')) : $consumerPhone = str_replace('+','',$order->receiver_tel);
        }else{
            $tmp = DigiwinPaymentDB::where('create_type',$createType)->orderBy('customer_no','asc')->first();
            !empty($tmp) ? $consumer = 'iCarry_'.$tmp->create_type : $consumer = 'iCarry';
            !empty($tmp) ? $orderNo = $tmp->customer_no.'_'.$timestamp : $orderNo = $timestamp;
            $consumerEmail = "nosend@icarry";
            $consumerPhone = "";
        }
        if(!empty($key) && count($items) > 0){
            ksort($items);
            $itemsString=json_encode($items,JSON_UNESCAPED_UNICODE);
            $itemsString=str_replace(['"',',',':'], ['',', ','='], $itemsString);
            $data = [
                'consumer' => "$consumer",
                'consumerEmail' => "$consumerEmail",
                'consumerPhone' => "$consumerPhone",
                'items' => $items,
                'notifyUrl' => $notifyUrl,
                'orderNo' => "$orderNo",
                'platformNo' => $platformNo,
                'purchaseDate' => $purchaseDate,
            ];
            $queryString = "consumer=$consumer&consumerEmail=$consumerEmail&consumerPhone=$consumerPhone&items=$itemsString&notifyUrl=$notifyUrl&orderNo=$orderNo&platformNo=$platformNo&purchaseDate=$purchaseDate";
            ksort($data);
            $data['sign'] = strtoupper(hash('sha256', $queryString.'&key='.$key));
            $data = json_encode($data,true);
            $log = $this->log('post',$data,'開票');
            $result = Curl::to($url)->withHeaders(['Content-Type:application/json','charset:utf-8'])->withTimeout(5)->withData( $data )->post();
            $log = $this->log('get',$result,null,$log);
            $result = json_decode($result,true);
            return $result;
        }
        return null;
    }


    protected function invalidTicket($ticketNo,$platformNo)
    {
        env('APP_ENV') == 'local' ? $url = env('ACPAY_TICKET_TEST_URL').'/Invalid' : $url = env('ACPAY_TICKET_URL').'/Invalid';
        $key = null;
        $platformNo == '090400000000005' ? $key = env('ACPAY_TICKET_QUERY_TEST_KEY') : '';
        $platformNo == '090400000000001' ? $key = env('ACPAY_TICKET_QUERY_0904_KEY') : '';
        $platformNo == '092600000000001' ? $key = env('ACPAY_TICKET_QUERY_0926_KEY') : '';
        $timestamp = date('Y').date('m').date('d').date('H').date('i').date('s');
        if(!empty($key)){
            $data = [
                'platformNo' => $platformNo,
                'ticketNo' => $ticketNo,
                'timestamp' => $timestamp,
            ];
            ksort($data);
            $queryString = http_build_query($data);
            $sign = strtoupper(hash('sha256', $queryString.'&key='.$key));
            $data['sign'] = $sign;
            $data = json_encode($data,true);
            $log = $this->log('post',$data,'作廢');
            $result = Curl::to($url)->withHeaders(['Content-Type:application/json','charset:utf-8'])
                        ->withTimeout(5)->withData( $data )->post();
            $log = $this->log('get',$result,null,$log);
            $result = json_decode($result,true);
            return $result;
        }
        return null;
    }

    protected function checkTicketsStatus($platformNo, $ticketNos = [], $orderNo = null)
    {
        env('APP_ENV') == 'local' ? $url = env('ACPAY_TICKET_TEST_URL').'/Query' : $url = env('ACPAY_TICKET_URL').'/Query';
        $key = null;
        $platformNo == '090400000000005' ? $key = env('ACPAY_TICKET_QUERY_TEST_KEY') : '';
        $platformNo == '090400000000001' ? $key = env('ACPAY_TICKET_QUERY_0904_KEY') : '';
        $timestamp = date('Y').date('m').date('d').date('H').date('i').date('s');
        if(!empty($key)){
            if(!empty($orderNo)){
                $data = [
                    'identifyType' => 'P',
                    'orderNo' => $orderNo,
                    'platformNo' => $platformNo,
                    'timestamp' => $timestamp,
                ];
                ksort($data);
                $queryString = http_build_query($data);
            }else{
                $data = [
                    'identifyType' => 'P',
                    'platformNo' => $platformNo,
                    'ticketNos' => $ticketNos,
                    'timestamp' => $timestamp,
                ];
                ksort($data);
                $ticketNosString = join(', ',$ticketNos);
                $queryString = "identifyType=P&platformNo=$platformNo&ticketNos=[$ticketNosString]&timestamp=$timestamp";
            }
            $sign = strtoupper(hash('sha256', $queryString.'&key='.$key));
            $data['sign'] = $sign;
            $data = json_encode($data,true);
            // $log = $this->log('post',$data,'查詢');
            $result = Curl::to($url)->withHeaders(['Content-Type:application/json','charset:utf-8'])
                        ->withTimeout(5)->withData( $data )->post();
            // $log = $this->log('get',$result,null,$log);
            $result = json_decode($result,true);
            return $result;
        }
        return null;
    }

    protected function log($cate,$data,$type = null,$log = null, $adminId = 0)
    {
        $key = env('TICKET_ENCRYPT_KEY');
        $admin = AdminDB::find($adminId);
        $adminId == 0 ? $adminName = '系統' : $adminName = !empty($admin) ? $admin->name : $adminName = '系統';
        if($cate == 'post'){
            $log = AcpayTicketLogDB::create([
                'type' => $type,
                'admin_id' => !empty(auth('gate')->user()) ? auth('gate')->user()->id : $adminId,
                'admin_name' => !empty(auth('gate')->user()) ? auth('gate')->user()->name : $adminName,
                'post_json' => DB::raw("AES_ENCRYPT('$data', '$key')"),
            ]);
        }elseif($cate == 'get' && !empty($log)){
            $return = json_decode($data,true);
            $log->update([
                'get_json' => DB::raw("AES_ENCRYPT('$data', '$key')"),
                'rtnCode' => $return['rtnCode'],
                'rtnMsg' => $return['rtnMsg'],
            ]);
        }else{
            $return = json_decode($data,true);
            $log = AcpayTicketLogDB::create([
                'type' => $type,
                'admin_id' => !empty(auth('gate')->user()) ? auth('gate')->user()->id : $adminId,
                'admin_name' => !empty(auth('gate')->user()) ? auth('gate')->user()->name : $adminName,
                'get_json' => DB::raw("AES_ENCRYPT('$data', '$key')"),
                'rtnCode' => $return['rtnCode'],
                'rtnMsg' => $return['rtnMsg'],
            ]);
        }
        return $log;
    }
}
