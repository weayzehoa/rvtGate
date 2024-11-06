<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderLog as OrderLogDB;
use App\Imports\OrderMemoImport;

class OrderMemoFileImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
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
        $error = [];
        $param = $this->param;
        $file = $param->file('filename');
        $importData = Excel::toArray(new OrderMemoImport($param), $file);
        $data = $importData[0];
        if($this->chkData($importData[0]) == true){
            for($i=0;$i<count($data);$i++){
                $order = [];
                $logData = null;
                $orderNo = $data[$i][0];
                $partnerNo = $data[$i][1];
                $type = $data[$i][2];
                $memo = $data[$i][3];
                if(!empty($orderNo)){
                    $order = OrderDB::where('order_number', $orderNo)->first();
                    empty($order) ? $error[] = $orderNo : '';
                }elseif(!empty($partnerNo)){
                    $order = OrderDB::where('partner_order_number',$partnerNo)->first();
                    empty($order) ? $error[] = $partnerNo : '';
                }
                if(!empty($order)){
                    if($type == '附加'){
                        $order->update(['admin_memo' => $order->admin_memo.' '.$memo ]);
                        $logData = $order->admin_memo;
                    }elseif($type == '取代'){
                        $order->update(['admin_memo' => $memo]);
                        $logData = $memo;
                    }
                    $log = OrderLogDB::create([
                        'column_name' => 'admin_memo',
                        'order_id' => $order->id,
                        'log' => $logData,
                        'editor' => auth('gate')->user()->id,
                    ]);
                }
            }
        }
        if(count($error) > 0){
            return $error;
        }else{
            return null;
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
