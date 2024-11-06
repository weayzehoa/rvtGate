<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ErpPURTC as ErpPURTCDB;
use App\Models\ErpPURTH as ErpPURTHDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (env('DB_MIGRATE_DIGIWIN_PURCHASE_ORDERS')) {
            //找出鼎新採購單
            $purchaseOrders = ErpPURTCDB::with('items')
                ->where('dbo.PURTC.TC001','A332') //手Key單
                ->where('dbo.PURTC.TC004','like','A%') //A開頭為iCarry廠商
                ->where('dbo.PURTC.CREATE_DATE', date('Ymd'));

            $purchaseOrders = $purchaseOrders->select([
                'dbo.PURTC.CREATE_DATE',
                'dbo.PURTC.CREATE_TIME',
                'dbo.PURTC.TC001', //單別
                'dbo.PURTC.TC002', //單號
                'dbo.PURTC.TC003', //日期
                'dbo.PURTC.TC004', //廠商代號
                'dbo.PURTC.TC019', //採購金額
                'dbo.PURTC.TC020', //稅額
                'dbo.PURTC.TC023', //數量合計
            ])->orderBy('CREATE_DATE','asc')->orderBy('CREATE_TIME','asc')->get();

            if(count($purchaseOrders) > 0){
                //找出今日建立採購單的最後一筆單號
                $c = 1;
                $log = [];
                $tmp = PurchaseOrderDB::where('purchase_no','>=',date('ymd').'00001')->select('purchase_no')->orderBy('purchase_no','desc')->first();
                foreach ($purchaseOrders as $erpPurchaseOrder) {
                    $PURTH = ErpPURTHDB::where('dbo.PURTH.TH012', $erpPurchaseOrder->TC002)->get();
                    $chk = 0;
                    if (count($PURTH) > 0) {
                        foreach ($PURTH as $p) {
                            $p->TH031 == 'N' ? $chk++ : '';
                        }
                    }
                    if ($chk > 0 || count($PURTH) == 0) { //沒有進貨單或者有其中一筆未結帳
                        $purchaseOrder = PurchaseOrderDB::where('erp_purchase_no', $erpPurchaseOrder->TC002)->first();
                        if (!empty($purchaseOrder)) {
                            $purchaseNo = $purchaseOrder->purchase_no;
                        } else {
                            !empty($tmp) ? $purchaseNo = $tmp->purchase_no + $c : $purchaseNo = date('ymd').str_pad($c, 5, 0, STR_PAD_LEFT);
                            $c++;
                        }
                        $data = [
                            'type' => $erpPurchaseOrder->TC001,
                            'purchase_no' => $purchaseNo,
                            'erp_purchase_no' => $erpPurchaseOrder->TC002,
                            'vendor_id' => ltrim(str_replace('A', '', $erpPurchaseOrder->TC004), '0'),
                            'quantity' => $erpPurchaseOrder->TC023,
                            'amount' => $erpPurchaseOrder->TC019,
                            'tax' => $erpPurchaseOrder->TC020,
                            'status' => $erpPurchaseOrder->TH031 == 'Y' ? 2 : 1,
                            'synced_time' => date('Y-m-d H:i:s'),
                            'created_at' => substr($erpPurchaseOrder->CREATE_DATE, 0, 4).'-'.substr($erpPurchaseOrder->CREATE_DATE, 4, 2).'-'.substr($erpPurchaseOrder->CREATE_DATE, 6, 2).' '.$erpPurchaseOrder->CREATE_TIME,
                            'updated_at' => substr($erpPurchaseOrder->CREATE_DATE, 0, 4).'-'.substr($erpPurchaseOrder->CREATE_DATE, 4, 2).'-'.substr($erpPurchaseOrder->CREATE_DATE, 6, 2).' '.$erpPurchaseOrder->CREATE_TIME,
                        ];
                        if (empty($purchaseOrder)) { //未存在於中繼
                            $purchaseOrder = PurchaseOrderDB::create($data);
                            $chkDel = $count = 0;
                            foreach ($erpPurchaseOrder->items as $erpItem) {
                                if ($erpItem->TD001 == 'A332') {
                                    $count++;
                                    $erpItem->TD016 == 'y' ? $chkDel++ : '';
                                    $PMtmp = ProductModelDB::where('digiwin_no', $erpItem->TD004)->first();
                                    $erpPURTH = ErpPURTHDB::where([['TH011',$erpItem->TD001],['TH012',$erpItem->TD002],['TH013',$erpItem->TD003]])->first();
                                    $purchaseOrderItem = PurchaseOrderItemDB::create([
                                        'purchase_no' => $purchaseOrder->purchase_no,
                                        'product_model_id' => $PMtmp->id,
                                        'erp_stockin_no' => !empty($erpPURTH) ? $erpPURTH->TH002 : null,
                                        'stockin_quantity' => $erpPurchaseOrder->TH031 == 'Y' ? $erpItem->TD008 : 0,
                                        'stockin_date' => $erpPurchaseOrder->TH031 == 'Y' ? substr($erpItem->TD012, 0, 4).'-'.substr($erpItem->TD012, 4, 2).'-'.substr($erpItem->TD012, 6, 2) : null,
                                        'quantity' => $erpItem->TD008,
                                        'purchase_price' => $erpItem->TD010,
                                        'vendor_arrival_date' => substr($erpItem->TD012, 0, 4).'-'.substr($erpItem->TD012, 4, 2).'-'.substr($erpItem->TD012, 6, 2),
                                        'is_del' => $erpItem->TD016 == 'y' ? 1 : 0,
                                        'created_at' => substr($erpItem->CREATE_DATE, 0, 4).'-'.substr($erpItem->CREATE_DATE, 4, 2).'-'.substr($erpItem->CREATE_DATE, 6, 2).' '.$erpItem->CREATE_TIME,
                                        'updated_at' => substr($erpItem->CREATE_DATE, 0, 4).'-'.substr($erpItem->CREATE_DATE, 4, 2).'-'.substr($erpItem->CREATE_DATE, 6, 2).' '.$erpItem->CREATE_TIME,
                                    ]);
                                    $purchaseOrderItemSingle = PurchaseOrderItemSingleDB::create([
                                        'purchase_no' => $purchaseOrder->purchase_no,
                                        'poi_id' => $purchaseOrderItem->id,
                                        'poip_id' => null,
                                        'erp_purchase_no' => $erpItem->TD002,
                                        'erp_purchase_sno' => $erpItem->TD003,
                                        'product_model_id' => $PMtmp->id,
                                        'erp_stockin_no' => !empty($erpPURTH) ? $erpPURTH->TH002 : null,
                                        'stockin_quantity' => $erpPurchaseOrder->TH031 == 'Y' ? $erpItem->TD008 : 0,
                                        'stockin_date' => $erpPurchaseOrder->TH031 == 'Y' ? substr($erpItem->TD012, 0, 4).'-'.substr($erpItem->TD012, 4, 2).'-'.substr($erpItem->TD012, 6, 2) : null,
                                        'purchase_price' => $erpItem->TD010,
                                        'quantity' => $erpItem->TD008,
                                        'vendor_arrival_date' => substr($erpItem->TD012, 0, 4).'-'.substr($erpItem->TD012, 4, 2).'-'.substr($erpItem->TD012, 6, 2),
                                        'is_del' => $erpItem->TD016 == 'y' ? 1 : 0,
                                    ]);
                                }
                            }
                            $count == $chkDel ? $purchaseOrder->update(['is_del' => 1]) : '';
                            //建立同步log
                            $log[] = [
                                'admin_id' => 0,
                                'purchase_order_id' => $purchaseOrder->id,
                                'quantity' => $purchaseOrder->quantity,
                                'amount' => $purchaseOrder->amount,
                                'tax' => $purchaseOrder->tax,
                                'status' => $purchaseOrder->status,
                                'created_at' => date('Y-m-d H:i:s'),
                            ];
                        }
                    }
                }
                !empty($log) ? PurchaseSyncedLogDB::insert($log) : '';
            }
        }
        echo "鼎新採購單已同步完成\n";
    }
}
