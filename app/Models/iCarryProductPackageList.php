<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryProductPackage as ProductPackageDB;

//使用軟刪除
use Illuminate\Database\Eloquent\SoftDeletes;

class iCarryProductPackageList extends Model
{
    use HasFactory;
    //使用軟刪除
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $connection = 'icarry';
    protected $table = 'product_package_lists';
    protected $fillable = [
        'product_package_id',
        'product_model_id',
        'quantity',
    ];

    //對應product_model的id欄位
    public function model(){
        return $this->belongsTo(ProductModelDB::class,'product_model_id','id');
    }

    //對應product_package的id欄位
    public function package(){
        return $this->belongsTo(ProductPackageDB::class,'product_package_id','id');
    }

}
