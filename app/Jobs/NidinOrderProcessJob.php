<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Jobs\iCarryOrderSynchronizeToDigiwinJob;
use App\Models\NidinOrder as nidinOrderDB;

class NidinOrderProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $purchaseSyncParam['admin_id'] = $syncOrderParam['admin_id'] = 1;
        $nidinOrder = nidinOrderDB::find($param['nidinOrderId']);
        if($nidinOrder->is_sync == 0){
            $syncOrderParam['id'] = $param['orderId'];
            $syncOrderParam['admin_name'] = '中繼系統';
            $result = iCarryOrderSynchronizeToDigiwinJob::dispatchNow($syncOrderParam);
        }
    }
}
