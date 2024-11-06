<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\iCarryHotProduct as HotProductDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use DB;

class HotProductSettingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        HotProductDB::truncate();
        $orderItems = OrderItemDB::join($orderTable,$orderTable.'.id',$orderItemTable.'.order_id')
            ->join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->join($vendorTable,$vendorTable.'.id',$orderItemTable.'.vendor_id')
            ->where([[$orderItemTable.'.is_del',0],[$orderTable.'.status','>=',3],[$orderTable.'.is_del',0],[$productTable.'.status',1],[$vendorTable.'.is_on',1]])
            ->select([
                $productModelTable.'.id as product_model_id',
                $productTable.'.id as product_id',
                DB::raw("SUM(1) as hits"),
                $vendorTable.'.id as vendor_id',
                $productTable.'.category_id',
            ])->groupBy($orderItemTable.'.product_model_id')->orderBy('hits','desc')->get();

        foreach($orderItems as $item){
            $data[] = $item->toArray();
        }
        $hits = array_chunk($data,300);
        for($i=0;$i<count($hits);$i++){
            HotProductDB::insert($hits[$i]);
        }
    }
}
