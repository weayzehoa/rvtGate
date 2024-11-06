<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ReturnDiscount as ReturnDiscountDB;
use App\Models\ReturnDiscountItem as ReturnDiscountItemDB;
use App\Models\ReturnDiscountItemPackage as ReturnDiscountItemPackageDB;
use DB;
use Carbon\Carbon;

trait ReturnDiscountFunctionTrait
{
    protected function getReturnDiscountData($request, $type = null, $name = null)
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $returnDiscountTable = env('DB_DATABASE').'.'.(new ReturnDiscountDB)->getTable();

        $returns = ReturnDiscountDB::with('items','items.packages');
        $returns = $returns->join($vendorTable,$vendorTable.'.id',$returnDiscountTable.'.vendor_id');

        if(isset($request['id'])){ //指定選擇的訂單
            $returns = $returns->whereIn('id',$request['id']);
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
        !empty($purchase_no) ? $returns = $returns->where($returnDiscountTable.'.purchase_no', 'like', "%$purchase_no%") : '';
        !empty($return_discount_no) ? $returns = $returns->where($returnDiscountTable.'.return_discount_no', 'like', "%$return_discount_no%") : '';
        !empty($vendor_name) ? $returns = $returns->whereIn($returnDiscountTable.'.vendor_id', VendorDB::where('name','like',"%$vendor_name%")->select('id')->get()) : '';
        !empty($created_at) ? $returns = $returns->where($returnDiscountTable.'.created_at', '>=', $created_at) : '';
        !empty($created_at_end) ? $returns = $returns->where($returnDiscountTable.'.created_at', '<=', $created_at_end) : '';
        !empty($return_date) ? $returns = $returns->where($returnDiscountTable.'.return_date', '>=', $return_date) : '';
        !empty($return_date_end) ? $returns = $returns->where($returnDiscountTable.'.return_date', '<=', $return_date_end) : '';
        !empty($is_del) ? $returns = $returns->where($returnDiscountTable.'.is_del', $is_del) : '';

        if(!empty($product_name)){
            $purchaseNos = ReturnDiscountItemDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->where($productTable.'.name','like',"%$product_name%")->select($purchaseOrderItemTable.'.purchase_no')->groupBy($purchaseOrderItemTable.'.purchase_no')->get();
            $returns = $returns->whereIn($returnDiscountTable.'.purchase_no',$purchaseNos);
        }

        !isset($list) ? $list = 50 : '';

        //找出最終資料
        $returns = $returns->select([
            $returnDiscountTable.'.*',
            $vendorTable.'.name as vendor_name',
        ]);

        if($type == 'index'){
            $returns = $returns->orderBy('created_at','desc')->paginate($list);
        }else{
            $returns = $returns->orderBy('created_at','desc')->get();
        }

        return $returns;
    }
}
