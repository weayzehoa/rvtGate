<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\Sell as SellDB;
use App\Models\SellItemSingle as SellItemSingleDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use DB;

trait SellFunctionTrait
{
    protected function getSellData($request, $type = null, $name = null)
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellTable = env('DB_DATABASE').'.'.(new SellDB)->getTable();
        $sellItemSingleTable = env('DB_DATABASE').'.'.(new SellItemSingleDB)->getTable();
        $sells = SellDB::join($orderTable,$orderTable.'.id',$sellTable.'.order_id')
            ->with('items','shipItems')
            ->where($sellTable.'.is_del',0);

        if(isset($request['id'])){ //指定選擇的訂單
            $sells = $sells->whereIn($purchaseOrderTable.'.id',$request['id']);
        }elseif(isset($request['con'])){ //by條件
            //將進來的資料作參數轉換
            foreach ($request['con'] as $key => $value) {
                $$key = $value;
            }
        }else{
            //將進來的資料作參數轉換
            foreach ($request->all() as $key => $value) {
                $$key = $value;
            }
        }

        //查詢參數
        !empty($sell_no) ? $sells = $sells->where($sellTable.'.sell_no', 'like', "%$sell_no%") : '';
        !empty($erp_sell_no) ? $sells = $sells->where($sellTable.'.erp_sell_no', 'like', "%$erp_sell_no%") : '';
        !empty($order_number) ? $sells = $sells->where($sellTable.'.order_number', 'like', "%$order_number%") : '';
        !empty($erp_order_number) ? $sells = $sells->where($sellTable.'.erp_order_number', 'like', "%$erp_order_number%") : '';
        !empty($sell_date) ? $sells = $sells->where($sellTable.'.sell_date', '>=', $sell_date) : '';
        !empty($sell_date_end) ? $sells = $sells->where($sellTable.'.sell_date', '<=', $sell_date_end) : '';

        if(!empty($vendor_name) || !empty($product_name) || !empty($sku) || !empty($digiwin_no) || !empty($express_way) || !empty($express_no)){
            $sellNos = SellItemSingleDB::join($productModelTable,$productModelTable.'.id',$sellItemSingleTable.'.product_model_id')
                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id');
            !empty($sku) ? $sellNos = $sellNos->where($productModelTable.'.sku','like',"%$sku%") : '';
            !empty($express_way) ? $sellNos = $sellNos->where($sellItemSingleTable.'.express_way',$express_way) : '';
            !empty($express_no) ? $sellNos = $sellNos->where($sellItemSingleTable.'.express_no','like',"%$express_no%") : '';
            !empty($digiwin_no) ? $sellNos = $sellNos->where($productModelTable.'.digiwin_no','like',"%$digiwin_no%") : '';
            !empty($vendor_name) ? $sellNos = $sellNos->where($vendorTable.'.name','like',"%$vendor_name%") : '';
            !empty($product_name) ? $sellNos = $sellNos->where($productTable.'.name','like',"%$product_name%") : '';
            $sellNos = $sellNos->select('sell_no')->get()->pluck('sell_no')->all();
            $sellNos = array_unique($sellNos);
            sort($sellNos);
            $sells = $sells->whereIn('sell_no',$sellNos);
        }

        if(!empty($pay_method) && $pay_method !='全部'){
            $payMethods = $digiwinPaymentIds = [];
            $payMethod = explode(',', $pay_method);
            for($i=0;$i<count($payMethod);$i++){
                if($payMethod[$i] == '台灣蝦皮'){
                    $digiwinPaymentIds[] = '010';
                }elseif($payMethod[$i] == '新加坡蝦皮'){
                    $digiwinPaymentIds[] = '011';
                }else{
                    $payMethods[] = $payMethod[$i];
                }
            }
            $sells = $sells->where(function($query)use($orderTable,$digiwinPaymentIds,$payMethods){
                if(count($digiwinPaymentIds) > 0){
                    $query = $query->whereIn($orderTable.'.digiwin_payment_id', $digiwinPaymentIds);
                    if(count($payMethods) > 0){
                        $query = $query->orWhereIn($orderTable.'.pay_method', $payMethods);
                    }
                }elseif(count($payMethods) > 0){
                    $query = $query->whereIn($orderTable.'.pay_method', $payMethods);
                }
            });
        }

        if(!empty($is_invoice)){
            if($is_invoice == 1){
                $sells = $sells->where(function($query)use($orderTable){
                    $query = $query->where($orderTable.'.is_invoice_no','!=','')->orWhere($orderTable.'.is_invoice_no','!=',null)->orWhereNotNull($orderTable.'.is_invoice_no');
                })->where(function($query)use($orderTable){
                    $query = $query->where($orderTable.'.invoice_time','!=','')->orWhere($orderTable.'.invoice_time','!=',null)->orWhereNotNull($orderTable.'.invoice_time');
                });
            }
            if($is_invoice == 'X'){
                $sells = $sells->where(function($query)use($orderTable){
                    $query = $query->where($orderTable.'.is_invoice_no','')->orWhere($orderTable.'.is_invoice_no',null)->orWhereNull($orderTable.'.is_invoice_no');
                })->where(function($query)use($orderTable){
                    $query = $query->where($orderTable.'.invoice_time','')->orWhere($orderTable.'.invoice_time',null)->orWhereNull($orderTable.'.invoice_time');
                });
            }
        }

        !isset($list) ? $list = 50 : '';

        if($type == 'index'){
            $sells = $sells->addSelect([
                $sellTable.'.*',
                $orderTable.'.is_invoice_no',
            ])->orderBy('created_at','desc')->paginate($list);
        }else{
            $sells = $sells->orderBy('created_at','desc')->get();
        }

        return $sells;
    }
}
