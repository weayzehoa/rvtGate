<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ExportCenter as ExportCenterDB;
use DB;
use File;
use Zip;
use App\Traits\PurchaseOrderFunctionTrait;
use App\Jobs\PurchaseOrderNoticeVendorOneOrderJob;
use App\Jobs\PurchaseOrderNoticeVendorNewOneOrderJob;
use App\Jobs\PurchaseOrderNoticeVendorModify;

class PurchaseOrderNoticeVendorJob implements ShouldQueue
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
        //目的目錄
        $destPath = storage_path('app/exports/');
        //檢查本地目錄是否存在，不存在則建立
        !file_exists($destPath) ? File::makeDirectory($destPath, 0755, true) : '';
        //找出採購單資料, 包含商品資料
        $data = $this->getPurchaseOrderData($this->param);
        if(count($data) > 0){
            $purchaseOrders = $data->groupBy('vendor_name')->all();
            $x = 0;
            $files = [];
            foreach($purchaseOrders as $vendorName => $orders){
                $result = null;
                $param['vendorName'] = $vendorName;
                $param['orders'] = $orders;
                if($param['cate'] == 'NoticeVendor'){
                    $param['version'] = 'new';
                    if ($param['type'] == 'Email') {
                        env('APP_ENV') == 'local' ? PurchaseOrderNoticeVendorNewOneOrderJob::dispatchNow($param) : PurchaseOrderNoticeVendorNewOneOrderJob::dispatch($param);
                    }elseif($param['type'] == 'Download'){
                        $result = PurchaseOrderNoticeVendorNewOneOrderJob::dispatchNow($param);
                    }else{
                        env('APP_ENV') == 'local' ? PurchaseOrderNoticeVendorModify::dispatchNow($param) : PurchaseOrderNoticeVendorModify::dispatch($param);
                    }
                }elseif($param['cate'] == 'Notice'){
                    $param['version'] = 'old';
                    if ($param['type'] == 'Email') {
                        env('APP_ENV') == 'local' ? PurchaseOrderNoticeVendorOneOrderJob::dispatchNow($param) : PurchaseOrderNoticeVendorOneOrderJob::dispatch($param);
                    }else{
                        $result = PurchaseOrderNoticeVendorOneOrderJob::dispatchNow($param);
                    }
                }
                if(!empty($result)){
                    $files[$x] = $result;
                }
                $x++;
            }
            if ($param['type'] == 'Download' && count($files) > 0) {
                $file = $param['filename'];
                $zip = Zip::create( $destPath . $file);
                for($i=0; $i<count($files);$i++){
                    for($j=0; $j<count($files[$i]); $j++){
                        $addFiles[] = $destPath . $files[$i][$j];
                    }
                }
                $zip->add($addFiles);
                $zip->close();
                //儲存紀錄到匯出中心資料表
                $param['end_time'] = date('Y-m-d H:i:s');
                $param['condition'] = json_encode($param,true);
                $param['cate'] = $param['model'];
                $log = ExportCenterDB::create($param);
                //刪除檔案
                for($xx = 0; $xx<count($files); $xx++){
                    for($y=0; $y<count($files[$xx]);$y++){
                        unlink($destPath . $files[$xx][$y]);
                    }
                }
            }
        }
    }
}
