<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\EmployeeAttendance as EmployeeAttendanceDB;
use App\Imports\EmployeeAttendanceFileImport;

use Carbon\Carbon;

class EmployeeAttendanceFileImportJob implements ShouldQueue
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
        $result = Excel::toArray(new EmployeeAttendanceFileImport, $param['filename']); //0代表第一個sheet
        if(count($result) == 1){
            if($result[0][0][9] == '打卡结果' && count($result[0][0]) == 20){
                $results = $result[0];
                for($i = 1;$i < count($results);$i++) {
                    $i==1 ? $startDate = explode(' ',$results[$i][8])[0] : '';
                    $i==count($results)-1 ? $endDate = explode(' ',$results[$i][8])[0] : '';
                }
                if($startDate > $endDate){
                    $tmp = $startDate;
                    $startDate = $endDate;
                    $endDate = $tmp;
                }

                //刪除範圍資料
                $attendances = EmployeeAttendanceDB::whereBetween('work_date',[$startDate,$endDate])->delete();
                for($i=1;$i<count($results);$i++){
                    if($this->chkData($results[$i]) == true){
                        $employeeNo = $results[$i][3];
                        $workDate = explode(' ',$results[$i][8])[0];
                        $week = date('w',strtotime($workDate));
                        $chkTime = $results[$i][8];
                        $result = $results[$i][9];
                        $memo = $results[$i][11];
                        if(!empty($employeeNo) && !empty($chkTime)){
                            if(!strstr($result,'打卡無效')){
                                EmployeeAttendanceDB::create([
                                    'employee_no' => $employeeNo,
                                    'work_date' => $workDate,
                                    'week' => $week,
                                    'chk_time' => $chkTime,
                                    'result' => $result,
                                    'memo' => $memo,
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
