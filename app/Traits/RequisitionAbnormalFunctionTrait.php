<?php

namespace App\Traits;

use App\Models\RequisitionAbnormal as RequisitionAbnormalDB;

trait RequisitionAbnormalFunctionTrait
{
    protected function getRequisitionAbnormalData($request, $type = null, $name = null)
    {
        $requisitionAbnormalTable = env('DB_DATABASE').'.'.(new RequisitionAbnormalDB)->getTable();
        $abnormals = new RequisitionAbnormalDB;

        if(isset($request['id'])){ //指定選擇的訂單
            $abnormals = $abnormals->whereIn($requisitionAbnormalTable.'.id',$request['id']);
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
        !empty($gtin13) ? $abnormals = $abnormals->where($requisitionAbnormalTable.'.gtin13', 'like', "%$gtin13%") : '';
        !empty($product_name) ? $abnormals = $abnormals->where($requisitionAbnormalTable.'.product_name', 'like', "%$product_name%") : '';
        !empty($stockin_date) ? $abnormals = $abnormals->where($requisitionAbnormalTable.'.stockin_date', '>=', $stockin_date) : '';
        !empty($stockin_date_end) ? $abnormals = $abnormals->where($requisitionAbnormalTable.'.stockin_date', '<=', $stockin_date_end) : '';
        !empty($expiry_date) ? $abnormals = $abnormals->where($requisitionAbnormalTable.'.expiry_date', '>=', $expiry_date) : '';
        !empty($expiry_date_end) ? $abnormals = $abnormals->where($requisitionAbnormalTable.'.expiry_date', '<=', $expiry_date_end) : '';

        !isset($list) ? $list = 50 : '';

        if($type == 'index'){
            $abnormals = $abnormals->orderBy($requisitionAbnormalTable.'.is_chk','asc')->paginate($list);
        }else{
            $abnormals = $abnormals->orderBy($requisitionAbnormalTable.'.is_chk','asc')->get();
        }
        return $abnormals;
    }
}
