<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\SystemSetting as SystemSettingDB;
use App\Models\iCarryOrderItemPackage as OrderItemPackageDB;
use App\Models\ShippingVendor as ShippingVendorDB;
use App\Models\OrderShipping as OrderShippingDB;
use DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        if (env('DB_MIGRATE_SHIPPING_VENDORS')) {
            //Shipping Vendor 資料遷移
            $oldSPvendors = DB::connection('icarry')->table('shipping_vendor')->get();
            foreach ($oldSPvendors as $oldVendor) {
                $shippingVendor = ShippingVendorDB::create([
                    'name' => $oldVendor->name,
                    'name_en' => $oldVendor->name_en,
                    'api_url' => $oldVendor->api_url,
                    'is_foreign' => $oldVendor->is_foreign,
                    'sort' => $oldVendor->sort_id,
                ]);
                $oldVendor->is_delete == 1 ? $shippingVendor->delete() : '';
            }
            echo "Shipping Vendor 遷移完成\n";
        }

        //物流資料遷移
        if (env('DB_MIGRATE_ORDER_SHIPPINGS')) {
            $subQuery = OrderDB::whereNotNull('shipping_memo')->select(['id','shipping_memo'])->orderBy('id','asc')->chunk(5000, function ($oldOrders) {
                foreach($oldOrders as $oldOrder){
                    $data = [];
                    if (!empty($oldOrder->shipping_memo)) {
                        $shippings = json_decode(str_replace('	', '', $oldOrder->shipping_memo));
                        if(is_array($shippings)){
                            foreach ($shippings as $shipping) {
                                $shipping->create_time = str_replace('/','-',$shipping->create_time);
                                $shipping->create_time == '1970-01-01 08:00:00' ? $shipping->create_time = null : '';
                                $data[] = [
                                    'order_id' => $oldOrder->id,
                                    'express_way' => $shipping->express_way,
                                    'express_no' => $shipping->express_no,
                                    'created_at' => $shipping->create_time,
                                ];
                            }
                        }
                    }
                    OrderShippingDB::insert($data);
                }
            });
            echo "Order Shipping 遷移完成\n";
        }

        // if (env('iCARRY_DB_ORDER_ITEM_PACKAGE')) {
        //     $grossWeightRate = SystemSettingDB::find(1)->gross_weight_rate;
        //     OrderItemPackageDB::truncate(); //先清空資料表
        //     $dbQuery = OrderItemDB::join('product_model', 'product_model.id', 'order_item.product_model_id')
        //         ->join('product', 'product.id', 'product_model.product_id')
        //         ->join('orders', 'orders.id', 'order_item.order_id')
        //         ->where('product_model.sku', 'like', 'BOM%')
        //         // ->where('order_item.id', '<', 9999999999) 正式上線時找出未補的資料用
        //         ->select([
        //             'order_item.*',
        //             'orders.digiwin_payment_id',
        //             'product.id as product_id',
        //             'product.package_data',
        //             'product_model.sku',
        //     ])->orderBy('order_item.create_time', 'asc')->chunk(1000, function ($orderItems) use ($grossWeightRate) {
        //         // dd($orderItems->toArray());
        //         $data = [];
        //         foreach ($orderItems as $item) {
        //             $item->package_data = str_replace(['	','\r','\t'], ['','',''], $item->package_data);
        //             $models = collect(json_decode(str_replace('	','',$item->package_data)));
        //             // dd($models);
        //             foreach ($models as $model) {
        //                 if ($item->sku == $model->bom) {
        //                     // dd($model->lists);
        //                     foreach ($model->lists as $list) {
        //                         if (!empty($list->sku)) {
        //                             empty($list->quantity) ? $list->quantity = 0 : !is_numeric($list->quantity) ? $list->quantity = 0 : '';
        //                             $productModel = ProductModelDB::join('product', 'product.id', 'product_model.product_id')
        //                                 ->where('product_model.sku', $list->sku)
        //                                 ->select([
        //                                     'product_model.id',
        //                                     'product_model.sku',
        //                                     'product_model.digiwin_no',
        //                                     'product.name',
        //                                     'product.gross_weight',
        //                                     'product.net_weight',
        //                                 ])->first();
        //                             // dd($productModel);
        //                             empty($productModel->net_weight) ? $productModel->net_weight = 0 : '';
        //                             $data[] = [
        //                                 'order_item_id' => $item->id,
        //                                 'product_model_id' => $productModel->id,
        //                                 'sku' => $productModel->sku,
        //                                 'digiwin_no' => $productModel->digiwin_no,
        //                                 'digiwin_payment_id' => $item->digiwin_payment_id,
        //                                 'gross_weight' => $list->quantity * $productModel->gross_weight * $grossWeightRate,
        //                                 'net_weight' => $list->quantity * $productModel->net_weight,
        //                                 'quantity' => $item->quantity * $list->quantity,
        //                                 'is_del' => $item->is_del,
        //                                 'admin_memo' => $item->admin_memo,
        //                                 'create_time' => $item->create_time,
        //                                 'product_name' => $productModel->name,
        //                                 'is_call' => $item->is_call,
        //                             ];
        //                         }
        //                     }
        //                 }
        //             }
        //         }
        //         OrderItemPackageDB::insert($data);
        //     });
        // }
    }
}
