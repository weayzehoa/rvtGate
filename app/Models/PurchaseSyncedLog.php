<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseNoticeFile as PurchaseNoticeFileDB;

class PurchaseSyncedLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'admin_id',
        'vendor_id',
        'purchase_order_id',
        'quantity',
        'amount',
        'tax',
        'status',
        'notice_time',
        'export_no',
        'confirm_time',
    ];

    public function files(){
        return $this->hasMany(PurchaseNoticeFileDB::class,'export_no','export_no')->orderBy('id','asc');
    }
}
