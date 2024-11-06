<?php

namespace App\Traits;
use Str;
use Http;
use Spatie\ArrayToXml\ArrayToXml;

trait ACpayPaymentFunctionTrait
{
    protected function chkACPayTransaction($data,$vendor)
    {
        $url = env('ACPAY_API_ROOT_URL').'/Query';
        $nonceStr = Str::random(32);
        $queryData['service'] ='unified.trade.query';
        $queryData['version'] = '2.0';
        $queryData['charset'] = 'UTF-8';
        $queryData['sign_type'] = 'SHA-256';
        $queryData['merchant_no'] = $vendor->merchant_no;
        $queryData['nonce_str'] = $nonceStr;
        $queryData['transaction_id'] = $data['transaction_id'];
        ksort($queryData);
        $queryString = http_build_query($queryData);
        $sign = strtoupper(hash('sha256', $queryString.'&key='.$vendor->merchant_key));
        $xmlData['service'] = ['_cdata' => 'unified.trade.query'];
        $xmlData['version'] = ['_cdata' => '2.0'];
        $xmlData['charset'] = ['_cdata' => 'UTF-8'];
        $xmlData['sign_type'] = ['_cdata' => 'SHA-256'];
        $xmlData['merchant_no'] = ['_cdata' => $vendor->merchant_no];
        $xmlData['nonce_str'] = ['_cdata' => $nonceStr];
        $xmlData['sign'] = ['_cdata' => $sign];
        $xmlData['transaction_id'] = ['_cdata' => $data['transaction_id']];
        ksort($xmlData);
        $xml = ArrayToXml::convert($xmlData,['rootElementName' => 'xml'], true, 'UTF-8', '1.0', []);
        $result = $this->ACPayPaymentPOST($xml);
        $result = new \SimpleXMLElement($result,LIBXML_NOCDATA);

        return $result;
    }

    protected function ACPayPaymentPOST($xml)
    {
        $response = null;
        $url = env('ACPAY_API_ROOT_URL').'/Query';

        if(!empty($xml)){
            $response = Http::withHeaders([
                "Content-Type" => "application/xml;charset=utf-8"
            ])->send("POST", $url, [
                "body" => $xml
            ]);
            return $response->body();
        }
        return $response;
    }

    //ACPAY傳送POST函式
    function acpay_post($url, $xml)
    {
        $headers = array(
            'Content-Type: application/xml'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    //消除CDATA函式
    protected function acpay_exclude_cdata($xmlStr)
    {
        $xmlStr = str_replace('<![CDATA[', '', $xmlStr);
        $xmlStr = str_replace(']]>', '', $xmlStr);
        return $xmlStr;
    }

    //在icarry這邊取代原本的out_trade_no
    protected function icarry_replace_getxml($original_xml)
    {
        $json = $this->acpay_xml_to_json($original_xml);
        $array = json_decode($json, true);
        strstr(env('APP_URL'),'localhost') ? $array["out_trade_no"] = time() : '';
        return $this->acpay_array_to_xml($array); //回傳XML字串
    }

    //在icarry這邊取代原本的notify和callback
    protected function icarry_replace_url($original_xml)
    {
        $json = $this->acpay_xml_to_json($original_xml);
        $array = json_decode($json, true);
        $array["notify_url"] = env('ACPAY_NOTIFY_URL');
        $array["callback_url"] = env('ACPAY_CALLBACK_URL');
        return $this->acpay_array_to_xml($array); //回傳XML字串
    }

    //ACPAY組成傳送用的XML函式
    function acpay_make_post_xml($original_xml, $key)
    {
        $sign = $this->acpay_make_sign($original_xml, $key);
        $xml = str_replace('</xml>', "<sign>{$sign}</sign></xml>", $original_xml);
        return $xml;
    }

    //ACPAY組成sign的函式
    function acpay_make_sign($xml, $key)
    {
        $json = $this->acpay_xml_to_json($xml);
        $array = json_decode($json, true);
        if (isset($array["sign"])) {
            unset($array["sign"]);
        }
        ksort($array);
        //把陣列轉成get字串
        $query = $this->acpay_http_build_query($array) . "&key={$key}";
        $hash = hash('sha256', $query);
        return strtoupper($hash);
    }

    //ACPAY專用的http_build_query(不能直接用http_build_query因為會把參數裡面的網址url_encode過，造成加密錯誤)
    function acpay_http_build_query($array)
    {
        $query = '';
        foreach ($array as $key => $val) {
            $query .= "&{$key}={$val}";
        }
        $query = substr($query, 1);
        return $query;
    }

    //ACPAY的array to xml函式
    function acpay_array_to_xml($array)
    {
    if (isset($array["sign"])) {
        unset($array["sign"]);
    }
    $xml = '<xml>';
    foreach ($array as $key => $val) {
        $xml .= "<{$key}>{$val}</{$key}>";
    }
    $xml .= '</xml>';
    return $xml;
    }

    //ACPAY的XML to JSON函式
    protected function acpay_xml_to_json($xml)
    {
    $json = json_encode(simplexml_load_string($this->acpay_exclude_cdata($xml)), JSON_PRETTY_PRINT);
    if ($json === "false") {
        $element = new SimpleXMLElement($xml, LIBXML_NOCDATA);
        $noCData = $element->asXML();
        $xmlObj = simplexml_load_string($noCData);
        $json = json_encode($xmlObj, JSON_PRETTY_PRINT);
    }
    return $json;
    }

    //ACPAY的XML to JSON函式
    protected function acpayXmlToJson($xml)
    {
        $json = json_encode(simplexml_load_string($this->acpay_exclude_cdata($xml)), JSON_PRETTY_PRINT);
        if ($json === "false") {
            $element = new SimpleXMLElement($xml, LIBXML_NOCDATA);
            $noCData = $element->asXML();
            $xmlObj = simplexml_load_string($noCData);
            $json = json_encode($xmlObj, JSON_PRETTY_PRINT);
        }
        return $json;
    }

    //移除sign
    function acpay_xml_remove_sign($original_xml)
    {
        $json = $this->acpay_xml_to_json($original_xml);
        $array = json_decode($json, true);
        return $this->acpay_array_to_xml($array); //回傳XML字串
    }

}
