<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Imports\OrderShippingImport;

use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\OrderImport as OrderImportDB;
use App\Models\OrderImportAbnormal as OrderImportAbnormalDB;

use App\Traits\OrderImportFunctionTrait;

use DB;

use App\Jobs\AdminOrderStatusJob;

class OrderShippingFileImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,OrderImportFunctionTrait;
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
        if($param['cate'] == 'orders'){
            $orderTypes = $this->param['imports'];
            if(in_array($param['type'], $orderTypes)){
                $file = $param->file('filename');
                $result = Excel::toArray(new OrderShippingImport($param), $file);
                if(count($result[0]) > 0){
                    $this->chkRows($result[0]) == false ? $result = 'rows error' : '';
                    if($result == 'rows error'){
                        $type = $param['type'];
                        return ['error' => "檔案內有欄位數錯誤， $type 欄位數為 3 欄，請檢查檔案內容。"];
                    }else{
                        $importData = $result[0];
                        $x=0;$results=[];
                        for($i=0;$i<count($importData);$i++){
                            if(!empty($importData[$i][0])){
                                if(strtoupper($importData[$i][0]) == 'DHL' || strtoupper($importData[$i][0]) == '順豐'){
                                    if(strtoupper($importData[$i][0]) == 'DHL'){
                                        $results[$x][0] = $importData[$i][0];
                                        $results[$x][1] = null;
                                        $results[$x][2] = $importData[$i][2];
                                        $x++;
                                    }
                                    if(strtoupper($importData[$i][0]) == '順豐'){
                                        $results[$x][0] = $importData[$i][0];
                                        $results[$x][1] = $importData[$i][1];
                                        $results[$x][2] = null;
                                        $x++;
                                    }
                                }
                            }
                        }
                        if(count($results) > 0){
                            return $results;
                        }else{
                            return ['error' => "檔案內沒有資料，請檢查檔案是否正確。"];
                        }
                    }
                }else{
                    return ['error' => "檔案內沒有資料，請檢查檔案是否正確。"];
                }
            }else{
                return ['error' => '選擇匯入的類別不存在'];
            }
        }
        return ['error' => '你確定是訂單匯入?'];
    }

    protected function chkRows($items){
        $chk = 0;
        for($i=0;$i<count($items);$i++){
            if(count($items[$i]) != 3){
                $chk++;
            }
        }
        if($chk == 0){
            return true;
        }else{
            return false;
        }
    }
}
