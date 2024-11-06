<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ShippingVendor as ShippingVendorDB;
use App\Models\SellReturnItem as SellReturnItemDB;
use App\Models\SellReturn as SellReturnDB;
use App\Models\ErpCOPTI as ErpCOPTIDB;
use App\Models\ErpCOPTJ as ErpCOPTJDB;
use DB;
use Session;
use App\Traits\SellReturnFunctionTrait;

class SellReturnController extends Controller
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
        $menuCode = 'M33S1';
        $appends = [];
        $compact = [];
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellReturnItemTable = env('DB_DATABASE').'.'.(new SellReturnItemDB)->getTable();
        $returns = $this->getSellReturnData(request(),'index');
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
        //找出運費及其他資料
        foreach($returns as $return){
            if(count($return->items) > 0){
                foreach($return->items as $item){
                    if($item->origin_digiwin_no == '901001'){
                        $item->product_name = '運費';
                        $item->unit_name = '個';
                        $item->vendor_name = 'iCarry';
                    }elseif($item->origin_digiwin_no == '901002'){
                        $item->product_name = '跨境稅';
                        $item->unit_name = '個';
                        $item->vendor_name = 'iCarry';
                    }else{
                        $tmp = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                        ->select([
                            $productModelTable.'.*',
                            $vendorTable.'.name as vendor_name',
                            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                            $productTable.'.unit_name',
                        ])->where($productModelTable.'.digiwin_no',$item->origin_digiwin_no)->first();
                        $item->vendor_name = $tmp->vendor_name;
                        $item->product_name = $tmp->product_name;
                        $item->unit_name = $tmp->unit_name;
                    }
                }
            }
        }
        $payMethods = OrderDB::select('pay_method as name')->groupBy('pay_method')->orderBy('pay_method', 'asc')->get();
        $shippingVendors = ShippingVendorDB::orderBy('sort','asc')->get();
        $compact = array_merge($compact, ['menuCode','returns','appends','shippingVendors','payMethods']);
        return view('gate.sellreturns.index', compact($compact));
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

    public function cancel(Request $request)
    {
        if(!empty($request->id)){
            $return = SellReturnDB::with('items')->find($request->id);
            if(!empty($return)){
                $erpReturn = ErpCOPTIDB::where([['TI001',$return->erp_return_type],['TI002',$return->erp_return_no]])->first();
                if(!empty($erpReturn)){
                    if($erpReturn->TI019 == 'Y'){
                        Session::put('error','鼎新銷退單未取消確認!!');
                    }else{
                        if(count($return->items) > 0){
                            $c = $orderItemId = $chkPackage = 0;
                            $data = [];
                            foreach($return->items as $item){
                                if($item->origin_digiwin_no != '901001' && $item->origin_digiwin_no != '901002'){
                                    if(!empty($item->order_item_package_id)){ //組合品
                                        $data[$c]['order_item_id'] = $item->order_item_id;
                                        $data[$c]['diffQty'] = $item->return_quantity;
                                        $c++;
                                    }else{ //單品
                                        $orderItem = OrderItemDB::find($item->order_item_id);
                                        !empty($orderItem) ? $orderItem->decrement('return_quantity') : '';
                                    }
                                }
                                $erpReturnItem = ErpCOPTJDB::where([['TJ001',$return->erp_return_type],['TJ002',$return->erp_return_no],['TJ003',$item->erp_return_sno]])->update(['TJ021' => 'V']);
                                $item->update(['is_del' => 1]);
                            }
                            $data = $this->array_unique_ex($data);
                            if(count($data) > 0){ //組合品處理
                                for($i=0;$i<count($data);$i++){
                                    $orderItem = OrderItemDB::find($data[$i]['order_item_id']);
                                    !empty($orderItem) ? $orderItem->update(['return_quantity' => $orderItem->return_quantity - $data[$i]['diffQty']]) : '';
                                }
                            }
                        }
                        $erpReturn = ErpCOPTIDB::where([['TI001',$return->erp_return_type],['TI002',$return->erp_return_no]])->update(['TI019' => 'V']);
                        $return->update(['is_del' => 1]);
                    }
                }else{
                    Session::put('error','鼎新銷退單不存在!!');
                }
            }
        }
        return redirect()->back();
    }

    //排除重複的陣列
    function array_unique_ex($ary){
        $new_ary=array();
        foreach($ary as $key=>$val){
            $new_ary[$key]=json_encode($val);
        }
        $ary=array_unique($new_ary);
        $new_ary=array();
        foreach($ary as $key=>$val){
            $new_ary[]=json_decode($val,true);
        }
        return $new_ary;
    }
}
