<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\Admin as AdminDB;
use App\Models\Sell as SellDB;
use App\Models\SellReturn as SellReturnDB;
use App\Models\SellReturnItem as SellReturnItemDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use DB;

trait SellReturnFunctionTrait
{
    protected function getSellReturnData($request, $type = null, $name = null)
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellReturnTable = env('DB_DATABASE').'.'.(new SellReturnDB)->getTable();
        $sellReturnItemTable = env('DB_DATABASE').'.'.(new SellReturnItemDB)->getTable();
        $returns = SellReturnDB::join($orderTable,$orderTable.'.id',$sellReturnTable.'.order_id')
            ->with('chkStockin','items')
            ->where($sellReturnTable.'.is_del',0);

        if(isset($request['id'])){ //指定選擇的訂單
            $returns = $returns->whereIn($purchaseOrderTable.'.id',$request['id']);
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
        !empty($return_no) ? $returns = $returns->where($sellReturnTable.'.return_no', 'like', "%$return_no%") : '';
        !empty($erp_return_no) ? $returns = $returns->where($sellReturnTable.'.erp_return_no', 'like', "%$erp_return_no%") : '';
        !empty($order_number) ? $returns = $returns->where($sellReturnTable.'.order_number', 'like', "%$order_number%") : '';
        !empty($return_date) ? $returns = $returns->where($sellReturnTable.'.return_date', '>=', $return_date) : '';
        !empty($return_date_end) ? $returns = $returns->where($sellReturnTable.'.return_date', '<=', $return_date_end) : '';
        if(isset($digiwin_no)) {
            $returns = $returns->whereIn($sellReturnTable.'.return_no', SellReturnItemDB::where('order_digiwin_no','like',"%$digiwin_no%")->orWhere('origin_digiwin_no','like',"%$digiwin_no%")->select('return_no')->groupBy('return_no'));
        }
        if(isset($vendor_name) || isset($product_name)){
            $returns = $returns->rightJoin($sellReturnItemTable,$sellReturnItemTable.'.return_no',$sellReturnTable.'.return_no');
            $returns = $returns->join($productModelTable.' as pp','pp.digiwin_no',$sellReturnItemTable.'.origin_digiwin_no');
            $returns = $returns->join($productModelTable.' as pm','pm.digiwin_no',$sellReturnItemTable.'.order_digiwin_no');
            isset($vendor_name) ? $vendorName = $vendor_name : $vendorName = null;
            isset($product_name) ? $productName = $product_name :  $productName = null;
            if(!empty($vendorName) || !empty($productName)){
                $returns = $returns->join($productTable,$productTable.'.id','pp.product_id');
                $returns = $returns->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id');
            }
            $returns = $returns->where(function($query)use($productTable,$vendorTable,$productModelTable,$sellReturnItemTable,$vendorName,$productName,$sku){
                if(!empty($productName)){
                    $query = $query->where($productTable.'.name','like',"%$productName%");
                }
                if(!empty($vendorName)){
                    $query = $query->where($vendorTable.'.name','like',"%$vendorName%");
                }
                if(!empty($sku)){
                    $query = $query->where('pp.sku','like',"%$sku%")->orWhere('pm.sku','like',"%$sku%");
                }
            });
            $returns = $returns->groupBy($sellReturnTable.'.return_no');
        }

        !isset($list) ? $list = 50 : '';

        $returns = $returns->select([
            $sellReturnTable.'.*',
        ]);

        if($type == 'index'){
            $returns = $returns->orderBy($sellReturnTable.'.created_at','desc')->paginate($list);
        }else{
            $returns = $returns->orderBy($sellReturnTable.'.created_at','desc')->get();
        }
        return $returns;
    }

