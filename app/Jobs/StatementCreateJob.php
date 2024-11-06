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
use App\Models\AcOrder as AcOrderDB;

use App\Exports\StatementExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\StockinItemSingle as StockinItemSingleDB;
use Storage;
use Session;
use File;
use PDF;
use DB;

class StatementCreateJob implements ShouldQueue
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
        $param = $this->param; //轉陣列
        //檢查目錄是否存在，不存在則建立
        $destPath = '/exports/statements/';
        $s3destPath = '/upload/statement/';
        !file_exists(public_path().$destPath) ? File::makeDirectory(public_path().$destPath, 0755, true) : '';
        !Storage::disk('s3')->has($destPath) ? Storage::disk('s3')->makeDirectory($s3destPath) : '';
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $stockinItemSingleTable = env('DB_DATABASE').'.'.(new StockinItemSingleDB)->getTable();
        $returnDiscountTable = env('DB_DATABASE').'.'.(new ReturnDiscountDB)->getTable();
        $returnDiscountItemTable = env('DB_DATABASE').'.'.(new ReturnDiscountItemDB)->getTable();
        $acOrderTable = env('DB_DATABASE').'.'.(new AcOrderDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();

        $stockinData = StockinItemSingleDB::with('purchaseOrderItem')
        ->join($purchaseOrderTable,$purchaseOrderTable.'.purchase_no',$stockinItemSingleTable.'.purchase_no');
        //不拘廠商
        if($param['vendorIds'][0] != null){
            $stockinData = $stockinData->whereIn($purchaseOrderTable.'.vendor_id',$param['vendorIds']);
        }
        $stockinData = $stockinData->whereBetween($stockinItemSingleTable.'.stockin_date',[$param['start_date'],$param['end_date']])
        ->where($stockinItemSingleTable.'.is_del',0)
        ->where($stockinItemSingleTable.'.is_lock',0)
        ->select([
            $stockinItemSingleTable.'.*',
            $purchaseOrderTable.'.vendor_id',
            $purchaseOrderTable.'.purchase_no',
            'serial_no' => AcOrderDB::whereColumn($acOrderTable.'.purchase_no',$stockinItemSingleTable.'.purchase_no')->select($acOrderTable.'.serial_no')->limit(1),
        ])->groupBy($stockinItemSingleTable.'.poi_id')->get();
        if(count($stockinData) > 0){
            $stockinData = $stockinData->groupBy('vendor_id');
            foreach($stockinData as $vendorId => $stockinItems){
                $param['vendor'] = $vendor = VendorDB::find($vendorId);
                $param['filename'] = 'A'.str_pad($vendorId,5,'0',STR_PAD_LEFT).' '.$vendor->name.' '.$param['start_date'].'~'.$param['end_date'].' 對帳單_'.time().'.xlsx';
                $param['stockinItems'] = $stockinItems;
                $param['discounts'] = $discounts = ReturnDiscountDB::with('items')->where([['type','A352'],['is_del',0],['vendor_id',$vendor->id]])->whereBetween('return_date',[$param['start_date'],$param['end_date']])->get();
                if(count($stockinItems) > 0 || count($discounts) > 0){
                    Excel::store(new StatementExport($param), $destPath.$param['filename'], 'real_public');
                    //上傳一份到S3
                    if(file_exists(public_path().$destPath.$param['filename'])){
                        Storage::disk('s3')->put($s3destPath.$param['filename'], file_get_contents(public_path().$destPath.$param['filename']), 'public');
                    }
                }
            }
        }else{
            $discountData = ReturnDiscountDB::with('items')->whereIn('vendor_id',$param['vendorIds'])->where([['type','A352'],['is_del',0],['is_lock',0]])->whereBetween('return_date',[$param['start_date'],$param['end_date']])->get();
            if(count($discountData) > 0){
                $discountData = $discountData->groupBy('vendor_id');
                foreach($discountData as $vendorId => $discounts){
                    $param['vendor'] = $vendor = VendorDB::find($vendorId);
                    $param['filename'] = 'A'.str_pad($vendorId,5,'0',STR_PAD_LEFT).' '.$vendor->name.' '.$param['start_date'].'~'.$param['end_date'].' 對帳單_'.time().'.xlsx';
                    $param['discounts'] = $discounts;
                    $param['stockinItems'] = [];
                    if(count($discounts) > 0){
                        Excel::store(new StatementExport($param), $destPath.$param['filename'], 'real_public');
                        //上傳一份到S3
                        if(file_exists(public_path().$destPath.$param['filename'])){
                            Storage::disk('s3')->put($s3destPath.$param['filename'], file_get_contents(public_path().$destPath.$param['filename']), 'public');
                        }
                    }
                }
            }else{
                $noData = 0;
                $vendorIds = $param['vendorIds'];
                for($i=0;$i<count($vendorIds);$i++){
                    $param['vendor'] = $vendor = VendorDB::find($vendorIds[$i]);
                    $param['filename'] = 'A'.str_pad($vendorIds[$i],5,'0',STR_PAD_LEFT).' '.$vendor->name.' '.$param['start_date'].'~'.$param['end_date'].' 對帳單_'.time().'.xlsx';
                    $param['stockinItems'] = $param['discounts'] = [];
                    if(in_array($vendor->id,[729,730])){ //你訂退貨=取消訂單內商品
                        $returnItems = OrderItemDB::join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
                        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                        ->whereBetween($orderItemTable.'.return_date',[$param['start_date'],$param['end_date']])
                        ->whereNotNull($orderItemTable.'.ticket_no')
                        ->where([
                            [$vendorTable.'.id',$vendorIds[$i]],
                            [$orderItemTable.'.is_del',1],
                            [$orderItemTable.'.is_statement',0]
                        ])->get();
                    }else{
                        //找未被列入的退貨
                        $returnItems = ReturnDiscountItemDB::join($returnDiscountTable,$returnDiscountTable.'.return_discount_no',$returnDiscountTable.'.return_discount_no')
                        ->join($productModelTable,$productModelTable.'.id',$returnDiscountItemTable.'.product_model_id')
                        ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                        ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                        ->where([
                            [$returnDiscountTable.'.vendor_id',$vendorIds[$i]],
                            [$returnDiscountTable.'.type','A351'],
                            [$returnDiscountTable.'.is_del',0],
                            [$returnDiscountTable.'.is_lock',0],
                            [$returnDiscountItemTable.'.is_del',0],
                            [$returnDiscountItemTable.'.is_lock',0],
                        ])->whereBetween($returnDiscountTable.'.return_date',[$param['start_date'],$param['end_date']])
                        ->select([
                            $returnDiscountItemTable.'.*',
                            $returnDiscountTable.'.return_date',
                            $productModelTable.'.digiwin_no',
                            $productTable.'.serving_size',
                            $productTable.'.unit_name',
                            DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                            'serial_no' => AcOrderDB::whereColumn($acOrderTable.'.purchase_no',$returnDiscountItemTable.'.purchase_no')->select($acOrderTable.'.serial_no')->limit(1),
                        ])->get();
                    }
                    if(count($returnItems) > 0){
                        Excel::store(new StatementExport($param), $destPath.$param['filename'], 'real_public');
                        //上傳一份到S3
                        if(file_exists(public_path().$destPath.$param['filename'])){
                            Storage::disk('s3')->put($s3destPath.$param['filename'], file_get_contents(public_path().$destPath.$param['filename']), 'public');
                        }
                    }else{
                        $noData++;
                    }
                }
                if($noData == count($param['vendorIds'])){
                    return 'no data';
                }
            }
        }
    }
}
