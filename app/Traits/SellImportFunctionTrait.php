<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\SellImport as SellImportDB;
use DB;

trait SellImportFunctionTrait
{
    protected function getSellImportData($request, $type = null, $name = null)
    {
        $sellImportTable = env('DB_DATABASE').'.'.(new SellImportDB)->getTable();

        $sellImports = new SellImportDB;

        if(!empty($name)){
            $sellImports = $sellImports->where('type',$name);
        }

        if(isset($request['id'])){ //指定選擇的訂單
            $sellImports = $sellImports->whereIn($sellImportTable.'.id',$request['id']);
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
        isset($status) ?  $status == 0 ? $sellImports = $sellImports->where($sellImportTable.'.status',0) : $sellImports = $sellImports->whereIn($sellImportTable.'.status', explode(',',$status)) : '';
        !empty($digiwin_no) ? $sellImports = $sellImports->where($sellImportTable.'.digiwin_no', 'like', "%$digiwin_no%") : '';
        !empty($gtin13) ? $sellImports = $sellImports->where($sellImportTable.'.gtin13', 'like', "%$gtin13%") : '';
        !empty($shipping_number) ? $sellImports = $sellImports->where($sellImportTable.'.shipping_number', 'like', "%$shipping_number%") : '';
        !empty($product_name) ? $sellImports = $sellImports->where($sellImportTable.'.product_name', 'like', "%$product_name%") : '';
        !empty($sell_no) ? $sellImports = $sellImports->where($sellImportTable.'.sell_no', 'like', "%$sell_no%") : '';
        !empty($order_number) ? $sellImports = $sellImports->where($sellImportTable.'.order_number', 'like', "%$order_number%") : '';
        !empty($sell_date) ? $sellImports = $sellImports->where($sellImportTable.'.sell_date', $sell_date) : '';

        !isset($list) ? $list = 50 : '';

        if($type == 'index'){
            $sellImports = $sellImports->orderBy('status','asc')->orderBy('created_at','desc')->paginate($list);
        }else{
            $sellImports = $sellImports->orderBy('status','asc')->orderBy('created_at','desc')->get();
        }
        return $sellImports;
    }
}
