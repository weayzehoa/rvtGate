<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Traits\OrderFunctionTrait;

use App\Jobs\OrderExportDigiWinOneOrderJob;

class OrderExportDigiWinJob implements ShouldQueue
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
        $orders = $this->getOrderData($this->param,'getOrderNumbers');
        if (count($orders) > 0) {
            foreach($orders as $order){
                env('APP_ENV') == 'local' ? OrderExportDigiWinOneOrderJob::dispatchNow($this->param,$order->order_number) : OrderExportDigiWinOneOrderJob::dispatch($this->param,$order->order_number);
            }
        }
    }
}
