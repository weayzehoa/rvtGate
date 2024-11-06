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

use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryOrderItemPackage as OrderItemPackageDB;
use App\Models\iCarrySiteSetup as SiteSetupDB;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use App\Models\iCarryShippingSet as ShippingSetDB;
use App\Models\iCarryShippingMethod as ShippingMethodDB;
use App\Models\Quotation as QuotationDB;
use App\Models\OrderImport as OrderImportDB;
use App\Models\SystemSetting as SystemSettingDB;

use App\Traits\OrderImportFunctionTrait;
use App\Traits\ProductAvailableDate;

use DB;

use App\Jobs\AdminOrderStatusJob;

class OrderImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,OrderImportFunctionTrait,ProductAvailableDate;
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
        $param['orders'] = 0;
        $orderNumbers = $abnormalOrder = [];
        $siteSetup = SiteSetupDB::first();
        $exchangeRate = SystemSettingDB::first()->exchange_rate_RMB;
        $grossWeightRate = SystemSettingDB::first()->gross_weight_rate;
        $specialDateStart = str_replace(array("/","-"),array("",""),$siteSetup->pre_order_start_date);
        $specialDateEnd = str_replace(array("/","-"),array("",""),$siteSetup->pre_order_end_date);
        $type = str_replace('匯入', '', $this->param['type']);
        if($type == 'MOMO'){
            $userId=47253;
            $userMobile = "+886906486688";
            $buyerEmail = $userEmail="icarry4tw@gmail.com";
            $originCountry = "台灣";
            $shipTo = "台灣";
            $createType = $payMethod = $userName = 'momo';
            $invoiceType = 2;
            $invoiceSubType = 2;
            $printFlag = 'N';
            $status = 1;
        }elseif($type == '鼎新訂單'){
            $userId = 2020;
            $userMobile = '+886906486688';
            $userEmail = 'icarry4tw@gmail.com';
            $originCountry = '台灣';
            $createType = null;
            $invoiceType = 2;
            $invoiceSubType = 2;
            $printFlag = 'N';
            $status = 1;
        }elseif($type == '宜睿'){
            $userId = 54961;
            $userMobile = '+886906486688';
            $buyerEmail = $userEmail='icarry@icarry.me';
            $originCountry = '台灣';
            $shipTo = '台灣';
            $createType = 'yirui'; //建立方式
            $payMethod = $userName = '宜睿';
            $shippingMemo = '';
            $shippingNumber = '';
            $invoiceType = 2;
            $invoiceSubType = 2;
            $printFlag = 'N';
            $status = 1;
        }
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $importData = OrderImportDB::where('import_no',$param['import_no'])->get();
        if(!empty($param['test'])){
            $importData = OrderImportDB::where('import_no',1667794989)->get();
        }
        foreach($importData as $data) $data->status == -1 ? $abnormalOrder[] = $data->partner_order_number : $orderNumbers[] = $data->partner_order_number;
        $abnormalOrder = array_unique($abnormalOrder); //找出有異常的訂單號碼
        $orderNumbers = array_unique($orderNumbers); //沒異常的訂單號碼
        $goodOrders = array_diff($orderNumbers,$abnormalOrder); //排除異常的訂單號碼
        //找出需要處理的資料
        $importData = OrderImportDB::where('import_no',$param['import_no'])->whereIn('partner_order_number',$goodOrders)->select([
            '*',
            DB::raw("SUM(quantity) as quantity"),
            DB::raw("unix_timestamp(pay_time) as ctime"),
        ])->groupBy('partner_order_number','sku','book_shipping_date')->get();
        $importData = $importData->groupBy('partner_order_number')->all();
        if(count($importData) > 0 && count($goodOrders) > 0){
            foreach($importData as $partnerOrderNumber => $data){
                // $bookShippingDate 有可能空值, 需要把partner_order_number拆開
                $orders = $data->groupBy('book_shipping_date')->all();
                foreach($orders as $bookShippingDate => $items){
                    $bookShippingDate == '' ? $bookShippingDate = null : '';
                    //從items裡面找出共用的資料
                    foreach($items as $item){
                        $type = $item->type;
                        $digiWinPaymentId = $item->digiwin_payment_id;
                        $createTime = $payTime = $item->pay_time;
                        $receiverAddress = $item->receiver_address;
                        $receiverName = $item->receiver_name;
                        $receiverTel = $item->receiver_tel;
                        $receiverKeyTime = $item->receiver_key_time;
                        $receiverKeyTime == '' ? $receiverKeyTime = null : '';
                        $receiverKeyword = $item->receiver_keyword;
                        $shippingMethod = $item->shipping_method;
                        $type == '鼎新訂單' ? $buyerEmail = $receiverEmail = $item->receiver_email : $receiverEmail = $item->receiver_email;
                        $ctime = $item->ctime;
                        $userMemo = $item->user_memo;
                        $invoiceType = $item->invoice_type;
                        $invoiceSubType = $item->invoice_sub_type;
                        $loveCode = $item->love_code;
                        $carrierNum = $item->carrier_num;
                        $carrierType = $item->carrier_type;
                        $invoiceTitle = $item->invoice_title;
                        $invoiceNumber =$item->invoice_number;
                        $buyerName = $item->buyer_name;
                    }
                    $amount = $discount = $spendPoint = $shippingFee = $parcelTax = 0;
                    $vendorArrivalDate = null;
                    $orderNumber = date("ymdHis",$ctime);
                    for($i=1;$i<=100;$i++){ //避免重複訂單號
                        $orderNumber = date("ymd",$ctime).substr($orderNumber,-5).rand(100,999);//12碼+2
                        $chkOrder = OrderDB::where('order_number',$orderNumber)->first();
                        if(!empty($chkOrder)){
                            continue;
                        }else{
                            break;
                        }
                    }
                    if($type == 'MOMO'){
                        //MOMO有同單號不同出貨日期, 需要重新變更momo訂單編號+001, 002...
                        $chkOrder = OrderDB::where('partner_order_number','like',"$partnerOrderNumber%")->orderBy('id','desc')->first();
                        if(!empty($chkOrder)){ //代表相同號碼不同預定出貨日
                            if($chkOrder->partner_order_number == $partnerOrderNumber){
                                $number = $partnerOrderNumber.'001';
                                $chkOrder->update([
                                    'partner_order_number' => $number,
                                    'user_memo' => str_replace($partnerOrderNumber,$number,$chkOrder->user_memo),
                                ]);
                                $newPartnerOrderNumber = $partnerOrderNumber.'002';
                            }else{
                                $newPartnerOrderNumber = $chkOrder->partner_order_number+1;
                            }
                        }else{
                            $newPartnerOrderNumber = $partnerOrderNumber;
                        }
                        $userMemo = "momo訂單編號: $newPartnerOrderNumber  MOMO預計出貨日：$bookShippingDate";
                        //momo訂單不管廠商出貨日
                        $bookShippingDate=str_replace(array("-","/"),array("",""),$bookShippingDate);
                        if(strtotime($payTime) >= strtotime($specialDateStart) && strtotime($payTime) <= strtotime($specialDateEnd)){
                            //活動檔期內 預定出貨日 與 MOMO 出貨日同一天
                            $data = $this->findMomoIsOut($bookShippingDate);
                            $is_out = $data['is_out'];
                            $bookShippingDate=$this->getMomoBookingShippingDateInTheSameDay($bookShippingDate,$is_out);
                        }else{
                            //非活動檔期內 預定出貨日與momo出貨日往前一天
                            $data = $this->findMomoIsOut($bookShippingDate);
                            $is_out = $data['is_out'];
                            $bookShippingDate=$this->getMomoBookingShippingDateMinusOneDay($bookShippingDate,$is_out);
                        }
                        $bookShippingDate = substr($bookShippingDate, 0, 4).'-'.substr($bookShippingDate, 4, 2).'-'.substr($bookShippingDate, -2);
                    }elseif($type == '鼎新訂單'){
                        if($shippingMethod == 4){
                            $shipTo = explode(' ', $receiverAddress)[0];
                        }else{
                            $shipTo = "台灣";
                        }
                        $customer = DigiwinPaymentDB::where('customer_no',$digiWinPaymentId)->first();
                        !empty($customer) ? $payMethod = $customer->customer_name : $payMethod = null;
                        !empty($customer) ?$createType = $customer->create_type : $createType = null;
                    }
                    //紀錄當下的運費基準
                    $shippingMethodName = $shippingBasePrice = $shippingKgPrice = null;
                    $shippingMethod == 1 ? $shippingMethodName = '當地機場' : '';
                    $shippingMethod == 2 ? $shippingMethodName = '當地旅店' : '';
                    $shippingMethod == 3 && $shippingMethod == 6 ? $shippingMethodName = '當地地址' : '';
                    $shippingMethod == 4 ? $shippingMethodName = $shipTo : '';
                    $originCountry == '台灣' && $shippingMethod == 5 ? $shippingMethodName = '當地地址' : '';
                    $originCountry == '日本' && $shipTo == '日本' ? $shippingMethodName = '當地地址' : '';
                    $shippingSet = ShippingSetDB::where([['product_sold_country',$originCountry],['shipping_methods',$shippingMethodName],['is_on',1]])->first();
                    if(!empty($shippingSet)){
                        $shippingBasePrice = $shippingSet->shipping_base_price;
                        $shippingKgPrice = $shippingSet->shipping_kg_price;
                    }
                    $key = env('APP_AESENCRYPT_KEY');
                    $orderData = [
                        'order_number' => $orderNumber,
                        'user_id' => $userId,
                        'origin_country' => $originCountry,
                        'ship_to' => $shipTo,
                        'book_shipping_date' => $bookShippingDate,
                        'receiver_name' => mb_substr($receiverName,0,30),
                        'receiver_tel' => DB::raw("AES_ENCRYPT('$receiverTel', '$key')"),
                        'receiver_email' => $receiverEmail,
                        'receiver_address' => $receiverAddress,
                        'receiver_keyword' => $receiverKeyword,
                        'receiver_key_time' => $receiverKeyTime,
                        'shipping_method' => $shippingMethod,
                        'invoice_type' => $invoiceType,
                        'invoice_sub_type' => $invoiceSubType,
                        'spend_point' => $spendPoint,
                        'amount' => $amount,
                        'shipping_fee' => $shippingFee,
                        'parcel_tax' => $parcelTax,
                        'pay_method' => $payMethod,
                        'exchange_rate' => $exchangeRate,
                        'discount' => $discount,
                        'user_memo' => $userMemo,
                        'partner_order_number' => $type == 'MOMO' ? $newPartnerOrderNumber : $partnerOrderNumber,
                        'pay_time' => $payTime,
                        'buyer_email' => $buyerEmail,
                        'print_flag' => $printFlag,
                        'create_type' => $createType,
                        'status' => $status,
                        'digiwin_payment_id' => $digiWinPaymentId,
                        'create_time' => $payTime,
                        'vendor_arrival_date' => $vendorArrivalDate,
                        'shipping_kg_price' => $shippingKgPrice,
                        'shipping_base_price' => $shippingBasePrice,
                        'invoice_type' => $invoiceType,
                        'invoice_sub_type' => $invoiceSubType,
                        'love_code' => $loveCode,
                        'carrier_num' => $carrierNum,
                        'carrier_type' => $carrierType,
                        'invoice_title' => $invoiceTitle,
                        'invoice_number' => $invoiceNumber,
                        'buyer_name' => $buyerName,
                    ];
                    $order = OrderDB::create($orderData);
                    //建立商品資料
                    foreach($items as $item){
                        $sku = $item->sku;
                        if($sku=="999000" || $sku=="999001" || $sku=="901001" || $sku=="901002"){
                            if($sku == "999000"){
                                $discount = $item->price;
                            }elseif($sku == "999001"){
                                $spendPoint = $item->price;
                            }elseif($sku == "901001"){
                                $shippingFee = $item->price;
                            }elseif($sku == "901002"){
                                $parcelTax = $item->price;
                            }
                        }else{
                            $product = $this->getProductData($sku,$payTime);
                            if (!empty($product)) {
                                $customer = DigiwinPaymentDB::where('customer_no',$digiWinPaymentId)->first();
                                if(!empty($customer)){
                                    //如果需要使用報價單時，所有商品資料(單品與組合)，皆以鼎新報價單為準，如果沒有缺少則填0.
                                    if($customer['use_quotation'] == 1){
                                        $quotation = QuotationDB::where('MB002',$product->digiwin_no)
                                        ->where('MB001',$digiWinPaymentId)
                                        ->where('MB017','<=',date('Ymd',strtotime($payTime)))
                                        ->orderBy('MB017','desc')->first();
                                        !empty($quotation) ? $item->price = $quotation->MB008 : $item->price = 0;
                                    }
                                }
                                $price = $item->price;
                                $quantity = $item->quantity;
                                $amount += $price * $quantity;
                                $adminMemo="原價：{$product->price}";
                                !empty($product->net_weight) ? $netWeight = $product->net_weight : $netWeight = 0;
                                !empty($product->gross_weight) ? $grossWeight = $grossWeightRate * $product->gross_weight : $grossWeight = 0;
                                $orderItem = [
                                    'order_id' => $order->id,
                                    'product_model_id' => $product->product_model_id,
                                    'product_name' => $product->product_name,
                                    'price' => $price,
                                    'purchase_price' => $product->vendor_price,
                                    'gross_weight' => $grossWeight,
                                    'net_weight' => $netWeight,
                                    'quantity' => $quantity,
                                    'digiwin_no' => $product->digiwin_no,
                                    'vendor_service_fee_percent' => $product->vendor_service_fee_percent,
                                    'shipping_verdor_percent' => $product->shipping_verdor_percent,
                                    'product_service_fee_percent' => $product->product_service_fee_percent,
                                    'admin_memo' => $adminMemo,
                                    'is_tax_free' => $product->is_tax_free,
                                    'direct_shipment' => $product->direct_shipment,
                                    'digiwin_payment_id' => $digiWinPaymentId,
                                    'create_time' => $payTime,
                                    'vendor_id' => $product->vendor_id,
                                ];
                                $orderItem = OrderItemDB::create($orderItem);
                                if(strstr($product->sku,'BOM')){
                                    $packageData = json_decode(str_replace('	','',$product->package_data));
                                    foreach($packageData as $package){
                                        if($package->bom == $product->sku){
                                            foreach($package->lists as $list){
                                                $useQty = $list->quantity;
                                                $pm = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                                ->where($productModelTable.'.sku',$list->sku)
                                                ->where($productModelTable.'.is_del',0)
                                                ->select([
                                                    $productModelTable.'.id as product_model_id',
                                                    $productModelTable.'.sku',
                                                    $productModelTable.'.digiwin_no',
                                                    $productTable.'.id as product_id',
                                                    DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                                                    $productTable.'.unit_name',
                                                    $productTable.'.price',
                                                    $productTable.'.gross_weight',
                                                    $productTable.'.net_weight',
                                                    $productTable.'.direct_shipment',
                                                    $productTable.'.is_tax_free',
                                                    $productTable.'.vendor_price',
                                                    $productTable.'.service_fee_percent as product_service_fee_percent',
                                                    $productTable.'.package_data',
                                                    $vendorTable.'.id as vendor_id',
                                                    $vendorTable.'.name as vendor_name',
                                                    $vendorTable.'.service_fee as vendor_service_fee',
                                                    $vendorTable.'.shipping_verdor_percent',
                                                ])->first();
                                                if(!empty($pm)){
                                                    $packageItem = [
                                                        'order_id' => $order->id,
                                                        'order_item_id' => $orderItem->id,
                                                        'product_model_id' => $pm->product_model_id,
                                                        'sku' => $pm->sku,
                                                        'digiwin_no' => $pm->digiwin_no,
                                                        'digiwin_payment_id' => $digiWinPaymentId,
                                                        'gross_weight' => $grossWeightRate * $pm->gross_weight,
                                                        'net_weight' => $pm->net_weight,
                                                        'quantity' => $useQty * $quantity,
                                                        'is_del' => 0,
                                                        'create_time' => $payTime,
                                                        'product_name' => $pm->product_name,
                                                        'purchase_price' => 0,
                                                        'direct_shipment' => $pm->direct_shipment,
                                                    ];
                                                    OrderItemPackageDB::create($packageItem);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //更新訂單金額
                    $order->update([
                        'amount' => $amount,
                        'discount' => $discount,
                        'parcel_tax' => $parcelTax,
                        'shipping_fee' => $shippingFee,
                        'spend_point' => $spendPoint,
                    ]);
                    $param['orders']++;
                }
            }
        }
        return $param;
    }
}
