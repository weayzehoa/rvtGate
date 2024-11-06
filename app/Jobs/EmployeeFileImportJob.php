<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Employee as EmployeeDB;
use App\Imports\EmployeeFileImport;

use Carbon\Carbon;

class EmployeeFileImportJob implements ShouldQueue
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
        $chk = 0;
        $param = $this->param;
        $result = Excel::toArray(new EmployeeFileImport, $param['filename']); //0代表第一個sheet

        if(count($result) == 1){
            if($result[0][0][0] == '分類'){
                $results = $result[0];
                for($i=1;$i<count($results);$i++){
                    if($this->chkData($results[$i]) == true){
                        $department = $results[$i][1];
                        $title = $results[$i][2];
                        $employeeNo = $results[$i][3];
                        $name = $results[$i][4];
                        $enName = $results[$i][5];
                        $email = $results[$i][12];
                        !empty($results[$i][13]) ? $onDutyDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($results[$i][13]))->toDateString() : $onDutyDate = null;
                        !empty($results[$i][15]) ? $leaveDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($results[$i][15]))->toDateString() : $leaveDate = null;
                        if(!empty($employeeNo)){
                            $employee = EmployeeDB::where('employee_no',$employeeNo)->first();
                            if(!empty($employee)){
                                $employee->update([
                                    'name' => $name,
                                    'employee_no' => $employeeNo,
                                    'department' => $department,
                                    'title' => $title,
                                    'onduty_date' => $onDutyDate,
                                    'leave_date' => $leaveDate,
                                ]);
                            }else{
                                EmployeeDB::create([
                                    'name' => $name,
                                    'employee_no' => $employeeNo,
                                    'department' => $department,
                                    'title' => $title,
                                    'onduty_date' => $onDutyDate,
                                    'leave_date' => $leaveDate,
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
