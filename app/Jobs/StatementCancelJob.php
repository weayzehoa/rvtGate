<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\ReturnDiscount as ReturnDiscountDB;
use App\Models\ReturnDiscountItem as ReturnDiscountItemDB;
use App\Models\NidinSetBalance as NidinSetBalanceDB;

use App\Exports\StatementExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\Statement as StatementDB;

use Session;
use Storage;
use File;
use PDF;
use DB;

class StatementCancelJob implements ShouldQueue
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
        putenv('AWS_SUPPRESS_PHP_DEPRECATION_WARNING=1');
        $param = $this->param;
        //檢查目錄是否存在，不存在則建立
        env('APP_ENV') == 'local' ? $destPath = '/exports/statements/' : $destPath = '/upload/statement/';
        env('APP_ENV') == 'local' ? (!file_exists($destPath) ? File::makeDirectory($destPath, 0755, true) : '') : (!Storage::disk('s3')->has($destPath) ? Storage::disk('s3')->makeDirectory($destPath) : '');

        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $stockinItemSingleTable = env('DB_DATABASE').'.'.(new StockinItemSingleDB)->getTable();

        $statement = StatementDB::find($param['id']);
        if(!empty($statement)){
            $startDate = $statement->start_date;
            $endDate = $statement->end_date;
            if(!empty($statement->purchase_item_ids)){ //解除採購單相關鎖定
                $purchaseOrderItems = PurchaseOrderItemDB::with('stockins','returns','exportPackage','exportPackage.stockins','exportPackage.returns')
                    ->join($productModelTable,$productModelTable.'.id',$purchaseOrderItemTable.'.product_model_id')
                    ->whereIn($purchaseOrderItemTable.'.id',explode(',',$statement->purchase_item_ids))
                    ->select([
                        $purchaseOrderItemTable.'.*',
                        $productModelTable.'.sku',
                    ])->get();
                foreach($purchaseOrderItems as $item){
                    $item->update(['is_lock' => 0]);
                    if(count($item->stockins) > 0){
                        foreach($item->stockins as $stockin){
                            if(strtotime($startDate) <= strtotime($stockin->stockin_date) && strtotime($stockin->stockin_date) <= strtotime($endDate)){
                                $stockin->update(['is_lock' => 0, 'statement_no' => null]);
                            }
                        }
                    }
                    if(count($item->returns) > 0){
                        foreach($item->returns as $return){
                            $return->update(['is_lock' => 0]);
                        }
                    }
                    if(strstr($item->sku,'BOM')){
                        foreach($item->exportPackage as $package){
                            if(count($package->stockins) > 0){
                                foreach($package->stockins as $stockin){
                                    $stockin->update(['is_lock' => 0]);
                                }
                            }
                        }
                    }
                }
            }
            if(!empty($statement->return_discount_ids)){ //解除折抵退貨單相關鎖定
                $returnDiscountItems = ReturnDiscountItemDB::whereIn('id',explode(',',$statement->return_discount_ids))->get();
                foreach($returnDiscountItems as $item){
                    $item->update(['is_lock' => 0]);
                    $returnDiscount = ReturnDiscountDB::where('return_discount_no',$item->return_discount_no)->first();
                    if(!empty($returnDiscount)){
                        $returnDiscount->update(['is_lock' => 0]);
                    }
                }
            }
            if(!empty($statement->return_order_item_ids)){ //解除OrderItem退貨單相關鎖定
                $orderItems = OrderItemDB::whereIn('id',explode(',',$statement->return_order_item_ids))->get();
                foreach($orderItems as $item){
                    $item->update(['is_statement' => 0]);
                }
            }
            if(!empty($statement->set_item_ids)){ //解除NidinSetBalance套票相關鎖定
                $setItems = NidinSetBalanceDB::whereIn('id',explode(',',$statement->set_item_ids))->get();
                foreach($setItems as $setItem){
                    $setItem->update(['is_lock' => 0]);
                }
            }
            //刪除檔案, 測試區刪除, 正式區S3不刪除
            env('APP_ENV') == 'local' ? (file_exists(public_path().$destPath.$statement->filename) ? unlink(public_path().$destPath.$statement->filename) : '') : '';
            $statement->update(['is_del' => 1]);
        }
    }
}
