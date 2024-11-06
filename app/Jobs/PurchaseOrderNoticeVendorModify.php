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
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\ErpPURTC as ErpPURTCDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\PurchaseNoticeFile as PurchaseNoticeFileDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\ExportCenter as ExportCenterDB;
use App\Models\SpecialVendor as SpecialVendorDB;
use App\Models\MailTemplate as MailTemplateDB;
use DB;
use File;
use PDF;
use Excel;
use Zip;
use Storage;
use App\Traits\PurchaseOrderFunctionTrait;
use App\Exports\Sheets\VendorDirectShipStockinSheet;
use App\Jobs\AdminSendEmail;

class PurchaseOrderNoticeVendorModify implements ShouldQueue
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
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemSingleTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemSingleDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        $spVendor = SpecialVendorDB::select('code')->orderBy('code','asc')->get()->pluck('code')->all();
        //目的目錄
        $destPath = storage_path('app/exports/');
        //檢查本地目錄是否存在，不存在則建立
        !file_exists($destPath) ? File::makeDirectory($destPath, 0755, true) : '';
        //找出採購單資料, 包含商品資料
        $data = $this->getPurchaseOrderData($this->param);

        if(count($data) > 0){
            $purchaseOrders = $data->groupBy('vendor_name')->all();
            foreach($purchaseOrders as $vendorName => $orders){
                $param['export_no'] = time();
                $vendorId = $orders[0]['vendor_id'];
                $vendor = VendorDB::find($vendorId);
                $companyName = $vendor->company;
                $purchaseOrderIds = $purchaseNos = [];
                foreach ($orders as $order) {
                    $syncedLog = PurchaseSyncedLogDB::where('purchase_order_id',$order->id)->whereNull('confirm_time')->orderBy('created_at','desc')->first();
                    if(!empty($syncedLog)){
                        $purchaseOrderIds[] = $order->id;
                        $i = $c = 1;
                        $purchasePrice = $totalQty = $totalPrice = $totalTax = $stockin = $directShipment = 0;
                        $productModelIds = $directShipProducts = [];
                        $orderIds = explode(',', $order->order_ids);
                        $tmp = ErpPURTCDB::where('TC001',$order->type)->find($order->erp_purchase_no);
                        foreach ($order->exportItems as $item) {
                            if($order->type != 'A332'){ //鼎新匯入的採購單已經算過稅額所以不再計算
                                //應稅內含與不計稅商家不扣掉稅
                                $tmp->TC018 != 4 && $tmp->TC018 != 1 && $tmp->TC018 != 9 ? $item->purchase_price = $item->purchase_price /1.05 : '';
                            }
                            $totalQty += $item->quantity;
                            $purchasePrice += $item->quantity * $item->purchase_price;
                            if ($item->direct_shipment == 1) {
                                $directShipment++;
                                $productModelIds[] = $item->product_model_id;
                            } else {
                                $stockin++;
                            }
                            if (strstr($item->sku, 'BOM')) {
                                foreach ($item->exportPackage as $package) {
                                    $package->snoForStockin = str_pad($c, 4, '0', STR_PAD_LEFT);
                                    $c++;
                                }
                            } else {
                                $item->snoForStockin = str_pad($c, 4, '0', STR_PAD_LEFT);
                                $c++;
                            }
                            $item->sno = str_pad($i, 4, '0', STR_PAD_LEFT);
                            $i++;
                        }
                        //課稅類別
                        if ($tmp->TC018 == 1) {
                            $purchasePrice = $purchasePrice / 1.05;
                            $order->taxType = '應稅內含';
                        } elseif ($tmp->TC018 == 2) {
                            $order->taxType = '應稅外加';
                        } elseif ($tmp->TC018 == 3) {
                            $order->taxType = '零稅率';
                        } elseif ($tmp->TC018 == 4) {
                            $order->taxType = '免稅';
                        } elseif ($tmp->TC018 == 9) {
                            $order->taxType = '不計稅';
                        } else {
                            $order->taxType = null;
                        }
                        $tmp = ErpVendorDB::find('A'.str_pad($order->vendor_id, 5, '0', STR_PAD_LEFT));
                        $order->payCondition = $tmp->MA025;
                        $order->purchasePrice = $purchasePrice;
                        $purchaseNos[] = $order->purchase_no;
                        $order->totalQty = $totalQty;
                    }
                }
                if(count($purchaseNos) > 0){
                    //寄送通知
                    $poId = join(',',$purchaseOrderIds);
                    $param['from'] = 'anita@icarry.me'; //寄件者
                    $param['name'] = 'Anita Tu'; //寄件者名字
                    $param['replyTo'] = 'icarryop@icarry.me'; //回信
                    $param['replyName'] = 'iCarry'; //回信
                    $param['vendor'] = 'A'.str_pad($vendorId,5,'0',STR_PAD_LEFT);
                    $param['purchaseNo'] = join(',',$purchaseNos);
                    $chk = md5($vendorId . $poId . $param['export_no']);
                    $today = date('Ymd');
                    $mailTemplate = MailTemplateDB::find(12);
                    !empty($mailTemplate) ? $param['model'] = $mailTemplate->file : $param['model'] = 'purchaseModifyMailBody';
                    !empty($mailTemplate) ? $param['subject'] = str_replace(['#^#today','#^#companyName','#^#vendorName'],[$today,$companyName,$vendorName],$mailTemplate->subject) : $param['subject'] = "【$today 採購異動通知】iCarry_$companyName($vendorName)";
                    // $param['confirmUrl'] = 'https://'.env('GATE_DOMAIN').'/vendorConfirm?vId='.$vendorId.'&poId='.$poId.'&no='.$param['export_no'].'&chk='.$chk;
                    $param['confirmUrl'] = 'https://'.env('VENDOR_DOMAIN');
                    $param['to'] = [];
                    if(env('APP_ENV') == 'local'){
                        $param['to'] = [env('TEST_MAIL_ACCOUNT')]; //收件者, 需使用陣列
                    }else{
                        $param['cc'] = ['icarryop@icarry.me']; //副本, 需使用陣列
                        if(!empty($vendor)){
                            empty($vendor->notify_email) ? $vendor->notify_email = $vendor->email : '';
                            $vendor->notify_email = str_replace(' ','',str_replace(['/',';','|',':','／','；','：','｜','　','，','、'],[',',',',',',',',',',',',',',',',',',',',','],$vendor->notify_email));
                            $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                            $mails = explode(',',$vendor->notify_email);
                            for($i=0;$i<count($mails);$i++){
                                $mail = strtolower($mails[$i]);
                                if(preg_match($pattern,$mail)){
                                    $param['to'][] = $mail; //收件者, 需使用陣列
                                };
                            }
                        }
                    }
                    //發送mail
                    if(count($param['to']) > 0){
                        $result = AdminSendEmail::dispatchNow($param); //馬上執行
                        // 紀錄匯出號碼到purchase synced log最後一筆
                        $purchaseOrders = PurchaseOrderDB::whereIn('purchase_no',$purchaseNos)->select('id')->get();
                        foreach($purchaseOrders as $order){
                            $syncedLog = PurchaseSyncedLogDB::where('purchase_order_id',$order->id)->orderBy('created_at','desc')->first();
                            $syncedLog->update(['export_no' => $param['export_no'], 'notice_time' => date('Y-m-d H:i:s')]);
                        }
                    }
                }
            }
        }
    }
}
