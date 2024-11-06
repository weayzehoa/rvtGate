<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\EmployeeOvertime as EmployeeOvertimeDB;
use App\Imports\EmployeeOvertimeFileImport;

use Carbon\Carbon;

class EmployeeOvertimeFileImportJob implements ShouldQueue
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
        $param = $this->param;
        $result = Excel::toArray(new EmployeeOvertimeFileImport, $param['filename']); //0代表第一個sheet
        if(count($result) == 1){
            if($result[0][0][14] == '加班人' && count($result[0][0]) == 23){
                $results = $result[0];
                for($i=1;$i<count($results);$i++){
                    if($this->chkData($results[$i]) == true){
                        $approvalNo = $results[$i][0];
                        $status = $results[$i][3];
                        $employeeNo = $results[$i][7];
                        $type = $results[$i][14];
                        $startTime = $results[$i][15];
                        $endTime = $results[$i][16];
                        $duration = $results[$i][20];
                        $startTime = date('Y-m-d H:i:s',strtotime($startTime));
                        $endTime = date('Y-m-d H:i:s',strtotime($endTime));
                        strstr($startTime,'AM') || strstr($startTime,'上午') ? $startTime = explode(' ',$startTime)[0].' 09:00:00' : '';
                        strstr($startTime,'PM') || strstr($startTime,'下午') ? $startTime = explode(' ',$startTime)[0].' 18:00:00' : '';
                        strstr($endTime,'AM') || strstr($endTime,'上午') ? $endTime = explode(' ',$endTime)[0].' 09:00:00' : '';
                        strstr($endTime,'PM') || strstr($endTime,'下午') ? $endTime = explode(' ',$endTime)[0].' 18:00:00' : '';
                        $duration = str_replace('小時','',$duration);
                        strstr($duration,'天') ? $duration = str_replace('天','',$duration) * 8 : ''; //換算一天8小時
                        if(!empty($employeeNo) && $status == '同意'){
                            $employeeOvertime = EmployeeOvertimeDB::where('approval_no',$approvalNo)->first();
                            if(!empty($employeeOvertime)){
                                $employeeOvertime->update([
                                    'approval_no' => $approvalNo,
                                    'employee_no' => $employeeNo,
                                    'type' => $type,
                                    'start_time' => $startTime,
                                    'end_time' => $endTime,
                                    'duration' => $duration,
                                ]);
                            }else{
                                EmployeeOvertimeDB::create([
                                    'approval_no' => $approvalNo,
                                    'employee_no' => $employeeNo,
                                    'type' => $type,
                                    'start_time' => $startTime,
                                    'end_time' => $endTime,
                                    'duration' => $duration,
                                ]);
                            }
                        }
                    }
                }
            }else{
                return 'rows error';
            }
        }else{
            return 'sheets error';
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
