<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\iCarryGroupBuyingOrder as OrderDB;
use App\Models\iCarryGroupBuyingOrderItem as OrderItemDB;
use App\Models\iCarryOrder as iCarryOrderDB;
use App\Models\iCarryOrderItem as iCarryOrderItemDB;
use App\Models\iCarryGroupBuyingOrderLog as OrderLogDB;
use App\Models\Admin as AdminDB;
use App\Models\AdminLoginLog as AdminLoginLogDB;
use App\Models\AdminKeypassLog as AdminKeypassLogDB;
use Session;
use Hash;
use DB;

use App\Jobs\AdminExportJob;
use App\Jobs\AdminInvoiceJob;
use App\Jobs\GroupBuyOrderCancelJob;
use App\Jobs\GroupBuySellAllowanceJob;
use App\Jobs\GroupBuyOrderRefundMailJob;
use App\Jobs\SellAllowanceJob;

use App\Traits\GroupBuyingOrderFunctionTrait;

class GroupBuyingOrdersController extends Controller
{
    use GroupBuyingOrderFunctionTrait;

    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate');
        //改走cloudflare需抓x-forwareded-for
        if(!empty(request()->header('x-forwarded-for'))){
            $this->loginIp = request()->header('x-forwarded-for');
        }else{
            $this->loginIp = request()->ip();
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $menuCode = 'M27S8';
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

        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }

        $orders = $this->getGroupBuyingOrderData(request(),'index');

        foreach($orders as $order){
            $order->totalWeight = $order->totalQty = 0;
            $order->totalPrice = $order->amount;
            foreach($order->itemData as $item){
                if(strstr($item->sku,'BOM')){
                    foreach($item->packageData as $package){
                        $order->totalQty += $package->quantity;
                    }
                }else{
                    $order->totalQty += $item->quantity;
                }
                $order->totalWeight += $item->quantity * $item->gross_weight;
            }
        }

        $compact = array_merge($compact, ['menuCode','orders','appends']);
        return view('gate.groupbuyings.index', compact($compact));
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
        $menuCode = 'M27S8';
        $compact = [];
        $myId['id'] = $id;
        $order = $this->getGroupBuyingOrderData($myId,'show');
        $order->totalWeight = $order->totalQty = 0;
        $order->totalPrice = $order->amount;
        foreach($order->itemData as $item){
            if(strstr($item->sku,'BOM')){
                foreach($item->packageData as $package){
                    $order->totalQty += $package->quantity;
                }
            }else{
                $order->totalQty += $item->quantity;
            }
            $order->totalWeight += $item->quantity * $item->gross_weight;
        }
        $compact = array_merge($compact, ['menuCode','order']);
        return view('gate.groupbuyings.show', compact($compact));
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
        $result = null;
        $data = $request->all();
        $myId['id'] = $id;
        $order = $this->getGroupBuyingOrderData($myId,'show');
        $iCarryOrder = iCarryOrderDB::with('itemData')->where('order_number',$order->partner_order_number)->first();
        $oldStatus = $order->status; //更新前取得舊狀態
        if(isset($request->invoice_sub_type)){
            if($data['invoice_sub_type'] == 1) { //捐贈
                $data['carrier_type'] = $data['carrier_num'] = null;
            }elseif($data['invoice_sub_type'] == 3) { //公司戶
                $data['carrier_type'] = $data['carrier_num'] = null;
            }else{
                $data['carrier_type'] == null ? $data['carrier_num'] = null : '';
            }
        }
        if(isset($data['invoice_sub_type']) && $data['invoice_sub_type'] != 1){
            $data['love_code'] = null;
        }
        if(!empty($data['receiver_tel'])){
            $key = env('APP_AESENCRYPT_KEY');
            $receiverTel = $data['receiver_tel'];
            $data['receiver_tel'] = DB::raw("AES_ENCRYPT('$receiverTel', '$key')");
        }
        //修改數量
        if((isset($data['itemQty']) && $data['itemQty'] == 1) || (isset($data['status']) && $oldStatus != -1 && $data['status'] == -1 && !empty($order->partner_order_number))){
            $result = GroupBuyOrderCancelJob::dispatchNow($order,$data);
        }
        //折讓處理
        if(isset($data['allowance']) && $data['allowance'] == 1 && $order->status >= 3){
            $newData = $data;
            unset($newData['allowanceMemo']);
            unset($newData['items']);
            $newData['allowanceMemo'] = $data['allowanceMemo']."(團購 $order->order_number 折讓)";
            $items = $data['items'];
            for($i=0;$i<count($items);$i++){
                if($items[$i]['price'] > 0){
                    $groupBuyItem = OrderItemDB::find($items[$i]['id']);
                    if(!empty($groupBuyItem)){
                        $newData['items'][$i]['price'] = $items[$i]['price'];
                        foreach($iCarryOrder->itemData as $item){
                            if($item->product_model_id == $groupBuyItem->product_model_id){
                                $newData['items'][$i]['id'] = $item->id;
                                break;
                            }
                        }
                    }
                }
            }
            if(count($newData['items']) > 0){
                $sellReturn = SellAllowanceJob::dispatchNow($iCarryOrder,$newData);
                if(!empty($sellReturn)) {
                    $total = $sellReturn->price+$sellReturn->tax;
                    $data['admin_memo'] = $order->admin_memo." (團購折讓$total)";
                }
            }
        }

