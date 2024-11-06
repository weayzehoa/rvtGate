<?php

namespace App\Http\Controllers\Gate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\VendorShippingFunctionTrait;
use App\Models\SellItemSingle as SellItemSingleDB;

class VendorShippingController extends Controller
{
    use VendorShippingFunctionTrait;

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
        $menuCode = 'M28S1';
        $appends = $compact = $shippings = [];

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

        $shippings = $this->getVendorShippingData(request(),'index');

        foreach($shippings as $shipping){
            $chkExpress = $chkItem = $chkShipping = $chkStockin = 0;
            $stockinFinishDate = [];
            $i = 0;
            foreach($shipping->items as $item){
                //直寄必須將入庫資料細分是否出貨
                if($item->direct_shipment == 1) {
                    if(strstr($item->sku, 'BOM')) {
                        if(count($item->express) > 0){
                            foreach($item->packages as $package){
                                if(count($package->stockins) > 0){
                                    $sells = SellItemSingleDB::where('order_number',$item->order_numbers)->where('product_model_id',$package->product_model_id)->get();
                                    if(count($sells) != 0){
                                        foreach($package->stockins as $stockin){
                                            $stockinFinishDate[] = $stockin->stockin_date;
                                        }
                                        $item->stockins = [1]; //確認出貨將 $item->stockins 設定為有資料
                                    }
                                }
                            }
                        }
                    }else{
                        $sells = SellItemSingleDB::where('order_number',$item->order_numbers)->where('product_model_id',$item->product_model_id)->get();
                        if(count($sells) == 0){
                            $item->stockins = [];
                        }
                        if(count($item->stockins) > 0){
                            foreach($item->stockins as $stockin){
                                $stockinFinishDate[] = $stockin->stockin_date;
                            }
                        }
                    }
                }else{
                    if(count($item->stockins) > 0){
                        foreach($item->stockins as $stockin){
                            $stockinFinishDate[] = $stockin->stockin_date;
                        }
                    }
                }
                $item->is_del == 0 ? $i++ : ''; //未被取消的item數量
                count($item->stockins) > 0 ? $chkStockin++ : ''; //有入庫的item數量
            }
            if(count($stockinFinishDate) > 0){
                if($shipping->status != 4 && $i > 0 && $i == $chkStockin){
                    $shipping->update(['status' => 4, 'stockin_finish_date' => max($stockinFinishDate)]);
                }
            }
        }
        $compact = array_merge($compact, ['menuCode','shippings','appends']);
        return view('gate.purchases.vendorShipping', compact($compact));
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
}
