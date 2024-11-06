<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\iCarryUser as UserDB;
use DB;

class SmsLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sms_id',
        'admin_id',
        'user_id',
        'vendor',
        'send_response',
        'get_response',
        'status',
        'message',
        'msg_id',
        'aws_id',
    ];

    public function user(){
        $secrtKey = env('APP_AESENCRYPT_KEY');
        return $this->belongsTo(UserDB::class)->select([
            '*',
            DB::raw("IF(mobile IS NULL,'',AES_DECRYPT(mobile,'$secrtKey')) as mobile"),
        ]);
    }
}
