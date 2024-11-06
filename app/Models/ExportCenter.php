<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportCenter extends Model
{
    use HasFactory;
    protected $fillable = [
        'export_no',
        'admin_id',
        'cate',
        'name',
        'condition',
        'filename',
        'start_time',
        'end_time',
    ];
}
