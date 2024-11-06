<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseNoticeFile extends Model
{
    use HasFactory;
    protected $fillable = [
        'export_no',
        'purchase_no',
        'type',
        'filename',
    ];
}
