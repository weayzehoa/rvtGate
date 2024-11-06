<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin as AdminDB;

class OrderImportAbnormal extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_import_id',
        'import_no',
        'type',
        'partner_order_number',
        'sku',
        'quantity',
        'price',
        'memo',
        'row_no',
        'is_chk',
        'chk_date',
        'admin_id',
    ];

    public function admin()
    {
        return $this->hasOne(AdminDB::class,'id','admin_id');
    }
}
