<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin as AdminDB;
use Spatie\Activitylog\Traits\LogsActivity; //資料表記錄功能

class CompanySetting extends Model
{
    use HasFactory;
    use LogsActivity;
    protected static $logName = '公司資料設定';
    protected static $logAttributes = ['*']; //代表全部欄位
    protected static $logAttributesToIgnore = ['updated_at']; //忽略特定欄位
    // protected static $logAttributes = ['updated_at']; 只紀錄特定欄位
    protected static $logOnlyDirty = true; //只記錄有改變的欄位
    protected static $submitEmptyLogs = false; //無異動資料則不增加空資料,若沒設定 $ogOnlyDirty = true 時使用

    protected $fillable = [
        'name',
        'name_en',
        'tax_id_num',
        'tel',
        'fax',
        'address',
        'address_en',
        'service_tel',
        'service_email',
        'website',
        'url',
        'fb_url',
        'Instagram_url',
        'Telegram_url',
        'line',
        'wechat',
        'admin_id',
    ];

    public function admin(){
        return $this->belongsTo(AdminDB::class);
    }

}














