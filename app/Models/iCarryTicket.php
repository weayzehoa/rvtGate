<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryUser as UserDB;
use DB;

class iCarryTicket extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'tickets';
    protected $fillable = [
        'create_type',
        'ticket_no',
        'ticket_order_no',
        'platform_no',
        'vendor_id',
        'vendor_name',
        'product_id',
        'product_name',
        'product_model_id',
        'digiwin_no',
        'sku',
        'order_id',
        'order_number',
        'order_item_id',
        'partner_order_number',
        'used_time',
        'purchase_no',
        'purchase_date',
        'status',
    ];
    public function order(){
        $key = env('APP_AESENCRYPT_KEY');
        return $this->belongsTo(OrderDB::class,'order_id','id')
        ->select([
            'orders.id',
            'orders.order_number',
            'orders.receiver_name',
            'orders.receiver_email',
            'orders.create_type',
            'orders.user_id',
            DB::raw("IF(orders.receiver_tel IS NULL,'',AES_DECRYPT(orders.receiver_tel,'$key')) as receiver_tel"),
        ]);
    }
    public function purchase(){
        return $this->setConnection('mysql')->belongsTo(PurchaseOrderDB::class,'purchase_no','purchase_no');
    }
}
