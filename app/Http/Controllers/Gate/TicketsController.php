<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Admin as AdminDB;
use App\Models\iCarryUser as UserDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryTicket as TicketDB;
use App\Models\AdminKeypassLog as AdminKeypassLogDB;
use App\Models\AdminLoginLog as AdminLoginLogDB;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as productDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\SellImport as SellImportDB;
use App\Jobs\AdminInvoiceJob;

use Session;
use Hash;
use DB;

use App\Jobs\TicketFileImportJob;
use App\Jobs\SellImportJob;
use App\Jobs\AdminExportJob;
use App\Jobs\TicketSettleJob;

use App\Traits\TicketFunctionTrait;
use App\Traits\ACpayTicketFunctionTrait;

class TicketsController extends Controller
{
    use TicketFunctionTrait,ACpayTicketFunctionTrait;

    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate', ['except' => ['notify','openTicketFromICARRY']]);
        $this->key = env('TICKET_ENCRYPT_KEY');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $menuCode = 'M27S4';
        $appends = [];
        $compact = [];
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();

        //將進來的資料作參數轉換及附加到appends及compact中
        foreach (request()->all() as $key => $value) {
            $$key = $value;
            if (isset($$key)) {
                $appends = array_merge($appends, [$key => $value]);
                $compact = array_merge($compact, [$key]);
            }
        }
        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }
        $tickets = $this->getTicketData(request(),'index');
        $createTypes = DigiwinPaymentDB::whereNotNull('create_type')
        // ->where('create_type','!=','web')
        ->select([
            'create_type',
            DB::raw("(CASE WHEN customer_name = '智付通信用卡' THEN 'iCarry Web' ELSE customer_name END) as name"),
        ])->groupBy('create_type')->orderBy('customer_no','asc')->get();
        $products = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->where($productTable.'.category_id',17)->select([
            $productModelTable.'.digiwin_no',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
        ])->get();
        $compact = array_merge($compact, ['menuCode','tickets','appends','createTypes','products']);
        return view('gate.tickets.index', compact($compact));
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
        $ticket = TicketDB::select([
            'id',
            'platform_no',
            DB::raw("IF(ticket_no IS NULL,'',AES_DECRYPT(ticket_no,'$this->key')) as ticket_no"),
        ])->findOrFail($id);
        $result = $this->invalidTicket($ticket->ticket_no,$ticket->platform_no);
        if($result['rtnMsg'] == '成功'){
            $ticket->update(['status' => -1]);
            Session::put('success','票券作廢成功。');
        }else{
            Session::put('error','票券作廢失敗。');
        }
        return redirect()->back();
    }

    public function import(Request $request)
    {
        // $request->request->add(['test' => true]); //測試用, 測試完關閉
        $request->request->add(['admin_id' => auth('gate')->user()->id]); //加入request
        $request->request->add(['import_no' => time()]); //加入request
        if ($request->hasFile('filename')) {
            $file = $request->file('filename');
            $uploadedFileMimeType = $file->getMimeType();
            $excelMimes = ['application/octet-stream','application/excel','application/vnd.ms-excel','application/vnd.msexcel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            if(!in_array($uploadedFileMimeType, $excelMimes)){
                $message = "檔案格式錯誤，$request->type 只接受 Excel 檔案格式。";
                Session::put('error', $message);
                return redirect()->back();
            }else{
                $results = TicketFileImportJob::dispatchNow($request); //直接馬上處理
                if(!empty($results['error']) ){
                    $message = $results['error'];
                    Session::put('error', $message);
                }else{
                    $fail = $results['fail'];
                    $success = $results['success'];
                    $total = $fail + $success;
                    if($fail > 0){
                        $message = $request->type." 共匯入 $total 筆，成功匯入 $success 筆，$fail 筆資料異常。";
                        Session::put('error', $message);
                        Session::put('importErrors', $results['failData']);
                    }else{
                        $message = $request->type." 共匯入 $total 筆，成功匯入 $success 筆。";
                        Session::put('success', $message);
                    }
                }
            }
        }
        return redirect()->back();
    }

    public function getInfo(Request $request)
    {
        $adminId = auth('gate')->user()->id;
        $adminUser = AdminDB::find($adminId);
        $data = [];
        $logMemo = $KeypassMemo = $data['message'] = $data['order'] = $data['count'] = null;
        if(!empty($request->pwd)){
            if(Hash::check($request->pwd, env('GET_INFO_PWD'))){
                $adminUser->update(['lock_on' => 0]);
                $ticket = $this->getTicketData($request,'getInfo');
                $isPass = 1;
                if(!empty($ticket)){
                    $data['ticket_no'] = $ticket->ticket_no;
                    $KeypassMemo = "票券號碼查詢 id = $ticket->id , no. = ($ticket->ticket_no_mask) 查詢成功。";
                }else{
                    $KeypassMemo = $data['message'] = '查無票券資料';
                }
            }else{
                $isPass = 0;
                $adminUser->increment('lock_on');
                $data['count'] = $adminUser->lock_on;
                $KeypassMemo = $data['message'] = '票券查詢密碼輸入錯誤！還剩 '.(3 - $adminUser->lock_on).' 次機會';
                $adminUser->lock_on >= 3 ? $KeypassMemo = $data['message'] = '密碼輸入錯誤三次，帳號已被鎖定！請聯繫管理員。' : '';
                if($adminUser->lock_on >= 3){
                    $logMemo = '票券查詢密碼輸入錯誤 3 次，帳號鎖定。';
                    auth('gate')->logout();
                }else{
                    $logMemo = "票券查詢密碼輸入錯誤 $adminUser->lock_on 次。";
                }
            }
        }
        AdminKeypassLogDB::create([
            'type' => '票券查詢',
            'is_pass' => $isPass,
            'memo' => $KeypassMemo,
            'admin_id' => $adminUser->id,
            'admin_name' => $adminUser->name,
        ]);
        if(!empty($logMemo)){
            AdminLoginLogDB::create([
                'admin_id' => $adminUser->id,
                'result' => $logMemo,
                'ip' => $request->ip(),
            ]);
        }
        return response()->json($data);
    }

    public function open(Request $request)
    {
        $error = $msg = null;
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        if(isset($request->open) && count($request->open) > 0){
            $open = $request->open;
            sort($open);
            for($i=0;$i<count($open);$i++){
                $createType = $open[$i]['create_type'];
                $digiwinNo = $open[$i]['digiwin_no'];
                $quantity = $open[$i]['quantity'];
                $product = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                ->where($productTable.'.category_id',17)
                ->where($productModelTable.'.digiwin_no',$digiwinNo)
                ->select([
                    $productTable.'.id',
                    $productTable.'.ticket_group',
                    $productTable.'.ticket_merchant_no',
                    $productTable.'.ticket_price',
                    $productTable.'.ticket_memo',
                    $productTable.'.vendor_id',
                    $productTable.'.name',
                    $productModelTable.'.id as product_model_id',
                    $productModelTable.'.sku',
                    $productModelTable.'.digiwin_no',
                    $vendorTable.'.name as vendor_name',
                ])->first();
                if(!empty($product)){
                    $expStartDate = date('Ymd');
                    $expEndDate = date('Ymd')+1000000;
                    !empty($product->ticket_merchant_no) ? $merchantNo = $product->ticket_merchant_no : $merchantNo = '';
                    !empty($product->ticket_group) ? $group = $product->ticket_group : $group = '';
                    $productName = $product->name;
                    empty($product->ticket_memo) ? $itemMemo = '' : $itemMemo = $product->ticket_memo;
                    $price = $product->ticket_price;
                    $issuedType = env('ACPAY_TICKET_ISSUE_TYPE');
                    !empty(env('ACPAY_TICKET_ISSUE_ID')) ? $issuerId = env('ACPAY_TICKET_ISSUE_ID') : $issuerId = '';
                    $items[0] = [
                        'merchantNo' => $merchantNo,
                        'group' => $group,
                        'issuedType' => $issuedType,
                        'issuerId' => "$issuerId",
                        'count' => (INT)$quantity,
                        'expStartDate' => $expStartDate,
                        'expEndDate' => "$expEndDate",
                        'itemName' => mb_substr($productName,0,78),
                        'itemNo' => $digiwinNo,
                        'itemAmount' => (INT)$price,
                        'itemMemo' => $itemMemo,
                    ];
                    ksort($items[0]);
                    $result = $this->openTicket($createType,$items);
                    if(!empty($result) && $result['rtnCode'] == 0){
                        if(count($result['items']) > 0){
                            $ticketNos = $result['items'][0]['ticketNos'];
                            $platformNo = $result['platformNo'];
                            $tickOrderNo = $result['orderNo'];
                            $ticketData = [];
                            for($t=0;$t<count($ticketNos);$t++){
                                $ticketNo = $ticketNos[$t];
                                $ticket = TicketDB::whereRaw(" ticket_no = AES_ENCRYPT('$ticketNo', '$this->key') ")->first();
                                if(empty($ticket)){
                                    $ticketData[] = [
                                        'create_type' => $createType,
                                        'ticket_no' => DB::raw("AES_ENCRYPT('$ticketNo', '$this->key')"),
                                        'ticket_order_no' => $tickOrderNo,
                                        'platform_no' => $platformNo,
                                        'vendor_id' => $product->vendor_id,
                                        'vendor_name' => $product->vendor_name,
                                        'product_id' => $product->id,
                                        'product_name' => mb_substr($product->name,0,250),
                                        'product_model_id' => $product->product_model_id,
                                        'digiwin_no' => $product->digiwin_no,
                                        'status' => 0,
                                        'sku' => $product->sku,
                                        'created_at' => date('Y-m-d H:i:s'),
                                    ];
                                }
                            }
                            if(count($ticketData) > 0){
                                TicketDB::insert($ticketData);
                            }
                            $count = count($ticketData);
                            $msg .= "$createType - $product->name - 共開立 $count 張。 <br>";
                        }
                    }else{
                        $errorMsg = $result['rtnMsg'];
                        $error .= "$createType - $product->name - 開票失敗。<br>$errorMsg";
                    }
                }
            }
        }
        !empty($msg) ? Session::put('success',$msg) : '';
        !empty($error) ? Session::put('error',$error) : '';

        return redirect()->back();
    }

    public function notify(Request $request)
    {
        return response($this->ticketNotify(), 200)->header('Content-Type', 'text/plain');
    }

    public function settle(Request $request)
    {
        if(!empty($request->id)){
            $ticket = TicketDB::where('status',9)->whereNotNull('order_number')->find($request->id);
            if(!empty($ticket)){
                $orderId = $ticket->order_id;
                $tickets = TicketDB::where('order_id',$ticket->order_id)
                ->where('status',9)
                ->select([
                    '*',
                    DB::raw("IF(ticket_no IS NULL,'',AES_DECRYPT(ticket_no,'$this->key')) as ticket_no"),
                    DB::raw("DATE_FORMAT(updated_at,'%Y-%m-%d') as sell_date"),
                ])->get();
                if(count($tickets) > 0){
                    $ticketSettles = [];
                    $importNo = time().rand(0,99);
                    foreach($tickets as $ticket){
                        $ticketSettles[] = [
                            'platformNo' => $ticket->platform_no,
                            'ticketNo' => $ticket->ticket_no,
                        ];
                    }
                    if(count($ticketSettles) > 0){
                        $result = $this->ticketSettle($ticketSettles);
                        if(strstr($result['rtnMsg'],'此票券已經結算過了') || ($result['rtnCode'] == 0 && count($result['tickets']) > 0)){
                            $cleanTicketNos = $cleanTicketIds = [];
                            if(strstr($result['rtnMsg'],'此票券已經結算過了')){
                                for($i=0;$i<count($ticketSettles);$i++){
                                    $tNo = $ticketSettles[$i]['ticketNo'];
                                    if(strstr($result['rtnMsg'],$tNo)){
                                        $cleanTicket = TicketDB::whereRaw(" ticket_no = AES_ENCRYPT('$tNo', '$this->key') ")->select(['*',DB::raw("IF(ticket_no IS NULL,'',AES_DECRYPT(ticket_no,'$this->key')) as ticket_no")])->first();
                                        if(!empty($cleanTicket)){
                                            $cleanTicketIds[] = $cleanTicket->id;
                                            $cleanTicketNos[] = $cleanTicket->ticket_no;
                                            $cleanTicket->update(['status' => 2]);
                                        }
                                    }
                                }
                            }else{
                                $cleanTickets = $result['tickets'];
                                for($i=0;$i<count($cleanTickets);$i++){
                                    if($cleanTickets[$i]['status'] == 1){
                                        $cleanTicketNo = $cleanTickets[$i]['ticketNo'];
                                        $cleanTicket = TicketDB::whereRaw(" ticket_no = AES_ENCRYPT('$cleanTicketNo', '$this->key') ")->select(['*',DB::raw("IF(ticket_no IS NULL,'',AES_DECRYPT(ticket_no,'$this->key')) as ticket_no")])->first();
                                        if(!empty($cleanTicket)){
                                            $cleanTicketIds[] = $cleanTicket->id;
                                            $cleanTicketNos[] = $cleanTicket->ticket_no;
                                            $cleanTicket->update(['status' => 2]);
                                        }
                                   }
                                }
                            }
                            $message = null;
                            if(count($cleanTicketIds) > 0){
                                $message .= '票號: '.join(',',$cleanTicketNos)." 結算完成。";
                                $getTickets = TicketDB::whereIn('id',$cleanTicketIds)
                                ->select([
                                    '*',
                                    DB::raw("DATE_FORMAT(updated_at,'%Y-%m-%d') as sell_date"),
                                ])->get();
                                if(count($getTickets) > 0){
                                    $importNo = time().rand(0,99);
                                    foreach($getTickets as $tt){
                                        $sellImports[] = [
                                            'import_no' => $importNo,
                                            'order_number' => $tt->order_number,
                                            'product_name' => $tt->product_name,
                                            'quantity' => 1,
                                            'type' => 'warehouse',
                                            'shipping_number' => '電子郵件',
                                            'gtin13' => $tt->sku,
                                            'sell_date' => $tt->sell_date,
                                            'status' => 0,
                                            'created_at' => date('Y-m-d H:i:s'),
                                        ];
                                    }
                                    if(count($sellImports) > 0){
                                        SellImportDB::insert($sellImports);
                                        $job['type'] = 'warehouse';
                                        $job['import_no'] = $importNo;
                                        $job['admin_id'] = auth('gate')->user()->id;
                                        $result = SellImportJob::dispatchNow($job);
                                        $message .= "銷貨處理完成。";
                                    }
                                }
                                !empty($message) ? Session::put('success',$message) : '';
                            }else{
                                Session::put('error','返回資訊中無結算資料。');
                            }
                        }else{
                            Session::put('error',nl2br($result['rtnMsg']));
                        }
                    }
                }
            }else{
                Session::put('error',"查無可結算票券。");
            }

        }
        return redirect()->back();
    }

    public function multiProcess(Request $request)
    {
        //將進來的資料作參數轉換及附加到$param中
        foreach ($request->all() as $key => $value) {
            $param[$key] = $value;
        }
        $method = null;
        $url = 'https://'.env('GATE_DOMAIN').'/exportCenter';
        $param['admin_id'] = auth('gate')->user()->id;
        $param['admin_name'] = auth('gate')->user()->name;
        if(!empty($param['method'])){
            $param['method'] == 'selected' ? $method = '自行勾選' : '';
            $param['method'] == 'allOnPage' ? $method = '目前頁面全選' : '';
            $param['method'] == 'byQuery' ? $method = '依查詢條件' : '';
            $param['method'] == 'allData' ? $method = '全部資料' : '';
        }
        !empty($method) ? $param['name'] = $param['filename'].'_'.$method : $param['name'] = $param['filename'];
        $param['export_no'] = time();
        $param['start_time'] = date('Y-m-d H:i:s');
        $param['cate'] == 'pdf' || $param['type'] == 'pdf' ? $param['filename'] = $param['name'].'_'.time().'.pdf' : $param['filename'] = $param['name'].'_'.time().'.xlsx';
        $message = $param['name'].'，工作單號：'.$param['export_no'].'<br>匯出已於背端執行，請過一段時間至匯出中心下載，<br>檔案名稱：'.$param['filename'].'<br>匯出中心連結：<a href="'.$url.'" target="_blank"><span class="text-danger">'.$url.'</span></a>';
        if($param['cate'] == 'Export'){ //匯出採購單
            if(env('APP_ENV') == 'local'){
                //本機測試用
                return AdminExportJob::dispatchNow($param); //直接馬上下載則必須使用 return
            }else{
                //放入隊列
                $param['store'] = true;
                AdminExportJob::dispatch($param);
            }
        }else{
            $message = $param['name'].'，工作單號：'.$param['export_no'].'<br>已於背景執行中，請稍後一段時間查詢。';
            env('APP_ENV') == 'local' ? TicketSettleJob::dispatchNow($param) : TicketSettleJob::dispatch($param);
        }
        Session::put('info', $message);
        return redirect()->back();
    }

    public function openTicketFromICARRY(Request $request)
    {
        $key = env('APP_AESENCRYPT_KEY');
        $tickKey = env('TICKET_ENCRYPT_KEY');
        $message = null;
        $status = 'error';
        if(!empty($request->chk)){
            if(Hash::check(env('OPEN_TICKET_FROM_ICARRY'), $request->chk)){
                $order = OrderDB::with('itemData')
                ->whereNotNull('pay_time')
                ->whereIn('status',[1,2])
                ->select([
                    'orders.id',
                    'orders.order_number',
                    'orders.receiver_name',
                    'orders.receiver_email',
                    'orders.create_type',
                    'orders.user_id',
                    DB::raw("IF(orders.receiver_tel IS NULL,'',AES_DECRYPT(orders.receiver_tel,'$key')) as receiver_tel"),
                ]);
                if(!empty($request->orderId) && is_numeric($request->orderId)){
                    $order = $order->find($request->orderId);
                }elseif(!empty($request->orderNumber)){
                    $order = $order->where('order_number',$request->orderNumber)->first();
                }else{
                    $order = null;
                }
                if(!empty($order)){
                    if(count($order->itemData) > 0){
                        $openTickets = [];
                        $issuedType = env('ACPAY_TICKET_ISSUE_TYPE');
                        !empty(env('ACPAY_TICKET_ISSUE_ID')) ? $issuerId = env('ACPAY_TICKET_ISSUE_ID') : $issuerId = '';
                        $expStartDate = date('Ymd');
                        $expEndDate = date('Ymd')+1000000;
                        $createType = $order->create_type;
                        $i=0;
                        $errorMsg = null;
                        foreach($order->itemData as $item){
                            if($item->product_category_id == 17){
                                $chkTicket = TicketDB::where([
                                    ['order_number',$order->order_number],
                                    ['digiwin_no',$item->digiwin_no],
                                    ['order_item_id',$item->id],
                                    ['status','!=',-1],
                                ])->count();
                                if($item->quantity != $chkTicket){
                                    $quantity = $item->quantity - $chkTicket;
                                    !empty($item->ticket_merchant_no) ? $merchantNo = $item->ticket_merchant_no : $merchantNo = '';
                                    !empty($item->ticket_group) ? $group = $item->ticket_group : $group = '';
                                    $price = $item->ticket_price;
                                    $productName = $item->name;
                                    $digiwinNo = $item->digiwin_no;
                                    empty($item->ticket_memo) ? $itemMemo = '' : $itemMemo = $item->ticket_memo;
                                    $items[0] = [
                                        'merchantNo' => $merchantNo,
                                        'group' => $group,
                                        'issuedType' => "$issuedType",
                                        'issuerId' => "$issuerId",
                                        'count' => (INT)$quantity,
                                        'expStartDate' => $expStartDate,
                                        'expEndDate' => "$expEndDate",
                                        'itemName' => mb_substr($productName,0,78),
                                        'itemNo' => $digiwinNo,
                                        'itemAmount' => (INT)$price,
                                        'itemMemo' => $itemMemo,
                                    ];
                                    ksort($items[0]);
                                    $result = $this->openTicket($createType,$items,$order);
                                    if(!empty($result)){
                                        if($result['rtnCode'] == 0){
                                            if(count($result['items']) > 0){
                                                $ticketNos = $result['items'][0]['ticketNos'];
                                                $platformNo = $result['platformNo'];
                                                $tickOrderNo = $result['orderNo'];
                                                $ticketData = [];
                                                for($t=0;$t<count($ticketNos);$t++){
                                                    $ticketNo = $ticketNos[$t];
                                                    $ticket = TicketDB::whereRaw(" ticket_no = AES_ENCRYPT('$ticketNo', '$tickKey') ")->first();
                                                    if(empty($ticket)){
                                                        $ticketData[] = [
                                                            'create_type' => $createType,
                                                            'ticket_no' => DB::raw("AES_ENCRYPT('$ticketNo', '$tickKey')"),
                                                            'ticket_order_no' => $tickOrderNo,
                                                            'platform_no' => $platformNo,
                                                            'vendor_id' => $item->vendor_id,
                                                            'vendor_name' => $item->vendor_name,
                                                            'product_id' => $item->id,
                                                            'product_name' => mb_substr($item->name,0,250),
                                                            'product_model_id' => $item->product_model_id,
                                                            'digiwin_no' => $item->digiwin_no,
                                                            'order_id' => $order->id,
                                                            'order_number' => $order->order_number,
                                                            'order_item_id' => $item->id,
                                                            'status' => 1,
                                                            'sku' => $item->sku,
                                                            'created_at' => date('Y-m-d H:i:s'),
                                                        ];
                                                    }
                                                }
                                                if(count($ticketData) > 0){
                                                    TicketDB::insert($ticketData);
                                                    $i++;
                                                }
                                            }
                                        }else{
                                            $errorMsg .= $result['rtnMsg'];
                                        }
                                    }
                                }
                            }
                        }
                        if($i > 0 && $errorMsg == null){
                            $message = '開票全部完成!';
                            $param['id'] = $order->id;
                            $param['model'] = 'ticketOrderOpenInvoice';
                            $param['type'] = 'create';
                            $param['return'] = true;
                            //開發票
                            $result = AdminInvoiceJob::dispatchNow($param);
                            if($result == 'success'){
                                $status = 'success';
                                $message .= ' 發票開立完成!';
                            }else{
                                $message .= '發票開立失敗!';
                            }
                        }elseif($i > 0 && $errorMsg != null){
                            $message = "開票部分完成!\n $errorMsg";
                        }elseif($i == 0 && $errorMsg != null){
                            $message = "開票全部失敗!\n $errorMsg";
                        }elseif($i == 0){
                            $message = "未開立任何票券!";
                        }
                    }else{
                        $message = "訂單商品資料不存在!";
                    }
                }else{
                    $message = "找不到訂單 或者 訂單狀態非已付款待出貨!";
                }
            }else{
                $message = "檢驗碼錯誤!";
            }
        }else{
            $message = "檢驗碼不存在!";
        }
        if((isset($request->site) && $request->site == 'gate')){
            Session::put($status,$message);
            return redirect()->back();
        }else{
            return response($message)->header('Content-Type', 'text/plain');
        }
    }

    public function resend(Request $request)
    {
        if(!empty($request->id)){
            $ticket = $this->getTicketData($request,'show');
            if(!empty($ticket)){
                if(!empty($ticket->order) && $ticket->order->create_type == 'web'){
                    $result = $this->resendTicket($ticket);
                    if(!empty($result) && $result['rtnCode'] == 0){
                        return response($result['rtnMsg'])->header('Content-Type', 'text/plain');
                    }else{
                        return response("發送失敗： ".$result['rtnMsg'])->header('Content-Type', 'text/plain');
                    }
                }
            }
        }
        return response(null)->header('Content-Type', 'text/plain');
    }
}
