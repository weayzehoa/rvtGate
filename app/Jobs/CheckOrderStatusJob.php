<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Jobs\CheckOrderSellJob;
use App\Traits\OrderFunctionTrait;

class CheckOrderStatusJob implements ShouldQueue
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
        $orders = $this->getOrderData($this->param,'CheckOrder');
        if (count($orders) > 0) {
            foreach($orders as $order){
                $order = $this->orderItemSplit($this->oneOrderItemTransfer($order),'single');
                if(!empty($order) && ($order->status == 2 || $order->status == 1)){
                    CheckOrderSellJob::dispatchNow($order);
                }
            }

        }
    }
}
