<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderImportAbnormal as OrderImportAbnormalDB;
use App\Models\OrderImport as OrderImportDB;

class OrderImportAbnormalController extends Controller
{
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
        $menuCode = 'M27S1';
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

        if (!isset($status)) {
            $status = '0';
            $compact = array_merge($compact, ['status']);
        }

        if (!isset($list)) {
            $list = 50;
            $compact = array_merge($compact, ['list']);
        }
        $orderAbnormals = OrderImportAbnormalDB::with('admin')->orderBy('is_chk','asc')->orderBy('created_at','desc')->paginate($list);
        foreach($orderAbnormals as $orderAbnormal){
            $admin = $orderAbnormal->admin;
            !empty($admin) ? $orderAbnormal->admin_name = $admin->name : $orderAbnormal->admin_name = null;
        }
        $compact = array_merge($compact, ['menuCode','orderAbnormals','appends']);
        return view('gate.orders.abnormal_index', compact($compact));
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
        if($request->is_chk == 1){
            $orderAbnormal = OrderImportAbnormalDB::findOrFail($id);
            $partnerOrderNumber = $orderAbnormal->partner_order_number;
            $sku = $orderAbnormal->sku;
            $type = $orderAbnormal->type;
            $adminId = auth('gate')->user()->id;
            $orderImports = OrderImportDB::where([['type',$type],['partner_order_number',$partnerOrderNumber]])->delete();
            $orderAbnormals = OrderImportAbnormalDB::where('partner_order_number',$partnerOrderNumber)->update(['is_chk' => 1, 'chk_date' => date('Y-m-d H:i:s'), 'admin_id' => $adminId]);
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

    public function deleteAll(Request $request)
    {
        $data = $request->all();
        if(!empty($data['is_chk']) && $data['is_chk'] == 'all'){
            $orderAbnormals = OrderImportAbnormalDB::where('is_chk',0)->get();
            foreach($orderAbnormals as $orderAbnormal){
                $partnerOrderNumber = $orderAbnormal->partner_order_number;
                $sku = $orderAbnormal->sku;
                $type = $orderAbnormal->type;
                $adminId = auth('gate')->user()->id;
                $orderImports = OrderImportDB::where([['type',$type],['partner_order_number',$partnerOrderNumber]])->delete();
                $orderAbnormal->update(['is_chk' => 1, 'chk_date' => date('Y-m-d H:i:s'), 'admin_id' => $adminId]);
            }
        }
        return redirect()->back();
    }
}
