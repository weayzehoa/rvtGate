<?php
namespace App\Traits;
use App\Models\iCarryPay2go as Pay2goDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryGroupBuyingOrder as GroupBuyOrderDB;

trait EzPayInvoiceFunctionTrait
{
    public function ezpayAllowance($order, $param)
    {
        $totalAmt = 0;
        $taxType = $param['taxType'];
        $productUnitName = $productSubtotal = $productQuantity = $productName = $productPrice = $product = [];
        if(!empty($order)){
            if(($order->status >= 3 && !empty($order->is_invoice_no)) || (!empty($order->nidinOrder) && !empty($order->is_invoice_no))){
                //檢查有無餘額可以折讓
                $chkRemain = 0;
                $allowanceInvoice = Pay2goDB::where([['type','allowance'],['order_number', $order->order_number],['invoice_no',$order->is_invoice_no]])->first();
                !empty($allowanceInvoice) ? ($allowanceInvoice->remain_amt == 0 ? $chkRemain++ : '') : '';
                if($chkRemain == 0){
                    //商品資料 AC0001 = 錢街案只能全部折讓, 商品數量等於金額 商品單價等於數量=1
                    if(in_array($order->digiwin_payment_id,['AC0001','AC000101','AC000102','AC000103'])){
                        $i=0;
                        foreach ($order->items as $item) {
                            if($item->is_del == 0){
                                $product[$i]['quantity'] = $productQuantity[] = 1;
                                $product[$i]['price'] = $productPrice[] = $item->price * $item->quantity;
                                $productName[] = mb_substr($item->product_name, 0, 30, 'utf-8'); //最多30個字
                                $productUnitName[] = '組';
                                if($order->invoice_type == 3){
                                    $productAmt[] = round(($item->price * $item->quantity / 1.05),0);
                                    $productTaxAmt[] = round(($item->price * $item->quantity)-($item->price * $item->quantity / 1.05),0);
                                }else{
                                    $productAmt[] = $item->price * $item->quantity;
                                    $productTaxAmt[] = 0;
                                }
                            }
                            $totalAmt += $item->quantity * $item->price;
                            $i++;
                        }
                    }else{//其他案子
                        $items = $param['items'];
                        for($i=0;$i<count($items);$i++){
                            $productQuantity[] = $items[$i]['quantity'];
                            $productPrice[] = $items[$i]['price'];
                            $productName[] = mb_substr($items[$i]['name'], 0, 30, 'utf-8'); //最多30個字
                            $productUnitName[] = $items[$i]['unit_name'];
                            $productTaxAmt[] = $items[$i]['tax'];
                            $productAmt[] = $items[$i]['quantity'] * $items[$i]['price'];
                            $totalAmt += $items[$i]['quantity'] * $items[$i]['price'] + $items[$i]['tax'];
                        }
                    }
                    //ezPay參數
                    $pay2go['HashKey'] = env('ezPay_HashKey');                  //HashKey
                    $pay2go['HashIV'] = env('ezPay_HashIV');                    //HashIV
                    $pay2go['MerchantID'] = env('ezPay_MerchantID');            //MerchantID
                    $pay2go['API'] = env('ezPay_ALLOWANCE_URL');                    //API URL
                    $pay2go['RespondType'] = 'JSON';                            //回傳格式
                    $pay2go['Version'] = '1.3';                                 //API版本
                    $pay2go['TimeStamp'] = time();                              //時間序
                    $pay2go['InvoiceNo'] = $order->is_invoice_no;               //發票號碼
                    $pay2go['MerchantOrderNo'] = $order->order_number;          //商店訂單編號
                    $pay2go['Status'] = 1;                                      //1=開立折讓後，立即確認折讓。
                    $pay2go['TaxTypeForMixed'] = (INT)$taxType;                 //1=應稅 2=零稅率
                    $pay2go['BuyerEmail'] = $order->buyer_email;                //買受人電子信箱
                    $pay2go['ItemName'] = join('|', $productName);              //商品名稱。多項商品時，商品名稱以 | 分隔。例：ItemName=”商品一|商品二” [店家-商品名稱]
                    $pay2go['ItemCount'] = join('|', $productQuantity);         //商品數量。1.純數字。2.多項商品時，商品數量以 |分隔。例：ItemCount =”1|2”
                    $pay2go['ItemUnit'] = join('|', $productUnitName);          //商品單位。1.內容如：個、件、本、張…..。2.多項商品時，商品單位以 | 分隔。例：ItemUnit =”個|本”
                    $pay2go['ItemPrice'] = join('|', $productPrice);            //多項商品時，商品單價以 | 分隔。例：ItemPrice =”200|100”，Category=B2B 時，此參數金額為未稅金額。 [商品單價1000元此處填寫為1000/1.05]，Category=B2C 時，此參數金額為含稅金額。 [商品單價1000元此處填寫為1000]
                    $pay2go['ItemAmt'] = join('|', $productAmt);                //多項商品時，商品小計以 | 分隔。例：ItemAmt =”200|200”
                    $pay2go['ItemTaxAmt'] = join('|', $productTaxAmt);          //折讓商品稅額。
                    $pay2go['TotalAmt'] = $totalAmt;                            //此次開立折讓加總金額。
                    $result = $this->ezpayPost($pay2go);
                    // $result['info'] = '{"Status":"SUCCESS","Message":"\u767c\u7968\u6298\u8b93\u958b\u7acb\u6210\u529f","Result":"{\"CheckCode\":\"1D2A519340C6DD7B4C3894646596F92FE8731BF8B7F12A4181AA9E60C42A1EE4\",\"AllowanceNo\":\"A240313085029794\",\"InvoiceNumber\":\"TA00000007\",\"MerchantID\":\"3449502\",\"MerchantOrderNo\":\"24031275948347\",\"AllowanceAmt\":50,\"RemainAmt\":0}"}';
                    if(!empty($result) && !empty($result['info'])){
                        $pay2goInfo = json_decode($result['info'],true);
                        if(!empty($pay2goInfo['Result'])){
                            $pay2goResult = json_decode($pay2goInfo['Result'],true);
                            $message['msg'] = $msg = $pay2goInfo['Status'].','.$pay2goInfo['Message'];
                            $message['allowanceNo'] = $allowanceNo = $pay2goResult['AllowanceNo'];
                            $allowanceAmt = $pay2goResult['AllowanceAmt'];
                            $remainAmt = $pay2goResult['RemainAmt'];
                            $invoiceNo = $pay2goResult['InvoiceNumber'];
                            $p2g = Pay2goDB::create([
                                'type' => 'allowance',
                                'order_number' => $order->order_number,
                                'post_json' => json_encode($pay2go, JSON_UNESCAPED_UNICODE),
                                'get_json' => $msg,
                                'invoice_no' => $invoiceNo,
                                'allowance_no' => $allowanceNo,
                                'allowance_amt' => $allowanceAmt,
                                'remain_amt' => $remainAmt
                            ]);
                        }else{
                            $message['msg'] = $pay2goInfo['Message'];
                            $message['allowanceNo'] = null;
                        }
                        return $message;
                    }else{
                        $p2g = Pay2goDB::create([
                            'type' => 'allowance',
                            'order_number' => $order->order_number,
                            'post_json' => json_encode($pay2go, JSON_UNESCAPED_UNICODE),
                            'get_json' => $result,
                        ]);
                        return 'ezpay return fail';
                    }
                }else{
                    return 'no remain amt';
                }
            }
        }
    }

