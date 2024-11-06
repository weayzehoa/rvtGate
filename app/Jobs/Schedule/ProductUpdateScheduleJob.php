<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryProductUnitName as UnitNameDB;
use App\Models\iCarryProductPackage as ProductPackageDB;
use App\Models\iCarryProductPackageList as ProductPackageListDB;
use App\Models\ErpProduct as ErpProductDB;
use App\Models\ACErpProduct as ACErpProductDB;
use App\Models\Log as LogDB;
use App\Models\Schedule as ScheduleDB;
use App\Jobs\AdminSendEmail;

use App\Traits\ProductFunctionTrait;

class ProductUpdateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,ProductFunctionTrait;

    //讓job不會timeout, 此設定需用 queue:work 才會優先於預設
    public $timeout = 0;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // //AC商品同步到iCarry
        // $acErpProducts = ACErpProductDB::get();
        // $acVendorData = VendorDB::find(706);
        // foreach($acErpProducts as $acProduct){
        //     //查詢iCarry是否有該商品
        //     $iCarryProductModel = ProductModelDB::where('ac_digiwin_no',$acProduct->MB010)->first();
        //     if(empty($iCarryProductModel)){ //建立新的商品資料
        //         $unitName = UnitNameDB::where('name',$acProduct->MB004)->first();
        //         !empty($unitName) ? $unitNameId = $unitName->id : $unitNameId = null;
        //         $data = [
        //             'vendor_id' => 706,
        //             'category_id' => 18,
        //             'unit_name' => $acProduct->MB004,
        //             'unit_name_id' => $unitNameId,
        //             'from_country_id' => 1,
        //             'product_sold_country' => '台灣',
        //             'name' => $acProduct->MB002,
        //             'brand' => $acVendorData->name,
        //             'serving_size' => $acProduct->MB003,
        //             'shipping_methods' => '1,2,3,4,5,6',
        //             'price' => round($acProduct->MB046,2),
        //             'gross_weight' => 0,
        //             'net_weight' => 0,
        //             'model_type' => 1,
        //             'is_tax_free' => 0,
        //             'specification' => $acProduct->MB002,
        //             'verification_reason' => null,
        //             'status' => -9,
        //             'is_hot' => null,
        //             'hotel_days' => 0,
        //             'airplane_days' => 0,
        //             'storage_life' => 0,
        //             'fake_price' => 0,
        //             'TMS_price' => 0,
        //             'allow_country' => null,
        //             'allow_country_ids' => null,
        //             'vendor_price' => round($acProduct->MB046,2),
        //             'unable_buy' => null,
        //             'pause_reason' => null,
        //             'tags' => null,
        //             'is_del' => 0,
        //             'pass_time' => date('Y-m-d H:i:s'),
        //             'curation_text_top' => null,
        //             'curation_text_bottom' => null,
        //             'service_fee_percent' => null,
        //             'package_data' => null,
        //             'new_photo1' => null,
        //             'new_photo2' => null,
        //             'new_photo3' => null,
        //             'new_photo4' => null,
        //             'new_photo5' => null,
        //             'type' => 1,
        //             'digiwin_product_category' => null,
        //             'vendor_earliest_delivery_date' => null,
        //             'vendor_latest_delivery_date' => null,
        //             'shipping_fee_category_id' => null, //棄用
        //             'ticket_price' => null,
        //             'ticket_group' => null,
        //             'ticket_merchant_no' => null,
        //             'ticket_memo' => null,
        //             'direct_shipment' => 0,
        //             'eng_name' => null,
        //             'trans_start_date' => null,
        //             'trans_end_date' => null,
        //         ];
        //         $iCarryProduct = ProductDB::create($data);
        //         //建立ProductModel資料
        //         $productModelData['quantity'] = 1;
        //         $productModelData['safe_quantity'] = 0;
        //         $productModelData['gtin13'] = null;
        //         $productModelData['name'] = '單一規格';
        //         $productModelData['is_del'] = 0;
        //         $productModelData['product_id'] = $iCarryProduct->id;
        //         $iCarryProductModel = ProductModelDB::create($productModelData);
        //         $data['product_model_id'] = $iCarryProductModel->id;
        //         $output = $this->makeSku($data);
        //         $iCarryProductModel->update($output);
        //     }else{ //檢查資料是否有變動
        //         $iCarryProduct = ProductDB::find($iCarryProductModel->product_id);
        //         if(!empty($iCarryProduct)){
        //             if($iCarryProduct->price != $acProduct->MB046){
        //                 $iCarryProduct->update([
        //                     'price' =>$acProduct->MB046,
        //                     'pass_time' => date('Y-m-d H:i:s'),
        //                 ]);
        //             }
        //         }
        //     }
        //     dd($iCarryProduct);
        // }

        //檢查商品金額為0或空值的直接下架
        $zeroProducts = ProductDB::where([['status',1],['type','!=',3]])->where(function($query){
            $query = $query->whereNull('price')->orWhere('price',0);
        })->get();
        $noticeData = [];
        if(count($zeroProducts) > 0){
            $i=0;
            env('APP_ENV') == 'local' ? $param['to'] = [env('TEST_MAIL_ACCOUNT')] : $param['to'] = ['icarryop@icarry.me'];
            $param['subject'] = '商品金額為0或空值下架通知通知';
            $param['model'] = 'zeroProductNoticeMailBody';
            $param['from'] = 'icarry@icarry.me'; //寄件者
            $param['name'] = 'iCarry中繼系統'; //寄件者名字
            foreach($zeroProducts as $zeroProduct){
                $zeroProduct->update(['status' => -9]);
                $noticeData[$i]['id'] = $zeroProduct->id;
                $noticeData[$i]['name'] = $zeroProduct->name;
                $i++;
            }
            $param['item'] = $noticeData;
            AdminSendEmail::dispatch($param);
        }

        // $ctime = microtime(true); //紀錄開始時間
        // LogDB::truncate(); //先清空Log資料表
        $products = $this->getProductData('schedule');
        $products = $products->chunk(1000, function($items)
        {
            foreach($items as $product){
                !empty($product->digiwin_vendor_no) ? $erpVendorNo = $product->digiwin_vendor_no : $erpVendorNo = 'A'.str_pad($product->vendor_id,5,"0",STR_PAD_LEFT);
                $buyPrice = $serviceFeePercent = 0;
                if(!empty($product->service_fee)){
                    $product->service_fee = str_replace(":}",":0}",$product->service_fee); //補0
                    $serviceFee = json_decode($product->service_fee,true);
                    if(is_array($serviceFee)){
                        foreach($serviceFee as $value){
                            if($value['name']=="iCarry"){
                                $serviceFeePercent = $value['percent'];
                                break;
                            }
                        }
                    }
                }
                if(empty($product->vendor_price) || $product->vendor_price == 0){
                    $buyPrice = $product->price * (100-$serviceFeePercent) / 100;
                }else{
                    $buyPrice = $product->vendor_price;
                }
                $buyPrice = round($buyPrice,4);
                $oldData = null;
                if(!empty($product->digiwin_no) && $product->digiwin_no != ''){
                    $data['db_name'] = 'INVMB';
                    $data['COMPANY'] = 'iCarry';
                    $data['USR_GROUP'] = 'DSC';
                    $data['FLAG'] = 1;
                    $data['MB001'] = $product->digiwin_no;
                    $data['MB002'] = mb_substr($product->name,0,110);
                    $data['MB003'] = mb_substr($product->serving_size,0,110);
                    $data['MB004'] = $product->unit_name;
                    $data['MB005'] = '104';
                    $data['MB010'] = $product->digiwin_no;
                    $data['MB011'] = '0001';
                    $data['MB013'] = $product->gtin13;
                    $data['MB015'] = 'g';
                    $data['MB017'] = 'W01';
                    $data['MB019'] = 'Y';
                    $data['MB020'] = 'N';
                    $data['MB022'] = 'N';
                    $data['MB023'] = $product->storage_life;
                    $data['MB024'] = 0;
                    $data['MB025'] = 'P'; //M 組合商品, P 單一規格, 全部使用P
                    $data['MB026'] = '99';
                    $data['MB032'] = $erpVendorNo;
                    $data['MB046'] = round($buyPrice / 1.05,4);
                    $data['MB049'] = $buyPrice;
                    $data['MB034'] = 'L';
                    $data['MB042'] = 1;
                    $data['MB043'] = 0;
                    $data['MB044'] = 'y';
                    $data['MB148'] = 0;
                    $data['MB150'] = 'N';
                    $data['MB151'] = 0;
                    if(!empty($product->erpProduct)){
                        $data['type'] = '更新';
                        $data['COMPANY'] = 'AC';
                        $data['MODIFIER'] = 'DS';
                        $data['MODI_DATE'] = date('Ymd');
                        $data['MODI_TIME'] = date('H:i:s');
                        $data['MODI_AP'] = 'Gate';
                        $data['MODI_PRID'] = 'INVI02';
                        $oldData = json_encode($product->erpProduct,true);
                        $product->erpProduct->update($data);
                    }else{
                        $data['type'] = '新增';
                        $data['CREATOR'] = 'DS';
                        $data['CREATE_DATE'] = date('Ymd');
                        $data['CREATE_TIME'] = date('H:i:s');
                        $data['COMPANY'] = 'AC';
                        $data['CREATE_AP'] = 'Gate';
                        $data['CREATE_PRID'] = 'INVI02';
                        $data['MB025'] = 'P'; //M 組合商品, P 單一規格, 全部使用P
                        $data['MB006'] = $product->digiwin_product_category;
                        ErpProductDB::create($data);
                    }
                    if(!empty($product->ac_digiwin_vendor_no)){
                        $data['MB032'] = $product->ac_digiwin_vendor_no;
                        $data['MB044'] = 'N';
                        $data['MB005'] = '101';
                        $data['MB006'] = '';
                        if(!empty($product->acErpProduct)){
                            $data['type'] = '更新';
                            $data['MODIFIER'] = 'DS';
                            $data['MODI_DATE'] = date('Ymd');
                            $data['MODI_TIME'] = date('H:i:s');
                            $data['MODI_AP'] = 'Gate';
                            $data['MODI_PRID'] = 'INVI02';
                            $oldData = json_encode($product->acErpProduct,true);
                            $product->acErpProduct->update($data);
                        }else{
                            $data['type'] = '新增';
                            $data['CREATOR'] = 'DS';
                            $data['CREATE_DATE'] = date('Ymd');
                            $data['CREATE_TIME'] = date('H:i:s');
                            $data['CREATE_AP'] = 'Gate';
                            $data['CREATE_PRID'] = 'INVI02';
                            ACErpProductDB::create($data);
                        }
                    }
                    $data['sku'] = $product->sku;
                    $data['digiwin_no'] = $product->digiwin_no;
                    LogDB::create($data);

                    //修正組合品資料
                    if($product->model_type == 3){
                        $dd = collect(json_decode(str_replace('	','',$product->package_data)));
                        if(!empty($dd) && count($dd) > 0){
                            foreach ($dd as $model) {
                                if (!empty($model->bom)) {
                                    $tmp = ProductModelDB::where('sku', $model->bom)->first();
                                    if(isset($model->is_del)){
                                        if($model->is_del == 1){
                                            if(!empty($tmp)){
                                                $pp = ProductPackageDB::where([['product_id',$product->id],['product_model_id',$tmp->id]])->first();
                                                if(!empty($pp)){
                                                    $packageList = ProductPackageListDB::where([['product_package_id',$pp->id],['product_model_id',$tmp->id]])->first();
                                                    if(!empty($packageList)){
                                                        $packageList->delete();
                                                    }
                                                    $pp->delete();
                                                }
                                            }
                                        }else{
                                            if(!empty($tmp)){
                                                $pp = ProductPackageDB::where([['product_id',$product->id],['product_model_id',$tmp->id]])->first();
                                                if(empty($pp)){
                                                    $pp = ProductPackageDB::create([
                                                        'product_id' => $product->id,
                                                        'product_model_id' => $tmp->id,
                                                    ]);
                                                }
                                                if(!empty($model->lists) && count($model->lists) > 0){
                                                    foreach ($model->lists as $li) {
                                                        if (!empty($li->sku)) {
                                                            $tmp = ProductModelDB::where('sku', $li->sku)->first();
                                                            if(!empty($tmp) && $li->quantity > 0){
                                                                $packageList = ProductPackageListDB::where([['product_package_id',$pp->id],['product_model_id',$tmp->id]])->first();
                                                                if(empty($packageList)){
                                                                    //組合商品中包含多個商品
                                                                    ProductPackageListDB::create([
                                                                        'product_package_id' => $pp->id,
                                                                        'product_model_id' => $tmp->id,
                                                                        'quantity' => $li->quantity,
                                                                    ]);
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }else{
                                        if(!empty($tmp)){
                                            $pp = ProductPackageDB::where([['product_id',$product->id],['product_model_id',$tmp->id]])->first();
                                            if(empty($pp)){
                                                $pp = ProductPackageDB::create([
                                                    'product_id' => $product->id,
                                                    'product_model_id' => $tmp->id,
                                                ]);
                                            }
                                            if(!empty($model->lists) && count($model->lists) > 0){
                                                foreach ($model->lists as $li) {
                                                    if (!empty($li->sku)) {
                                                        $tmp = ProductModelDB::where('sku', $li->sku)->first();
                                                        if(!empty($tmp) && $li->quantity > 0){
                                                            $packageList = ProductPackageListDB::where([['product_package_id',$pp->id],['product_model_id',$tmp->id]])->first();
                                                            if(empty($packageList)){
                                                                //組合商品中包含多個商品
                                                                ProductPackageListDB::create([
                                                                    'product_package_id' => $pp->id,
                                                                    'product_model_id' => $tmp->id,
                                                                    'quantity' => $li->quantity,
                                                                ]);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });
        $schedule = ScheduleDB::where('code','productUpdate')->first()->update(['last_update_time' => date('Y-m-d H:i:s')]);
        // $ctime = microtime(true) - $ctime; //紀錄結束時間
        // dd($ctime);
    }
}
