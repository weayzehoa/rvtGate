<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseExcludeProduct as PurchaseExcludeProductDB;
class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (env('DB_MIGRATE_PURCHASE_EXCLUDE_PRODUCTS')) {
            //採購排除商品資料建立
            $productModels = ProductModelDB::join('product','product.id','product_model.product_id')
                ->join('vendor','vendor.id','product.vendor_id')
                ->whereIn('vendor.id',[20,58,25,169,189]) //20,58 佳德, 25,169,189 糖村
                ->select([
                    'product_model.id',
                ])->get();
            foreach ($productModels as $productModel) {
                $ids[] = [
                    'product_model_id' => $productModel->id,
                ];
            }
            PurchaseExcludeProductDB::insert($ids);
            echo "採購排除商品資料建立完成\n";
        }
    }
}