    protected function getSellReturnItemData($request, $type = null, $name = null)
    {
        $adminTable = env('DB_DATABASE').'.'.(new AdminDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $sellReturnTable = env('DB_DATABASE').'.'.(new SellReturnDB)->getTable();
        $sellReturnItemTable = env('DB_DATABASE').'.'.(new SellReturnItemDB)->getTable();
        $returns = SellReturnItemDB::with('product')
            ->join($sellReturnTable,$sellReturnTable.'.return_no',$sellReturnItemTable.'.return_no')
            ->where([[$sellReturnTable.'.type','銷退'],[$sellReturnTable.'.is_del',0],[$sellReturnItemTable.'.is_del',0]])
            ->where(function($query)use($sellReturnItemTable){ //排除運費及跨境稅
                $query->where($sellReturnItemTable.'.origin_digiwin_no','!=','901001')
                ->where($sellReturnItemTable.'.origin_digiwin_no','!=','901002');
            });

        if(isset($request['id'])){ //指定選擇的訂單
            $returns = $returns->whereIn($purchaseOrderTable.'.id',$request['id']);
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
        !empty($return_no) ? $returns = $returns->where($sellReturnItemTable.'.return_no', 'like', "%$return_no%") : '';
        !empty($erp_return_no) ? $returns = $returns->where($sellReturnItemTable.'.erp_return_no', 'like', "%$erp_return_no%") : '';
        !empty($order_number) ? $returns = $returns->where($sellReturnItemTable.'.order_number', 'like', "%$order_number%") : '';
        !empty($return_date) ? $returns = $returns->where($sellReturnTable.'.return_date', '>=', $return_date) : '';
        !empty($return_date_end) ? $returns = $returns->where($sellReturnTable.'.return_date', '<=', $return_date_end) : '';
        !empty($erp_requisition_no) ? $returns = $returns->where($sellReturnItemTable.'.erp_requisition_no', 'like', "%$erp_requisition_no%") : '';
        isset($is_chk) ? $returns = $returns->where($sellReturnItemTable.'.is_chk', $is_chk) : '';
        isset($is_stockin) ? $returns = $returns->where($sellReturnItemTable.'.is_stockin', $is_stockin) : '';
        isset($is_confirm) ? $returns = $returns->where($sellReturnItemTable.'.is_confirm', $is_confirm) : '';

        if(isset($vendor_name) || isset($product_name) || isset($digiwin_no)){
            $returns = $returns->join($productModelTable,$productModelTable.'.digiwin_no',$sellReturnItemTable.'.origin_digiwin_no');
            isset($vendor_name) ? $vendorName = $vendor_name : $vendorName = null;
            isset($product_name) ? $productName = $product_name :  $productName = null;
            isset($digiwin_no) ? $digiwinNo = $digiwin_no : $digiwinNo = null;
            if(!empty($vendorName) || !empty($productName)){
                $returns = $returns->join($productTable,$productTable.'.id',$productModelTable.'.product_id');
                $returns = $returns->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id');
            }

            $returns = $returns->where(function($query)use($productTable,$vendorTable,$productModelTable,$sellReturnItemTable,$digiwinNo,$vendorName,$productName){
                if(!empty($productName)){
                    $query = $query->where($productTable.'.name','like',"%$productName%");
                }
                if(!empty($vendorName)){
                    $query = $query->where($vendorTable.'.name','like',"%$vendorName%");
                }
                if(!empty($digiwinNo)){
                    $query = $query->where($sellReturnItemTable.'.order_digiwin_no','like',"%$digiwinNo%")
                    ->orWhere($sellReturnItemTable.'.origin_digiwin_no','like',"%$digiwinNo%");
                }
            });
        }

        !isset($list) ? $list = 50 : '';

        $returns = $returns->select([
            $sellReturnItemTable.'.*',
            $sellReturnTable.'.type',
            $sellReturnTable.'.memo',
            $sellReturnTable.'.return_date',
            'return_admin_name' => AdminDB::whereColumn($sellReturnTable.'.return_admin_id',$adminTable.'.id')->select($adminTable.'.name')->limit(1),
            'stockin_admin_name' => AdminDB::whereColumn($sellReturnItemTable.'.stockin_admin_id',$adminTable.'.id')->select($adminTable.'.name')->limit(1),
            'admin_name' => AdminDB::whereColumn($sellReturnItemTable.'.admin_id',$adminTable.'.id')->select($adminTable.'.name')->limit(1),
        ]);

        if($type == 'index'){
            $returns = $returns->orderBy($sellReturnItemTable.'.is_stockin','asc')
            ->orderBy($sellReturnItemTable.'.is_chk','asc')
            ->orderBy($sellReturnItemTable.'.chk_date','desc')
            ->orderBy($sellReturnItemTable.'.expiry_date','asc')
            ->paginate($list);
        }else{
            $returns = $returns->orderBy($sellReturnItemTable.'.is_chk','asc')
            ->orderBy($sellReturnItemTable.'.is_stockin','asc')
            ->orderBy($sellReturnItemTable.'.origin_digiwin_no','asc')
            ->orderBy($sellReturnItemTable.'.expiry_date','asc')
            ->get();
        }
        foreach($returns as $return){
            $return->product_name = $return->product->product_name;
         }
        return $returns;
    }
}