        if(!empty($result)){
            Session::put('error', $result);
            return redirect()->back();
        }

        //更新訂單資料
        if(isset($data['admin_memo']) && ($order->admin_memo != $data['admin_memo'])){ //管理者備註修改需做紀錄
            $orderLog = OrderLogDB::create([
                'order_id' => $order->id,
                'column_name' => 'admin_memo',
                'log' => $data['admin_memo'],
                'editor' => auth('gate')->user()->id,
            ]);
        }
        $order->update($data);
        return redirect()->back();
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

    public function getInfo(Request $request)
    {
        $adminId = auth('gate')->user()->id;
        $adminUser = AdminDB::find($adminId);
        $data = [];
        $logMemo = $KeypassMemo = $data['message'] = $data['order'] = $data['count'] = null;
        if(!empty($request->pwd)){
            if(Hash::check($request->pwd, env('GET_INFO_PWD'))){
                $key = env('APP_AESENCRYPT_KEY');
                $adminUser->update(['lock_on' => 0]);
                $order = $this->getGroupBuyingOrderData($request,'getInfo');
                $isPass = 1;
                if(!empty($order)){
                    $order->user_name = null;
                    $order->user_email = null;
                    $order->user_tel = null;
                    $data['order'] = $order;
                    $KeypassMemo = "查詢訂單資訊 ($order->order_number) 成功。";
                }else{
                    $KeypassMemo = $data['message'] = '查無訂單資料';
                }
            }else{
                $isPass = 0;
                $adminUser->increment('lock_on');
                $data['count'] = $adminUser->lock_on;
                $KeypassMemo = $data['message'] = '訂單資訊密碼輸入錯誤！還剩 '.(3 - $adminUser->lock_on).' 次機會';
                $adminUser->lock_on >= 3 ? $KeypassMemo = $data['message'] = '密碼輸入錯誤三次，帳號已被鎖定！請聯繫管理員。' : '';
                if($adminUser->lock_on >= 3){
                    $logMemo = '訂單資訊密碼輸入錯誤 3 次，帳號鎖定。';
                    auth('gate')->logout();
                }else{
                    $logMemo = "訂單資訊密碼輸入錯誤 $adminUser->lock_on 次。";
                }
            }
        }
        AdminKeypassLogDB::create([
            'type' => '訂單查詢',
            'is_pass' => $isPass,
            'memo' => $KeypassMemo,
            'admin_id' => $adminUser->id,
            'admin_name' => $adminUser->name,
        ]);
        if(!empty($logMemo)){
            AdminLoginLogDB::create([
                'admin_id' => $adminUser->id,
                'result' => $logMemo,
                'ip' => $this->loginIp,
            ]);
        }
        return response()->json($data);
    }

    public function modify(Request $request)
    {
        $orders = $this->getGroupBuyingOrderData($request,'modify');
        if(!empty($orders)){
            foreach($orders as $order){
                $request->column_data == null || $request->column_data == '' ? $request->column_data = '清除註記' : '';
                $request->column_name == 'cancel' ? $request->column_data = $request->column_data.'(後台取消訂單)' : '';
                $request->column_name == 'cancel' ? $request->column_name = 'admin_memo' : '';
                if($request->column_data == '清除註記'){
                    $order->update([ $request->column_name => null]);
                }
                $orderLog = OrderLogDB::create([
                    'order_id' => $order->id,
                    'column_name' => $request->column_name,
                    'log' => $request->column_data,
                    'editor' => auth('gate')->user()->id,
                ]);
            }
        }

        if(!empty($request->id) && is_array($request->id)){
            return response()->json($orders);
        }
        if(!empty($request->con) && is_array($request->con)){
            return redirect()->back();
        }
        return null;
    }

