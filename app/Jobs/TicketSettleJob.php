<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;


use App\Models\iCarryTicket as TicketDB;
use App\Models\SellImport as SellImportDB;
use DB;

use App\Traits\TicketFunctionTrait;
use App\Traits\ACpayTicketFunctionTrait;
use App\Jobs\SellImportJob;

class TicketSettleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,TicketFunctionTrait,ACpayTicketFunctionTrait;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $key = env('TICKET_ENCRYPT_KEY');
        request()->request->add(['nodata' => null]);
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        !empty(auth('gate')->user()) ? $adminId = auth('gate')->user()->id : $adminId = 0;
        $ticketData = $this->getTicketData(request(),'settle');
        if(count($ticketData) > 0){
            foreach($ticketData as $ticket){
                $orderId = $ticket->order_id;
                $tickets = TicketDB::where('order_id',$ticket->order_id)
                ->where('status',9)
                ->select([
                    '*',
                    DB::raw("IF(ticket_no IS NULL,'',AES_DECRYPT(ticket_no,'$key')) as ticket_no"),
                    DB::raw("DATE_FORMAT(updated_at,'%Y-%m-%d') as sell_date"),
                ])->get();
                if(count($tickets) > 0){
                    $ticketSettles = [];
                    $importNo = time().rand(0,99);
                    foreach($tickets as $ticket){
                        $ticketSettles[] = [
                            'platformNo' => $ticket->platform_no,
                            'ticketNo' => $ticket->ticket_no,
                        ];
                    }
                    if(count($ticketSettles) > 0){
                        $result = $this->ticketSettle($ticketSettles,$adminId);
                        if(!empty($result)){
                            if(isset($result['tickets']) && is_array($result['tickets'])){
                                if(strstr($result['rtnMsg'], '此票券已經結算過了') || ($result['rtnCode'] == 0 && count($result['tickets']) > 0)) {
                                    $aesStr = [];
                                    if(strstr($result['rtnMsg'],'此票券已經結算過了')){
                                        for($i=0;$i<count($ticketSettles);$i++){
                                            $tNo = $ticketSettles[$i]['ticketNo'];
                                            if(strstr($result['rtnMsg'],$tNo)){
                                                $cleanTicket = TicketDB::whereRaw(" ticket_no = AES_ENCRYPT('$tNo', '$key') ")->select(['*',DB::raw("IF(ticket_no IS NULL,'',AES_DECRYPT(ticket_no,'$key')) as ticket_no")])->first();
                                                if(!empty($cleanTicket)){
                                                    $cleanTicketNo = $cleanTicket->ticket_no;
                                                    $aesStr[] = "AES_ENCRYPT('$cleanTicketNo', '$key')";
                                                    $cleanTicket->update(['status' => 2]);
                                                }
                                            }
                                        }
                                    }else{
                                        $cleanTickets = $result['tickets'];
                                        for($i=0;$i<count($cleanTickets);$i++){
                                            if($cleanTickets[$i]['status'] == 1){
                                                $cleanTicketNo = $cleanTickets[$i]['ticketNo'];
                                                $cleanTicket = TicketDB::whereRaw(" ticket_no = AES_ENCRYPT('$cleanTicketNo', '$key') ")->first();
                                                if(!empty($cleanTicket)){
                                                    $aesStr[] = "AES_ENCRYPT('$cleanTicketNo', '$key')";
                                                    $cleanTicket->update(['status' => 2]);
                                                }
                                            }
                                        }
                                    }
                                    count($aesStr) > 1 ? $aesStr = array_unique($aesStr) : '';
                                    if(count($aesStr) > 0){
                                        $str = join(',',$aesStr);
                                        $getTickets = TicketDB::whereRaw(" ticket_no in ($str) ")
                                        ->where('status',2) //已結帳的才做出貨
                                        ->select([
                                            '*',
                                            DB::raw("DATE_FORMAT(updated_at,'%Y-%m-%d') as sell_date"),
                                        ])->get();
                                        if(count($getTickets) > 0){
                                            $importNo = time().rand(0,99);
                                            $sellImports = [];
                                            foreach($getTickets as $tt){
                                                $sellImports[] = [
                                                    'import_no' => $importNo,
                                                    'order_number' => $tt->order_number,
                                                    'product_name' => $tt->product_name,
                                                    'quantity' => 1,
                                                    'type' => 'warehouse',
                                                    'shipping_number' => '電子郵件',
                                                    'gtin13' => $tt->sku,
                                                    'sell_date' => $tt->sell_date,
                                                    'status' => 0,
                                                    'created_at' => date('Y-m-d H:i:s'),
                                                ];
                                            }
                                            if(count($sellImports) > 0){
                                                SellImportDB::insert($sellImports);
                                                $job['type'] = 'warehouse';
                                                $job['import_no'] = $importNo;
                                                $job['admin_id'] = !empty(auth('gate')->user()) ? auth('gate')->user()->id : 0;
                                                $result = SellImportJob::dispatchNow($job);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
