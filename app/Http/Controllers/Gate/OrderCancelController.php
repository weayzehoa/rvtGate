<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin as AdminDB;
use App\Models\OrderCancel as OrderCancelDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\OrderCancelExcludeProduct as ExcludeProductDB;
use App\Jobs\OrderCancelAutoProcessJob;
use DB;
use Session;

class OrderCancelController extends Controller
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
        $menuCode = 'M27S3';
        $appends = [];
        $compact = [];
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderCancelTable = env('DB_DATABASE').'.'.(new OrderCancelDB)->getTable();
        $excludeProductTable = env('DB_DATABASE').'.'.(new ExcludeProductDB)->getTable();
        $orderCancels = new OrderCancelDB;

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

        isset($order_number) ? $orderCancels = $orderCancels->where($orderCancelTable.'.order_number','like',"%$order_number%") : '';
        if(isset($digiwin_no)){
            $orderCancels = $orderCancels->where(function($query) use ($digiwin_no,$orderCancelTable){
                $query->where($orderCancelTable.'.order_digiwin_no','like',"%$digiwin_no%")
                ->orWhere($orderCancelTable.'.purchase_digiwin_no','like',"%$digiwin_no%");
            });
        }

        isset($purchase_no) ? $orderCancels = $orderCancels->where($orderCancelTable.'.purchase_no','like',"%$purchase_no%") : '';

        if(isset($product_name)){
            $orderCancels = $orderCancels->join($productModelTable,$productModelTable.'.id',$orderCancelTable.'.purchase_digiwin_no')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id');
            $orderCancels = $orderCancels->where($productTable.'.name','like',"%$product_name%");
        }

        isset($is_chk) ? $orderCancels = $orderCancels->where($orderCancelTable.'.is_chk',$is_chk) : '';

        $orderCancels = $orderCancels->orderBy($orderCancelTable.'.is_chk','asc')->orderBy($orderCancelTable.'.chk_date','desc')->paginate($list);;

        foreach($orderCancels as $orderCancel){
            $tmp = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
            ->where($productModelTable.'.digiwin_no',$orderCancel->purchase_digiwin_no)
            ->select([
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            ])->first();
            !empty($tmp) ? $orderCancel->product_name = $tmp->product_name : $orderCancel->product_name = null;
            if(empty($orderCancel->admin_id)){
                $orderCancel->admin_name = null;
            }else{
                $tmp = AdminDB::find($orderCancel->admin_id);
                !empty($tmp) ? $orderCancel->admin_name = $tmp->name : $orderCancel->admin_name = '系統';
            }
            //檢查是否被排除
            $excludeProduct = ExcludeProductDB::join($productModelTable,$productModelTable.'.id',$excludeProductTable.'.product_model_id')
            ->where($productModelTable.'.digiwin_no',$orderCancel->purchase_digiwin_no)->first();
            !empty($excludeProduct) ? $orderCancel->excludeProduct = 1 : $orderCancel->excludeProduct = null;
        }

        $compact = array_merge($compact, ['menuCode','orderCancels','appends']);
        return view('gate.orders.cancel_index', compact($compact));
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
        $data = $request->all();
        $orderCancel = OrderCancelDB::findOrFail($id);
        $deductQuantity = $data['deduct_quantity'];

        if($orderCancel->is_chk == 0){
            if(($deductQuantity - $orderCancel->quantity) > 0){
                Session::put('error',"處理數量 $deductQuantity 大於應處扣數量 $orderCancel->quantity");
                $data['is_chk'] = 0;
            }elseif(($deductQuantity - $orderCancel->quantity) == 0){
                $data['is_chk'] = 1;
            }else{
                $data['is_chk'] = 0;
            }
            $data['chk_date'] = date('Y-m-d H:i:s');
            $data['admin_id'] = auth('gate')->user()->id;
            $orderCancel->update($data);
        }else{
            if($data['deduct_quantity'] >= $orderCancel->quantity){
                Session::put('error',"處理數量 $deductQuantity 必須小於應扣數量 $orderCancel->quantity");
            }else{
                $data['is_chk'] = 0;
                if($data['deduct_quantity'] == 0){
                    $data['chk_date'] = $data['admin_id'] = null;
                }else{
                    $data['chk_date'] = date('Y-m-d H:i:s');
                    $data['admin_id'] = auth('gate')->user()->id;
                }
                $orderCancel->update($data);
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

    public function process(Request $request)
    {
        if(!empty($request->id)){
            $param['type'] = 'process';
            $param['id'] = $request->id;
            $param['adminId'] = auth('gate')->user()->id;
            OrderCancelAutoProcessJob::dispatchNow($param);
        }
        return redirect()->back();
    }
}
