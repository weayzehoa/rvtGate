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

class PurchaseOrderNoticeVendorOneOrderJob implements ShouldQueue
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
        $vendorTable = env('DB_ICARRY') . '.' . (new VendorDB())->getTable();
        $productTable = env('DB_ICARRY') . '.' . (new ProductDB())->getTable();
        $productModelTable = env('DB_ICARRY') . '.' . (new ProductModelDB())->getTable();
        $purchaseOrderItemSingleTable = env('DB_DATABASE') . '.' . (new PurchaseOrderItemSingleDB())->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE') . '.' . (new PurchaseOrderItemDB())->getTable();
        $purchaseOrderTable = env('DB_DATABASE') . '.' . (new PurchaseOrderDB())->getTable();
        $spVendor = SpecialVendorDB::select('code')->orderBy('code', 'asc')->get()->pluck('code')->all();
        //目的目錄
        $destPath = storage_path('app/exports/');
        //檢查本地目錄是否存在，不存在則建立
        !file_exists($destPath) ? File::makeDirectory($destPath, 0755, true) : '';
        $orders = $param['orders'];
        $vendorName = $param['vendorName'];
        $vendorId = $orders[0]['vendor_id'];
        $vendor = VendorDB::find($vendorId);
        $vendorCode = 'A' . str_pad($vendorId, 5, '0', STR_PAD_LEFT);
        $companyName = $vendor->company;
        $purchaseOrderIds = $purchaseNos = [];
        foreach ($orders as $order) {
            $purchaseOrderIds[] = $order->id;
            $i = $c = 1;
            $purchasePrice = $totalQty = $totalPrice = $totalTax = $stockin = $directShipment = 0;
            $productModelIds = $directShipProducts = [];
            $orderIds = explode(',', $order->order_ids);
            $tmp = ErpPURTCDB::where('TC001', $order->type)->find($order->erp_purchase_no);
            foreach ($order->exportItems as $item) {
                if($order->type != 'A332') { //鼎新匯入的採購單已經算過稅額所以不再計算
                    //應稅內含與不計稅商家不扣掉稅
                    $tmp->TC018 != 4 && $tmp->TC018 != 1 && $tmp->TC018 != 9 ? $item->purchase_price = $item->purchase_price / 1.05 : '';
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
            $tmp = ErpVendorDB::find('A' . str_pad($order->vendor_id, 5, '0', STR_PAD_LEFT));
            $order->payCondition = $tmp->MA025;
            $order->purchasePrice = $purchasePrice;
            $purchaseNos[] = $order->purchase_no;
            $order->totalQty = $totalQty;
        }
        // 訂單採購單存PDF
        $title = '訂單採購單';
        $files[] = $fileName1 = $vendorCode . ' ' . $vendorName . '_1._' . date('Y-m-d') . '_訂單採購單_' . $param['export_no'] . '.pdf';
        $viewFile = 'gate.purchases.pdf_view_purchase_order';
        $pdf = PDF::loadView($viewFile, compact('orders', 'title'));
        $pdf = $pdf->setPaper('A4', 'landscape')->setOptions(['defaultFont' => 'TaipeiSansTCBeta-Regular']);
        $pdf->save($destPath . $fileName1);

        // 訂單入庫單存PDF
        $purchaseItems = PurchaseOrderItemSingleDB::join($productModelTable, $productModelTable . '.id', $purchaseOrderItemSingleTable . '.product_model_id')
            ->join($productTable, $productTable . '.id', $productModelTable . '.product_id')
            ->join($vendorTable, $vendorTable . '.id', $productTable . '.vendor_id')
            ->whereIn($purchaseOrderItemSingleTable . '.purchase_no', $purchaseNos)
            ->where($purchaseOrderItemSingleTable . '.is_del', 0)
            ->where($purchaseOrderItemSingleTable . '.quantity', '>', 0)
            ->where(function ($query) use ($purchaseOrderItemSingleTable) {
                $query->where($purchaseOrderItemSingleTable . '.direct_shipment', 0)
                ->orWhereNull($purchaseOrderItemSingleTable . '.direct_shipment');
            })//排除直寄
            ->select([
                $purchaseOrderItemSingleTable . '.*',
                $productModelTable . '.sku',
                $productModelTable . '.gtin13',
                $productModelTable . '.digiwin_no',
                // $productTable.'.name as product_name',
                DB::raw("CONCAT($vendorTable.name,' ',$productTable.name,'-',$productModelTable.name) as product_name"),
                $productTable . '.serving_size',
                $vendorTable . '.name as vendor_name',
                DB::raw("GROUP_CONCAT($purchaseOrderItemSingleTable.purchase_no) as purchaseNos"),
                DB::raw("SUM($purchaseOrderItemSingleTable.quantity) as purchase_quantity"),
            ])->groupBy($purchaseOrderItemSingleTable . '.vendor_arrival_date', $purchaseOrderItemSingleTable . '.product_model_id')->get();
        $purchaseItems = $purchaseItems->groupBy('vendor_arrival_date')->all();
        foreach($purchaseItems as $date => $items) {
            $viewFile = 'gate.purchases.pdf_view_purchase_stockin';
            $title = '入庫管理表';
            $files[] = $fileName2 = $vendorCode . ' ' . $items[0]['vendor_name'] . '_2._' . $date . '_' . $title . '_' . $param['export_no'] . '.pdf';
            $c = 1;
            $pNoString = null;
            foreach($items as $item) {
                $pNoString .= $item->purchaseNos . ',';
                $pNos = explode(',', rtrim($pNoString, ','));
                $item->snoForStockin = str_pad($c, 4, '0', STR_PAD_LEFT);
                $c++;
            }
            $pNos = array_unique($pNos);
            sort($pNos);
            $pdf = PDF::loadView($viewFile, compact('items', 'pNos', 'title'));
            $pdf = $pdf->setPaper('A4', 'landscape')->setOptions(['defaultFont' => 'TaipeiSansTCBeta-Regular']);
            $pdf->save($destPath . $fileName2);
        }
        // 訂單廠商直寄存excel
        $purchaseOrderItems = PurchaseOrderItemDB::join($productModelTable, $productModelTable . '.id', $purchaseOrderItemTable . '.product_model_id')
        ->join($productTable, $productTable . '.id', $productModelTable . '.product_id')
        ->whereIn($purchaseOrderItemTable . '.purchase_no', $purchaseNos)
        ->where($purchaseOrderItemTable . '.is_del', 0)
        ->where($purchaseOrderItemTable . '.quantity', '>', 0)
        ->where($purchaseOrderItemTable . '.direct_shipment', 1) //排除非直寄
        ->select([
            $purchaseOrderItemTable . '.*',
            $productTable . '.id as product_id',
            DB::raw("GROUP_CONCAT($purchaseOrderItemTable.purchase_no) as purchaseNos")
        ])->groupBy($purchaseOrderItemTable . '.vendor_arrival_date', $purchaseOrderItemTable . '.product_model_id')->get();

        $directProductModelIds = $directPurchaseNos = [];
        $directShipment = 0;
        $directShipQty = 0;
        foreach($purchaseOrderItems as $item) {
            if($item->direct_shipment == 1) {
                $directShipQty += $item->quantity;
                $directShipment++;
                $directProductModelIds[] = $item->product_model_id;
                $directPurchaseNos[] = $item->purchase_no;
            }
        }
        if($directShipment > 0 && $directShipQty > 0) {
            $orderIds = SyncedOrderItemDB::where('is_del', 0)->whereIn('purchase_no', $directPurchaseNos)->get()->pluck('order_id')->all();
            $orderIds = array_unique($orderIds);
            sort($orderIds);
            $param['orderIds'] = $orderIds;
            $param['purchaseOrderIds'] = $purchaseOrderIds;
            $param['productModelIds'] = $directProductModelIds;
            $param['title'] = '直寄訂單入庫管理表';
            $files[] = $fileName3 = $vendorCode . ' ' . $vendorName . '_3._' . date('Y-m-d') . '_' . $param['title'] . '_' . $param['export_no'] . '.xlsx';
            Excel::store(new vendorDirectShipStockinSheet($param), $fileName3, 'export');
        }

        //寄送通知
        if($param['type'] == 'Email') {
            $poId = join(',', $purchaseOrderIds);
            $param['from'] = 'anita@icarry.me'; //寄件者
            $param['name'] = 'Anita Tu'; //寄件者名字
            $param['replyTo'] = 'icarryop@icarry.me'; //回信
            $param['replyName'] = 'iCarry'; //回信
            $param['specialVendor'] = $spVendor;
            $param['vendor'] = 'A' . str_pad($order->vendor_id, 5, '0', STR_PAD_LEFT);
            $chk = md5($order->vendor_id . $poId . $param['export_no']);
            $today = date('Ymd');
            if(in_array($param['vendor'], $param['specialVendor'])) {
                $mailTemplate = MailTemplateDB::find(2);
                $param['subject'] = str_replace(['#^#today','#^#companyName','#^#vendorName'], [$today,$companyName,$vendorName], $mailTemplate->subject);
            } else {
                $mailTemplate = MailTemplateDB::find(1);
                $param['subject'] = str_replace(['#^#today','#^#companyName','#^#vendorName'], [$today,$companyName,$vendorName], $mailTemplate->subject);
            }
            $param['confirmUrl'] = 'https://' . env('GATE_DOMAIN') . '/vendorConfirm?vId=' . $order->vendor_id . '&poId=' . $poId . '&no=' . $param['export_no'] . '&chk=' . $chk;
            $param['files'] = $files;
            $param['to'] = [];
            if(env('APP_ENV') == 'local') {
                $param['to'] = [env('TEST_MAIL_ACCOUNT')]; //收件者, 需使用陣列
            } else {
                $vendor = VendorDB::find($order->vendor_id);
                if(!empty($vendor)) {
                    empty($vendor->notify_email) ? $vendor->notify_email = $vendor->email : '';
                    $vendor->notify_email = str_replace(' ', '', str_replace(['/',';','|',':','／','；','：','｜','　','，','、'], [',',',',',',',',',',',',',',',',',',',',','], $vendor->notify_email));
                    $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                    $mails = explode(',', $vendor->notify_email);
                    for($i = 0;$i < count($mails);$i++) {
                        $mail = strtolower($mails[$i]);
                        if(preg_match($pattern, $mail)) {
                            $param['to'][] = $mail; //收件者, 需使用陣列
                        };
                    }
                }
                if(in_array($param['vendor'], $param['specialVendor'])) {
                    $param['cc'] = ['icarryop@icarry.me','evanchao@icarry.me']; //副本, 需使用陣列
                } else {
                    $param['cc'] = ['icarryop@icarry.me']; //副本, 需使用陣列
                }
            }
            //發送mail
            if(count($param['to']) > 0) {
                $result = AdminSendEmail::dispatchNow($param); //馬上執行
                // 紀錄匯出號碼到purchase synced log最後一筆
                $purchaseOrders = PurchaseOrderDB::whereIn('purchase_no', $purchaseNos)->select('id')->get();
                foreach($purchaseOrders as $order) {
                    $syncedLog = PurchaseSyncedLogDB::where('purchase_order_id', $order->id)->orderBy('created_at', 'desc')->first();
                    $syncedLog->update(['export_no' => $param['export_no'], 'notice_time' => date('Y-m-d H:i:s')]);
                }
            }
            //刪除檔案
            for($xx = 0; $xx < count($files); $xx++) {
                unlink($destPath . $files[$xx]);
            }
        }
        if ($param['type'] == 'Download') {
            return $files;
        }
    }
}
