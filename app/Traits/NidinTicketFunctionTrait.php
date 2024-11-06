<?php

namespace App\Traits;
use App\Models\Admin as AdminDB;
use App\Models\iCarryTicket as TicketDB;
use App\Models\NidinTicketLog as NidinTicketLogDB;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use Curl;
use DB;
use Validator;

trait NidinTicketFunctionTrait
{
    protected function nidinOpenTicket($nidinOrder, $items, $log)
    {
        env('APP_ENV') == 'local' ? $url = env('ACPAY_TICKET_TEST_URL').'/Create' : $url = env('ACPAY_TICKET_URL').'/Create';
        !empty($nidinOrder->payment->pay_time) ? $purchaseDate = date('Ymd',strtotime($nidinOrder->payment->pay_time)) : $purchaseDate = date('Ymd');
        $orderNo = $nidinOrder->nidin_order_no;
        !empty($nidinOrder->order->receiver_name) ? $consumer = mb_substr($nidinOrder->order->receiver_name,0,38) : $consumer = 'Nidin';
        $consumerEmail = "nosend@nidin";
        $consumerPhone = "";
        $transactionId = $nidinOrder->transaction_id;
        $platformNo = $nidinOrder->vendor->merchant_no;
        $key = $nidinOrder->vendor->merchant_key;
        if(!empty($platformNo) && !empty($key) && count($items) > 0){
            ksort($items);
            $itemsString=json_encode($items,JSON_UNESCAPED_UNICODE);
            $itemsString=str_replace(['"',',',':'], ['',', ','='], $itemsString);
            //測試忽略金流序號
            if(env('APP_ENV') == 'local'){
                $data = [
                    'consumer' => "$consumer",
                    'consumerEmail' => "$consumerEmail",
                    'consumerPhone' => "$consumerPhone",
                    'items' => $items,
                    'orderNo' => "$orderNo",
                    'platformNo' => $platformNo,
                    'purchaseDate' => $purchaseDate,
                ];
                $queryString = "consumer=$consumer&consumerEmail=$consumerEmail&consumerPhone=$consumerPhone&items=$itemsString&orderNo=$orderNo&platformNo=$platformNo&purchaseDate=$purchaseDate";
            }else{
                $data = [
                    'consumer' => "$consumer",
                    'consumerEmail' => "$consumerEmail",
                    'consumerPhone' => "$consumerPhone",
                    'items' => $items,
                    'orderNo' => "$orderNo",
                    'platformNo' => $platformNo,
                    'purchaseDate' => $purchaseDate,
                    'transactionId' => "$transactionId",
                ];
                $queryString = "consumer=$consumer&consumerEmail=$consumerEmail&consumerPhone=$consumerPhone&items=$itemsString&orderNo=$orderNo&platformNo=$platformNo&purchaseDate=$purchaseDate&transactionId=$transactionId";
            }

            ksort($data);
            $data['sign'] = strtoupper(hash('sha256', $queryString.'&key='.$key));
            $data = json_encode($data,true);
            $log->update(['to_acpay' => $data]);
            // $result = Curl::to($url)->withHeaders(['Content-Type:application/json','charset:utf-8'])->withTimeout(5)->withData( $data )->post();
            $result = $this->acpay_post($url,$data);
            $log->update(['from_acpay' => $result]);
            $result = json_decode($result,true);
            $log->update(['rtnCode'=> $result['rtnCode'], 'rtnMsg' => $result['rtnMsg']]);
            return $result;
        }
        return null;
    }

    protected function nidinWriteOffTicket($data, $key, $log)
    {
        env('APP_ENV') == 'local' ? $url = env('ACPAY_TICKET_TEST_URL').'/WriteOff' : $url = env('ACPAY_TICKET_URL').'/WriteOff';
        if(!empty($key) && !empty($data)){
            $handler = $data['handler'];
            $merchantNo = $data['merchantNo'];
            $ticketNo = $data['ticketNo'];
            $timestamp = $data['timestamp'];
            $queryString = "handler=$handler&merchantNo=$merchantNo&ticketNo=$ticketNo&timestamp=$timestamp";

            $data['sign'] = strtoupper(hash('sha256', $queryString.'&key='.$key));
            $data = json_encode($data,true);
            $log->update(['to_acpay' => $data]);
            // $result = Curl::to($url)->withHeaders(['Content-Type:application/json','charset:utf-8'])->withTimeout(5)->withData( $data )->post();
            $result = $this->acpay_post($url,$data);
            // $log->update(['from_acpay' => $result]);
            $result = json_decode($result,true);
            return $result;
        }
        return null;
    }

    protected function nidinInvalidTicket($nidinOrder, $items, $log)
    {
        env('APP_ENV') == 'local' ? $url = env('ACPAY_TICKET_TEST_URL').'/BatchInvalid' : $url = env('ACPAY_TICKET_URL').'/BatchInvalid';
        $timestamp = date('YmdHis');
        $transactionId = $nidinOrder->transaction_id;
        $platformNo = $nidinOrder->vendor->merchant_no;
        $key = $nidinOrder->vendor->merchant_key;
        if(!empty($platformNo) && !empty($key) && count($items) > 0){
            ksort($items);
            $ticketString=json_encode($items,JSON_UNESCAPED_UNICODE);
            $ticketString=str_replace(['"',',',':'], ['',', ','='], $ticketString);
            $data = [
                'platformNo' => "$platformNo",
                'ticket' => $items,
                'timestamp' => "$timestamp",
            ];
            $queryString = "platformNo=$platformNo&ticket=$ticketString&timestamp=$timestamp";
            ksort($data);
            $data['sign'] = strtoupper(hash('sha256', $queryString.'&key='.$key));
            $data = json_encode($data,true);
            $log->update(['to_acpay' => $data]);
            // $result = Curl::to($url)->withHeaders(['Content-Type:application/json','charset:utf-8'])->withTimeout(5)->withData( $data )->post();
            $result = $this->acpay_post($url,$data);
            $log->update(['from_acpay' => $result]);
            $result = json_decode($result,true);
            $log->update(['rtnCode'=> $result['rtnCode'], 'rtnMsg' => $result['rtnMsg']]);
            return $result;
        }
        return null;
    }

    protected function getNidinServiceFee($merchantNo, $key, $type = null)
    {
        env('APP_ENV') == 'local' ? $url = env('ACPAY_TICKET_TEST_URL').'/QueryTicketFeeSetting' : $url = env('ACPAY_TICKET_URL').'/QueryTicketFeeSetting';
        if(!empty($merchantNo) && !empty($key)){
            $serviceFee = 0;
            $data['merchantNo'] = "$merchantNo";
            $queryString = "merchantNo=$merchantNo";
            ksort($data);
            $data['sign'] = strtoupper(hash('sha256', $queryString.'&key='.$key));
            $data = json_encode($data,true);
            // $result = Curl::to($url)->withHeaders(['Content-Type:application/json','charset:utf-8'])->withTimeout(5)->withData( $data )->post();
            $result = $this->acpay_post($url,$data);
            $result = json_decode($result,true);
            if(!empty($result['tfsList']) && count($result['tfsList']) > 0){
                if(!empty($type)){
                    $tfsList = $result['tfsList'];
                    for($i=0;$i<count($tfsList);$i++){
                        if($tfsList[$i]['tfsType'] == $type){
                            $serviceFee = $tfsList[$i]['tfsFee'];
                        }
                    }
                    return $serviceFee;
                }else{
                    return $result['tfsList'];
                }
            }
        }
        return null;
    }
    function acpay_post($url, $data)
    {
        $headers = array(
            'Content-Type: application/json',
            'charset:utf-8'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
