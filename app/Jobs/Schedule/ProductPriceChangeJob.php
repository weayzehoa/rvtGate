<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\iCarryPriceChangeRecord as PriceChangeRecordDB;
use App\Models\iCarryProductUpdateRecord as ProductUpdateRecordDB;
use App\Jobs\AdminSendEmail;

class ProductPriceChangeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $now = date('Y-m-d H:i:s');
        $yyyymmdd = date("Y-m-d",time()+300);
        $yyyymmddhh = date("Y-m-d H",time());
        //變價設定排程
        $items = PriceChangeRecordDB::with('admin','product')->where('is_disabled',0)
            ->whereRaw(" DATE_FORMAT(colF,'%Y-%m-%d')='{$yyyymmdd}' AND DATE_FORMAT(colF,'%Y-%m-%d %H')='{$yyyymmddhh}' ")
            ->get();
        if(count($items) > 0){
            foreach($items as $item){
                $checkChange = 0;
                $param['item'] = $item;
                $param['to'] = [$item->admin->email];
                $param['subject'] = '商品變價失敗通知';
                $param['model'] = 'PriceChangeFailMailBody';
                $param['from'] = 'icarry@icarry.me'; //寄件者
                $param['name'] = 'iCarry中繼系統'; //寄件者名字
                if(env('APP_ENV') == 'local'){
                    $param['to'] = [env('TEST_MAIL_ACCOUNT')]; //收件者, 需使用陣列
                }else{
                    $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                    if(!empty($mailTo) && preg_match($pattern,$mailTo)){
                        $param['to'][] = $mailTo; //收件者, 需使用陣列
                    }
                }
                if(empty($item->product->price)){
                    $checkChange++;
                }else{
                    $beforeStatus = $item->product->status;
                    $beforePrice = $item->product->price;
                    $beforeFakePrice = $item->product->fake_price;
                    $beforeVendorPrice = $item->product->vendor_price;
                    $afterStatus = $item->status_updown;
                    $afterPrice = $item->colC;
                    $afterFakePrice = $item->colD;
                    $afterVendorPrice = $item->colE;
                    if(!empty($item->colC) && $item->colC != $item->product->price){
                        if($item->colC > 0){
                            $item->update(['original_price' => $beforePrice]);
                            $item->product->update(['price' => $afterPrice]);
                            ProductUpdateRecordDB::create([
                                'product_id' => $item->product_id,
                                'admin_id' => $item->admin_id,
                                'vendor_id' => $item->product->vendor_id,
                                'column' => 'price',
                                'before_value' => $beforePrice,
                                'after_value' => $afterPrice,
                            ]);
                        }else{
                            $checkChange++;
                        }
                    }
                    if(!empty($item->colD) && $item->colD != $item->product->fake_price){
                        if($item->colD > 0){
                            $item->update(['original_fake_price' => $beforeFakePrice]);
                            $item->product->update(['fake_price' => $afterFakePrice]);
                            ProductUpdateRecordDB::create([
                                'product_id' => $item->product_id,
                                'admin_id' => $item->admin_id,
                                'vendor_id' => $item->product->vendor_id,
                                'column' => 'fake_price',
                                'before_value' => $beforeFakePrice,
                                'after_value' => $afterFakePrice,
                            ]);
                        }else{
                            $checkChange++;
                        }
                    }
                    if(!empty($item->colE) && $item->colE!=$item->product->vendor_price){
                        if($item->colD > 0){
                            $item->update(['original_vendor_price' => $beforeVendorPrice]);
                            $item->product->update(['vendor_price' => $afterVendorPrice]);
                            ProductUpdateRecordDB::create([
                                'product_id' => $item->product_id,
                                'admin_id' => $item->admin_id,
                                'vendor_id' => $item->product->vendor_id,
                                'column' => 'vendor_price',
                                'before_value' => $beforeVendorPrice,
                                'after_value' => $afterVendorPrice,
                            ]);
                        }else{
                            $checkChange++;
                        }
                    }
                    if($beforeStatus != $afterStatus){
                        $item->update(['original_status' => $beforeStatus]);
                        $item->product->update(['status' => $afterStatus]);
                    }
                    if(empty($item->colG)){
                        $item->update(['is_disabled' => 1]);
                    }
                    $checkChange > 0 ? AdminSendEmail::dispatch($param) : '';
                }
            }
        }
        //恢復價格排程
        $items = PriceChangeRecordDB::with('admin','product')->where('is_disabled',0)
            ->whereRaw(" DATE_FORMAT(colG,'%Y-%m-%d')='{$yyyymmdd}' AND DATE_FORMAT(colG,'%Y-%m-%d %H')='{$yyyymmddhh}' ")
            ->where(function($query){
                $query = $query->whereNotNull('colG')->orWhere('colG','!=','');
            })->get();
        if(count($items) > 0){
            foreach($items as $item){
                $checkChange = 0;
                $param['item'] = $item;
                $param['to'] = [$item->admin->email];
                $param['subject'] = '商品變價恢復失敗通知';
                $param['model'] = 'PriceRecoverFailMailBody';
                $param['from'] = 'icarry@icarry.me'; //寄件者
                $param['name'] = 'iCarry中繼系統'; //寄件者名字
                if(env('APP_ENV') == 'local'){
                    $param['to'] = [env('TEST_MAIL_ACCOUNT')]; //收件者, 需使用陣列
                }else{
                    $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                    if(!empty($mailTo) && preg_match($pattern,$mailTo)){
                        $param['to'][] = $mailTo; //收件者, 需使用陣列
                    }
                }
                if(empty($item->product->price)){
                    $checkChange++;
                }else{
                    $beforeStatus = $item->product->status;
                    $beforePrice = $item->product->price;
                    $beforeFakePrice = $item->product->fake_price;
                    $beforeVendorPrice = $item->product->vendor_price;
                    $afterStatus = $item->original_status;
                    $afterPrice = $item->original_price;
                    $afterFakePrice = $item->original_fake_price;
                    $afterVendorPrice = $item->original_vendor_price;
                    if((!empty($item->colC)) && $afterPrice > 0){
                        if($item->colC > 0){
                            $item->product->update(['price' => $afterPrice]);
                            ProductUpdateRecordDB::create([
                                'product_id' => $item->product_id,
                                'admin_id' => $item->admin_id,
                                'vendor_id' => $item->product->vendor_id,
                                'column' => 'price',
                                'before_value' => $beforePrice,
                                'after_value' => $afterPrice,
                            ]);
                        }else{
                            $checkChange++;
                        }
                    }
                    if((!empty($item->colD)) && $afterFakePrice > 0){
                        if($item->colD > 0){
                            $item->product->update(['fake_price' => $afterFakePrice]);
                            ProductUpdateRecordDB::create([
                                'product_id' => $item->product_id,
                                'admin_id' => $item->admin_id,
                                'vendor_id' => $item->product->vendor_id,
                                'column' => 'fake_price',
                                'before_value' => $beforeFakePrice,
                                'after_value' => $afterFakePrice,
                            ]);
                        }else{
                            $checkChange++;
                        }
                    }
                    if((!empty($item->colE)) && $afterVendorPrice > 0){
                        if($item->colE > 0){
                            $item->product->update(['vendor_price' => $afterVendorPrice]);
                            ProductUpdateRecordDB::create([
                                'product_id' => $item->product_id,
                                'admin_id' => $item->admin_id,
                                'vendor_id' => $item->product->vendor_id,
                                'column' => 'vendor_price',
                                'before_value' => $beforeVendorPrice,
                                'after_value' => $afterVendorPrice,
                            ]);
                        }else{
                            $checkChange++;
                        }
                    }
                    if($beforeStatus != $afterStatus){
                        $item->product->update(['status' => $afterStatus]);
                    }
                    $item->update(['is_disabled' => 1]);
                }
                $checkChange > 0 ? AdminSendEmail::dispatch($param) : '';
            }
        }
    }
}
