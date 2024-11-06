<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\iCarryVendor as VendorDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;

class VendorConfirmController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $message = null;
        \Debugbar::disable();
        $request = request();
        if(!empty($request->vId) && !empty($request->poId) && !empty($request->no) && !empty($request->chk)){
            $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
            $purchaseSynceLogTable = env('DB_DATABASE').'.'.(new PurchaseSyncedLogDB)->getTable();
            $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
            foreach ($request->all() as $key => $value) {
                $$key = $value;
            }
            $chk = md5($request->vId . $request->poId . $request->no);
            if($request->chk == $chk){
                $vendorName = null;
                $vendor = VendorDB::find($vId);
                if(!empty($vendor)){
                    $vendorName = $vendor->name;
                    $poIds = explode(',',$poId);
                    if(is_array($poIds) && count($poIds) > 0){
                        $c=0;
                        $pn = [];
                        for($i=0;$i<count($poIds);$i++){
                            //先找出是否有更新的通知
                            $po = PurchaseSyncedLogDB::join($purchaseOrderTable,$purchaseOrderTable.'.id',$purchaseSynceLogTable.'.purchase_order_id')
                            ->where([
                                [$purchaseSynceLogTable.'.vendor_id',$vId],
                                [$purchaseSynceLogTable.'.export_no',$no],
                                [$purchaseSynceLogTable.'.purchase_order_id',$poIds[$i]]
                            ])->whereNotNull($purchaseSynceLogTable.'.notice_time')
                            ->select([
                                $purchaseSynceLogTable.'.*',
                                $purchaseOrderTable.'.purchase_no',
                            ])->orderBy($purchaseSynceLogTable.'.created_at','desc')->first();
                            if(!empty($po)) {
                                if(empty($po->confirm_time)){
                                    $pn[] = $po->purchase_no;
                                }else{
                                    $c++;
                                }
                            }
                        }
                        if($c == count($poIds)){
                            $message = "採購單已全部確認完成，請勿重複確認。";
                        }else{
                            $purchaseNos = join(',',$pn);
                            return view('gate.vendorconfirm',compact('chk','vId','poId','no','purchaseNos','vendorName'));
                        }
                    }else{
                        $message = '採購單資料錯誤，請確認您的資料是否正確。';
                    }
                }else{
                    $message = "商家資料錯誤，請確認您的資料是否正確。";
                }
            }else{
                $message = '檢查碼錯誤，請確認您的資料是否正確。';
            }
        }
        return $message;
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
        if(!empty($request->vId) && !empty($request->poId) && !empty($request->no) && !empty($request->chk)){
            $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
            $purchaseSynceLogTable = env('DB_DATABASE').'.'.(new PurchaseSyncedLogDB)->getTable();
            $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
            foreach ($request->all() as $key => $value) {
                $$key = $value;
            }
            if(!empty($chk) && $chk == md5($request->vId . $request->poId . $request->no)){
                $vendorName = null;
                $vendor = VendorDB::find($vId);
                if(!empty($vendor)) {
                    $poIds = explode(',',$poId);
                    if(is_array($poIds)) {
                        for($i=0;$i<count($poIds);$i++) {
                            //先找出是否有更新的通知
                            $po = PurchaseSyncedLogDB::join($purchaseOrderTable, $purchaseOrderTable.'.id', $purchaseSynceLogTable.'.purchase_order_id')
                            ->where([
                                [$purchaseSynceLogTable.'.vendor_id',$vId],
                                [$purchaseSynceLogTable.'.export_no',$no],
                                [$purchaseSynceLogTable.'.purchase_order_id',$poIds[$i]]
                            ])->whereNotNull($purchaseSynceLogTable.'.notice_time')
                            ->select([
                                $purchaseSynceLogTable.'.*',
                                $purchaseOrderTable.'.purchase_no',
                            ])->orderBy($purchaseSynceLogTable.'.created_at', 'desc')->first();
                            if(!empty($po) && empty($po->confirm_time)){
                                $po->update(['confirm_time' => date('Y-m-d H:i:s')]);
                            }
                        }
                        return '採購單已全部確認，您已經可以關閉此視窗。';
                    }else{
                        return '採購單資料錯誤，請確認您的資料是否正確。';
                    }
                }else{
                    return '商家資料錯誤，請確認您的資料是否正確。';
                }
            }else{
                return '檢查碼錯誤，請確認您的資料是否正確。';
            }
        }
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
}
