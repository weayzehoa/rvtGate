<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee as EmployeeDB;

class EmployeeOvertime extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_no',
        'employee_no',
        'start_time',
        'end_time',
        'duration',
    ];

    public function employee(){
        return $this->hasOne(EmployeeDB::class,'employee_no','employee_no');
    }
}