    public function ezpayCreate($order, $param, $newOrderNumber = null)
    {
        if(!empty($order)){
            if($param['type'] == 'reopen' || (($order->status == 3 || !empty($order->acOrder) || !empty($order->nidinOrder)) && empty($order->invoice_time) && empty($order->is_invoice_no))){
                $chkPay2Go = Pay2GoDB::where('order_number',$order->order_number)->orderBy('id','desc')->first();
                //購買者信箱如果找不到改收貨者再找不到改成 icarry4tw@gmail.com
                if (empty($order->buyer_email) || !stristr($order->buyer_email, '@') || substr($order->buyer_email, -1)=='@') {
                    $order->buyer_email = $order->receiver_email;
                    $order->buyer_email == '' ? $order->buyer_email = 'icarry4tw@gmail.com' : '';
                }
                //invoice_title與invoice_number相反時(抬頭與統編)
                if (is_numeric($order->invoice_title) && !is_numeric($order->invoice_number)) {
                    $tmp = $order->invoice_title;
                    $order->invoice_title=$order->invoice_number;
                    $order->invoice_number=$tmp;
                }

                //購買者名稱最長30字限制
                empty($order->buyer_name) ? $order->buyer_name = $order->user_id : '';
                $order->buyer_name = mb_substr($order->buyer_name, 0, 30, 'utf-8');

                //處理載具號碼
                if ($order->carrier_num) {
                    $order->carrier_num = str_replace('／', '/', $order->carrier_num);
                    substr($order->carrier_num, 0, 1) != '/' ? '/'.$order->carrier_num : '';
                }
                $i = 0;
                $productUnitName = $productSubtotal = $productQuantity = $productName = $productPrice = $product = [];
                foreach ($order->items as $item) {
                    if($item->is_del == 0){
                        $item->product_name = str_replace([
                            ' ','','|','\t','【即日起預購至12/18止，12/19依序出貨】',
                            '【01/26依序出貨，暫不寄送中國大陸】','【01/26依序出貨】',
                            '【新年預購】','收藏天地-台湾文創禮品館-'
                        ], ['','','','','','','','',''], $item->product_name); //去除不要的字
                        $productName[] = mb_substr($item->product_name, 0, 30, 'utf-8'); //最多30個字
                        if(in_array($order->digiwin_payment_id,['AC0001','AC000101','AC000102','AC000103'])){
                            $productUnitName[] = '組';
                            $product[$i]['quantity'] = $productQuantity[] = 1;
                            $product[$i]['price'] = $productPrice[] = $item->price * $item->quantity;
                        }else{
                            $productUnitName[] = $item->unit_name;
                            $product[$i]['quantity'] = $productQuantity[] = $item->quantity;
                            $product[$i]['price'] = $productPrice[] = $item->price;
                        }
                        $product[$i]['subTotal'] = $productSubtotal[] = $item->quantity * $item->price;
                        $i++;
                    }
                }
                //使用購物金
                if ($order->spend_point > 0) {
                    $productName[] ='購物金折抵';
                    $product[$i]['quantity'] = $productQuantity[] = 1;
                    $productUnitName[] = 'pcs';
                    $product[$i]['price'] = $productPrice[] = -$order->spend_point;
                    $product[$i]['subTotal'] = $productSubtotal[] = -$order->spend_point;
                    $i++;
                }
                //折扣
                if ($order->discount > 0) {
                    $productName[] ='活動折抵';
                    $product[$i]['quantity'] = $productQuantity[] = 1;
                    $productUnitName[] = 'pcs';
                    $product[$i]['price'] = $productPrice[] = -$order->discount;
                    $product[$i]['subTotal'] = $productSubtotal[] = -$order->discount;
                    $i++;
                }elseif ($order->discount < 0) {
                    $productName[] ='活動折抵';
                    $product[$i]['quantity'] = $productQuantity[] = 1;
                    $productUnitName[] = 'pcs';
                    $product[$i]['price'] = $productPrice[] = $order->discount;
                    $product[$i]['subTotal'] = $productSubtotal[] = $order->discount;
                    $i++;
                }
                //跨境稅
                if ($order->parcel_tax > 0) {
                    $productName[] ='跨境稅';
                    $product[$i]['quantity'] = $productQuantity[] = 1;
                    $productUnitName[] = 'pcs';
                    $product[$i]['price'] = $productPrice[] = $order->parcel_tax;
                    $product[$i]['subTotal'] = $productSubtotal[] = $order->parcel_tax;
                    $i++;
                }
                //運費
                if ($order->shipping_fee > 0) {
                    $productName[] ='運費';
                    $product[$i]['quantity'] = $productQuantity[] = 1;
                    $productUnitName[] = 'pcs';
                    $product[$i]['price'] = $productPrice[] = $order->shipping_fee;
                    $product[$i]['subTotal'] = $productSubtotal[] = $order->shipping_fee;
                    $i++;
                }

                //ezPay參數
                $pay2go['HashKey'] = env('ezPay_HashKey');                  //HashKey
                $pay2go['HashIV'] = env('ezPay_HashIV');                    //HashIV
                $pay2go['MerchantID'] = env('ezPay_MerchantID');            //MerchantID
                $pay2go['API'] = env('ezPay_ISSUE_URL');                    //API URL
                $pay2go['RespondType'] = 'JSON';                            //回傳格式
                $pay2go['Version'] = '1.4';                                 //API版本
                $pay2go['TimeStamp'] = time();                              //時間序
                $pay2go['Status'] = 1;                                      //1=即時開立發票
                $pay2go['TaxType'] = 1;                                     //稅別
                $pay2go['TaxRate'] = 5;                                     //稅率 5%
                $pay2go['MerchantOrderNo'] = !empty($newOrderNumber) ? $newOrderNumber : $order->order_number;          //商店訂單編號
                $pay2go['BuyerName'] = $order->buyer_name;                  //買受人名稱
                $pay2go['BuyerEmail'] = $order->buyer_email;                //買受人電子信箱
                $pay2go['BuyerAddress'] = $order->invoice_address;          //買受人地址 非必填
                $pay2go['Category'] = 'B2C';                                //B2B=買受人為營業人(有統編)。三聯時B2B
                $pay2go['BuyerUBN'] = '';                                   //買受人統一編號 B2B時須填寫
                $pay2go['PrintFlag'] = $order->print_flag;                  //索取紙本發票
                $pay2go['LoveCode'] = $order->love_code;                    //愛心碼 (當 Category=B2C 時，才適用此參數)
                $pay2go['CarrierType'] = $order->carrier_type;              //Category=B2C 時，才適用此參數
                $pay2go['CarrierNum'] = $order->carrier_type >= 0 ? rawurlencode($order->carrier_num) : null;  //1.若 CarrierType 參數有提供數值時，則此參數為必填。
                $pay2go['ItemName'] = join('|', $productName);               //商品名稱。多項商品時，商品名稱以 | 分隔。例：ItemName=”商品一|商品二” [店家-商品名稱]
                $pay2go['ItemCount'] = join('|', $productQuantity);          //商品數量。1.純數字。2.多項商品時，商品數量以 |分隔。例：ItemCount =”1|2”
                $pay2go['ItemUnit'] = join('|', $productUnitName);           //商品單位。1.內容如：個、件、本、張…..。2.多項商品時，商品單位以 | 分隔。例：ItemUnit =”個|本”
                $pay2go['ItemPrice'] = join('|', $productPrice);             //多項商品時，商品單價以 | 分隔。例：ItemPrice =”200|100”，Category=B2B 時，此參數金額為未稅金額。 [商品單價1000元此處填寫為1000/1.05]，Category=B2C 時，此參數金額為含稅金額。 [商品單價1000元此處填寫為1000]
                $pay2go['ItemAmt'] = join('|', $productSubtotal);            //多項商品時，商品小計以 | 分隔。例：ItemAmt =”200|200”
                //發票類型
                if ( $order->invoice_type !=3 ) {
                    $pay2go['CarrierType'] == '' && $pay2go['LoveCode']=='' ? $pay2go['PrintFlag']='Y' : $pay2go['PrintFlag']='N';
                    empty($pay2go['BuyerName']) && !empty($pay2go['LoveCode']) ? $pay2go['BuyerName'] = $pay2go['LoveCode'] : '';
                    if ($pay2go['BuyerUBN'] == '') {
                        unset($pay2go['BuyerUBN']);
                    }

                    if ($pay2go['LoveCode'] == '') {
                        unset($pay2go['LoveCode']);
                    } else {
                        unset($pay2go['CarrierType']);
                        unset($pay2go['CarrierNum']);
                    }
                } else {                                                    //三聯式
                    $pay2go['Category'] = 'B2B';                            //改變類別為 B2B
                    $pay2go['BuyerName'] = $order->invoice_title;           //買受人名稱置換成抬頭
                    $pay2go['BuyerUBN'] = $order->invoice_number;           //買受人統編
                    $pay2go['PrintFlag']='Y';                               //B2B 類別的發票，只能選擇索取發票
                    unset($pay2go['LoveCode']);                             //清空載具類別
                    unset($pay2go['CarrierType']);                          //清空載具號碼
                    unset($pay2go['CarrierNum']);                           //清空愛心碼
                    $tmpPrice = $tmpSubTotal = null;
                    for($x=0;$x<count($product);$x++){
                        $tmpPrice .= '|'.round($product[$x]['price'] / 1.05, 2);
                        $tmpSubTotal .= '|'.round(round($product[$x]['price'] / 1.05 , 2) * $product[$x]['quantity'],2);
                    }
                    $pay2go['ItemPrice'] = substr($tmpPrice, 1);                  //Category=B2B 時，此參數金額為未稅金額。 [商品單價1000元此處填寫為1000/1.05]
                    $pay2go['ItemAmt'] = substr($tmpSubTotal, 1);                    //Category=B2B 時，此參數金額為未稅金額。
                }

                //發票金額 [訂單1000元此處填寫為1000]
                $pay2go['TotalAmt'] = ($order->amount + $order->shipping_fee + $order->parcel_tax - $order->spend_point - $order->discount);
                $pay2go['Amt'] = round($pay2go['TotalAmt'] / 1.05, 0);       //銷售額合計 [訂單1000元此處填寫為1000/1.05]
                $pay2go['TaxAmt'] = $pay2go['TotalAmt'] - $pay2go['Amt'];   //稅額 [訂單1000元此處填寫為1000-(1000/1.05)]

                $pay2go['Comment'] = "訂單號碼：$order->order_number";
                if (strstr($order->pay_method, '智付通')) {
                    $pay2go['TransNum'] = $Card4No = null;
                    if(!empty($order->spgateway)){
                        $spgateway = $order->spgateway;
                        if($spgateway->pay_status == 1 && strtoupper($spgateway->PaymentType) == 'CREDIT'){
                            $strStart = strpos($spgateway->result_json,'"Result":"{')+10;
                            $resultJson = str_replace('"}"','"',substr($spgateway->result_json,$strStart));
                            $sp = json_decode($resultJson, true);
                            if(!empty($sp)){
                                $pay2go['TransNum'] = $sp['TradeNo'];//智付寶平台交易序號
                                if (strstr($order->pay_method, '信用卡')) {
                                    $Card4No = $sp['Card4No'];
                                    $pay2go['Comment']="訂單號碼： $order->order_number ，信用卡末四碼： $Card4No ";
                                }
                            }
                        }
                    }
                }

                //當訂單為以下情況【全部符合】時，2021/4/1 起改開零稅率發票
                $yyyymmdd=intval(date('Ymd'));
                if ($yyyymmdd>=20210401) {
                    if ($order->origin_country == '台灣' && $order->ship_to != '台灣' && $pay2go['Category']=='B2C') {
                        if ($pay2go['PrintFlag'] == 'Y' || $order->love_code != '') {
                            if ($order->create_type == 'shopee' && strstr($order->user_memo, '(新加坡)')) {
                                $pay2go['TaxType'] = 2;
                                $pay2go['TaxRate'] = 0;
                                $pay2go['CustomsClearance'] = '';
                                $pay2go['Amt'] = $pay2go['TotalAmt'];
                                $pay2go['TaxAmt'] = 0;
                                $pay2go['CustomsClearance'] = 2;
                            } elseif ($order->create_type == 'app' || $order->create_type == 'kiosk' || $order->create_type == 'admin' || $order->create_type == 'web' || $order->create_type == 'Amazon' || $order->create_type == 'vendor') {
                                $pay2go['TaxType'] = 2;
                                $pay2go['TaxRate'] = 0;
                                $pay2go['CustomsClearance'] = '';
                                $pay2go['Amt'] = $pay2go['TotalAmt'];
                                $pay2go['TaxAmt'] = 0;
                                $pay2go['CustomsClearance'] = 2;
                            }
                        }
                    }
                }

                if ($order->digiwin_payment_id == '011') {
                    $pay2go['Category'] = 'B2B';                            //改變類別為 B2B
                    $pay2go['BuyerName']='樂購商城有限公司';                  //買受人名稱
                    $pay2go['BuyerUBN']='52945710';
                    $pay2go['BuyerAddress']='台北市信義區菸廠路88號9樓';       //買受人地址 非必填
                    $pay2go['BuyerEmail']='joyce.hsu@shopee.com';            //買受人電子信箱
                    $pay2go["Comment"]=$pay2go["Comment"]." 蝦皮訂單編號：".$order->partner_order_number;
                }
                $result = $message = $invoiceTime = $invoiceNo = null;
                $pay2goInfo = $pay2goResult = [];
                $result = $this->ezpayPost($pay2go);
                if(!empty($result['info'])){
                    $pay2goInfo = json_decode($result['info'],true);
                    if(!empty($pay2goInfo['Result'])){
                        $pay2goResult = json_decode($pay2goInfo['Result'],true);
                    }

                    $message = $pay2goInfo['Status'].','.$pay2goInfo['Message'];

                    $p2g = Pay2goDB::create([
                        'type' => $param['type'],
                        'order_number' => $order->order_number,
                        'post_json' => json_encode($pay2go, JSON_UNESCAPED_UNICODE),
                        'get_json' => $message,
                        'tax_type' => $pay2go['TaxType'],
                        'total_amt' => $pay2go['TotalAmt'],
                        'buyer_name' => $pay2go['BuyerName'],
                        'buyer_UBN' => $pay2go['BuyerUBN'] ?? null,
                        'invoice_no' => !empty($pay2goResult['InvoiceNumber']) ? $pay2goResult['InvoiceNumber'] : null,
                        'random_num' => !empty($pay2goResult['RandomNum']) ? $pay2goResult['RandomNum'] : null,
                        'canceled_order_number' => $param['type'] == 'reopen' ? (!empty($newOrderNumber) ? $newOrderNumber : $order->order_number) : null,
                    ]);

                    if(strtoupper($pay2goInfo['Status']) == 'SUCCESS' && !empty($pay2goResult['InvoiceNumber'])){
                        $invoiceNo = $pay2goResult['InvoiceNumber'];
                        $randNum = $pay2goResult['RandomNum'];
                        $invoiceTime = date('Y-m-d H:i:s');
                        $param['model'] == 'groupbuyOrders' ? $order = GroupBuyOrderDB::find($order->id) : $order = OrderDB::with('acOrder','nidinOrder')->find($order->id);
                        if(!empty($invoiceNo) && !empty($order)){
                            $order->update([
                                'is_invoice' => 1,
                                'is_invoice_no' => $invoiceNo,
                                'invoice_time' => $invoiceTime,
                                'invoice_rand' => $randNum,
                            ]);
                            if(!empty($order->acOrder) || !empty($order->nidinOrder)){
                                if(!empty($order->acOrder)){
                                    strstr($order->acOrder->message,'訂單建立成功，發票開立失敗。') ? $order->acOrder->update(['is_invoice' => 1, 'message' => '發票重新開立成功。']) : $order->acOrder->update(['is_invoice' => 1]);
                                }elseif(!empty($order->nidinOrder)){
                                    strstr($order->nidinOrder->message,'開立發票失敗') ? $order->nidinOrder->update(['is_invoice' => 1, 'message' => '發票重新開立成功。']) : $order->nidinOrder->update(['is_invoice' => 1]);
                                }
                                return $result;
                            }else{
                                return 'success';
                            }
                        }
                    }
                }else{
                    $p2g = Pay2goDB::create([
                        'type' => $param['type'],
                        'order_number' => $order->order_number,
                        'post_json' => json_encode($pay2go, JSON_UNESCAPED_UNICODE),
                        'get_json' => json_encode($result, JSON_UNESCAPED_UNICODE),
                        'tax_type' => $pay2go['TaxType'],
                        'total_amt' => $pay2go['TotalAmt'],
                        'buyer_name' => $pay2go['BuyerName'],
                        'buyer_UBN' => $pay2go['BuyerUBN'] ?? null,
                    ]);
                }
            }
        }
        return null;
    }

