<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Employee as EmployeeDB;

class EmployeeAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_no',
        'work_date',
        'week',
        'chk_time',
        'result',
        'memo',
    ];

    public function employee(){
        return $this->hasOne(EmployeeDB::class,'employee_no','employee_no');
    }
}
