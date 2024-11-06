<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ReturnDiscount as ReturnDiscountDB;
use App\Models\ReturnDiscountItem as ReturnDiscountItemDB;
use App\Models\ReturnDiscountItemPackage as ReturnDiscountItemPackageDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;


use App\Models\ErpVendor as ErpVendorDB;
use App\Models\ErpPURTI as ErpPURTIDB;
use App\Models\ErpPURTJ as ErpPURTJDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseOrderItemPackage as PurchaseOrderItemPackageDB;
use DB;
use Session;
use App\Jobs\ReturnDiscountJob;
use App\Traits\ReturnDiscountFunctionTrait;

class ReturnDiscountController extends Controller
{
    use ReturnDiscountFunctionTrait;

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
        $ctime = microtime(true); //紀錄開始時間
        $menuCode = 'M28S3';
        $appends = [];
        $compact = [];
        $purchases = [];

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

        $returns = $this->getReturnDiscountData(request(),'index');

        $compact = array_merge($compact, ['menuCode','returns','appends']);
        return view('gate.returns.index', compact($compact));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $menuCode = 'M28S3';
        $vendors = VendorDB::where('name','!=','')->select(['id','name','is_on'])->orderBy('is_on','desc')->get();

        return view('gate.returns.create',compact('menuCode','vendors'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(empty($request->return_date)){
            Session::put('error','退貨日期不得為空值');
            return redirect()->back();
        }
        if(!empty($request->data) && count($request->data) > 0){
            $data = $request->data;
            $chk = 0;
            for($i=0;$i<count($data);$i++){
                if(empty($data[$i]['price'])){
                    $chk++;
                }
            }
            if($chk == 0){
                ReturnDiscountJob::dispatchNow($request);
            }else{
                Session::put('error','金額不得為空值。');
                return redirect()->back();
            }
        }
        return redirect()->route('gate.returnDiscounts.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $menuCode = 'M28S3';
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $returnDiscountTable = env('DB_DATABASE').'.'.(new ReturnDiscountDB)->getTable();
        $return = ReturnDiscountDB::with('items','items.packages')
            ->join($vendorTable,$vendorTable.'.id',$returnDiscountTable.'.vendor_id')
            ->select([
                $returnDiscountTable.'.*',
                $vendorTable.'.name as vendor_name',
            ])->findOrFail($id);

        return view('gate.returns.show',compact('menuCode','return'));
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
        $returnDiscountTable = env('DB_DATABASE').'.'.(new ReturnDiscountDB)->getTable();
        $returnDiscountItemTable = env('DB_DATABASE').'.'.(new ReturnDiscountItemDB)->getTable();
        $returnDiscountItemPackageTable = env('DB_DATABASE').'.'.(new ReturnDiscountItemPackageDB)->getTable();

        $returnData = ReturnDiscountDB::findOrFail($id);
        $erpReturn = ErpPURTIDB::findOrFail($returnData->erp_return_discount_no);
        $erpVendor = ErpVendorDB::find('A'.str_pad($returnData->vendor_id,5,'0',STR_PAD_LEFT));
        $diffTax = $diffQty = $diffPrice = 0;
        if ($erpReturn->TI013 == 'N') {
            if(!empty($request->items)){
                foreach ($request->items as $it) {
                    if($it['qty'] > 0){
                        $returnItem = ReturnDiscountItemDB::with('packages')->find($it['id']);
                        $purchaseItem = PurchaseOrderItemDB::find($returnItem->poi_id);
                        if($returnData->type == 'A351'){
                            //計算進貨數量及退貨數量
                            $newReturnQty = $remindQty = $returnQuantity = $stockinQuantity = 0;
                            if(count($returnItem->packages) > 0){
                                foreach($returnItem->packages as $package){
                                    $useQty = $package->quantity / $returnItem->quantity;
                                    $stockins = StockinItemSingleDB::where([['purchase_no',$package->purchase_no],['poi_id',$package->poi_id],['poip_id',$package->poip_id]])->get();
                                    if(count($stockins) > 0 ){
                                        foreach($stockins as $stockin){
                                            $stockinQuantity += $stockin->stockin_quantity;
                                        }
                                    }
                                    $returns = ReturnDiscountItemPackageDB::join($returnDiscountTable,$returnDiscountTable.'.return_discount_no',$returnDiscountItemPackageTable.'.return_discount_no')
                                    ->where($returnDiscountTable.'.is_del',0) //排除掉被取消的退貨
                                    ->where([[$returnDiscountItemPackageTable.'.purchase_no',$package->purchase_no],[$returnDiscountItemPackageTable.'.poi_id',$package->poi_id],[$returnDiscountItemPackageTable.'.poip_id',$package->poip_id]])->get();
                                    if(count($returns) > 0){
                                        $allReturnQuantity = 0;
                                        foreach($returns as $return){
                                                $allReturnQuantity += $return->quantity;
                                            }
                                        $returnQuantity = $allReturnQuantity - $package->quantity;
                                    }
                                }
                                $remindQty = $stockinQuantity - $returnQuantity;
                                $newReturnQty = $it['qty'] * $useQty;
                            }else{
                                $stockins = StockinItemSingleDB::where([['purchase_no',$returnItem->purchase_no],['poi_id',$returnItem->poi_id],['poip_id',null]])->get();
                                if(count($stockins) > 0){
                                    foreach($stockins as $stockin){
                                        $stockinQuantity += $stockin->stockin_quantity;
                                    }
                                }
                                $returns = ReturnDiscountItemDB::join($returnDiscountTable,$returnDiscountTable.'.return_discount_no',$returnDiscountItemTable.'.return_discount_no')
                                ->where($returnDiscountTable.'.is_del',0) //排除掉被取消的退貨
                                ->where([[$returnDiscountItemTable.'.purchase_no',$returnItem->purchase_no],[$returnDiscountItemTable.'.poi_id',$returnItem->poi_id]])->get();
                                if(count($returns) > 0){
                                    $allReturnQuantity = 0;
                                    foreach($returns as $return){
                                            $allReturnQuantity += $return->quantity;
                                        }
                                    $returnQuantity = $allReturnQuantity - $returnItem->quantity;
                                }
                                $remindQty = $stockinQuantity - $returnQuantity;
                                $newReturnQty = $it['qty'];
                            }
                        }
                        if($returnData->type != 'A351' || ($returnData->type == 'A351' && $newReturnQty <= $remindQty)){
                            if (!empty($it['qty']) && $it['qty'] != $returnItem->quantity) {
                                if(count($returnItem->packages) > 0){
                                    foreach($returnItem->packages as $package){
                                        $useQty = $package->quantity / $returnItem->quantity;
                                        if($erpVendor->MA044 == 1){
                                            $purchasePrice = $package->purchase_price;
                                        }else{
                                            $purchasePrice = $package->purchase_price / 1.05;
                                        }
                                        $TJ009 = $useQty * $it['qty'];
                                        $TJ010 = $TJ009 * $purchasePrice;
                                        if($erpVendor->MA044 == 1){
                                            $TJ030 = $TJ010 / 1.05;
                                            $TJ031 = $TJ030 * 0.05;
                                        }else{
                                            $TJ030 = $TJ010;
                                            $TJ031 = $TJ030 * 0.05;
                                        }
                                        //更新ERP商品資料
                                        ErpPURTJDB::where([['TJ001',$erpReturn->TI001],['TJ002',$package->erp_return_discount_no],['TJ003',$package->erp_return_discount_sno]])->update([
                                            'TJ009' => $TJ009,
                                            'TJ010' => round($TJ010,0),
                                            'TJ030' => round($TJ030,0),
                                            'TJ031' => round($TJ031,0),
                                            'TJ032' => round($TJ030,0),
                                            'TJ033' => round($TJ031,0),
                                            'TJ034' => $TJ009,
                                        ]);
                                        //更新中繼資料
                                        $package->update(['quantity' => $useQty * $it['qty']]);
                                        //重新計算整張退貨單資料
                                        $TI011 = $TI015 = $TI022 = $TI028 = $TI029 = 0;
                                        $erpReturnItems = ErpPURTJDB::where([['TJ001',$erpReturn->TI001],['TJ002',$package->erp_return_discount_no]])->get();
                                        foreach($erpReturnItems as $erpReturnItem){
                                            $TI011 += $erpReturnItem->TJ030; //退貨金額 (未稅)
                                            $TI015 += $erpReturnItem->TJ031; //退貨稅額
                                            $TI022 += $erpReturnItem->TJ009; //數量合計
                                        }
                                        $erpReturn->update([
                                            'TI011' => round($TI011,0),
                                            'TI015' => round($TI015,0),
                                            'TI022' => $TI022,
                                            'TI028' => round($TI011,0),
                                            'TI029' => round($TI015,0),
                                        ]);
                                        $returnData->update([
                                            'quantity' => $TI022,
                                            'amount' => round($TI011,0),
                                            'tax' => round($TI015,0),
                                        ]);
                                    }
                                }else{
                                    $purchasePrice = $item->purchase_price;
                                    $TJ009 = $it['qty'];
                                    $TJ010 = $TJ009 * $purchasePrice;
                                    if($erpVendor->MA044 == 1){
                                        $TJ030 = $TJ010 / 1.05;
                                        $TJ031 = $TJ030 * 0.05;
                                    }else{
                                        $TJ030 = $TJ010;
                                        $TJ031 = $TJ030 * 0.05;
                                    }
                                    //更新ERP商品資料
                                    ErpPURTJDB::where([['TJ001',$erpReturn->TI001],['TJ002',$returnItem->erp_return_discount_no],['TJ003',$returnItem->erp_return_discount_sno]])->update([
                                        'TJ009' => $TJ009,
                                        'TJ010' => round($TJ010,0),
                                        'TJ030' => round($TJ030,0),
                                        'TJ031' => round($TJ031,0),
                                        'TJ032' => round($TJ030,0),
                                        'TJ033' => round($TJ031,0),
                                        'TJ034' => $TJ009,
                                    ]);
                                    //重新計算整張退貨單資料
                                    $TI011 = $TI015 = $TI022 = $TI028 = $TI029 = 0;
                                    $erpReturnItems = ErpPURTJDB::where([['TJ001',$erpReturn->TI001],['TJ002',$returnItem->erp_return_discount_no]])->get();
                                    foreach($erpReturnItems as $erpReturnItem){
                                        $TI011 += $erpReturnItem->TJ030; //退貨金額 (未稅)
                                        $TI015 += $erpReturnItem->TJ031; //退貨稅額
                                        $TI022 += $erpReturnItem->TJ009; //數量合計
                                    }
                                    $erpReturn->update([
                                        'TI011' => round($TI011,0),
                                        'TI015' => round($TI015,0),
                                        'TI022' => $TI022,
                                        'TI028' => round($TI011,0),
                                        'TI029' => round($TI015,0),
                                    ]);
                                    $returnData->update([
                                        'quantity' => $TI022,
                                        'amount' => round($TI011,0),
                                        'tax' => round($TI015,0),
                                    ]);
                                }
                                //更新中繼資料
                                $returnItem->update(['quantity' => $it['qty']]);
                            }
                        }else{
                            Session::put('error','退貨數量不可大於進貨數量。');
                            return redirect()->back();
                        }
                    }else{
                        Session::put('error','修改退貨數量不可為0。');
                        return redirect()->back();
                    }
                }
            }
            if(!empty($request->price)){
                $chk = 0;
                foreach ($request->price as $p) {
                    if(floor($p['price']) != $p['price'] || $p['price'] == null){
                        $chk++;
                    }
                }
                if($chk > 0){
                    Session::put('error','金額不得為空值或有小數點');
                    return redirect()->back();
                }
                foreach ($request->price as $p) {
                    $returnItem = ReturnDiscountItemDB::with('packages')->find($p['id']);
                    if (!empty($p['price']) && $p['price'] != $returnItem->purchase_price) {
                        if(count($returnItem->packages) > 0){
                            $radio = $p['price'] / $returnItem->purchase_price;
                            foreach($returnItem->packages as $package){
                                $newPrice = $package->purchase_price * $radio;
                                $erpReturnItem = ErpPURTJDB::where([['TJ001',$erpReturn->TI001],['TJ002',$erpReturn->TI002],['TJ003',$package->erp_return_discount_sno]])->first();
                                $diffPrice = $erpReturnItem->TJ010 - $package->quantity * $newPrice;
                                $diffTax = $erpReturnItem->TJ031 - ($package->quantity * $newPrice * 0.05);
                                $erpReturnItem->update([
                                    'TJ008' => $newPrice,
                                    'TJ010' => $newPrice * $erpReturnItem->TJ009,
                                    'TJ030' => $newPrice * $erpReturnItem->TJ009,
                                    'TJ031' => $newPrice * $erpReturnItem->TJ009 * 0.05,
                                    'TJ032' => $newPrice * $erpReturnItem->TJ009,
                                    'TJ033' => $newPrice * $erpReturnItem->TJ009 * 0.05,
                                ]);
                                $package->update(['purchase_price' => $newPrice]);
                            }
                        }else{
                            $erpReturnItem = ErpPURTJDB::where([['TJ001',$erpReturn->TI001],['TJ002',$erpReturn->TI002],['TJ003',$returnItem->erp_return_discount_sno]])->first();
                            $newPriceWithTax = $p['price'];
                            if($erpVendor->MA044 != 1){
                                $newPrice = $newPriceWithTax / 1.05;
                                $tax = $newPrice * 0.05;
                            }else{
                                $newPrice = $newPriceWithTax;
                                $tax = $newPrice / 1.05 * 0.05;
                            }
                            ErpPURTJDB::where([['TJ001',$erpReturn->TI001],['TJ002',$erpReturn->TI002],['TJ003',$returnItem->erp_return_discount_sno]])->update([
                                'TJ008' => round($newPrice,2), //單價
                                'TJ010' => round($newPriceWithTax,0), //金額
                                'TJ030' => round($newPrice / 1.05,0), //原幣未稅金額
                                'TJ031' => round($tax,0), //原幣稅額
                                'TJ032' => round($newPrice / 1.05,0), //本幣未稅金額
                                'TJ033' => round($tax,0) //本幣稅額
                            ]);
                        }
                        $returnItem->update(['purchase_price' => $p['price']]);
                    }
                }
                //改用直接將資料拉出來重新計算
                $erpReturnItems = ErpPURTJDB::where([['TJ001',$erpReturn->TI001],['TJ002',$erpReturn->TI002]])->get();
                $TJ030 = $TJ031 = 0;
                foreach($erpReturnItems as $item){
                    $TJ030 += $item->TJ030;
                    $TJ031 += $item->TJ031;
                }
                ErpPURTIDB::findOrFail($returnData->erp_return_discount_no)->update([
                    'TI011' => $TJ030, //原幣退貨金額
                    'TI015' => $TJ031, //原幣退貨稅額
                    'TI028' => $TJ030, //本幣退貨金額
                    'TI029' => $TJ031, //本幣退貨稅額
                ]);
                $returnData->update([
                    'amount' => round($TJ030,0),
                    'tax' => round($TJ031,0),
                ]);
            }
            if(isset($request->return_date) && !empty($request->return_date)){
                $returnData->update(['return_date' => $request->return_date, 'memo' => $request->memo]);
                $erpReturn->update(['TI003' => str_replace('-','',$request->return_date)]);
            }else{
                Session::put('error','退貨日期不可為空白');
            }
        }else{
            Session::put('error','該折抵/退貨單已於頂新系統中鎖定，若需要修改請先至鼎新系統將其解除鎖定。');
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
            $return = ReturnDiscountDB::findOrFail($request->id);
            $erpReturn = ErpPURTIDB::findOrFail($return->erp_return_discount_no);
            if($erpReturn->TI013 == 'N'){
                $erpReturnItems = ErpPURTJDB::where([['TJ001',$erpReturn->TI001],['TJ002',$erpReturn->TI002]])->update(['TJ013' => 'V']);
                $erpReturn->update(['TI013' => 'V']);
                $return->update(['is_del' => 1]);
            }
        }
        return redirect()->back();
    }

    public function getProducts(Request $request)
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $products = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->where('sku','like','%EC%'); //只選單品

        if($request->vendor){
            $products = $products->where($productTable.'.vendor_id',$request->vendor)
            ->whereNotNull($productTable.'.pass_time');
        }
        if($request->ids){
            $products = $products->whereIn($productModelTable.'.id',$request->ids);
        }

        $products = $products->select([
            $productModelTable.'.id',
            DB::raw("CONCAT($productModelTable.digiwin_no,' ',$vendorTable.name,' ',$productTable.name,'-',$productModelTable.name,' ',$productModelTable.sku) as name"),
        ])->distinct()->orderBy($productModelTable.'.digiwin_no','desc')->get();

        if(count($products) > 0){
            return $products;
        }else{
            return null;
        }
    }

}
