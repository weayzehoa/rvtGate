<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryProductPackageList as ProductPackageListDB;
use DB;
//使用軟刪除
use Illuminate\Database\Eloquent\SoftDeletes;

class iCarryProductPackage extends Model
{
    use HasFactory;
    //使用軟刪除
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $connection = 'icarry';
    protected $table = 'product_packages';
    protected $fillable = [
        'product_id',
        'product_model_id',
    ];

    //對應product的id欄位
    public function product(){
        return $this->belongsTo(ProductDB::class,'product_id','id');
    }

    //對應product_models的id欄位
    public function model(){
        return $this->belongsTo(ProductModelDB::class,'product_model_id','id');
    }

    //對應product_package_lists的id欄位
    public function lists(){
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $productPackageListTable = env('DB_ICARRY').'.'.(new ProductPackageListDB)->getTable();

        return $this->hasMany(ProductPackageListDB::class,'product_package_id','id')
        ->join($productModelTable,$productModelTable.'.id',$productPackageListTable.'.product_model_id')
        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
        ->select([
            $productPackageListTable.'.*',
            $productModelTable.'.sku',
            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as name"),
        ]);
    }

}
