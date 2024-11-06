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
use App\Models\RequisitionAbnormal as RequisitionAbnormalDB;
use App\Imports\StockinImport;
use App\Imports\PurchaseOrderStockinImport;
use DB;

use App\Traits\UniversalFunctionTrait;

class SellReturnStockinFileImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,UniversalFunctionTrait;
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
        if($param['cate'] == 'stockin'){
            // Excel::import(new StockinImport($param), $param['filename']);
            $result = Excel::toArray(new PurchaseOrderStockinImport, $param['filename']); //0代表第一個sheet
            $chkRows = 0;
            $data = $result[0];
            for($i=0;$i<count($data);$i++){
                count($data[$i]) != 16 ? $chkRows++ : '';
            }
            if($chkRows > 0){
                return 'rows error';
            }else{
                return $this->stockImport($result);
            }
        }
    }

    private function stockImport($result)
    {
        $param = $this->param;
        $c = $chkY = $i=0;
        $importData = $stockins = [];
        $warehouseExportTime = $result[0][1][1];
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemSingleTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemSingleDB)->getTable();
        $exclude = [0,293,340,351,288,346]; //排除鼎新內無資料的商家
        //檢查欄位Y值
        foreach($result[0] as $stockin){
            if ($c>=2) {
                if($stockin[14] != 'Y'){
                    $chkY++;
                }
            }
            $c++;
        }
        if($chkY == 0){
            foreach($result[0] as $stockin){
                if ($i>=2) {
                    if (!empty($stockin[11]) && $stockin[11] > 0 && !empty($stockin[12]) && $stockin[14] == 'Y') {
                        $gtin13 = $stockin[7];
                        !empty($stockin[13]) ? $expiryDate = substr($stockin[13],0,4).'-'.substr($stockin[13],4,2).'-'.substr($stockin[13],6,2) : $expiryDate = null;
                        $productModel = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                        ->where($productModelTable.'.gtin13',$gtin13)
                        ->whereNotIn($productTable.'.vendor_id',$exclude) //排除商家id
                        ->select([
                            $productTable.'.vendor_id',
                        ])->orderBy($productModelTable.'.id','desc')->first();
                        if(empty($productModel)){ //找修改紀錄
                            $tmp = ProductQuantityRecordDB::where('before_gtin13',"$gtin13")->orwhere('after_gtin13',"$gtin13")->orderBy('create_time','desc')->first();
                            if(!empty($tmp)){
                                $productModelId = $tmp->product_model_id;
                                $productModel = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                                ->whereNotIn($productTable.'.vendor_id',$exclude) //排除商家id
                                ->select([
                                    $productTable.'.vendor_id',
                                ])->find($tmp->product_model_id);
                            }
                        }
                        if(empty($productModel)){
                            $productModel = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                            ->where($productModelTable.'.sku',$gtin13)
                            ->whereNotIn($productTable.'.vendor_id',$exclude) //排除商家id
                            ->select([
                                $productTable.'.vendor_id',
                            ])->orderBy($productModelTable.'.id','desc')->first();
                        }
                        if(!empty($productModel)){
                            $rowNo = $i+1;
                            if($this->chkDate($expiryDate) == false){
                                RequisitionAbnormalDB::create([
                                    'stockin_import_id' => rand(1000000,9999999),
                                    'import_no' => $this->param['import_no'],
                                    'gtin13' => $gtin13,
                                    'product_name' => $stockin[8],
                                    'quantity' => $stockin[11],
                                    'stockin_date' => $stockin[12],
                                    'memo' => "檔案中第 $rowNo 列，匯入的到期日 $expiryDate 錯誤。",
                                    'created_at' => date('Y-m-d'),
                                ]);
                            }else{
                                $importData[] = [
                                    'import_no' => $this->param['import_no'],
                                    'warehouse_export_time' => $warehouseExportTime,
                                    'warehouse_stockin_no' => $stockin[5],
                                    'vendor_id' => $productModel->vendor_id,
                                    'gtin13' => $gtin13,
                                    'product_name' => $stockin[8],
                                    'expected_quantity' => (INT)ceil($stockin[10]),
                                    'stockin_quantity' => !empty($stockin[11]) ? (INT)ceil($stockin[11]) : 0,
                                    'stockin_time' => !empty(ltrim($stockin[12],' ')) ? ltrim($stockin[12],' ') : null,
                                    'purchase_nos' => null,
                                    'row_no' => $i+1,
                                    'expiry_date' => $expiryDate,
                                    'type' => $stockin[14],
                                    'created_at' => date('Y-m-d H:i:s'),
                                ];
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
            return 'no Y value';
        }
    }
}
