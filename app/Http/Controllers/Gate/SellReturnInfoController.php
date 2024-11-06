<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ShippingVendor as ShippingVendorDB;
use App\Models\SellReturnItem as SellReturnItemDB;
use App\Models\SellReturn as SellReturnDB;
use DB;
use Session;
use App\Jobs\SellReturnStockinFileImportJob;
use App\Jobs\SellReturnStockinImportJob;
use App\Traits\SellReturnFunctionTrait;

class SellReturnInfoController extends Controller
{
    use SellReturnFunctionTrait;

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
        $menuCode = 'M33S2';
        $appends = [];
        $compact = [];
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellReturnItemTable = env('DB_DATABASE').'.'.(new SellReturnItemDB)->getTable();
        $returns = $this->getSellReturnItemData(request(),'index');

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
        $compact = array_merge($compact, ['menuCode','returns','appends']);
        return view('gate.sellreturns.return_info', compact($compact));
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
        $returnItem = SellReturnItemDB::findOrFail($id);
        $expiryDate = $request->expiry_date;
        $itemMemo = $request->item_memo;
        $isChk = $request->is_chk;
        if(!empty($returnItem)){
            if($isChk == 1){
                $returnItem->update([
                    'is_chk' => 1,
                    'chk_date' => date('Y-m-d'),
                    'admin_id' => auth('gate')->user()->id,
                ]);
            }else{
                $returnItem->update([
                    'expiry_date' => $expiryDate,
                    'item_memo' => $itemMemo,
                ]);
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

    public function confirm(Request $request)
    {
        $isConfirm = $request->is_confirm;
        $returnItem = SellReturnItemDB::findOrFail($request->id);
        $returnItem->update(['is_confirm' => $isConfirm]);
        return redirect()->back();
    }

    public function multiProcess(Request $request)
    {
        $ids = $request->ids;
        $method = $request->method;
        $memo = $request->memo;
        $confirm = $chk = null;
        if(is_array($ids) && count($ids) > 0 && !empty($method)){
            $method == 'confirm' ? $confirm = 1 : '';
            $method == 'unconfirm' ? $confirm = 0 : '';
            $method == 'stockin' ? $chk = 1 : '';
            $method == 'unchk' ? $chk = 0 : '';
            $items = SellReturnItemDB::whereIn('id',$ids)->get();
            if($method == 'memoModify'){
                SellReturnItemDB::whereIn('id',$ids)->update(['item_memo' => $memo]);
            }elseif($method == 'memoCancel'){
                SellReturnItemDB::whereIn('id',$ids)->update(['item_memo' => null]);
            }elseif($chk == 1){
                SellReturnItemDB::whereIn('id',$ids)->update([
                    'is_stockin' => $chk,
                    'is_chk' => $chk,
                    'chk_date' => date('Y-m-d H:i:s'),
                    'admin_id' => auth('gate')->user()->id,
                ]);
            }elseif($chk == 0){
                SellReturnItemDB::whereIn('id',$ids)->update([
                    'is_chk' => $chk,
                    'chk_date' => null,
                    'admin_id' => null,
                ]);
            }elseif($confirm == 1 || $confirm == 0){
                SellReturnItemDB::whereIn('id',$ids)->update([
                    'is_confirm' => $confirm
                ]);
            }
        }
        return redirect()->back();
    }

    public function import(Request $request)
    {
        // $request->request->add(['test' => true]); //加入test bypass 檔案匯入功能
        $request->request->add(['admin_id' => auth('gate')->user()->id]); //加入request
        $request->request->add(['import_no' => time()]); //加入request
        $request->cate == 'stockin' ? $cate = '倉庫入庫匯入' : '廠商直寄匯入';
        if($request->cate == 'stockin'){
            if ($request->hasFile('filename')) {
                $file = $request->file('filename');
                $uploadedFileMimeType = $file->getMimeType();
                $mimes = array('application/excel','application/vnd.ms-excel','application/vnd.msexcel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/CDFV2','application/octet-stream');
                if(in_array($uploadedFileMimeType, $mimes)){
                    //檔案不可以直接放入Job中使用dispatch去跑,只能使用dispatchNow
                    $result = SellReturnStockinFileImportJob::dispatchNow($request);
                    if(!empty($result)){
                        if($result == 'no Y value'){
                            $message = '檔案內資料O欄，至少有一筆不是 Y 值。';
                            Session::put('error', $message);
                        }elseif($result == 'rows error'){
                            $message = '檔案內資料欄位數錯誤，請檢查第一Sheet的欄位總數為16。';
                            Session::put('error', $message);
                        }elseif(!empty($result['import_no'])){
                            if(!empty($request->test) && $request->test == true){
                                SellReturnStockinImportJob::dispatchNow($result);
                            }else{
                                env('APP_ENV') == 'local' ? SellReturnStockinImportJob::dispatchNow($result) : SellReturnStockinImportJob::dispatch($result);
                            }
                            $message = "$cate 已於背端處理，請稍後一段時間，按F5重新整理頁面，確認是否已完成。";
                            Session::put('success', $message);
                        }
                    }
                    return redirect()->back();
                } else{
                    $message = '只接受 xls 或 xlsx 檔案格式';
                    Session::put('error', $message);
                    return redirect()->back();
                }
            }
        }
    }
}
