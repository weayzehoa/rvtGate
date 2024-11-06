<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee as EmployeeDB;

class EmployeeVacation extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_no',
        'employee_no',
        'type',
        'start_time',
        'end_time',
        'duration',
    ];

    public function employee(){
        return $this->hasOne(EmployeeDB::class,'employee_no','employee_no');
    }
}
