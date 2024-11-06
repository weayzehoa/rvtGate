<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\ErpINVTA as ErpINVTADB;
use App\Models\ErpINVTB as ErpINVTBDB;
use App\Models\StockinImport as StockinImportDB;
use App\Models\SellReturn as SellReturnDB;
use App\Models\SellReturnItem as SellReturnItemDB;
use App\Models\RequisitionAbnormal as RequisitionAbnormalDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use DB;

class SellReturnStockinImportJob implements ShouldQueue
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
        $abnormals = $erpINVTA = $erpINVTB = [];
        $param = $this->param;
        $createDate = date('Ymd');
        $createTime = date('H:i:s');
        $stockinFinishDate = date('Y-m-d');
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        !empty($param['admin_id']) ? $adminId = $param['admin_id'] : $adminId = null;
        !empty(auth('gate')->user()->account) ? $creator = strtoupper(auth('gate')->user()->account) : $creator = 'DS';
        //測試用要設定參數
        if(!empty($param['test']) && $param['test'] == true){
            $chkDate = StockinImportDB::where('import_no',1668585958)
            ->select([
                'stockin_time',
                DB::raw("DATE_FORMAT(stockin_time,'%Y%m%d') as stockinDate"),
            ])->groupBy('stockin_time')->first();
            $TA003 = $TA014 = $chkDate->stockinDate;
        }else{
            $chkDate = StockinImportDB::where('import_no',$param['import_no'])
            ->select([
                'stockin_time',
                DB::raw("DATE_FORMAT(stockin_time,'%Y%m%d') as stockinDate"),
            ])->groupBy('stockin_time')->first();
            $TA003 = $TA014 = $chkDate->stockinDate;
        }
        $six = substr($TA014,2);
        $tmp = ErpINVTADB::where('TA002','like',"$six%")->select('TA002')->orderBy('TA002','desc')->first();
        !empty($tmp) ? $TA002 = $tmp->TA002 + 1 : $TA002 = $six.str_pad(1,5,0,STR_PAD_LEFT);;
        if(!empty($param['test']) && $param['test'] == true){
            $stockins = StockinImportDB::where('import_no',1668585958)->get();
        }else{
            $stockins = StockinImportDB::where('import_no',$param['import_no'])->get();
        }
        $c = 1;
        if(count($stockins) > 0){
            foreach($stockins as $stockin){
                $rowNo = $stockin->row_no;
                $gtin13 = $stockin->gtin13;
                $stockinQty = $stockin->stockin_quantity;
                $productModels = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                    ->join($vendorTable,$vendorTable.'.id',$productTable.'.vendor_id')
                    ->where('gtin13',$gtin13)
                    ->select([
                        $productModelTable.'.*',
                        $productTable.'.serving_size',
                        $productTable.'.unit_name',
                        DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                    ])->get();
                if(count($productModels) > 0){
                    $chkItems = 0;
                    $sellReturnTable = env('DB_DATABASE').'.'.(new SellReturnDB)->getTable();
                    $sellReturnItemTable = env('DB_DATABASE').'.'.(new SellReturnItemDB)->getTable();
                    foreach($productModels as $productModel){
                        $sellReturnItems = SellReturnItemDB::join($sellReturnTable,$sellReturnTable.'.return_no',$sellReturnItemTable.'.return_no')
                        ->where($sellReturnTable.'.type','銷退')
                        ->where($sellReturnItemTable.'.origin_digiwin_no',$productModel->digiwin_no)
                        ->whereNull($sellReturnItemTable.'.erp_requisition_no')
                        ->where([[$sellReturnItemTable.'.is_del',0],[$sellReturnItemTable.'.is_chk',0],[$sellReturnItemTable.'.is_stockin',0]])
                        ->select([
                            $sellReturnItemTable.'.*',
                        ])->orderBy($sellReturnItemTable.'.created_at','asc')->get();
                        if(count($sellReturnItems) > 0){
                            $needQty = count($sellReturnItems);
                            $chkItems++;
                            $remindQty = $stockinQty;
                            if($stockinQty <= $needQty){
                                foreach($sellReturnItems as $item){
                                    $erpINVTB[] = [
                                        'COMPANY' => 'iCarry',
                                        'CREATOR' => $creator,
                                        'USR_GROUP' => 'DSC',
                                        'CREATE_DATE' => $createDate,
                                        'MODIFIER' => '',
                                        'MODI_DATE' => '',
                                        'FLAG' => '1',
                                        'CREATE_TIME' => $createTime,
                                        'CREATE_AP' => 'iCarry',
                                        'CREATE_PRID' => 'COPI08',
                                        'MODI_TIME' => '',
                                        'MODI_AP' => '',
                                        'MODI_PRID' => '',
                                        'EF_ERPMA001' => '',
                                        'EF_ERPMA002' => '',
                                        'TB001' => 'A121',  //單別
                                        'TB002' => $TA002,  //單號
                                        'TB003' => str_pad($c,4,'0',STR_PAD_LEFT),  //序號
                                        'TB004' => $item->origin_digiwin_no,  //品號
                                        'TB005' => $productModel->product_name,  //品名
                                        'TB006' => $productModel->serving_size,  //規格
                                        'TB007' => 1,  //數量
                                        'TB008' => $productModel->unit_name,  //單位
                                        'TB009' => 0,  //庫存數量
                                        'TB010' => 0,  //單位成本
                                        'TB011' => 0,  //金額
                                        'TB012' => 'W12',  //轉出庫
                                        'TB013' => 'W01',  //轉入庫
                                        'TB014' => '',  //批號
                                        'TB015' => '',  //有效日期
                                        'TB016' => '',  //複檢日期
                                        'TB017' => '',  //備註
                                        'TB018' => 'N',  //確認碼
                                        'TB019' => $TA003,  //異動日期
                                        'TB020' => '',  //小單位
                                        'TB021' => '',  //專案代號
                                        'TB022' => null,  //包裝數量
                                        'TB023' => '',  //包裝單位
                                        'TB025' => null,  //產品序號數量
                                    ];
                                    //更新被沖銷的商品資料
                                    $item->update([
                                        'erp_requisition_type' => 'A121',
                                        'erp_requisition_no' => $TA002,
                                        'erp_requisition_sno' => str_pad($c,4,'0',STR_PAD_LEFT),
                                        'expiry_date' => $stockin->expiry_date,
                                        'is_chk' => strtotime($stockin->expiry_date) <= strtotime(date('Y-m-d')) ? 1 : 0,
                                        'chk_date' => strtotime($stockin->expiry_date) <= strtotime(date('Y-m-d')) ? date('Y-m-d H:i:s') : null,
                                        'is_stockin' => 1,
                                        'admin_id' => strtotime($stockin->expiry_date) <= strtotime(date('Y-m-d')) ? $adminId : null,
                                        'stockin_admin_id' => $adminId,
                                    ]);
                                    $c++;
                                    $remindQty--;
                                    if($remindQty == 0){
                                        break;
                                    }
                                }
                            }else{
                                $abnormals[] = [
                                    'stockin_import_id' => $stockin->id,
                                    'import_no' => $stockin->import_no,
                                    'gtin13' => $gtin13,
                                    'product_name' => $stockin->product_name,
                                    'quantity' => $stockin->stockin_quantity,
                                    'expiry_date' => $stockin->expiry_date,
                                    'stockin_date' => $stockin->stockin_time,
                                    'memo' => "檔案中第 $rowNo 列，匯入的條碼 $gtin13 及數量 $stockinQty 超過需要沖銷的數量 $needQty 。",
                                    'created_at' => date('Y-m-d'),
                                ];
                            }
                        }
                    }
                    if($chkItems == 0){ //找不到可沖銷的資料
                        $abnormals[] = [
                            'stockin_import_id' => $stockin->id,
                            'import_no' => $stockin->import_no,
                            'gtin13' => $gtin13,
                            'product_name' => $stockin->product_name,
                            'quantity' => $stockin->stockin_quantity,
                            'expiry_date' => $stockin->expiry_date,
                            'stockin_date' => $stockin->stockin_time,
                            'memo' => "檔案中第 $rowNo 列，匯入的條碼 $gtin13 找不到可沖銷的資料。",
                            'created_at' => date('Y-m-d'),
                        ];
                    }
                }else{ //找不到商品
                    $abnormals[] = [
                        'stockin_import_id' => $stockin->id,
                        'import_no' => $stockin->import_no,
                        'gtin13' => $gtin13,
                        'product_name' => $stockin->product_name,
                        'quantity' => $stockin->stockin_quantity,
                        'expiry_date' => $stockin->expiry_date,
                        'stockin_date' => $stockin->stockin_time,
                        'memo' => "檔案中第 $rowNo 列，匯入的條碼 $gtin13 找不到對應的商品，請檢查是否輸入錯誤條碼號碼。",
                        'created_at' => date('Y-m-d'),
                    ];
                }
            }
            if(count($abnormals) > 0){
                RequisitionAbnormalDB::insert($abnormals);
            }
            if(count($erpINVTB) > 0){
                if(count($erpINVTB) >= 20) {
                    $items = array_chunk($erpINVTB, 20);
                    for($i=0;$i<count($items);$i++) {
                        ErpINVTBDB::insert($items[$i]);
                    }
                    ErpINVTADB::create([
                        'COMPANY' => 'iCarry',
                        'CREATOR' => $creator,
                        'USR_GROUP' => 'DSC',
                        'CREATE_DATE' => $createDate,
                        'MODIFIER' => '',
                        'MODI_DATE' => '',
                        'FLAG' => '1',
                        'CREATE_TIME' => $createTime,
                        'CREATE_AP' => 'iCarry',
                        'CREATE_PRID' => 'COPI08',
                        'MODI_TIME' => '',
                        'MODI_AP' => '',
                        'MODI_PRID' => '',
                        'EF_ERPMA001' => '',
                        'EF_ERPMA002' => '',
                        'TA001' => 'A121',  //單別
                        'TA002' => $TA002,  //單號
                        'TA003' => $TA003,  //異動日期
                        'TA004' => '',  //部門代號
                        'TA005' => '',  //備註
                        'TA006' => 'N',  //確認碼
                        'TA007' => 0,  //列印次數
                        'TA008' => '001',  //廠別代號
                        'TA009' => 12,  //單據性質碼
                        'TA010' => 0,  //件數
                        'TA011' => count($erpINVTB),  //總數量
                        'TA012' => 0,  //總金額
                        'TA013' => 'N',  //產生分錄碼
                        'TA014' => $TA003,  //單據日期
                        'TA015' => '',  //確認者
                        'TA016' => 'N',  //簽核狀態碼
                        'TA017' => 0,  //總包裝數量
                        'TA018' => 0,  //傳送次數
                        'TA025' => '',  //來源
                    ]);
                }else{
                    ErpINVTBDB::insert($erpINVTB);
                    ErpINVTADB::create([
                        'COMPANY' => 'iCarry',
                        'CREATOR' => $creator,
                        'USR_GROUP' => 'DSC',
                        'CREATE_DATE' => $createDate,
                        'MODIFIER' => '',
                        'MODI_DATE' => '',
                        'FLAG' => '1',
                        'CREATE_TIME' => $createTime,
                        'CREATE_AP' => 'iCarry',
                        'CREATE_PRID' => 'COPI08',
                        'MODI_TIME' => '',
                        'MODI_AP' => '',
                        'MODI_PRID' => '',
                        'EF_ERPMA001' => '',
                        'EF_ERPMA002' => '',
                        'TA001' => 'A121',  //單別
                        'TA002' => $TA002,  //單號
                        'TA003' => $TA003,  //異動日期
                        'TA004' => '',  //部門代號
                        'TA005' => '',  //備註
                        'TA006' => 'N',  //確認碼
                        'TA007' => 0,  //列印次數
                        'TA008' => '001',  //廠別代號
                        'TA009' => 12,  //單據性質碼
                        'TA010' => 0,  //件數
                        'TA011' => count($erpINVTB),  //總數量
                        'TA012' => 0,  //總金額
                        'TA013' => 'N',  //產生分錄碼
                        'TA014' => $TA003,  //單據日期
                        'TA015' => '',  //確認者
                        'TA016' => 'N',  //簽核狀態碼
                        'TA017' => 0,  //總包裝數量
                        'TA018' => 0,  //傳送次數
                        'TA025' => '',  //來源
                    ]);
                }
            }
        }
    }
}
