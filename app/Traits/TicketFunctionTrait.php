<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\iCarryTicket as TicketDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use DB;

trait TicketFunctionTrait
{
    protected function getTicketData($request = null,$type = null, $name = null)
    {
        $key = env('TICKET_ENCRYPT_KEY');
        $ticketTable = env('DB_ICARRY').'.'.(new TicketDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $tickets = TicketDB::with('purchase','order')
            ->join($productModelTable,$productModelTable.'.id',$ticketTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id');

        if(isset($request['id'])){ //指定選擇的訂單
            $type != 'show' && is_array($request['id']) ? $tickets = $tickets->whereIn($ticketTable.'.id',$request['id']) : '';
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
        isset($status) ? $tickets = $tickets->whereIn($ticketTable.'.status',explode(',',$status)) : $tickets = $tickets->whereIn($ticketTable.'.status',[-1,0,1,2,9]);
        isset($create_type) && $create_type ? $tickets = $tickets->where($ticketTable.'.create_type','like', "%$create_type%") : '';
        isset($ticket_no) && $ticket_no ? $tickets = $tickets->whereRaw(" AES_DECRYPT($ticketTable.ticket_no, '$key') like '%$ticket_no%' ") : '';
        isset($purchase_no) && $purchase_no ? $tickets = $tickets->where($ticketTable.'.purchase_no','like', "%$purchase_no%") : '';
        isset($order_number) && $order_number ? $tickets = $tickets->where($ticketTable.'.order_number','like', "%$order_number%") : '';
        isset($product_name) && $product_name ? $tickets = $tickets->where($ticketTable.'.product_name','like', "%$product_name%") : '';
        isset($vendor_name) && $vendor_name ? $tickets = $tickets->where($ticketTable.'.vendor_name','like', "%$vendor_name%") : '';
        isset($digiwin_no) && $digiwin_no ? $tickets = $tickets->where($ticketTable.'.digiwin_no','like', "%$digiwin_no%") : '';
        isset($used_time) && $used_time ? $tickets = $tickets->where($ticketTable.'.used_time','>=', $used_time) : '';
        isset($used_time_end) && $used_time_end ? $tickets = $tickets->where($ticketTable.'.used_time','<=', $used_time_end) : '';
        isset($create_time) && $create_time ? $tickets = $tickets->where($ticketTable.'.created_at','>=', $create_time) : '';
        isset($create_time_end) && $create_time_end ? $tickets = $tickets->where($ticketTable.'.created_at','<=', $create_time_end) : '';
        if (!isset($list)) {
            $list = 50;
        }

        if($type == 'settle'){
            $tickets = $tickets->whereNotNull($ticketTable.'.order_number');
            $tickets = $tickets->where($ticketTable.'.status',9);
            $tickets = $tickets->groupBy($ticketTable.'.order_id');
        }

        //選擇資料
        if($type == 'getInfo' || $type == 'show'){
            $tickets = $tickets->select([
                $ticketTable.'.id',
                $ticketTable.'.order_id',
                $ticketTable.'.platform_no',
                DB::raw("IF($ticketTable.ticket_no IS NULL,'',AES_DECRYPT($ticketTable.ticket_no,'$key')) as ticket_no"),
                DB::raw("IF($ticketTable.ticket_no IS NULL,'',CONCAT('********',SUBSTRING(AES_DECRYPT($ticketTable.ticket_no,'$key'),10))) as ticket_no_mask"),
            ]);
        }else{
            $tickets = $tickets->select([
                $ticketTable.'.*',
                $type == 'settle' || $type == 'export' || $type == 'getInfo' ? DB::raw("IF($ticketTable.ticket_no IS NULL,'',AES_DECRYPT($ticketTable.ticket_no,'$key')) as ticket_no") : DB::raw("IF($ticketTable.ticket_no IS NULL,'',CONCAT('********',SUBSTRING(AES_DECRYPT($ticketTable.ticket_no,'$key'),10))) as ticket_no"),
                $vendorTable.'.name as vendor_name',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
            ]);
        }

        if($type == 'index'){
            $tickets = $tickets->orderBy($ticketTable.'.id', 'desc')->paginate($list);
        }elseif($type == 'show' || $type == 'getInfo'){
            $tickets = $tickets->find($request['id']);
        }else{
            if($type == 'export'){
                $tickets = $tickets->orderBy($ticketTable.'.created_at', 'asc')->get();
            }else{
                $tickets = $tickets->orderBy($ticketTable.'.id', 'desc')->get();
            }
        }
        return $tickets;
    }
}