    public function ezpayCancel($orders,$type,$reason)
    {
        if ($type == 'cancel' && count($orders) > 0) {
            foreach($orders as $order){
                //找出是否曾經作廢重開過
                $canceledOrderNumber = null;
                $chkCancel = Pay2GoDB::where([['type','reopen'],['invoice_no',$order->is_invoice_no]])->first();
                !empty($chkCancel) ? $canceledOrderNumber = $chkCancel->canceled_order_number : '';
                $pay2go=[
                    'HashKey'=>env('ezPay_HashKey'),
                    'HashIV'=>env('ezPay_HashIV'),
                    'MerchantID'=>env('ezPay_MerchantID'),
                    'RespondType'=>'JSON',
                    'Version'=>'1.0',
                    'TimeStamp'=>time(),
                    'InvoiceNumber'=>$order->is_invoice_no, //發票號碼
                    'InvalidReason'=> mb_substr($reason,0,12),
                    'API'=> env('ezPay_INVALID_URL'),
                ];
                $result = $this->ezpayPost($pay2go);
                $short=array('InvoiceNumber'=>$order->is_invoice_no,'InvalidReason'=>mb_substr($reason,0,12));
                $result['info'] = preg_replace('/^\xef\xbb\xbf/', '', $result['info']);
                $info=json_decode($result['info'],true);
                $message = $info['Status'].','.$info['Message']."($order->is_invoice_no)";
                $pay2go['LoveCode'] = $order->love_code;
                $pay2go['PrintFlag'] = $order->print_flag;
                $pay2go['CarrierType'] = $order->carrier_type;
                $pay2go['TaxType'] = 1;
                $pay2go['TotalAmt'] = ($order->amount + $order->shipping_fee + $order->parcel_tax - $order->spend_point - $order->discount);
                $pay2go['BuyerName'] = $order->buyer_name;
                $pay2go['BuyerUBN'] = null;
                $pay2go['CarrierType'] == '' && $pay2go['LoveCode'] == '' ? $pay2go['PrintFlag'] = 'Y' : $pay2go['PrintFlag'] = 'N';
                if ($order->digiwin_payment_id == '011') {
                    $pay2go['BuyerName']='樂購商城有限公司';                  //買受人名稱
                    $pay2go['BuyerUBN']='52945710';
                    $pay2go['PrintFlag']='Y';
                }
                if ($order->invoice_type ==3) {
                    $pay2go['BuyerName'] = $order->invoice_title;
                }
                //當訂單為以下情況【全部符合】時，2021/4/1 起改開零稅率發票
                $yyyymmdd=intval(date('Ymd'));
                if ($yyyymmdd>=20210401) {
                    if ($order->origin_country == '台灣' && $order->ship_to != '台灣' && $order->invoice_no == null) {
                        if ($pay2go['PrintFlag'] == 'Y' || $pay2go['LoveCode'] != '') {
                            if ($order->create_type == 'shopee' && strstr($order->user_memo, '(新加坡)')) {
                                $pay2go['TaxType'] = 2;
                            } elseif ($order->create_type == 'app' || $order->create_type == 'kiosk' || $order->create_type == 'admin' || $order->create_type == 'web' || $order->create_type == 'Amazon' || $order->create_type == 'vendor') {
                                $pay2go['TaxType'] = 2;
                            }
                        }
                    }
                }
                Pay2goDB::create([
                    'type' => 'cancel',
                    'order_number' => $order->order_number,
                    'post_json' => json_encode($short),
                    'get_json' => $message,
                    'tax_type' => $pay2go['TaxType'],
                    'total_amt' => $pay2go['TotalAmt'],
                    'buyer_name' => $pay2go['BuyerName'],
                    'buyer_UBN' => $pay2go['BuyerUBN'],
                    'invoice_no' => $order->is_invoice_no,
                    'canceled_order_number' => !empty($canceledOrderNumber) ? $canceledOrderNumber : $order->order_number,
                ]);

                if($info['Status']=='SUCCESS' || $info['Status']=='LIB10005'){
                    $order->update(['is_invoice' => 2, 'is_invoice_no' => $order->is_invoice_no.'(廢)']);
                }
            }
        }
        return null;
    }

