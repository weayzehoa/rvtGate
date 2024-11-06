<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\iCarryTicket as TicketDB;
use DB;

use App\Traits\TicketFunctionTrait;
use App\Traits\ACpayTicketFunctionTrait;

class CheckTicketStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,TicketFunctionTrait,ACpayTicketFunctionTrait;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //使用票券號碼
        $key = env('TICKET_ENCRYPT_KEY');
        // $ticketData = TicketDB::whereIn('status',[0,1])
        // ->select([
        //     'platform_no',
        //     DB::raw("GROUP_CONCAT(AES_DECRYPT(ticket_no,'$key')) as ticketNos"),
        // ])->groupBy('platform_no')->get();
        // if(count($ticketData) > 0){
        //     foreach($ticketData as $data){
        //         $platformNo = $data->platform_no;
        //         $ticketNos = explode(',',$data->ticketNos);
        //         $results = $this->checkTicketsStatus($platformNo,$ticketNos);
        //         if(!empty($results['rtnMsg']) && $results['rtnMsg'] == '成功'){
        //             if(count($results['tickets']) > 0){
        //                 $tickets = $results['tickets'];
        //                 for($i=0;$i<count($tickets);$i++){
        //                     if($tickets[$i]['status'] != 0){
        //                         $ticketNo = $tickets[$i]['ticketNo'];
        //                         $status = $tickets[$i]['status'];
        //                         !empty($tickets[$i]['writeOffTime']) ? $usedTime = date('Y-m-d H:i:s',strtotime($tickets[$i]['writeOffTime'])) : $usedTime = null;
        //                         $status == 0 ? $newStatus = null : ''; //未核銷
        //                         $status == 1 ? $newStatus = 9 : ''; //已核銷
        //                         $status == 2 ? $newStatus = -1 : ''; //已作廢
        //                         $ticket = TicketDB::whereRaw(" AES_DECRYPT(ticket_no,'$key') = '$ticketNo' ")->whereIn('status',[0,1])->first();
        //                         if(!empty($newStatus) && !empty($ticket)){
        //                             $ticket->update(['status' => $newStatus, 'used_time' => $usedTime ]);
        //                         }
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // }

        // //GROUP_CONCAT有字數長度限制故需改變方法.
        // $platformNos = TicketDB::whereIn('status',[0,1])
        // ->select([
        //     'platform_no',
        // ])->groupBy('platform_no')->get()->pluck('platform_no')->all();
        // for($c=0;$c<count($platformNos);$c++){
        //     $ticketNos = TicketDB::where('platform_no',$platformNos[$c])->whereIn('status',[0,1])
        //     ->select([
        //         DB::raw(" AES_DECRYPT(ticket_no,'$key') as ticket_no"),
        //     ])->get()->pluck('ticket_no')->all();
        //     $results = $this->checkTicketsStatus($platformNos[$c],$ticketNos);
        //     if(!empty($results['rtnMsg']) && $results['rtnMsg'] == '成功'){
        //         if(count($results['tickets']) > 0){
        //             $tickets = $results['tickets'];
        //             for($i=0;$i<count($tickets);$i++){
        //                 if($tickets[$i]['status'] != 0){
        //                     $ticketNo = $tickets[$i]['ticketNo'];
        //                     $status = $tickets[$i]['status'];
        //                     !empty($tickets[$i]['writeOffTime']) ? $usedTime = date('Y-m-d H:i:s',strtotime($tickets[$i]['writeOffTime'])) : $usedTime = null;
        //                     $status == 0 ? $newStatus = null : ''; //未核銷
        //                     $status == 1 ? $newStatus = 9 : ''; //已核銷
        //                     $status == 2 ? $newStatus = -1 : ''; //已作廢
        //                     $ticket = TicketDB::whereRaw(" AES_DECRYPT(ticket_no,'$key') = '$ticketNo' ")->whereIn('status',[0,1])->first();
        //                     if(!empty($newStatus) && !empty($ticket)){
        //                         $ticket->update(['status' => $newStatus, 'used_time' => $usedTime ]);
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // }

        //使用券商訂單號碼
        $ticketData = TicketDB::whereIn('status',[0,1])
        ->select([
            'platform_no',
            'ticket_order_no',
        ])->groupBy('platform_no','ticket_order_no')->get();
        foreach($ticketData as $data){
            $platformNo = $data->platform_no;
            $ticketOrderNo = $data->ticket_order_no;
            $results = $this->checkTicketsStatus($platformNo,null,$ticketOrderNo);
            if(!empty($results['rtnMsg']) && $results['rtnMsg'] == '成功'){
                if(count($results['tickets']) > 0){
                    $tickets = $results['tickets'];
                    for($i=0;$i<count($tickets);$i++){
                        if($tickets[$i]['status'] != 0){
                            $ticketNo = $tickets[$i]['ticketNo'];
                            $status = $tickets[$i]['status'];
                            !empty($tickets[$i]['writeOffTime']) ? $usedTime = date('Y-m-d H:i:s',strtotime($tickets[$i]['writeOffTime'])) : $usedTime = null;
                            $status == 0 ? $newStatus = null : ''; //未核銷
                            $status == 1 ? $newStatus = 9 : ''; //已核銷
                            $status == 2 ? $newStatus = -1 : ''; //已作廢
                            $ticket = TicketDB::whereRaw(" AES_DECRYPT(ticket_no,'$key') = '$ticketNo' ")->whereIn('status',[0,1])->first();
                            if(!empty($newStatus) && !empty($ticket)){
                                $ticket->update(['status' => $newStatus, 'used_time' => $usedTime ]);
                            }
                        }
                    }
                }
            }
        }
    }
}
