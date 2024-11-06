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

class OrderShippingMemoFileImportJob implements ShouldQueue
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
                $orderNo = $data[$i][1];
                $expressNos = $data[$i][2];
                $expressWay = $data[$i][3];
                $expressNos = str_replace([' ',',,',':',';'],['',',','',''],$expressNos);
                $expressNosArray = explode(',',$expressNos);
                if(!empty($orderNo) && count($expressNosArray) > 0 && !empty($expressWay)){
                    $order = OrderDB::where('order_number', $orderNo)->first();
                    empty($order) ? $error[] = $orderNo : '';
                    if(!empty($order)){
                        $shippingMemo = [];
                        for($x=0;$x<count($expressNosArray);$x++){
                            $shippingMemo[] = [
                                'create_time' => date('Y-m-d H:i:s'),
                                'express_way' => $expressWay,
                                'express_no' => $expressNosArray[$x]
                            ];
                        }
                        if(count($shippingMemo) > 0){
                            $shippingMemo = json_encode($shippingMemo);
                            $order->update(['shipping_memo' => $shippingMemo, 'shipping_number' => $expressNos]);
                            $log = OrderLogDB::create([
                                'column_name' => 'shipping_number',
                                'order_id' => $order->id,
                                'log' => "物流單號 $expressNos",
                                'editor' => auth('gate')->user()->id,
                            ]);
                        }
                    }
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
