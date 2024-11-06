<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin as AdminDB;
use App\Models\SellAbnormal as SellAbnormalDB;
use App\Models\SellImport as SellImportDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;

class SellAbnormalController extends Controller
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
        $menuCode = 'M29S2';
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
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellAbnormalTable = env('DB_DATABASE').'.'.(new SellAbnormalDB)->getTable();
        $sellAbnormals = SellAbnormalDB::with('order','product','admin')->orderBy('is_chk','asc')->orderBy('created_at','desc')->paginate($list);
        foreach($sellAbnormals as $sellAbnormal){
            $vendorName = $sku = null;
            $order = $sellAbnormal->order;
            if(!empty($order)){
                $sellAbnormal->order_id = $order->id;
                if(!empty($order->syncedOrder)){
                    $sellAbnormal->erp_order_number = $order->syncedOrder->erp_order_no;
                }
            }
            if(!empty($sellAbnormal->product)){
                $productModel = $sellAbnormal->product;
                if(!empty($productModel)){
                    $sellAbnormal->vendor_name = $productModel->vendor_name;
                    $sellAbnormal->sku = $productModel->sku;
                }
            }
            $admin = $sellAbnormal->admin;
            if(!empty($admin)){
                $sellAbnormal->admin_name = $admin->name;
            }
            $sellAbnormal->sku = $sku;
            $sellAbnormal->vendor_name = $vendorName;
        }
        $compact = array_merge($compact, ['menuCode','sellAbnormals','appends']);
        return view('gate.sell.abnormal_index', compact($compact));
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
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellAbnormalTable = env('DB_DATABASE').'.'.(new SellAbnormalDB)->getTable();
        $sellAbnormal = SellAbnormalDB::findOrFail($id);
        $sellImports = [];
        if($request->is_chk == 1){
            $sellAbnormal->direct_shipment == 0 ? $type = 'warehouse' : $type = 'directShip';
            //清除匯入的資料
            if(strstr($sellAbnormal->memo,'訂單狀態非集貨中')){
                $order = OrderDB::where('order_number',$sellAbnormal->order_number)->first();
                if($order->status == 1){
                    $order->update(['status' => 1]);
                }
            }else{
                if($type == 'warehouse'){
                    if(!empty($sellAbnormal->product_model_id)){
                        $productModel = ProductModelDB::find($sellAbnormal->product_model_id);
                        if(!empty($productModel)){
                            $sellImports = SellImportDB::where([
                                ['import_no',$sellAbnormal->import_no],
                                ['type',$type],
                                ['order_number',$sellAbnormal->order_number],
                                ['gtin13',$productModel->gtin13 ?? $productModel->sku],
                                ['sell_date',$sellAbnormal->sell_date],
                                ['shipping_number',$sellAbnormal->shipping_memo],
                            ])->get();
                        }
                    }else{
                        $sellImports = SellImportDB::where([
                            ['import_no',$sellAbnormal->import_no],
                            ['type',$type],
                            ['order_number',$sellAbnormal->order_number],
                            ['shipping_number',$sellAbnormal->shipping_memo],
                        ])->get();
                    }
                }else{
                    //廠商直寄不刪除原始資料
                }
            }
            if(count($sellImports) > 0){
                foreach($sellImports as $sellImport){
                    $sellImport->delete();
                }
            }
            $sellAbnormal->update([
                'admin_id' => auth('gate')->id(),
                'chk_date' => date('Y-m-d H:i:s'),
                'is_chk' => 1,
            ]);
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
}
