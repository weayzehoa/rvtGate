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
use App\Models\iCarryOrder as OrderDB;

use App\Models\Statement as StatementDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\ReturnDiscount as ReturnDiscountDB;
use App\Models\ReturnDiscountItem as ReturnDiscountItemDB;
use DB;
use Carbon\Carbon;

trait StatementFunctionTrait
{
    protected function getStatementData($request, $type = null, $name = null)
    {
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $statementTable = env('DB_DATABASE').'.'.(new StatementDB)->getTable();
        $statements = StatementDB::join($vendorTable,$vendorTable.'.id',$statementTable.'.vendor_id')->where('is_del',0);

        if(isset($request['id'])){ //指定選擇的訂單
            $statements = $statements->whereIn($statementTable.'.id',$request['id']);
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
        isset($start_date) && $start_date ? $statements = $statements->where($statementTable.'.start_date', '>=' , $start_date) : '';
        isset($end_date) && $end_date ? $statements = $statements->where($statementTable.'.end_date', '<=' , $end_date) : '';
        isset($statement_no) && $statement_no ? $statements = $statements->where($statementTable.'.statement_no', $statement_no) : '';
        isset($VAT_number) && $VAT_number ? $statements = $statements->where($vendorTable.'.VAT_number', 'like', "%$VAT_number%") : '';
        isset($vendor_name) && $vendor_name ? $statements = $statements->where($vendorTable.'.name', 'like', "%$vendor_name%") : '';
        isset($payment_com) && $payment_com ? $statements = $statements->whereIn($vendorTable.'.id',explode(',',$payment_com)) : '';

        if(isset($invoice_date_not_fill) && $invoice_date_not_fill == 1){
            $invoice_date = '';
            $invoice_date_end = '';
            $statements = $statements->whereNull($statementTable.'.invoice_date');
        }

        !empty($invoice_date) ? $statements = $statements->where($statementTable.'.invoice_date', '>=', $invoice_date) : '';
        !empty($invoice_date_end) ? $statements = $statements->where($statementTable.'.invoice_date', '<=', $invoice_date_end) : '';

        if(isset($notice)){
            if($notice == 'Y'){
                $statements = $statements->whereNotNull('notice_time');
            }elseif($notice == 'N'){
                $statements = $statements->whereNull('notice_time');
            }
        }

        if(isset($return_discount_no) && $return_discount_no){
            $rdnIds = ReturnDiscountItemDB::where('return_discount_no','like',"%$return_discount_no%")->select('id')->get()->pluck('id')->all();
            if(count($rdnIds) > 0){
                $c = '';
                for($i=0;$i<count($rdnIds);$i++){
                    $c .= "FIND_IN_SET('$rdnIds[$i]',$statementTable.return_discount_ids) OR ";
                }
                $c = rtrim($c,' OR ');
                $statements = $statements->where(function($query)use($c){
                    $query->whereRaw($c);
                });
            }
        }

        if(isset($purchase_no) && $purchase_no){
            $poiIds = PurchaseOrderItemDB::where('purchase_no','like',"%$purchase_no%")->select('id')->get()->pluck('id')->all();
            if(count($poiIds) > 0){
                $c = '';
                for($i=0;$i<count($poiIds);$i++){
                    $c .= "FIND_IN_SET('$poiIds[$i]',$statementTable.purchase_item_ids) OR ";
                }
                $c = rtrim($c,' OR ');
                $statements = $statements->where(function($query)use($c){
                    $query->whereRaw($c);
                });
            }
        }

        if(isset($erp_purchase_no) && $erp_purchase_no){
            $purchaseNos = PurchaseOrderDB::where('erp_purchase_no','like',"%$erp_purchase_no%")->select('purchase_no')->get()->pluck('purchase_no')->all();
            if(count($purchaseNos) > 0){
                $ids = PurchaseOrderItemDB::whereIn('purchase_no',$purchaseNos)->select('id')->get()->pluck('id')->all();
                if(count($ids) > 0){
                    $c = '';
                    for($i=0;$i<count($ids);$i++){
                        $c .= "FIND_IN_SET('$ids[$i]',$statementTable.purchase_item_ids) OR ";
                    }
                    $c = rtrim($c,' OR ');
                    $statements = $statements->where(function($query)use($c){
                        $query->whereRaw($c);
                    });
                }
            }
        }

        if (!isset($list)) {
            $list = 50;
        }

        //選擇資料
        if($type == 'notice'){
            $statements = $statements->select([
                $statementTable.'.*',
                $vendorTable.'.company',
                $vendorTable.'.name as vendor_name',
                $vendorTable.'.email',
                $vendorTable.'.notify_email',
                $vendorTable.'.bill_email',
                $vendorTable.'.VAT_number',
            ]);
        }else{
            $statements = $statements->select([
                $statementTable.'.*',
                $vendorTable.'.name as vendor_name',
                $vendorTable.'.VAT_number',
            ]);
        }

        //找出最終資料
        if ($type == 'index') {
            $statements = $statements->orderBy($statementTable.'.id', 'desc')->paginate($list);
            foreach($statements as $statement){
                $statement->returnOrders = $returnDiscountIds = $returnDiscountNos = $statement->purchaseOrders =  $statement->returnDiscounts = [];
                if(!empty($statement->purchase_nos)){
                    $purchaseNos = explode(',',$statement->purchase_nos);
                    $temp = PurchaseOrderDB::whereIn('purchase_no',$purchaseNos)
                    ->select(['id','purchase_no'])->limit(10)->get();
                    if(count($temp) > 0){
                        $statement->purchaseOrders = $temp;
                    }
                }
                if(!empty($statement->return_discount_ids)){
                    $returnDiscountIds = explode(',',$statement->return_discount_ids);
                    $tmp = ReturnDiscountItemDB::whereIn('id',$returnDiscountIds)
                        ->select(['return_discount_no'])->groupBy('return_discount_no')->get();
                    foreach($tmp as $t){
                        $returnDiscountNos[] = $t->return_discount_no;
                    }
                    $temp = ReturnDiscountDB::whereIn('return_discount_no',$returnDiscountNos)
                    ->select(['id','return_discount_no'])->orderBy('return_discount_no','desc')->get();
                    if(count($temp) > 0){
                        $statement->returnDiscounts = $temp;
                    }
                }
                if(!empty($statement->return_order_ids)){
                    $returnOrderIds = explode(',',$statement->return_order_ids);
                    $temp = OrderDB::whereIn('id',$returnOrderIds)
                    ->select(['id','order_number'])->orderBy('order_number','desc')->get();
                    if(count($temp) > 0){
                        $statement->returnOrders = $temp;
                    }
                }
            }
        }else{
            $statements = $statements->orderBy($statementTable.'.id', 'desc')->get();
        }

        return $statements;
    }
}
