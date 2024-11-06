<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\IpAddress as IpAddressDB;
use App\Models\AcOrder as AcOrderDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use App\Models\SellImport as SellImportDB;
use DB;

use App\Jobs\AdminInvoiceJob;
use App\Jobs\AcOrderProcessJob;
use App\Jobs\AcOrderReturnProcessJob;
use App\Traits\OrderFunctionTrait;
use App\Traits\UniversalFunctionTrait;
use App\Traits\ApiResponser;

use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class AcOrderController extends Controller
{
    use OrderFunctionTrait,UniversalFunctionTrait,ApiResponser;

    // 先經過 middleware 檢查
    public function __construct()
    {
        $this->middleware('auth:gate', ['except' => ['acOrder','acOrderReturn']]);
        //改走cloudflare需抓x-forwareded-for
        if(!empty(request()->header('x-forwarded-for'))){
            $this->loginIp = request()->header('x-forwarded-for');
        }else{
            $this->loginIp = request()->ip();
        }
        $this->allowIps = IpAddressDB::where([['admin_id',0],['is_on',1]])->select('ip')->get()->pluck('ip')->all();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $menuCode = 'M27S9';
        $appends =  $compact = $orders = [];
        //將進來的資料作參數轉換及附加到appends及compact中
        foreach (request()->all() as $key => $value) {
            $$key = $value;
            if (isset($$key)) {
                //轉換參數
                if(isset($synced_date_not_fill)) {
                    strtolower($synced_date_not_fill) == 'on' ? $synced_date_not_fill == 1 : '';
                }
                $appends = array_merge($appends, [$key => $value]);
                $compact = array_merge($compact, [$key]);
            }
            !empty($value) ? $con[$key] = $value : '';
        }

        if (!isset($status)) {
            $status = '1,2,3,4';
            $compact = array_merge($compact, ['status']);
        }

        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }
        //計算及item資料分拆
        $orders = $this->orderItemSplit($this->getOrderData(request(),'index','acOrder'));
        foreach($orders as $order){
            !empty($order->syncDate) ? $order->syncDate = str_replace('-','/',substr($order->syncDate['created_at'],0,10)) : $order->syncDate = null;
            foreach($order->items as $item){
                if(count($item->sells) > 0){
                    foreach($item->sells as $sell){
                        $item->sell_quantity += $sell->sell_quantity;
                    }
                }
            }
            //檢查acOrder是否有全部處理
            $order->acOrderChk = 0;
            if(!empty($order->acOrder)){
                $order->acOrder->is_sync == 0 ? $order->acOrderChk++ : '';
                $order->acOrder->is_invoice == 0 ? $order->acOrderChk++ : '';
                $order->acOrder->purchase_sync == 0 ? $order->acOrderChk++ : '';
                $order->acOrder->is_sell == 0 ? $order->acOrderChk++ : '';
                empty($order->acOrder->purchase_id) ? $order->acOrderChk++ : '';
                $order->acOrder->is_stockin == 0 ? $order->acOrderChk++ : '';
            }
        }
        $compact = array_merge($compact, ['menuCode','orders','appends']);
        return view('gate.acOrders.index', compact($compact));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function acOrder(Request $request)
    {
        $return['err_code'] = 0;
        $return['err_msg'] = null;
        $detials = $icontent = [];
        $key = env('APP_AESENCRYPT_KEY');
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $userId = 68081;
        $buyerEmail = $receiverEmail = $receiverName = $buyerName = $partnerOrderNumber = $printFlag = $digiwinNo = $receiverTel = $invoiceType = $amount = $loveCode = $carrierNum = $carrierType = $invoiceNumber = $invoiceTitle = null;
        if(in_array($this->loginIp,$this->allowIps)){
            $data = $request->all();
            if(!empty($request->getContent())){
                $getJson = $request->getContent();
                $data = json_decode($getJson, true);
            }
            if(count($data) > 0){
                if(!isset($data['transaction_id'])){
                    $return['err_code'] = 1;
                    $return['err_msg'] = "transaction_id 錯誤/不存在。";
                }else{
                    $partnerOrderNumber = $data['transaction_id'];
                    $allowance = 0;
                    isset($data['allowance_date']) ? $allowance = 1 : $allowance = 0;
                    $acOrder = AcOrderDB::where('serial_no',$partnerOrderNumber)->first();
                    if(!empty($acOrder)){
                        if($allowance == 1){
                            $allowanceDate = $this->convertAndValidateDate($data['allowance_date']);
                            if($allowanceDate == false){
                                $return['err_code'] = 4;
                                $return['err_msg'] = "allowance_date 日期格式資料錯誤。";
                            }else{
                                $allowanceAmount = $data['allowance_total_amount'];
                                if($acOrder->amount != $allowanceAmount){
                                    $return['err_code'] = 5;
                                    $return['err_msg'] = "allowance_total_amount 金額與訂單金額不符。";
                                }else{
                                    $acOrderId = $acOrder->id;
                                    $orderId = $acOrder->order_id;
                                    $orderNumber = $acOrder->order_number;
                                    $order = OrderDB::with('sells','returns')->select(['id','order_number'])->find($acOrder->order_id);
                                    if(!empty($order)){
                                        $purchaseOrder = PurchaseOrderDB::with('returns')->find($acOrder->purchase_id);
                                        $sells = $order->sells;
                                        $returns = $order->returns;
                                        if(count($sells) > 0){
                                            if(count($returns) == 0 && count($purchaseOrder->returns) == 0){
                                                if(!empty($purchaseOrder)){
                                                    $acOrder->update(['return_date' => $allowanceDate]);
                                                    if(strstr(env('APP_URL'),'localhost')){
                                                        AcOrderReturnProcessJob::dispatchNow($acOrderId);
                                                    }else{
                                                        //背端處理檢查
                                                        $chkQueue = 0;
                                                        $delay = null;
                                                        $minutes = 1;
                                                        $jobName = 'AcOrderReturnProcessJob';
                                                        $countQueue = Redis::llen('queues:default');
                                                        $allQueues = Redis::lrange('queues:default', 0, -1);
                                                        $allDelayQueues = Redis::zrange('queues:default:delayed', 0, -1);
                                                        if(count($allQueues) > 0){
                                                            if(count($allDelayQueues) > 0){
                                                                $allDelayQueues = array_reverse($allDelayQueues);
                                                                for($i=0;$i<count($allDelayQueues);$i++){
                                                                    $job = json_decode($allDelayQueues[$i],true);
                                                                    if(strstr($job['displayName'],$jobName)){
                                                                        $commandStr = $job['data']['command'];
                                                                        if(strstr($commandStr,'s:26')){
                                                                            $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                                                                            $command = explode('____',$commandStr);
                                                                            $time = $command[1];
                                                                            $delay = Carbon::parse($time)->addminutes($minutes);
                                                                        }else{
                                                                            $delay = Carbon::now()->addminutes($minutes);
                                                                        }
                                                                        $chkQueue++;
                                                                        break;
                                                                    }
                                                                }
                                                            }else{
                                                                foreach($allQueues as $queue){
                                                                    $job = json_decode($queue,true);
                                                                    if(strstr($job['displayName'],$jobName)){
                                                                        $delay = Carbon::now()->addminutes($minutes);
                                                                        $chkQueue++;
                                                                    }
                                                                }
                                                            }
                                                        }else{
                                                            $queue = DB::table('jobs')->where('payload','like',"%$jobName%")->orderBy('id','desc')->first();
                                                            if(!empty($queue)){
                                                                $payload = $queue->payload;
                                                                $job = json_decode($payload,true);
                                                                $commandStr = $job['data']['command'];
                                                                if(strstr($commandStr,'s:26')){
                                                                    $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                                                                    $command = explode('____',$commandStr);
                                                                    $time = $command[1];
                                                                    $delay = Carbon::parse($time)->addminutes($minutes);
                                                                }else{
                                                                    $delay = Carbon::now()->addminutes($minutes);
                                                                }
                                                                $chkQueue++;
                                                            }
                                                        }
                                                        if($chkQueue > 0){
                                                            !empty($delay) ? AcOrderReturnProcessJob::dispatch($acOrderId)->delay($delay) : AcOrderReturnProcessJob::dispatch($acOrderId);
                                                        }else{
                                                            AcOrderReturnProcessJob::dispatch($acOrderId);
                                                        }
                                                    }
                                                    $param['id'] = $order->id;
                                                    $param['type'] = 'allowance';
                                                    $result = AdminInvoiceJob::dispatchNow($param);
                                                    if($result['msg'] != 'SUCCESS,發票折讓開立成功'){
                                                        $return['err_code'] = 64;
                                                        $return['err_msg'] = "發票折讓失敗。";
                                                    }else{
                                                        $return['err_msg'] = "折讓單單號：".$result['allowanceNo'];
                                                    }
                                                }else{
                                                    $return['err_code'] = 63;
                                                    $return['err_msg'] = "iCarry 採購單不存在，無法完成退貨。";
                                                }
                                            }else{
                                                $return['err_code'] = 62;
                                                $return['err_msg'] = "transaction_id 此訂單曾經退貨，無法完成退貨。";
                                            }
                                        }else{
                                            $return['err_code'] = 61;
                                            $return['err_msg'] = "iCarry 出貨單不存在，無法完成退貨。";
                                        }
                                    }else{
                                        $return['err_code'] = 6;
                                        $return['err_msg'] = "iCarry 訂單不存在，無法完成退貨。";
                                    }
                                }
                            }
                            $acOrder->update(['message' => $return['err_msg']]);
                        }else{
                            $return['err_code'] = 99;
                            $return['err_msg'] = "transaction_id 重複，訂單已存在。";
                        }
                    }else{
                        if($allowance == 1){
                            $return['err_code'] = 99;
                            $return['err_msg'] = "transaction_id 查無訂單存在。";
                        }else{
                            $acOrder = AcOrderDB::create([
                                'serial_no' => $partnerOrderNumber,
                                'get_json' => $getJson,
                                'ip' => $this->loginIp,
                            ]);
                            if(!isset($data['icontent'])){
                                $return['err_code'] = 2;
                                $return['err_msg'] = "icontent 錯誤/不存在。";
                            }else{
                                $icontent = $data['icontent'];
                                //檢查發票資料
                                if(!isset($icontent['print_mark']) || !isset($icontent['buyer_mobile']) || !isset($icontent['buyer_email']) || !isset($icontent['donate_mark']) || !isset($icontent['total_amount']) || !isset($icontent['details'])){
                                    $return['err_code'] = 21;
                                    $return['err_msg'] = "icontent 內容格式錯誤不完整。";
                                }else{
                                    if($icontent['buyer_name'] == '' || $icontent['buyer_mobile'] == ''){
                                        $return['err_code'] = 211;
                                        $return['err_msg'] = "buyer_name、buyer_mobile 不可為空值。";
                                    }else{
                                        if($this->chkMobile($icontent['buyer_mobile']) == false){
                                            $return['err_code'] = 2111;
                                            $return['err_msg'] = "buyer_mobile 格式錯誤。";
                                        }else{
                                            $name = $this->removeEmoji($icontent['buyer_name']);
                                            $receiverName = $buyerName = mb_substr($name,0,30);
                                            if(!empty($icontent['buyer_email']) && $this->chkEmail($icontent['buyer_email']) != false){
                                                $receiverEmail = $buyerEmail = $icontent['buyer_email'];
                                            }
                                            $receiverTel = $this->chkMobile($icontent['buyer_mobile']);
                                            if(!in_array($icontent['print_mark'],['Y','N'])){
                                                $return['err_code'] = 212;
                                                $return['err_msg'] = "print_mark 只能填Y或N。";
                                            }else{
                                                $printFlag = $icontent['print_mark'];
                                                if($icontent['print_mark'] == 'Y'){
                                                    $invoiceType = 3;
                                                    $invoiceSubType = 3;
                                                    $invoiceNumber = $icontent['buyer_identifier'];
                                                    $invoiceTitle = $buyerName;
                                                    $preg = '/^[0-9]{8}$/i';
                                                    if(!preg_match($preg,$invoiceNumber)){
                                                        $return['err_code'] = 2121;
                                                        $return['err_msg'] = "統編只能8個數字。";
                                                    }
                                                }else{
                                                    $invoiceType = 2;
                                                    $invoiceSubType = 2;
                                                    if(isset($icontent['carrier_type'])){
                                                        if(in_array($icontent['carrier_type'],['CQ0001','3J0002','Donate'])){
                                                            if($icontent['carrier_type'] == 'Donate'){ //捐贈
                                                                $preg = '/^[0-9]{3,7}$/i';
                                                                isset($icontent['npoban']) ? $loveCode = $icontent['npoban'] : $loveCode = 86888;
                                                                if(!preg_match($preg,$loveCode)){
                                                                    $return['err_code'] = 2123;
                                                                    $return['err_msg'] = "捐贈碼只能3-7個數字。";
                                                                }
                                                            }elseif($icontent['carrier_type'] == '3J0002' || $icontent['carrier_type'] == 'CQ0001'){ //手機條碼
                                                                if(isset($icontent['carrier_id1'])){
                                                                    if($icontent['carrier_type'] == '3J0002'){ //手機條碼
                                                                        $carrierType = 0;
                                                                        $preg = '/^[0-9A-Z\/\.\-\_]+$/';
                                                                        $carrierNum = $icontent['carrier_id1'];
                                                                        $carrierNum = str_replace(['\\','+',' '],['','_','_'],$carrierNum);
                                                                        if(strlen($carrierNum) != 8 || substr($carrierNum,0,1) != '/' || !preg_match($preg,$carrierNum)){
                                                                            $return['err_code'] = 2124;
                                                                            $return['err_msg'] = "手機條碼必須為8碼，且第一碼必須為/符號且由0-9A-Z與.-+符號組成。";
                                                                        }
                                                                        $carrierNum = str_replace('_','+',$carrierNum);
                                                                    }elseif($icontent['carrier_type'] == 'CQ0001'){ //自然人憑證
                                                                        $carrierType = 1;
                                                                        $preg1 = '/^[A-Z]+$/';
                                                                        $preg2 = '/^[0-9]{14}$/i';
                                                                        $carrierNum = $icontent['carrier_id1'];
                                                                        if(strlen($carrierNum) != 16 || !preg_match($preg1,substr($carrierNum,0,2)) || !preg_match($preg2,substr($carrierNum,2))){
                                                                            $return['err_code'] = 2125;
                                                                            $return['err_msg'] = "自然人憑證號碼必須為16碼，且前兩碼為英文字後14碼為數字。";
                                                                        }
                                                                    }
                                                                }else{
                                                                    $return['err_code'] = 2126;
                                                                    $return['err_msg'] = "carrier_id1 不存在。";
                                                                }
                                                            }
                                                        }else{
                                                            $return['err_code'] = 2122;
                                                            $return['err_msg'] = "carrier_type 格式錯誤。";
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                //檢查商品資料
                                if(empty($return['err_msg']) && $return['err_code'] == 0){
                                    if(!isset($icontent['details'])){
                                        $return['err_code'] = 22;
                                        $return['err_msg'] = "details 商品資料錯誤/不存在。";
                                    }else{
                                        $detials = $icontent['details'][0];
                                        $digiwinNo = $detials['product_num'];
                                        $amount = $detials['amount'];
                                        $productName = $detials['description'];
                                        $product = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                                        ->where($productModelTable.'.digiwin_no',$digiwinNo)
                                        ->select([
                                            $productModelTable.'.gtin13',
                                            $productModelTable.'.sku',
                                            $productModelTable.'.digiwin_no',
                                            $productModelTable.'.id as product_model_id',
                                            $productTable.'.id as product_id',
                                            // $productTable.'.name as product_name',
                                            $productTable.'.unit_name',
                                            $productTable.'.price',
                                            $productTable.'.vendor_price',
                                            $vendorTable.'.id as vendor_id',
                                            $vendorTable.'.name as vendor_name',
                                            $vendorTable.'.digiwin_vendor_no',
                                        ])->first();
                                        if(!empty($product)){
                                            if($amount > 0){
                                                $product->product_name = $productName;
                                                $digiwinPayment = $this->getDigiwinPayment($product->digiwin_no);
                                                $digiWinPaymentId = $digiwinPayment->customer_no;
                                                $payMethod = $createType = $digiwinPayment->create_type;
                                                $payTime = date('Y-m-d H:i:s');
                                                $ctime = strtotime($payTime); //付款時間
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
                                                $orderData = [
                                                    'order_number' => $orderNumber,
                                                    'user_id' => $userId,
                                                    'origin_country' => '台灣',
                                                    'ship_to' => '台灣',
                                                    'book_shipping_date' => date('Y-m-d'),
                                                    'vendor_arrival_date' => date('Y-m-d'),
                                                    'receiver_name' => mb_substr($receiverName,0,30),
                                                    'receiver_tel' => DB::raw("AES_ENCRYPT('$receiverTel', '$key')"),
                                                    'receiver_email' => $receiverEmail,
                                                    'shipping_method' => 6,
                                                    'invoice_type' => $invoiceType,
                                                    'invoice_sub_type' => $invoiceSubType,
                                                    'spend_point' => 0,
                                                    'amount' => $amount,
                                                    'shipping_fee' => 0,
                                                    'parcel_tax' => 0,
                                                    'pay_method' => $payMethod,
                                                    'discount' => 0,
                                                    'partner_order_number' => $partnerOrderNumber,
                                                    'pay_time' => $payTime,
                                                    'buyer_email' => $buyerEmail,
                                                    'print_flag' => $printFlag,
                                                    'create_type' => $createType,
                                                    'status' => 1,
                                                    'digiwin_payment_id' => $digiWinPaymentId,
                                                    'create_time' => $payTime,
                                                    'shipping_kg_price' => 0,
                                                    'shipping_base_price' => 0,
                                                    'love_code' => $loveCode,
                                                    'carrier_num' => $carrierNum,
                                                    'carrier_type' => $carrierType,
                                                    'invoice_title' => $invoiceTitle,
                                                    'invoice_number' => $invoiceNumber,
                                                    'buyer_name' => $buyerName,
                                                ];
                                                $order = OrderDB::create($orderData);
                                                $param['acOrderId'] = $acOrder->id;
                                                $param['orderId'] = $order->id;
                                                $param['orderNumber'] = $order->order_number;
                                                $acOrder->update(['order_id' => $order->id, 'order_number' => $order->order_number, 'amount' => $order->amount]);
                                                $orderItemData = [
                                                    'order_id' => $order->id,
                                                    'product_model_id' => $product->product_model_id,
                                                    'product_name' => $product->product_name,
                                                    'price' => $product->price,
                                                    'purchase_price' => $product->vendor_price,
                                                    'gross_weight' => 0,
                                                    'net_weight' => 0,
                                                    'quantity' => $amount,
                                                    'digiwin_no' => $product->digiwin_no,
                                                    'direct_shipment' => 0,
                                                    'digiwin_payment_id' => $digiWinPaymentId,
                                                    'create_time' => $payTime,
                                                    'vendor_id' => $product->vendor_id,
                                                    'shipping_memo' => '電子郵件',
                                                ];
                                                $orderItem = OrderItemDB::create($orderItemData);
                                                $importNo = time().rand(100,999);
                                                // 入庫資料匯入
                                                $sellImport = SellImportDB::create([
                                                    'import_no' => $importNo,
                                                    'type' => 'warehouse',
                                                    'order_number' => $order->order_number,
                                                    'shipping_number' => $partnerOrderNumber,
                                                    'gtin13' => $product->sku,
                                                    'purchase_no' => null,
                                                    'digiwin_no' => null,
                                                    'product_name' => $product->product_name,
                                                    'quantity' => $amount,
                                                    'sell_date' => date('Y-m-d'),
                                                    'stockin_time' => date('Y-m-d'), //對應廠商到貨日給入庫用
                                                    'status' => 0,
                                                ]);
                                                if(!empty($order) && !empty($orderItem) && !empty($sellImport)){
                                                    $invoiceParam['id'] = $order->id;
                                                    $invoiceParam['type'] = 'create';
                                                    $invoiceParam['model'] = 'acOrderOpenInvoice';
                                                    $result = AdminInvoiceJob::dispatchNow($invoiceParam);
                                                    if(!empty($result) && !empty($result['info'])){
                                                        $pay2goInfo = json_decode($result['info'],true);
                                                        if(!empty($pay2goInfo['Result'])){
                                                            $pay2goResult = json_decode($pay2goInfo['Result'],true);
                                                        }
                                                        $message = $pay2goInfo['Status'].','.$pay2goInfo['Message'];
                                                        if(strtoupper($pay2goInfo['Status']) == 'SUCCESS' && !empty($pay2goResult['InvoiceNumber'])){
                                                            $invoiceNo = $pay2goResult['InvoiceNumber'];
                                                            $invoiceTime = date('Y-m-d H:i:s');
                                                            $return['invoice_number'] = $pay2goResult['InvoiceNumber'];
                                                            $return['rand'] = $pay2goResult['RandomNum'];
                                                            $return['invoice_time_end'] = str_replace([' ','-',':'],['','',''],$pay2goResult['CreateTime']);
                                                            $return['total_amount'] = $amount;
                                                            $return['tax_amount'] = $amount - round($amount/1.05,0);
                                                            $return['sales_amount'] = round($amount/1.05,0);
                                                        }else{
                                                            $return['err_code'] = 3;
                                                            $return['err_msg'] = "訂單建立成功，發票開立失敗。";
                                                        }
                                                    }else{
                                                        $return['err_code'] = 3;
                                                        $return['err_msg'] = "訂單建立成功，發票開立失敗。";
                                                    }
                                                    //背端處理檢查
                                                    $chkQueue = 0;
                                                    $delay = null;
                                                    $minutes = 1;
                                                    $jobName = 'AcOrderProcessJob';
                                                    $countQueue = Redis::llen('queues:default');
                                                    $allQueues = Redis::lrange('queues:default', 0, -1);
                                                    $allDelayQueues = Redis::zrange('queues:default:delayed', 0, -1);
                                                    if(count($allQueues) > 0){
                                                        if(count($allDelayQueues) > 0){
                                                            $allDelayQueues = array_reverse($allDelayQueues);
                                                            for($i=0;$i<count($allDelayQueues);$i++){
                                                                $job = json_decode($allDelayQueues[$i],true);
                                                                if(strstr($job['displayName'],$jobName)){
                                                                    $commandStr = $job['data']['command'];
                                                                    if(strstr($commandStr,'s:26')){
                                                                        $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                                                                        $command = explode('____',$commandStr);
                                                                        $time = $command[1];
                                                                        $delay = Carbon::parse($time)->addminutes($minutes);
                                                                    }else{
                                                                        $delay = Carbon::now()->addminutes($minutes);
                                                                    }
                                                                    $chkQueue++;
                                                                    break;
                                                                }
                                                            }
                                                        }else{
                                                            foreach($allQueues as $queue){
                                                                $job = json_decode($queue,true);
                                                                if(strstr($job['displayName'],$jobName)){
                                                                    $delay = Carbon::now()->addminutes($minutes);
                                                                    $chkQueue++;
                                                                }
                                                            }
                                                        }
                                                    }else{
                                                        $queue = DB::table('jobs')->where('payload','like',"%$jobName%")->orderBy('id','desc')->first();
                                                        if(!empty($queue)){
                                                            $payload = $queue->payload;
                                                            $job = json_decode($payload,true);
                                                            $commandStr = $job['data']['command'];
                                                            if(strstr($commandStr,'s:26')){
                                                                $commandStr = str_replace(['s:26:\"','s:26:"','";s:13','\";s:13'],['____','____','____','____'],$commandStr);
                                                                $command = explode('____',$commandStr);
                                                                $time = $command[1];
                                                                $delay = Carbon::parse($time)->addminutes($minutes);
                                                            }else{
                                                                $delay = Carbon::now()->addminutes($minutes);
                                                            }
                                                            $chkQueue++;
                                                        }
                                                    }
                                                    if($chkQueue > 0){
                                                        !empty($delay) ? AcOrderProcessJob::dispatch($param)->delay($delay) : AcOrderProcessJob::dispatch($param);
                                                    }else{
                                                        AcOrderProcessJob::dispatch($param);
                                                    }
                                                }else{
                                                    $return['err_code'] = 9;
                                                    $return['err_msg'] = "iCarry 訂單建立失敗。";
                                                }
                                            }else{
                                                $return['err_code'] = 222;
                                                $return['err_msg'] = "amount 金額不可小於等於0。";
                                            }
                                        }else{
                                            $return['err_code'] = 221;
                                            $return['err_msg'] = "product_num 商品資料錯誤/不存在。";
                                        }
                                    }
                                }
                            }
                            $acOrder->update(['message' => $return['err_msg']]);
                        }
                    }
                }
            }else{
                $return['err_msg'] = "No Data Input。";
            }
        }else{
            $return['err_code'] = 9999;
            $return['err_msg'] = "非允許IP連接。";
        }
        return response()->json($return);
    }

    private function getDigiwinPayment($digiwinNo)
    {
        switch ($digiwinNo) {
            case '5TWA007170001':
                $customerNo = 'AC000101';
                break;
            case '5TWA007170002':
                $customerNo = 'AC000102';
                break;
            case '5TWA007170003':
                $customerNo = 'AC000103';
                break;

            default:
                $customerNo = null;
                break;
        }
        $digiwinPayment = DigiwinPaymentDB::where('customer_no',$customerNo)->first();
        return $digiwinPayment;
    }
}
