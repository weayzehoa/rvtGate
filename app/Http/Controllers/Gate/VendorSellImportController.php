<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SellImport as SellImportDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ErpCustomer as ErpCustomerDB;
use App\Models\SpecialVendor as SpecialVendorDB;
use App\Models\ErpOrder as ErpOrderDB;
use App\Models\VendorShipping as ShippingDB;
use App\Models\VendorShippingExpress as ExpressDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Traits\SellImportFunctionTrait;
use App\Traits\OrderFunctionTrait;
use App\Jobs\DirectShipmentSellImportJob as SellImportJob;
use Carbon\Carbon;
use Session;

class VendorSellImportController extends Controller
{
    use SellImportFunctionTrait,OrderFunctionTrait;

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
        $menuCode = 'M29S4';
        $appends = [];
        $compact = [];
        $sellImports = $this->getSellImportData(request(),'index','directShip');

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
        $compact = array_merge($compact, ['menuCode','sellImports','appends']);
        return view('gate.sell.vendor_sellimport_index', compact($compact));
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
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellImport = SellImportDB::findOrFail($id);
        $type = $sellImport->type;
        $data['status'] = 0;
        $orderNumber = $data['order_number'];
        $quantity = $data['quantity'];
        $shippingNumber = $data['shipping_number'];
        $digiwinNo = $data['digiwin_no'];
        $purchaseNo = $data['purchase_no'];
        $memo = null;
        if(!empty($data['sell_date'])){
            $sellDate = $data['sell_date'];
            $sellDateNoDash = str_replace('-','',$sellDate);
            if(!is_numeric($sellDateNoDash) || strlen($sellDateNoDash) != 8){
                $memo .= "$sellDate 出貨日格式錯誤。";
                $data['sell_date'] = null;
            }
        }else{
            $memo .= "出貨日期未填寫。";
        }
        if(!empty($data['order_number'])){
            $order = OrderDB::with('syncedOrder','items','items.package')->where('order_number',$data['order_number'])->first();
            $order = $this->oneOrderItemTransfer($order);
            if(!empty($order)){
                $erpCustomer = $order->erpCustomer;
                $digiwinPaymentId = $order->digiwin_payment_id;
                //檢查訂單的digiwin_payment_id
                if(empty($erpCustomer)){
                    if(strlen($order->digiwin_payment_id) <= 2 ){
                        $digiwinPaymentId = str_pad($order->digiwin_payment_id,3,'0',STR_PAD_LEFT);
                        $order->update(['digiwin_payment_id' => $digiwinPaymentId]);
                        $erpCustomer = ErpCustomerDB::find($digiwinPaymentId);
                    }
                }
                empty($erpCustomer) ? $memo .= "$digiwinPaymentId 客戶資料不存在於鼎新中。" : '';
                !empty($order->syncedOrder) ? $erpOrder = ErpOrderDB::with('items')->where('TC002',$order->syncedOrder->erp_order_no)->first() : $erpOrder = null;
                if(count($order->items) > 0){
                    $chkItem = 0;
                    foreach($order->items as $item){
                        $item->direct_shipment == 1 && $item->is_del == 0 && $item->digiwin_no == $digiwinNo ? $chkItem++ : '';
                    }
                    $chkItem == 0 ? $memo .= "$digiwinNo 不存在於 $orderNumber 訂單中。" : '';
                }
                if(!empty($erpOrder)){
                    //直寄有可能是組合品,須將商品拆成單品來檢查鼎新內單品
                    if(count($erpOrder->items) > 0){
                        $chkErpItem = 0;
                        foreach($order->items as $item){
                            if($item->direct_shipment == 1){
                                if(strstr($item->sku,'BOM')){
                                    foreach($item->package as $package){
                                        foreach($erpOrder->items as $erpItem){
                                            $erpItem->TD007 == 'W02' && $erpItem->TD004 == $package->digiwin_no ? $chkErpItem++ : '';
                                        }
                                    }
                                }else{
                                    foreach($erpOrder->items as $erpItem){
                                        $erpItem->TD007 == 'W02' && $erpItem->TD004 == $item->digiwin_no ? $chkErpItem++ : '';
                                    }
                                }
                            }
                        }
                        $chkErpItem == 0 ? $memo .= "$digiwinNo 商品不存在於鼎新同步資料中。" : '';
                    }else{
                        $memo .= "$orderNumber 訂單所有商品不存在於鼎新中，忘記同步？";
                    }
                }else{
                    $memo .= "$orderNumber 訂單不存在於鼎新中，忘記同步？";
                }
                $pm = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                ->where('digiwin_no',$digiwinNo)
                ->select([
                    $productModelTable.'.*',
                    $vendorTable.'.id as vendor_id',
                ])->first();
                if(!empty($pm)){
                        $vendorId = $chkpm = $totalSellQty = $totalRequireQty = 0;
                        //直寄不需要比對組合內的單品
                        foreach($order->items as $item){
                            if($item->direct_shipment == 1){
                                if($item->product_model_id == $pm->id){
                                    $chkpm++;
                                    $vendorId = $pm->vendor_id;
                                    $totalRequireQty += $item->quantity;
                                    if(count($item->sells) > 0){
                                        foreach($item->sells as $sell){
                                            $totalSellQty += $sell->sell_quantity;
                                        }
                                    }
                                }
                            }
                        }
                        if($chkpm > 0){
                            $needQty = ($totalRequireQty - $totalSellQty);
                            if($quantity > $needQty){
                                if($chkpm > 0){
                                    $memo .= "商品 $digiwinNo ，此次出庫數量 $quantity 大於應出數量 $needQty 。";
                                }
                            }
                        }else{
                            $memo .= "商品 $digiwinNo ，找不到對應 $orderNumber 訂單內商品。";
                        }
                        //特殊廠商排除檢查日期
                        if($type == 'directShip'){
                            $spVendors = SpecialVendorDB::where('vendor_id',$vendorId)->orderBy('code','asc')->first();
                            if(empty($spVendors)){
                                $sd = strtotime($sellDate);
                                $vt = strtotime($order->vendor_arrival_date);
                                $befor5days = strtotime(Carbon::create(date('Y',$vt), date('m',$vt), date('d',$vt))->addDays(-5));
                                $after3days = strtotime(Carbon::create(date('Y',$vt), date('m',$vt), date('d',$vt))->addDays(3));
                                if($sd < $befor5days || $sd > $after3days){
                                    $status = -1;
                                    $memo .= "出貨日期範圍錯誤。";
                                }
                            }
                        }
                }else{
                    $memo .= "$digiwinNo 商品不存在於iCarry中。";
                }
            }else{
                $memo .= "$orderNumber 訂單不存在。";
            }
        }else{
            $memo .= "訂單號碼未填寫。";
        }
        $productModel = ProductModelDB::where('digiwin_no',$digiwinNo)->first();
        if(!empty($productModel)){
            $purchaseOrderItem = PurchaseOrderItemDB::where([['direct_shipment',1],['purchase_no',$purchaseNo],['product_model_id',$productModel->id],['is_del',0],['is_lock',0]])->first();
            empty($purchaseOrderItem) ? $memo .= "找不到對應採購單資料。" : '';
        }else{
            $memo .= "鼎新貨號錯誤，找不到商品。";
        }
        $quantity <= 0 ? $memo .= "銷貨數量 $quantity 等於小於 0。" : '';
        empty($shippingNumber) ? $memo .= "物流資訊未填寫。" : '';
        $data['memo'] = $memo;
        !empty($data['memo']) ? $data['status'] = -1 : '';
        $sellImport->update($data);
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
        $sellImport = SellImportDB::with('vendorShipping','vendorShipping.expresses')->findOrFail($id);
        if(!empty($sellImport->vendorShipping)){
            $vendorShipping = $sellImport->vendorShipping;

            if(count($vendorShipping->expresses) > 0){
                foreach($vendorShipping->expresses as $express){
                    $express->delete();
                }
            }

            $shipping = ShippingDB::with('items')->where('shipping_no',$vendorShipping->shipping_no)->first();
            $shippingNo = $shipping->shipping_no;
            //檢查出貨狀況
            $chkShipping = 0;
            $items = $shipping->items;
            foreach($items as $item){
                $express = ExpressDB::where('shipping_no',$item->shipping_no)->where('vsi_id',$item->id)->count();
                if($express > 0){
                    $chkShipping++;
                }
            }
            if($chkShipping == count($items)){
                $temp = ExpressDB::where('shipping_no',$shippingNo)->selectRaw("MAX(shipping_date) as shippingDate")->groupBy('shipping_no')->first();
                !empty($temp) ? $shippingDate = $temp->shippingDate : $shippingDate = null;
                $shipping->update(['status' => 3, 'shipping_finish_date' => $shippingDate]);
            }elseif($chkShipping > 0){
                $shipping->update(['status' => 2]);
            }

        }
        $sellImport->delete();
        return redirect()->back();
    }

    public function executeImport(Request $request)
    {
        if(!empty($request->id)){
            $sellImport = SellImportDB::findOrFail($request->id);
            $array['admin_id'] = auth('gate')->user()->id;
            $array['orderNumbers'] = [$sellImport->order_number];
            $array['type'] = 'directShip';
            SellImportJob::dispatchNow($array);
        }
        return redirect()->back();
    }

    public function delete(Request $request)
    {
        if(!empty($request->type) && $request->type == 'directShip'){
            $sellImports = SellImportDB::where([['type','directShip'],['status','!=',1]])->delete();
        }
        return redirect()->back();
    }

    public function multiProcess(Request $request)
    {
        if(!empty($request->ids) && count($request->ids) > 0 && !empty($request->method)){
            if($request->method == 'process'){
                $sellImports = sellImportDB::whereIn('id',$request->ids)->get();
                $orderNumbers = $sellImports->pluck('order_number')->all();
                $orderNumbers = array_unique($orderNumbers);
                sort($orderNumbers);
                $result['type'] = 'directShip';
                $result['orderNumbers'] = $orderNumbers;
                $result['admin_id'] = auth('gate')->user()->id;
                env('APP_ENV') == 'local' ? SellImportJob::dispatchNow($result) : SellImportJob::dispatch($result);
                Session::put('success', '選擇的資料已於背端執行，請過一段時間重新整理頁面。');
            }elseif($request->method == 'delete'){
                $sellImports = sellImportDB::with('vendorShipping','vendorShipping.expresses')->whereIn('id',$request->ids)->get();
                if(count($sellImports) > 0){
                    foreach($sellImports as $sellImport){
                        if(!empty($sellImport->vendorShipping)){
                            $vendorShipping = $sellImport->vendorShipping;
                            if(count($vendorShipping->expresses) > 0){
                                foreach($vendorShipping->expresses as $express){
                                    $express->delete();
                                }
                            }

                            $shipping = ShippingDB::with('items')->where('shipping_no',$vendorShipping->shipping_no)->first();
                            $shippingNo = $shipping->shipping_no;
                            //檢查出貨狀況
                            $chkShipping = 0;
                            $items = $shipping->items;
                            foreach($items as $item){
                                $express = ExpressDB::where('shipping_no',$item->shipping_no)->where('vsi_id',$item->id)->count();
                                if($express > 0){
                                    $chkShipping++;
                                }
                            }
                            if($chkShipping == count($items)){
                                $temp = ExpressDB::where('shipping_no',$shippingNo)->selectRaw("MAX(shipping_date) as shippingDate")->groupBy('shipping_no')->first();
                                !empty($temp) ? $shippingDate = $temp->shippingDate : $shippingDate = null;
                                $shipping->update(['status' => 3, 'shipping_finish_date' => $shippingDate]);
                            }elseif($chkShipping > 0){
                                $shipping->update(['status' => 2]);
                            }
                        }
                        $sellImport->delete();
                    }
                }
            }
        }
        return redirect()->back();
    }
}
