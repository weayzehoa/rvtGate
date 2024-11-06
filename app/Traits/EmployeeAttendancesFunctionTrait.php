<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\EmployeeAttendance as EmployeeAttendanceDB;

use DB;

trait EmployeeAttendancesFunctionTrait
{
    protected function getEmployeeAttendances($request = null,$type = null, $name = null)
    {
        $attendances = EmployeeAttendanceDB::with('employee');

        if(isset($request['id'])){ //指定選擇的訂單
            $type != 'show' && is_array($request['id']) ? $attendances = $attendances->whereIn('id',$request['id']) : '';
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
        // isset($status) ? $attendances = $attendances->whereIn('status',explode(',',$status)) : $attendances = $attendances->whereIn('status',[-1,0,1,2,9]);
        // isset($create_type) && $create_type ? $attendances = $attendances->where('create_type','like', "%$create_type%") : '';
        // isset($ticket_no) && $ticket_no ? $attendances = $attendances->whereRaw(" AES_DECRYPT($ticketTable.ticket_no, '$key') like '%$ticket_no%' ") : '';
        // isset($purchase_no) && $purchase_no ? $attendances = $attendances->where('purchase_no','like', "%$purchase_no%") : '';
        // isset($order_number) && $order_number ? $attendances = $attendances->where('order_number','like', "%$order_number%") : '';
        // isset($product_name) && $product_name ? $attendances = $attendances->where('product_name','like', "%$product_name%") : '';
        // isset($vendor_name) && $vendor_name ? $attendances = $attendances->where('vendor_name','like', "%$vendor_name%") : '';
        // isset($digiwin_no) && $digiwin_no ? $attendances = $attendances->where('digiwin_no','like', "%$digiwin_no%") : '';
        // isset($used_time) && $used_time ? $attendances = $attendances->where('used_time','>=', $used_time) : '';
        // isset($used_time_end) && $used_time_end ? $attendances = $attendances->where('used_time','<=', $used_time_end) : '';
        // isset($create_time) && $create_time ? $attendances = $attendances->where('created_at','>=', $create_time) : '';
        // isset($create_time_end) && $create_time_end ? $attendances = $attendances->where('created_at','<=', $create_time_end) : '';

        if (!isset($list)) {
            $list = 50;
        }

        if($type == 'index'){
            $attendances = $attendances->orderBy('chk_time', 'desc')->paginate($list);
        }elseif($type == 'show' || $type == 'getInfo'){
            $attendances = $attendances->find($request['id']);
        }else{
            if($type == 'export'){
                $attendances = $attendances->orderBy('created_at', 'asc')->get();
            }else{
                $attendances = $attendances->orderBy('id', 'desc')->get();
            }
        }
        return $attendances;
    }
}
