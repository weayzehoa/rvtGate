<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Jobs\AdminInvoiceJob;
use App\Jobs\iCarryOrderSynchronizeToDigiwinJob;
use App\Jobs\iCarryOrderToPurchaseOrderJob;
use App\Jobs\PurchaseOrderSynchronizeToDigiwinJob;
use App\Jobs\SellImportJob;
use App\Models\AcOrder as AcOrderDB;

class AcOrderProcessJob implements ShouldQueue
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
        $acOrder = AcOrderDB::find($param['acOrderId']);

        if($acOrder->is_sync == 0){
            $syncOrderParam['id'] = $param['orderId'];
            $syncOrderParam['admin_name'] = '中繼系統';
            $result = iCarryOrderSynchronizeToDigiwinJob::dispatchNow($syncOrderParam);
        }

        if(empty($acOrder->purchase_id)){
            $toPurchaseParam['orderIds'] = $param['orderId'];
            $result = iCarryOrderToPurchaseOrderJob::dispatchNow($toPurchaseParam);
        }

        $acOrder = AcOrderDB::find($param['acOrderId']);
        if(!empty($acOrder->purchase_id) && $acOrder->purchase_sync == 0){
            $purchaseSyncParam['id'] = $acOrder->purchase_id;
            $result = PurchaseOrderSynchronizeToDigiwinJob::dispatchNow($purchaseSyncParam);
        }

        if($acOrder->is_sell == 0){
            $sellParam['type'] = 'warehouse';
            $sellParam['order_number'] = $param['orderNumber'];
            $result = SellImportJob::dispatchNow($sellParam);
        }
    }
}
