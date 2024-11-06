<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Traits\OrderFunctionTrait;

class addNotPurchaseMarkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,OrderFunctionTrait;

    protected $param;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $param = $this->param;
        $orders = $this->getOrderData($this->param);
        if (count($orders) > 0) {
            foreach($orders as $order){
                if(!empty($order->syncedOrder)){ //已同步
                    foreach($order->syncedItems as $syncedItem){
                        if(empty($syncedItem->purchase_no)){
                            foreach($order->items as $item){
                                if($item->id == $syncedItem->order_item_id){
                                    $item->update(['not_purchase' => 1]);
                                    break;
                                }
                            }
                            $syncedItem->update(['not_purchase' => 1]);
                        }
                    }
                }else{
                    foreach($order->items as $item){
                        $item->update(['not_purchase' => 1]);
                    }
                }
            }

        }
    }
}
