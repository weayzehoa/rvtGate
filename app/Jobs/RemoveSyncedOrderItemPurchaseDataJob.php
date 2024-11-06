<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\SyncedOrderItemPackage as SyncedOrderItemPackageDB;

use App\Traits\PurchaseOrderFunctionTrait;

class RemoveSyncedOrderItemPurchaseDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,PurchaseOrderFunctionTrait;

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
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $syncedOrderItemTable = env('DB_DATABASE').'.'.(new SyncedOrderItemDB)->getTable();
        if(count($param['synceOrderItemIds']) > 0){
            for($i=0;$i<count($param['synceOrderItemIds']);$i++){
                $item = SyncedOrderItemDB::with('package')
                    ->join($productModelTable,$productModelTable.'.id',$syncedOrderItemTable.'.product_model_id')
                    ->select([
                        $syncedOrderItemTable.'.*',
                        $productModelTable.'.sku',
                    ])->find($param['synceOrderItemIds'][$i]);
                if(strstr($item->sku,'BOM')){
                    foreach($item->package as $package){
                        $package->update(['purchase_no' => null, 'purchase_date' => null]);
                    }
                }
                $item->update(['purchase_no' => null, 'purchase_date' => null]);
            }
        }
    }
}
