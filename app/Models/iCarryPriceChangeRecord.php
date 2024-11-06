<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin as AdminDB;
use App\Models\iCarryProduct as ProductDB;

class iCarryPriceChangeRecord extends Model
{
    use HasFactory;
    protected $connection = 'icarry';
    protected $table = 'price_change_record';
    //變更 Laravel 預設 created_at 與 updated_at 欄位名稱
    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;
    protected $fillable = [
        'original_price',
        'original_fake_price',
        'original_vendor_price',
        'is_disabled',
    ];
    public function admin()
    {
        return $this->setConnection('mysql')->beLongsTo(AdminDB::class);
    }

    public function product()
    {
        return $this->beLongsTo(ProductDB::class)->select(['id','vendor_id','price','vendor_price','fake_price','status']);
    }
}
