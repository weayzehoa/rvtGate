<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\iCarryVendor as VendorDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\Statement as StatementDB;
use DB;
use Session;
use App\Jobs\StatementCreateJob;
use App\Jobs\StatementCancelJob;
use App\Jobs\StatementSendToVendorAndDownloadJob as StatementSendToVendorAndDownload;
use App\Traits\StatementFunctionTrait;
use App\Traits\UniversalFunctionTrait;

class StatementController extends Controller
{
    use StatementFunctionTrait,UniversalFunctionTrait;

    public function __construct()
    {
        // 先經過 middleware 檢查
        $this->middleware('auth:gate');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $menuCode = 'M28S4';
        $appends = [];
        $compact = [];
        //將進來的資料作參數轉換及附加到appends及compact中
        foreach (request()->all() as $key => $value) {
            $$key = $value;
            if (isset($$key)) {
                $appends = array_merge($appends, [$key => $value]);
                $compact = array_merge($compact, [$key]);
            }
            !empty($value) ? $con[$key] = $value : '';
        }

        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }

        $temp = ErpVendorDB::where('MA001','like','%A%')
        ->where('MA055','!=','')
        ->select([
            'MA001',
            'MA002',
            'MA025',
            'MA055',
        ])->get();
        $temp = $temp->groupBy('MA055');
        $i=0;
        foreach($temp as $pc => $value){
            foreach($value as $v){
                $payments[$i]['code'] = $pc;
                $payments[$i]['name'] = $v->MA025;
                $payments[$i]['vendorIds'][] = ltrim(rtrim(str_replace('A','',$v->MA001),' '),'0');
                $vendorIds[] = ltrim(rtrim(str_replace('A','',$v->MA001),' '),'0');
            }
            $payments[$i]['vendorIds'] = join(',',$payments[$i]['vendorIds']);
            $i++;
        }
        $vIds = null;
        for($x=0;$x<count($payments);$x++){
            $vIds .= $payments[$x]['vendorIds'].',';
        }
        $vIds = rtrim($vIds,',');
        $vendors = VendorDB::orderBy('is_on','desc')->get();
        $statements = $this->getStatementData(request(),'index');

        $compact = array_merge($compact, ['menuCode','statements','payments','vendors','appends','vIds']);
        return view('gate.statement.index', compact($compact));

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
        $vendorIds = [];
        if(!empty($request->start_date) && !empty($request->end_date)){
            if(!empty($request->vendorIds)){
                $vendorIds = $request->vendorIds;
            }elseif(!empty($request->paymentCon)){
                if($request->paymentCon[0] != null){
                    $payCon = null;
                    foreach($request->paymentCon as $key => $pay){
                        $payCon .= $pay;
                    }
                    $vendorIds = explode(',',$payCon);
                    $vendorIds = array_unique($vendorIds);
                    sort($vendorIds);
                }else{
                    $vendorIds[0] = null;
                }
            }else{
                Session::put('error','請選擇商家或付款條件');
            }
        }else{
            Session::put('error','請填寫對帳日期區間');
        }
        if(!empty($vendorIds)){
            $request->request->add(['vendorIds' => $vendorIds]);
            //背端執行須以陣列形式不能以request()形式.
            strstr(env('APP_URL'),'localhost') ? $result = StatementCreateJob::dispatchNow($request->all()) : $result = StatementCreateJob::dispatch($request->all());
            if($result == 'no data'){
                Session::put('error','您選擇的商家或付款條件找不到資料，請重新選擇新的條件。');
            }else{
                Session::put('success','對帳單建立作業已於背景執行中，請稍後一段時間按F5重整本頁面查詢');
            }
        }
        return redirect()->back();
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
        $invoiceDate = $request->invoice_date;
        $statement = StatementDB::findOrFail($id);
        if(!empty($invoiceDate)){
            if($this->chkDate($invoiceDate) == true){
                $statement->update(['invoice_date' => $invoiceDate]);
            }else{
                Session::put('error', '填寫的發票收受日日期格式資料錯誤。');
            }
        }
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

    public function cancel(Request $request)
    {
        if(!empty($request->id)){
            StatementCancelJob::dispatchNow([
                "id" => $request->id,
                "admin_id" => auth('gate')->user()->id,
                "admin_name" => auth('gate')->user()->name,
            ]);
            Session::put('success', '選擇的對帳單已被取消，相關鎖定資料已解除。');
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
        $param['export_no'] = time();
        $param['start_time'] = date('Y-m-d H:i:s');
        !empty($method) ? $param['name'] = $param['filename'].'_'.$method : $param['name'] = $param['filename'];
        $param['type'] == 'Download' ? $param['filename'] = $param['name'].'_'.time().'.zip' : '';
        $param['type'] == 'Download' ? $message = $param['name'].'，工作單號：'.$param['export_no'].'<br>匯出已於背端執行，請過一段時間至匯出中心下載，<br>檔案名稱：'.$param['filename'].'<br>匯出中心連結：<a href="'.$url.'" target="_blank"><span class="text-danger">'.$url.'</span></a>' : $message = '對帳單寄送已於背端執行，請過一段時間與廠商聯繫，確認是否有收到對帳單。';

        if(env('APP_ENV') == 'production'){
            StatementSendToVendorAndDownload::dispatch($param);
        }else{
            StatementSendToVendorAndDownload::dispatchNow($param);
        }

        Session::put('info', $message);
        return redirect()->back();
    }
}