    public function ezpayPost($postDataArray)
    {
        $key = env('ezPay_HashKey');                        //HashKey
        $iv = env('ezPay_HashIV');                          //HashIV
        $MerchantID = env('ezPay_MerchantID');              //MerchantID
        $url = $postDataArray['API'];                       //連接網址
        unset($postDataArray['HashIV']);                    //不放入postData
        unset($postDataArray['MerchantID']);                //不放入postData
        unset($postDataArray['HashKey']);                   //不放入postData
        unset($postDataArray['API']);                       //不放入postData
        $postDataStr = http_build_query($postDataArray);    //轉成字串排列
        if (phpversion() > 7) {
            //php 7 以上版本加密
            $postData = trim(bin2hex(openssl_encrypt($this->ezAddPadding($postDataStr),'AES-256-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv)));
        } else {
            //php 7 之前版本加密
            $postData = trim(bin2hex(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key,$this->ezAddPadding($postDataStr), MCRYPT_MODE_CBC, $iv)));
        }

        $transactionArray = array( //送出欄位
            'MerchantID_' => $MerchantID,
            'PostData_' => $postData
        );
        $transactionStr = http_build_query($transactionArray);
        return $info = $this->ezCurl($url, $transactionStr);
        /*
            Array ( [url] => https://inv.pay2go.com/API/invoice_issue [parameter] => 亂碼 [status] =>200 [error] => 0 [result] =>{'Status':'SUCCESS','Message':'\u96fb\u5b50\u767c\u7968\u958b\u7acb\u6210\u529f','Result':'{\'CheckCode'=>'C4156CA208897278C84D929DE48F4A2BCD1FF3ED4B97D09A14E2E2143E3EFD2E','MerchantID'=>'3622183','MerchantOrderNo'=>'201409170000001','InvoiceNumber'=>'UY25000014','TotalAmt\':500,\'InvoiceTransNo'=>'14061313541640927','RandomNum'=>'0142','CreateTime'=>'2014-06-13 13:54:16\'}'} )
        */
    }

    function ezAddPadding($string, $blocksize = 32) {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
        return $string;
    }

    function ezCurl($url = '', $parameter = '') {
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'Google Bot',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_SSL_VERIFYHOST => FALSE,
            CURLOPT_POST => '1',
            CURLOPT_HTTP_VERSION => '1.1',
            CURLOPT_POSTFIELDS => $parameter
        );
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch);
        curl_close($ch);
        $info = array(
            'url' => $url,
            'sent_parameter' => $parameter,
            'http_status' => $retcode,
            'curl_error_no' => $error,
            'info' => $result
        );
        return $info;
    }
}
