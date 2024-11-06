<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\iCarryOrder as OrderDB;
use App\Models\OrderShipping as OrderShippingDB;
use App\Models\iCarryShippingVendor as iCarryShippingVendorDB;
use App\Models\ShippingVendor as ShippingVendorDB;
use App\Models\Schedule as ScheduleDB;

use App\Traits\ProductFunctionTrait;

class OrderShippingUpdateScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,ProductFunctionTrait;

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
        dd('此功能已不在使用');
        //物流商資料
        $oldVendors = iCarryShippingVendorDB::get();
        foreach($oldVendors as $oldVendor){
            $data = [
                'name' => $oldVendor->name,
                'name_en' => $oldVendor->name_en,
                'api_url' => $oldVendor->api_url,
                'is_foreign' => $oldVendor->is_foreign,
                'sort' => $oldVendor->sort_id,
            ];
            $shippingVendor = ShippingVendorDB::where('name',$oldVendor->name)->first();
            !empty($shippingVendor) ? $shippingVendor->update($data) : $shippingVendor = ShippingVendorDB::create($data);
            $oldVendor->is_delete == 1 ? $shippingVendor->delete() : '';
        }

        //訂單物流資料
        $subQuery = OrderDB::with('shippings')->orderBy('id','asc')->chunk(1000, function ($orders) {
            $data = [];
            foreach($orders as $order){
                $orderShippings = $order->shippings; //找出此訂單所有物流單
                if (!empty($order->shipping_memo)) {
                    $shippings = json_decode(str_replace('	', '', $order->shipping_memo));
                    if(is_array($shippings)){
                        foreach ($orderShippings as $orderShipping) {
                            $count = 0;
                            foreach($shippings as $shipping){
                                if($shipping->express_way == $orderShipping->express_way && $shipping->express_no == $orderShipping->express_no){
                                    $count = 1;
                                    break;
                                }
                            }
                            //此物流單不存在現在訂單中
                            $count == 0 ? $orderShipping->delete() : '';
                        }
                        foreach ($shippings as $shipping) {
                            $shipping->create_time = str_replace('/','-',$shipping->create_time);
                            $shipping->create_time == '1970-01-01 08:00:00' ? $shipping->create_time = null : '';
                            $chk = 0;
                            foreach ($orderShippings as $orderShipping) {
                                if($shipping->express_way == $orderShipping->express_way && $shipping->express_no == $orderShipping->express_no){
                                    $chk = 1;
                                    break;
                                }
                            }
                            if($chk == 0){ //目前物流單不存在中繼站
                                $data[] = [
                                    'order_id' => $order->id,
                                    'express_way' => $shipping->express_way,
                                    'express_no' => $shipping->express_no,
                                    'created_at' => $shipping->create_time,
                                ];
                            }
                        }
                    }else{//解析出來非陣列一樣清除
                        if(!empty($orderShippings)){
                            foreach ($orderShippings as $orderShipping) {
                                $orderShipping->delete();
                            }
                        }
                    }
                }else{ //shipping_memo 沒資料則清除
                    if(!empty($orderShippings)){
                        foreach ($orderShippings as $orderShipping) {
                            $orderShipping->delete();
                        }
                    }
                }
            }
            if(!empty($data)){
                $orderShippings = OrderShippingDB::insert($data);
            }
        });

        $schedule = ScheduleDB::where('code','orderShippingUpdate')->first()->update(['last_update_time' => date('Y-m-d H:i:s')]);
    }
}
