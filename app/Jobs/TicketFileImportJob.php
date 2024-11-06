<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Imports\TicketImport;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryTicket as TicketDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use DB;

use App\Traits\ACpayTicketFunctionTrait;

class TicketFileImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,ACpayTicketFunctionTrait;
    protected $param;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $param = $this->param;
        if($param['test'] == true){
            $result['test'] = $param['test'];
            $result['type'] = $param['type'];
            $result['import_no'] = $param['import_no'];
            $result['admin_id'] = $param['admin_id'];
            $result['fail'] = 0;
            $result['success'] = 0;
            return $result;
        }
        if($param['cate'] == 'tickets'){
            $file = $param->file('filename');
            $result = Excel::toArray(new TicketImport($param), $file);
            $this->chkRows($result[0]) == false ? $result = 'rows error' : '';
            if($result == 'rows error'){
                return ['error' => "檔案內有欄位數錯誤， 欄位數應為 2 欄，請檢查檔案內容。"];
            }else{
                $importData = $result[0];
                if(count($importData) > 0){
                    $result = $this->import($importData);
                    $result['type'] = $param['type'];
                    $result['import_no'] = $param['import_no'];
                    $result['admin_id'] = $param['admin_id'];
                    return $result;
                }
            }
        }
        return ['error' => '你確定是票券資料匯入?'];
    }

    protected function import($items)
    {
        $result['fail'] = $result['success'] = 0;
        $result['failData'] = [];
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();

        if(count($items) > 0){
            $i = 1;
            foreach($items as $item){
                $rowNo = $i+1;
                $usedTime = $orderItem = $memo = null;
                $status = 0;
                if($this->chkData($item) == true){
                    $ticketNo = $item[0];
                    $partnerOrderNo = $item[1];
                    empty($ticketNo) ? $memo .= "票券號碼不存在。 " : '';
                    empty($partnerOrderNo) ? $memo .= "外渠訂單編號不存在。 " : '';
                    if(!empty($ticketNo) && !empty($partnerOrderNo)){
                        $key = env('TICKET_ENCRYPT_KEY');
                        $ticket = TicketDB::whereRaw(" ticket_no = AES_ENCRYPT('$ticketNo', '$key') ")->first();
                        if(empty($ticket)){
                            $memo .= "票券號碼 $ticketNo 不存在資料庫中。";
                        }else{
                            if(!empty($ticket->order_number)){
                                $memo .= "票券號碼 $ticketNo 已有對應訂單 $ticket->order_number 。";
                            }elseif($ticket->status == -1 || $ticket->status == 1){
                                $memo .= "票券號碼 $ticketNo 狀態錯誤。";
                            }else{
                                $order = OrderDB::with('itemData')->where('partner_order_number',$partnerOrderNo)->first();
                                $orderNumber = $orderId = $orderItemId = null;
                                if(!empty($order)){
                                    if($order->status == 1 || $order->status == 2){
                                        $status = $ticket->status == 0 ? 1 : $ticket->status;
                                        $orderId = $order->id;
                                        $orderNumber = $order->order_number;
                                        if(strtoupper($ticket->create_type) == strtoupper($order->create_type)){
                                            $needQty = $chkQty = 0;
                                            foreach($order->itemData as $item){
                                                if($item->digiwin_no == $ticket->digiwin_no){
                                                    $orderItemId = $item->id;
                                                    $needQty = $item->quantity;
                                                    $chkQty = $item->quantity - TicketDB::where('order_item_id',$item->id)->count();
                                                    break;
                                                }
                                            }
                                            if($chkQty <= 0){
                                                $memo .= "票券號碼 $ticketNo 超出訂單 $orderNumber 需求數量 $needQty 張。";
                                            }
                                        }else{
                                            $memo .= "票券 $ticket->create_type 使用渠道與訂單渠道 $order->create_type 不同。";
                                        }
                                    }else{
                                        $memo .= "訂單 ($partnerOrderNo) 已被取消或已出貨完成。";
                                    }
                                }else{
                                    $memo .= "$partnerOrderNo 訂單不存在。";
                                }
                            }
                        }
                    }
                    if(!empty($memo)){
                        $result['failData'][$i-1] = "第 $rowNo 列， $memo";
                        $result['fail']++;
                    }else{
                        $ticket->update([
                            'status' => $status,
                            'order_id' => $orderId,
                            'order_number' =>$orderNumber,
                            'order_item_id' => $orderItemId,
                            'partner_order_number' => $partnerOrderNo,
                        ]);
                        $result['success']++;
                    }
                }else{
                    $result['failData'][$i-1] = "第 $rowNo 列資料空白";
                    $result['fail']++;
                }
                $i++;
            }
        }
        count($result['failData']) > 0 ?  sort($result['failData']) : '';
        return $result;
    }

    protected function chkRows($items){
        $chk = 0;
        for($i=0;$i<count($items);$i++){
            if(count($items[$i]) != 2){
                $chk++;
            }
        }
        if($chk == 0){
            return true;
        }else{
            return false;
        }
    }

    private function chkData($result)
    {
        $count = count($result);
        $chk = 0;
        for($i=0;$i<count($result);$i++){
            empty($result[$i]) ? $chk++ : '';
        }
        if($chk != count($result)){ //表示有資料
            return true;
        }else{ //表示全部空值
            return false;
        }
    }
}
