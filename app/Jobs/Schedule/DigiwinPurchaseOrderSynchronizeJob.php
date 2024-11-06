<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\ErpPURTC as ErpPURTCDB;
use App\Models\ErpPURTH as ErpPURTHDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use App\Models\Schedule as ScheduleDB;

use App\Traits\PurchaseOrderFunctionTrait;
use DB;
use Carbon\Carbon;


class DigiwinPurchaseOrderSynchronizeJob implements ShouldQueue
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
        //找出鼎新採購單
        $purchaseOrders = ErpPURTCDB::with('items')
            ->where('dbo.PURTC.TC014','!=','V') //排除已作廢
            ->whereIn('dbo.PURTC.TC001',['A332','A338']) //手Key單
            ->where('dbo.PURTC.TC004','like','%A%'); //A開頭為iCarry廠商

        //依據排程設定頻率抓取時間範圍
        $schedule = ScheduleDB::where('code','productUpdate')->first();
        $date = $this->getScheduleTime($schedule->frequency);
        $start = str_replace('-','',$date['start']);
        $end = str_replace('-','',$date['end']);
        $start = explode(' ',$start)[0];
        $end = explode(' ',$end)[0];
        $purchaseOrders = $purchaseOrders->whereBetween('dbo.PURTC.CREATE_DATE',[$start,$end]);
        $purchaseOrders = $purchaseOrders->select([
            'dbo.PURTC.CREATE_DATE',
            'dbo.PURTC.CREATE_TIME',
            'dbo.PURTC.TC001', //單別
            'dbo.PURTC.TC002', //單號
            'dbo.PURTC.TC003', //日期
            'dbo.PURTC.TC004', //廠商代號
            'dbo.PURTC.TC018', //課稅別
            'dbo.PURTC.TC019', //採購金額
            'dbo.PURTC.TC020', //稅額
            'dbo.PURTC.TC023', //數量合計
        ])->orderBy('TC002','desc')->get();
        if(count($purchaseOrders) > 0){
            //找出今日建立採購單的最後一筆單號
            $c = 1;
            $log = [];
            $tmp = PurchaseOrderDB::where('purchase_no','>=',date('ymd').'00001')->select('purchase_no')->orderBy('purchase_no','desc')->first();
            foreach ($purchaseOrders as $erpPurchaseOrder) {
                $PURTH = ErpPURTHDB::where([['dbo.PURTH.TH011', $erpPurchaseOrder->TC001],['dbo.PURTH.TH012', $erpPurchaseOrder->TC002]])->get();
                $chk = 0;
                if (count($PURTH) > 0) {
                    foreach ($PURTH as $p) {
                        $p->TH031 == 'N' ? $chk++ : '';
                    }
                }
                if ($chk > 0 || count($PURTH) == 0) { //沒有進貨單或者有其中一筆未結帳
                    $purchaseOrder = PurchaseOrderDB::where([['type',$erpPurchaseOrder->TC001],['erp_purchase_no', $erpPurchaseOrder->TC002]])->first();
                    if (!empty($purchaseOrder)) {
                        $purchaseNo = $purchaseOrder->purchase_no;
                    } else {
                        !empty($tmp) ? $purchaseNo = $tmp->purchase_no + $c : $purchaseNo = date('ymd').str_pad($c, 5, 0, STR_PAD_LEFT);
                        $c++;
                    }
                    $data = [
                        'type' => $erpPurchaseOrder->TC001,
                        'tax_type' => $erpPurchaseOrder->TC018,
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
                        $count = $chkDel = 0;
                        foreach ($erpPurchaseOrder->items as $erpItem) {
                            if ($erpItem->TD001 == $erpPurchaseOrder->TC001) {
                                $count++;
                                $erpItem->TD016 == 'y' ? $chkDel++ : '';
                                $PMtmp = ProductModelDB::where('digiwin_no', $erpItem->TD004)->first();
                                $erpPURTH = ErpPURTHDB::where([['TH011',$erpItem->TD001],['TH012',$erpItem->TD002],['TH013',$erpItem->TD003]])->first();
                                $purchaseOrderItem = PurchaseOrderItemDB::create([
                                    'purchase_no' => $purchaseOrder->purchase_no,
                                    'product_model_id' => $PMtmp->id,
                                    'quantity' => $erpItem->TD008,
                                    'purchase_price' => $erpItem->TD010,
                                    'vendor_arrival_date' => substr($erpItem->TD012, 0, 4).'-'.substr($erpItem->TD012, 4, 2).'-'.substr($erpItem->TD012, 6, 2),
                                    'direct_shipment' => $erpItem->TD007 == 'W02' ? 1 : 0,
                                    'is_del' => $erpItem->TD016 == 'y' ? 1 : 0,
                                    'gtin13' => !empty($PMtmp->gtin13) ? $PMtmp->gtin13 : $PMtmp->sku,
                                    'created_at' => substr($erpItem->CREATE_DATE, 0, 4).'-'.substr($erpItem->CREATE_DATE, 4, 2).'-'.substr($erpItem->CREATE_DATE, 6, 2).' '.$erpItem->CREATE_TIME,
                                    'updated_at' => substr($erpItem->CREATE_DATE, 0, 4).'-'.substr($erpItem->CREATE_DATE, 4, 2).'-'.substr($erpItem->CREATE_DATE, 6, 2).' '.$erpItem->CREATE_TIME,
                                ]);
                                $purchaseOrderItemSingle = PurchaseOrderItemSingleDB::create([
                                    'type' => $erpItem->TD001,
                                    'purchase_no' => $purchaseOrder->purchase_no,
                                    'poi_id' => $purchaseOrderItem->id,
                                    'poip_id' => null,
                                    'gtin13' => !empty($PMtmp->gtin13) ? $PMtmp->gtin13 : $PMtmp->sku,
                                    'erp_purchase_no' => $erpItem->TD002,
                                    'erp_purchase_sno' => $erpItem->TD003,
                                    'product_model_id' => $PMtmp->id,
                                    'purchase_price' => $erpItem->TD010,
                                    'quantity' => $erpItem->TD008,
                                    'vendor_arrival_date' => substr($erpItem->TD012, 0, 4).'-'.substr($erpItem->TD012, 4, 2).'-'.substr($erpItem->TD012, 6, 2),
                                    'is_del' => $erpItem->TD016 == 'y' ? 1 : 0,
                                    'direct_shipment' => $erpItem->TD007 == 'W02' ? 1 : 0,
                                ]);
                            }
                        }
                        $count == $chkDel ? $purchaseOrder->update(['is_del' => 1]) : '';
                        //建立同步log
                        $log[] = [
                            'admin_id' => !empty($this->param['admin_id']) ?? 0,
                            'vendor_id' => ltrim(str_replace('A','',$erpPurchaseOrder->TC004),'0'),
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

    protected function getScheduleTime($time){
        $date['start'] = null;
        $now = Carbon::now();
        $date['end'] = Carbon::now()->toDateTimeString();

        switch ($time) {
            case 'everyFiveMinutes': //每五分鐘
                $date['start'] = $now->subMinutes(5)->toDateTimeString();
                break;
            case 'everyTenMinutes': //每十分鐘
                $date['start'] = $now->subMinutes(10)->toDateTimeString();
                break;
            case 'everyFifteenMinutes': //每十五分鐘
                $date['start'] = $now->subMinutes(15)->toDateTimeString();
                break;
            case 'everyThirtyMinutes': //每三十分鐘
                $date['start'] = $now->subMinutes(30)->toDateTimeString();
                break;
            case 'hourly': //每小時
                $date['start'] = $now->subMinutes(60)->toDateTimeString();
                break;
            case 'everyThreeHours': //每三小時
                $date['start'] = $now->subMinutes(180)->toDateTimeString();
                break;
            case 'everySixHours': //每六小時
                $date['start'] = $now->subMinutes(360)->toDateTimeString();
                break;
            case 'daily': //每日午夜
                $date['start'] = $now->subDay()->toDateTimeString();
                break;
            case 'weekly': //每週六午夜
                # code...
                break;
            case 'monthly': //每月第一天的午夜
                $date['start'] = $now->subMonth()->toDateTimeString();
                break;
            case 'quarterly': //每季第一天的午夜
                $date['start'] = $now->subQuarters(1)->toDateTimeString();
                break;
            case 'yearly': //每年第一天的午夜
                $date['start'] = $now->subYears(1)->toDateTimeString();
                break;
        }
        return $date;
    }
}
