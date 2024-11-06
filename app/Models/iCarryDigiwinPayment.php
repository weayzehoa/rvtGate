<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class iCarryDigiwinPayment extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'digiwin_payment';
    protected $primaryKey = 'customer_no';
    protected $keyType = 'string';
    public $timestamps = FALSE;

    protected $fillable = [
        'customer_no',
        'customer_name',
        'set_deposit_ratio',
        'user_name',
        'use_quotation', //使用報價單
        'MA015', //部門
        'MA016', //業務人員
        'MA031', //付款條件
        'MA037', //發票聯數
        'MA038', //課稅別
        'MA048', //運輸方式
        'MA083', //付款條件代號
        'create_type',
        'is_invoice',
        'is_acrtb'
    ];
}
