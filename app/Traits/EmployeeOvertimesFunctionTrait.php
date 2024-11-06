<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\EmployeeOvertime as EmployeeOvertimeDB;

use DB;

trait EmployeeOvertimesFunctionTrait
{
    protected function getEmployeeOvertimes($request = null,$type = null, $name = null)
    {
        $overtimes = EmployeeOvertimeDB::with('employee');

        if(isset($request['id'])){ //指定選擇的訂單
            $type != 'show' && is_array($request['id']) ? $overtimes = $overtimes->whereIn('id',$request['id']) : '';
        }elseif(isset($request['con'])){ //by條件
            //將進來的資料作參數轉換
            foreach ($request['con'] as $requestKeyName => $value) {
                $$requestKeyName = $value;
            }
        }else{
            //將進來的資料作參數轉換
            foreach ($request->all() as $requestKeyName => $value) {
                $$requestKeyName = $value;
            }
        }

        //查詢參數
        // isset($status) ? $overtimes = $overtimes->whereIn('status',explode(',',$status)) : $overtimes = $overtimes->whereIn('status',[-1,0,1,2,9]);
        // isset($create_type) && $create_type ? $overtimes = $overtimes->where('create_type','like', "%$create_type%") : '';
        // isset($ticket_no) && $ticket_no ? $overtimes = $overtimes->whereRaw(" AES_DECRYPT($ticketTable.ticket_no, '$key') like '%$ticket_no%' ") : '';
        // isset($purchase_no) && $purchase_no ? $overtimes = $overtimes->where('purchase_no','like', "%$purchase_no%") : '';
        // isset($order_number) && $order_number ? $overtimes = $overtimes->where('order_number','like', "%$order_number%") : '';
        // isset($product_name) && $product_name ? $overtimes = $overtimes->where('product_name','like', "%$product_name%") : '';
        // isset($vendor_name) && $vendor_name ? $overtimes = $overtimes->where('vendor_name','like', "%$vendor_name%") : '';
        // isset($digiwin_no) && $digiwin_no ? $overtimes = $overtimes->where('digiwin_no','like', "%$digiwin_no%") : '';
        // isset($used_time) && $used_time ? $overtimes = $overtimes->where('used_time','>=', $used_time) : '';
        // isset($used_time_end) && $used_time_end ? $overtimes = $overtimes->where('used_time','<=', $used_time_end) : '';
        // isset($create_time) && $create_time ? $overtimes = $overtimes->where('created_at','>=', $create_time) : '';
        // isset($create_time_end) && $create_time_end ? $overtimes = $overtimes->where('created_at','<=', $create_time_end) : '';

        if (!isset($list)) {
            $list = 50;
        }

        if($type == 'index'){
            $overtimes = $overtimes->orderBy('end_time', 'desc')->paginate($list);
        }elseif($type == 'show' || $type == 'getInfo'){
            $overtimes = $overtimes->find($request['id']);
        }else{
            if($type == 'export'){
                $overtimes = $overtimes->orderBy('created_at', 'asc')->get();
            }else{
                $overtimes = $overtimes->orderBy('id', 'desc')->get();
            }
        }
        return $overtimes;
    }
}
