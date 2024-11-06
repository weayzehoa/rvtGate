<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DigiwinOrderImport;
use App\Imports\MomoOrderImport;

use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\OrderImport as OrderImportDB;
use App\Models\OrderImportAbnormal as OrderImportAbnormalDB;

use App\Traits\OrderImportFunctionTrait;

use DB;

use App\Jobs\AdminOrderStatusJob;

class OrderFileImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,OrderImportFunctionTrait;
    protected $param;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $param = $this->param;
        if($param['test'] == true){
            $result['test'] = $param['test'];
            $result['type'] = $param['type'];
            $result['import_no'] = $param['import_no'];
            $result['admin_id'] = $param['admin_id'];
            $result['fail'] = 0;
            $result['success'] = 0;
            return $result;
        }
        if($param['cate'] == 'orders'){
            $orderTypes = $this->param['imports'];
            if(in_array($param['type'], $orderTypes)){
                $file = $param->file('filename');
                if($param['type'] == '宜睿匯入'){
                    $result = $this->yiruiOrderImport($file);
                }elseif($param['type'] == '鼎新訂單匯入'){
                    $result = Excel::toArray(new DigiwinOrderImport($param), $file);
                    $this->chkRows($result[0]) == false ? $result = 'rows error' : '';
                }elseif($param['type'] == 'MOMO匯入'){
                    $result = Excel::toArray(new MomoOrderImport($param), $file);
                    $this->chkRows($result[0]) == false ? $result = 'rows error' : '';
                }
                if($result == 'rows error'){
                    $type = $param['type'];
                    if($param['type'] == '宜睿匯入'){
                        return ['error' => "檔案內有欄位數錯誤， $type 欄位數為 17 欄，請檢查檔案內容。"];
                    }elseif($param['type'] == 'MOMO匯入'){
                        return ['error' => "檔案內有欄位數錯誤， $type 欄位數為 27 欄，請檢查檔案內容。"];
                    }elseif($param['type'] == '鼎新訂單匯入'){
                        return ['error' => "檔案內有欄位數錯誤， $type 欄位數為 26 欄，請檢查檔案內容。"];
                    }
                }elseif($result == 'no data imported'){
                    return ['error' => "檔案資料已存在，未存入。"];
                }elseif($result == 'no data'){
                    return ['error' => "檔案內沒有資料，請檢查檔案是否正確。"];
                }elseif(count($result) != 1){
                    return ['error' => '檔案內有其他 Sheet 資料，請刪除其他不必要的 Sheet。'];
                }else{
                    $importData = $result[0];
                    if(count($importData) > 0){
                        if($param['type'] == '宜睿匯入'){
                            $importData = [$importData];
                        }
                        $result = $this->OrderItemImport($importData);
                        $result['type'] = $param['type'];
                        $result['import_no'] = $param['import_no'];
                        $result['admin_id'] = $param['admin_id'];
                        return $result;
                    }
                }
            }else{
                return ['error' => '選擇匯入的類別不存在'];
            }
        }
        return ['error' => '你確定是訂單匯入?'];
    }

    protected function OrderItemImport($items)
    {
        $result['fail'] = $result['success'] = 0;
        $import = ['宜睿匯入', 'MOMO匯入', '鼎新訂單匯入'];
        if(count($items) > 0 && in_array($this->param['type'],$import)){
            $i = 1;
            foreach($items as $item){
                if($this->chkData($item) == true){
                    $tmpb = $bookShippingDate = $receiverTel = $receiverEmail = $receiverKeyword = $receiverKeyTime = $receiverAddress = $carrierType = $carrierNum = $buyerName = $invoiceTitle = $invoiceNumber = $invoiceType = $invoiceSubType = $loveCode = $repeate = $memo = null;
                    $type = str_replace('匯入', '', $this->param['type']);
                    $importNumber = $this->param['import_no'];
                    if($this->param['type'] == '宜睿匯入'){
                        $digiWinPaymentId = '016';
                        $partnerOrderNumber = $item[1];
                        $createTime = $payTime = substr($item["1"],0,4).'-'.substr($item["1"],4,2).'-'.substr($item["1"],6,2).' 08:00:00';//時間
                        $receiverAddress = "{$item["9"]} {$item["10"]} {$item["11"]}";//收件人地址 分別對應城市+郵遞碼+收件人地址
                        $receiverName = $item["8"];//收件人姓名
                        $receiverTel = empty($item["13"])?$item["12"]:$item["13"];//如果沒有收件人{13}就拿{12}來用
                        $receiverTel = '+886'.$this->bigintval($receiverTel);//台灣的電話號碼
                        $userMemo = "宜睿唯一碼:{$item["0"]},宜睿訂編:{$item["1"]},提貨日期:{$item["15"]},訂單備註:{$item["16"]}";
                        $receiverEmail = $item["14"];//收件人email
                        $shippingMethod = 5; //寄送台灣
                        $sku = $item["4"];//貨號
                        $quantity = $item["5"];//商品數量
                        $price = $item["6"];//商品單價
                        $amount = $item["7"];//訂單總價
                        //提貨日期是yyyyMMdd 要分割成yyyy-MM-dd 00:00:00 這邊直接加入''因為有機會出現NULL(NULL是不能加''的關係)
                        strstr($item['15'],'NULL') ? $item['15'] = null : '';
                        if(!empty($item['15'])){
                            if($this->convertAndValidateDate($item['15']) == false){
                                $tmpb = $item['15'];
                                $memo .= "提貨日期 $tmpb 資料錯誤。";
                            }else{
                                $receiverKeyTime = $this->convertAndValidateDate($item['15']).' 00:00:00';
                            }
                        }
                    }elseif($this->param['type'] == 'MOMO匯入'){
                        $digiWinPaymentId = '022';
                        $shippingMethod = 6; //寄送當地
                        $partnerOrderNumber = explode('-',$item[4])[0];
                        $receiverName = $item["5"];//收件人姓名
                        $receiverAddress = $item["6"];//收件人地址
                        !empty($item["11"]) ? $payTime = $createTime = str_replace('/','-',substr($item["11"],0,10)." 08:00:00") : $payTime = $createTime = null;
                        !empty($item["12"]) ? $item["12"] = str_replace(['/','-'],['',''],$item["12"]) : '';
                        if(!empty($item['12'])){
                            if($this->convertAndValidateDate($item['12']) == false){
                                $tmpb = $item["12"];
                                $memo .= "預定出貨日 $tmpb 資料錯誤。";
                            }else{
                                $bookShippingDate = $this->convertAndValidateDate($item['12']);
                            }
                        }else{
                            $memo .= "預定出貨日不可為空值。";
                        }
                        $userMemo = "momo訂單編號: {$partnerOrderNumber}　MOMO預計出貨日：{$bookShippingDate}";
                        $sku = $item["13"];//貨號
                        $quantity = $item["18"];//商品數量
                        $price = $item["19"];//商品單價
                    }elseif($this->param['type'] == '鼎新訂單匯入'){
                        !empty($item["9"]) ? $item["9"] = substr($item['9'],0,4)."-".substr($item["9"],4,2)."-".substr($item["9"],6,2) : '';
                        $item["11"] = str_replace([' ','-'],['',''],$item["11"]);
                        $digiWinPaymentId = $item["2"];
                        $shippingMethodName = $item["10"];
                        if(!empty($shippingMethodName)){
                            if($shippingMethodName == "寄送當地" || $shippingMethodName == "寄送台灣"){
                                $shippingMethod = 6;//寄送台灣
                                $receiverAddress = "台灣 ".$item["3"];
                                !empty($item["9"]) ? $receiverKeyTime = str_replace("/","-",$item["9"]) : '';
                            }elseif($shippingMethodName == "寄送海外"){
                                $shippingMethod = 4;//寄送海外
                                $receiverAddress = $item["3"];
                            }elseif($shippingMethodName == "機場提貨"){
                                $shippingMethod = 1;
                                $receiverAddress = $item["3"];
                                !empty($item["9"]) ? $receiverKeyTime = str_replace("/","-",$item["9"])." ".$item["6"] : '';
                                !empty($item["5"]) ? $receiverKeyword = $item["5"] : '';
                                empty($receiverKeyTime) ? $memo = "機場提貨必須填寫提貨時間。" : '';
                            }elseif($shippingMethodName == "旅店提貨"){
                                $shippingMethod = 2;
                                $receiverAddress = $item["3"];
                                !empty($item["9"]) ? $receiverKeyTime = str_replace("/","-",$item["9"]) : '';
                                empty($receiverKeyTime) ? $memo = "旅店提貨必須填寫提貨時間。" : '';
                            }else{
                                $receiverAddress = null;
                                $memo .= "寄送方式欄位資料錯誤。";
                            }
                        }else{
                            $memo .= "寄送方式未填寫";
                        }
                        //避免漏寫秒或分
                        if(!empty($receiverKeyTime)){
                            $tmp = strtotime($receiverKeyTime);
                            if (!$tmp) {
                                $memo .= "提貨時間欄位資料有誤。";
                                $receiverKeyTime = null;
                            }else{
                                $receiverKeyTime = date('Y-m-d H:i:s',$tmp);
                            }
                        }
                        $createTime = $payTime = substr($item["1"],0,4).'-'.substr($item["1"],4,2).'-'.substr($item["1"],6,2).' 08:00:00';//時間
                        $partnerOrderNumber = $item["7"];
                        $receiverName = $item["8"];//收件人姓名
                        $receiverTel = $item["11"];
                        if(!empty($item['15'])){
                            if($this->convertAndValidateDate($item['15']) == false){
                                $tmpb = $item['15'];
                                $memo .= "預定出貨日 $tmpb 資料錯誤。";
                            }else{
                                $bookShippingDate = $this->convertAndValidateDate($item['15']);
                            }
                        }
                        $userMemo = "(客戶訂單編號: {$partnerOrderNumber})，{$item["4"]}";
                        $receiverEmail = $item["17"]; //收件人email
                        $sku = $item["12"];//貨號
                        $quantity = $item["13"];//商品數量
                        $price = $item["14"];//商品單價
                        //發票資料
                        $invoiceSubType = $item['18'];
                        $invoiceType = $item['19'];
                        $loveCode = $item['20'];
                        $carrierType = $item['21'];
                        $carrierNum = $item['22'];
                        $buyerName = $item['23'];
                        $invoiceNumber = $item['24'];
                        $invoiceTitle = $item['25'];
                    }
                    if(strtotime($payTime) == false){
                        $memo .= "付款日期格式錯誤。";
                        $createTime = $payTime = null;
                    }
                    empty($partnerOrderNumber) ? $memo .= "客戶訂單號碼不可為空值。" : '';
                    if(!is_numeric($quantity)){
                        $memo .= "數量必須為數字。";
                        $quantity = null;
                    }elseif($quantity <= 0){
                        $memo .= "數量不可小於等於 0。";
                    }
                    if((!empty($price) && !is_numeric($price))){
                        $memo .= "金額必須為數字或大於等於0。";
                        $price = null;
                    }else{
                        $price = round($price,2);//商品單價 強轉int
                    }
                    if(!empty($sku)){
                        if($sku=="999000" || $sku=="999001" || $sku=="901001" || $sku=="901002"){
                            //這些代號不檢查商品.
                        }else{
                            $checkProduct = $this->checkProduct($sku);
                            !empty($checkProduct) ? $memo .= $checkProduct : '';
                        }
                    }else{
                        $memo .= "商品貨號不可為空值。";
                    }
                    $this->param['type'] == '鼎新訂單匯入' ? $chkOrder = OrderDB::where('partner_order_number',$partnerOrderNumber)->first() : $chkOrder = OrderDB::where('partner_order_number','like',"$partnerOrderNumber%")->first();
                    if(!empty($chkOrder)){
                        $orderNumber = $chkOrder->order_number;
                        !empty($chkOrder) ? $memo .= "訂單已存在，iCarry訂單號碼： $orderNumber 。" : '';
                    }
                    if(!empty($digiWinPaymentId)){
                        $customer = DigiwinPaymentDB::where('customer_no',$digiWinPaymentId)->first();
                        empty($customer) ? $memo .= "客戶代號錯誤，查無客戶資料。" : '';
                    }else{
                        $memo .="客戶代號不可為空值。";
                    }
                    if($this->param['type'] == '鼎新訂單匯入' && !empty($digiWinPaymentId) && !empty($partnerOrderNumber) && !empty($sku)){ //檢查是否重複
                        $repeate = OrderImportDB::where([
                            ['type','鼎新訂單'],
                            ['digiwin_payment_id',$digiWinPaymentId],
                            ['partner_order_number',$partnerOrderNumber],
                            ['sku',$sku],
                        ])->first();
                        !empty($repeate) ? $memo .= "訂單匯入資料重複。" : '';
                    }
                    //檢查地址內是否有機場字與機場提貨是否相符
                    if((strstr($receiverAddress,'桃園機場') || strstr($receiverAddress,'松山機場') || strstr($receiverAddress,'花蓮航空站')) && $shippingMethodName != '機場提貨'){
                        $addressStr = null;
                        strstr($receiverAddress,'桃園機場') ? $addressStr = '桃園機場' : '';
                        strstr($receiverAddress,'松山機場') ? $addressStr = '松山機場' : '';
                        strstr($receiverAddress,'花蓮航空站') ? $addressStr = '花蓮航空站' : '';
                        $memo .= "提貨方式 $shippingMethodName 與地址中有 $addressStr 方式不符。";
                    }
                    if($this->param['type'] == '鼎新訂單匯入'){
                        if(!empty($invoiceSubType)){
                            if($invoiceSubType > 0 && $invoiceSubType <=3){
                                if($invoiceType != 2 && $invoiceType != 3){
                                    $memo .= "發票聯數錯誤，只有二聯或三聯式。";
                                }
                                if($invoiceSubType == 1){
                                    if(empty($loveCode)){
                                        $memo .= "發票類別為1時，愛心碼欄位必填。";
                                    }
                                }elseif($invoiceSubType == 2){
                                    if(empty($buyerName)){
                                        $memo .= "發票類別為2時，買受人欄位必填。";
                                    }
                                    if($carrierType != null || $carrierType != ''){
                                        if($carrierType >= 0 && $carrierType <=2){
                                            if(empty($carrierNum)){
                                                $memo .= "載具類別存在時，載具號碼欄位必填。";
                                            }
                                        }else{
                                            $memo .= "載具類別應為0=手機條碼 1=自然人憑證 2=智付寶。";
                                        }
                                    }
                                }elseif($invoiceSubType == 3){
                                    if(empty($invoiceTitle)){
                                        $memo .= "發票類別為3時，抬頭欄位必填。";
                                    }
                                    if(empty($invoiceNumber)){
                                        $memo .= "發票類別為3時，統編欄位必填。";
                                    }
                                }
                            }else{
                                $memo .= "發票類別錯誤，應使用數字型態1-3。";
                            }
                        }
                    }
                    if(mb_strlen($receiverAddress) > 255){
                        $memo .= '收件人地址長度超過255字元。';
                        $receiverAddress = mb_substr($receiverAddress,0,254);
                    }
                    !empty($memo) ? $status = -1 : $status = 0;
                    $orderImport = OrderImportDB::create([
                        'import_no' => $importNumber,
                        'type' => $type,
                        'digiwin_payment_id' => $digiWinPaymentId,
                        'partner_order_number' => $partnerOrderNumber,
                        'create_time' => $createTime,
                        'pay_time' => $payTime,
                        'receiver_address' => $receiverAddress,
                        'receiver_name' => mb_substr($receiverName,0,30),
                        'receiver_tel' => str_replace([" ","-","_","'","/",'"','|'],["","","","","","",""],$receiverTel),
                        'receiver_email' => $receiverEmail,
                        'user_memo' => $userMemo,
                        'receiver_keyword' => $receiverKeyword,
                        'receiver_key_time' => $receiverKeyTime,
                        'shipping_method' => $shippingMethod,
                        'sku' => $sku,
                        'quantity' => $quantity,
                        'price' => $price,
                        'book_shipping_date' => $bookShippingDate,
                        'status' => $status,
                        'invoice_type' => $invoiceType,
                        'invoice_sub_type' => $invoiceSubType,
                        'love_code' => $loveCode,
                        'carrier_num' => $carrierNum,
                        'carrier_type' => $carrierType,
                        'invoice_title' => $invoiceTitle,
                        'invoice_number' => $invoiceNumber,
                        'buyer_name' => $buyerName,
                    ]);
                    //檢查資料
                    if(!empty($memo)){
                        $this->param['type'] == '宜睿匯入' ? $rowNo = $i : $rowNo = $i+1;
                        $row = "第 $rowNo 列，";
                        OrderImportAbnormalDB::create([
                            'order_import_id' => $orderImport->id,
                            'import_no' => $importNumber,
                            'type' => $type,
                            'partner_order_number' => $partnerOrderNumber,
                            'sku' => $sku,
                            'quantity' => $quantity,
                            'price' => $price,
                            'memo' => $row.$memo,
                            'row_no' => $rowNo,
                        ]);
                        $result['fail']++;
                    }else{
                        $result['success']++;
                    }
                    $i++;
                }
            }
        }
        return $result;
    }

    protected function yiruiOrderImport($file)
    {
        $result = null;
        //宜睿匯入檔案為文字檔案, 直接使用 file_get_contents 抓出資料, 一筆訂單一個檔案
        // $rowData = 'bbc73d5b-4f06-443c-b079-6ebd51e9aa1c,2021111701624528,,海邊走走花生愛餡蛋捲單盒即享券,EC00295011134,1,520.0000,520.0000,郭華萍,苗栗縣,357,通東里福德路11巷3號,0938712221,,show11077@yahoo.com.tw,,';
        $rowData = file_get_contents($file);
        if(!empty($rowData)){
            $content = explode(',',$rowData);
            if(!empty($content) && is_array($content) && count($content) == 17){
                $chk = OrderImportDB::where([['type',$this->param['type']],['partner_order_number',$content[1]],['sku',$content[4]]])->first();
                if(!empty($chk)){
                    return 'no data imported';
                }else{
                    return [$content];
                }
            }else{
                return 'rows error';
            }
        }else{
            return 'no data';
        }
    }

    protected function chkRows($items){
        $chk = 0;
        for($i=0;$i<count($items);$i++){
            if($this->param['type'] == 'MOMO匯入' && count($items[$i]) != 27){
                $chk++;
            }if($this->param['type'] == '鼎新訂單匯入' && count($items[$i]) != 26){
                $chk++;
            }
        }
        if($chk == 0){
            return true;
        }else{
            return false;
        }
    }

    private function chkData($result)
    {
        $count = count($result);
        $chk = 0;
        for($i=0;$i<count($result);$i++){
            empty($result[$i]) ? $chk++ : '';
        }
        if($chk != count($result)){ //表示有資料
            return true;
        }else{ //表示全部空值
            return false;
        }
    }

    function convertAndValidateDate($date, $format = null)
    {
        // 如果格式參數為 null，則預設輸出格式為 "Y-m-d"
        $outputFormat = $format ? $format : "Y-m-d";

        // 驗證輸入的日期格式是否為 8 位數字
        if (!preg_match('/^\d{8}$/', $date)) {
          // 如果不是數字日期格式，則認為是日期格式
          $timestamp = strtotime($date);

          // 驗證轉換後的時間戳是否有效
          if (!$timestamp) {
            return false;
          }
        } else {
          // 將數字日期轉換為 "YYYY-MM-DD" 格式
          $formattedDate = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);

          // 驗證輸入的日期格式是否為 YYYY-MM-DD
          if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $formattedDate)) {
            return false;
          }

          // 轉換日期為時間戳
          $timestamp = strtotime($formattedDate);

          // 驗證轉換後的時間戳是否有效
          if (!$timestamp) {
            return false;
          }

          // 驗證轉換後的日期是否和輸入的日期一致，避免 2020-02-30 這種非法日期被轉換為 2020-03-01
          if (date('Y-m-d', $timestamp) !== $formattedDate) {
            return false;
          }
        }

        // 轉換日期為指定格式
        return date($outputFormat, $timestamp);
    }
}