    public function getLog(Request $request)
    {
        $adminTable = env('DB_DATABASE').'.'.(new AdminDB)->getTable();
        $orderLogTable = env('DB_ICARRY').'.'.(new OrderLogDB)->getTable();
        $orderLogs = OrderLogDB::join($adminTable, $adminTable.'.id', $orderLogTable.'.editor');
        !empty($request->order_item_id) ? $orderLogs = $orderLogs->where($orderLogTable.'.order_item_id', $request->order_item_id) : '';
        !empty($request->order_id) ? $orderLogs = $orderLogs->where($orderLogTable.'.order_id', $request->order_id) : '';
        !empty($request->column_name) ? $orderLogs = $orderLogs->where($orderLogTable.'.column_name',$request->column_name) :'';
        $orderLogs = $orderLogs->select([
            $adminTable.'.name',
            $orderLogTable.'.column_name',
            $orderLogTable.'.log',
            DB::raw("DATE_FORMAT($orderLogTable.create_time,'%Y-%m-%d %H:%i:%s') as created_at"),
        ])->orderBy($orderLogTable.'.create_time', 'desc')->get();
        return response()->json($orderLogs);
    }

    public function multiProcess(Request $request)
    {
        //將進來的資料作參數轉換及附加到$param中
        foreach ($request->all() as $key => $value) {
            $param[$key] = $value;
        }
        $method = null;
        $url = 'https://' . env('GATE_DOMAIN') . '/exportCenter';
        $param['admin_id'] = auth()->user()->id;
        $param['admin_name'] = auth()->user()->name;
        $param['export_no'] = time();
        if(!empty($param['method'])) {
            $param['method'] == 'OneOrder' ? $method = '單一訂單' : '';
            $param['method'] == 'selected' ? $method = '自行勾選' : '';
            $param['method'] == 'allOnPage' ? $method = '目前頁面全選' : '';
            $param['method'] == 'byQuery' ? $method = '依查詢條件' : '';
            $param['method'] == 'allData' ? $method = '全部資料' : '';
        }
        !empty($method) ? (!empty($param['filename']) ? $param['name'] = $param['filename'].'_'.$method : $param['name'] = $param['filename']) : '';
        $param['cate'] == 'export' ? $param['filename'] = $param['name'].'_'.$param['export_no'].'.xlsx' : '';
        if($param['cate'] == 'export'){ //匯出
            if($param['type'] == 'OrderDetail') {
                Session::put('error', "功能尚未完成，待提供匯出表格格式。");
                return redirect()->back();
            }
            if(env('APP_ENV') == 'local'){
                $param['store'] = false;
                return AdminExportJob::dispatchNow($param); //直接馬上下載則必須使用 return
            }else{
                $param['store'] = true;
                $message = $param['name'].'，工作單號：'.$param['export_no'].'<br>匯出已於背端執行，請過一段時間重新整理頁面或至匯出中心下載，<br>檔案名稱：'.$param['filename'].'<br>匯出中心連結：<a href="'.$url.'" target="_blank"><span class="text-danger">'.$url.'</span></a>';
                AdminExportJob::dispatch($param);
            }
        }elseif($param['cate'] == 'invoice'){ //發票處理
            if($param['method'] == 'byQuery') {
                $message = '執行編號：' . $param['export_no'] . '<br>' . $param['name'] . '，依條件查詢資料量較大，改由於背端執行，請稍後一段時間，再重新查詢或按F5重新整理。';
            } else {
                $param['type'] == 'create' ? $message = '執行編號：' . $param['export_no'] . '<br>' . $param['name'] . '，選擇的訂單已開立發票。' :
                $message = '執行編號：' . $param['export_no'] . '<br>' . $param['name'] . '，選擇的訂單發票已作廢。';
            }
            if(env('APP_ENV') == 'local') {
                AdminInvoiceJob::dispatchNow($param);
            }else{
                if($param['method'] == 'byQuery') {
                    AdminInvoiceJob::dispatch($param);
                } else {
                    AdminInvoiceJob::dispatchNow($param);
                }
            }
        }elseif($param['cate'] == 'Refund') {
            $message = GroupBuyOrderRefundMailJob::dispatchNow($param);
        }
        if(is_array($message)){
            Session::put($message['status'], $message['message']);
        }else{
            Session::put('info', $message);
        }
        return redirect()->back();
    }
}
