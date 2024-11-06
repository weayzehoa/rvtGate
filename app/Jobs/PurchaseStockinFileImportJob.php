<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\StockinImport as StockinImportDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseOrderItemPackage as PurchaseOrderItemPackageDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\ErpPURTG as ErpPURTGDB;
use App\Models\ErpPURTH as ErpPURTHDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\StockinItemSingle as StockinItemSingleDB;
use App\Models\PurchaseOrderChangeLog as PurchaseOrderChangeLogDB;
use App\Models\iCarryProductQuantityRecord as ProductQuantityRecordDB; //gtin13修改記錄在這
use App\Imports\StockinImport;
use App\Imports\PurchaseOrderStockinImport;
use DB;

class PurchaseStockinFileImportJob implements ShouldQueue
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
        if($param['test'] == true){
            $newParam['test'] = $param['test'];
            $newParam['import_no'] = $param['import_no'];
            $newParam['admin_id'] = $param['admin_id'];
            $newParam['cate'] = $param['cate'];
            return $newParam;
        }
        $purchaseNos = [];
        if($param['cate'] == 'stockin'){
            // Excel::import(new StockinImport($param), $param['filename']);
            $result = Excel::toArray(new PurchaseOrderStockinImport, $param['filename']); //0代表第一個sheet
            if(count($result) == 2){
                $tmp = $result[1]; //1代表第二個sheet
                foreach($tmp as $t){
                    is_numeric($t[0]) ? $purchaseNos[] = (INT)$t[0] : '';
                }
                $chkRows = 0;
                $data = $result[0];
                for($i=0;$i<count($data);$i++){
                    count($data[$i]) != 16 ? $chkRows++ : '';
                }
                if(count($purchaseNos) <= 0){
                    return 'purhcase no error';
                }elseif($chkRows > 0){
                    return 'rows error';
                }else{
                    $this->purchaseNos = $purchaseNos;
                    return $this->stockImport($result);
                }
            }else{
                return 'sheets error';
            }
        }
    }

    private function stockImport($result)
    {
        $param = $this->param;
        $purchaseNos = $this->purchaseNos;
        $c = $chkN = $i=0;
        $importData = $stockins = [];
        $warehouseExportTime = $result[0][1][1];
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $purchaseOrderItemSingleTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemSingleDB)->getTable();
        $exclude = [0,293,340,351,288,346]; //排除鼎新內無資料的商家
        //檢查欄位Y值
        foreach($result[0] as $stockin){
            if ($c>=2) {
                if($stockin[14] != 'N'){
                    $chkN++;
                }
            }
            $c++;
        }
        if($chkN == 0){
            foreach($result[0] as $stockin){
                if ($i>=2) {
                    if($this->chkData($stockin) == true){
                        if (!empty($stockin[11]) && $stockin[11] > 0 && !empty($stockin[12]) && $stockin[14] == 'N') {
                            $gtin13 = $stockin[7];
                            !empty($stockin[13]) ? $expiryDate = substr($stockin[13],0,4).'-'.substr($stockin[13],4,2).'-'.substr($stockin[13],6,2) : $expiryDate = null;
                            $chk = StockinImportDB::where([ ['warehouse_stockin_no',$stockin[5]], ['gtin13',$stockin[7]] ])->first();
                            if(empty($chk)){
                                //改抓採購單上的vendor_id
                                $productModel = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                ->join($purchaseOrderItemSingleTable,$purchaseOrderItemSingleTable.'.product_model_id',$productModelTable.'.id')
                                ->join($purchaseOrderTable,$purchaseOrderTable.'.purchase_no',$purchaseOrderItemSingleTable.'.purchase_no')
                                ->whereIn($purchaseOrderItemSingleTable.'.purchase_no',$purchaseNos)
                                ->where($purchaseOrderItemSingleTable.'.is_del',0)
                                ->where($purchaseOrderItemSingleTable.'.is_close',0)
                                ->where($purchaseOrderTable.'.is_del',0)
                                ->where($purchaseOrderTable.'.is_lock',0)
                                ->where($purchaseOrderTable.'.status',1)
                                ->where($purchaseOrderItemSingleTable.'.gtin13',$gtin13)
                                ->whereNotIn($productTable.'.vendor_id',$exclude) //排除商家id
                                ->select([
                                    $purchaseOrderTable.'.vendor_id',
                                ])->GroupBy($productModelTable.'.id')->first();
                                if(!empty($productModel)){
                                    $importData[] = [
                                        'import_no' => $this->param['import_no'],
                                        'warehouse_export_time' => $warehouseExportTime,
                                        'warehouse_stockin_no' => $stockin[5],
                                        'vendor_id' => $productModel->vendor_id,
                                        'gtin13' => $gtin13,
                                        'product_name' => $stockin[8],
                                        'expected_quantity' => !empty($stockin[10]) ? (INT)ceil($stockin[10]) : 0,
                                        'stockin_quantity' => !empty($stockin[11]) ? (INT)ceil($stockin[11]) : 0,
                                        'stockin_time' => !empty(ltrim($stockin[12],' ')) ? ltrim($stockin[12],' ') : null,
                                        'purchase_nos' => join(',',$purchaseNos),
                                        'row_no' => $i+1,
                                        'expiry_date' => $expiryDate,
                                        'type' => $stockin[14],
                                        'created_at' => date('Y-m-d H:i:s'),
                                    ];
                                }
                            }
                        }
                    }
                }
                $i++;
            }
            if(!empty($importData)){
                StockinImportDB::insert($importData);
                $newParam['import_no'] = $param['import_no'];
                $newParam['admin_id'] = $param['admin_id'];
                $newParam['cate'] = $param['cate'];
                return $newParam;
            }else{
                return 'no data';
            }
        }else{
            return 'no N value';
        }
    }

    private function chkData($result)
    {
        $count = count($result);
        $chk = 0;
        for($i=0;$i<count($result);$i++){
            empty($result[$i]) ? $chk++ : '';
        }
        if($chk != count($result)){ //表示有資料
            return true;
        }else{ //表示全部空值
            return false;
        }
    }
}
