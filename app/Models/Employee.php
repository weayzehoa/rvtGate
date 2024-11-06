<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_en',
        'employee_no',
        'department',
        'title',
        'onduty_date',
        'leave_date',
    ];
}
