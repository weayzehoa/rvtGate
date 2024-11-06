<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\ErpPURTC as ErpPURTCDB;
use App\Models\ErpPURTD as ErpPURTDDB;
use App\Models\ErpPURTG as ErpPURTGDB;
use App\Models\ErpPURTH as ErpPURTHDB;
use App\Models\ErpVendor as ErpVendorDB;
use App\Models\iCarryTicket as TicketDB;
use App\Models\iCarryVendor as VendorDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;
use App\Models\PurchaseOrderItem as PurchaseOrderItemDB;
use App\Models\PurchaseSyncedLog as PurchaseSyncedLogDB;
use App\Models\PurchaseOrderItemSingle as PurchaseOrderItemSingleDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;
use App\Models\SyncedOrderItemPackage as SyncedOrderItemPackageDB;
use App\Models\PurchaseOrderChangeLog as PurchaseOrderChangeLogDB;
use App\Models\VendorShipping as VendorShippingDB;
use App\Models\VendorShippingItem as VendorShippingItemDB;

use App\Traits\PurchaseOrderFunctionTrait;
use App\Jobs\PurchaseOrderNoticeVendorModify;

use DB;

class CancelPurchaseOrderJob implements ShouldQueue
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
        $vendorTable = env('DB_ICARRY').'.'.(new VendorDB)->getTable();
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $purchaseOrderItemSingleTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemSingleDB)->getTable();
        $purchaseOrderItemTable = env('DB_DATABASE').'.'.(new PurchaseOrderItemDB)->getTable();
        $purchaseOrderTable = env('DB_DATABASE').'.'.(new PurchaseOrderDB)->getTable();
        //找出採購單資料, 包含商品資料
        $purchaseOrders = $this->getPurchaseOrderData($this->param);
        !empty($this->param['admin_id']) ? $adminId = $this->param['admin_id'] : $adminId = null;
        !empty(auth('gate')->user()->account) ? $creator = strtoupper(auth('gate')->user()->account) : $creator = 'DS';
        $createDate = date('Ymd');
        $createTime = date('H:i:s');
        $c = 1;
        foreach($purchaseOrders as $order){
            $beforStatus = $order->status;
            $erpOrder = ErpPURTCDB::where('TC001',$order->type)->find($order->erp_purchase_no);
            if(!empty($order->erp_purchase_no)){
                $erpPurchaseOrderItems = ErpPURTDDB::where([['TD001',$order->type],['TD002',$order->erp_purchase_no]])->get();
                foreach($erpPurchaseOrderItems as $item){
                    if($item->TD016 == 'N'){
                        //註記鼎新的採購商品結案碼為y 且確認碼為V
                        ErpPURTDDB::where([['TD001',$order->type],['TD002',$order->erp_purchase_no],['TD003',$item->TD003]])->update(['TD018' => 'V']);
                        //將PurchaseOrderItemSingleDB裡面相關的註記掉, 避免再做入庫, 但purchaseOrderItem 與 purchaseOrderItemPackage 並不做 is_del 標記
                        $single = PurchaseOrderItemSingleDB::join($productModelTable,$productModelTable.'.id',$purchaseOrderItemSingleTable.'.product_model_id')
                        ->where($purchaseOrderItemSingleTable.'.type',$order->type)
                        ->where($purchaseOrderItemSingleTable.'.purchase_no',$order->purchase_no)
                        ->where($productModelTable.'.digiwin_no',$item->TD004)
                        ->first();
                        $single->update(['is_del' => 1]);
                    }
                }
            }
            // 取消票券
            $tickets = TicketDB::where('purchase_no',$order->purchase_no)->get();
            !empty($tickets) ? TicketDB::where('purchase_no',$order->purchase_no)->update(['purchase_no' => null, 'purchase_date' => null]) : '';
            !empty($erpOrder) ? ErpPURTCDB::where([['TC001',$order->type],['TC002',$order->erp_purchase_no]])->update(['TC014' => 'V']) : '';
            $order->update(['status' => -1]);
            PurchaseSyncedLogDB::create([
                'admin_id' => $this->param['admin_id'],
                'vendor_id' => $order->vendor_id,
                'purchase_order_id' => $order->id,
                'quantity' => !empty($order->quantity) ? $order->quantity : 0,
                'amount' => round($order->amount),
                'tax' => round($order->tax),
                'status' => $order->status,
            ]);
            $log = PurchaseOrderChangeLogDB::create([
                'purchase_no' => $order->purchase_no,
                'admin_id' => $adminId,
                'status' => '取消',
                'memo' => '採購單取消',
            ]);

            //找出是否有廠商出貨單號並取消
            $vendorShippingNos = [];
            foreach($order->items as $item){
                if(!empty($item->vendor_shipping_no)){
                    $vendorShippingNos[] = $item->vendor_shipping_no;
                    if($item->direct_shipment == 1){
                        $vendorShippings = VendorShippingItemDB::where([['shipping_no',$item->vendor_shipping_no],['is_del',0]])->get();
                        if(count($vendorShippings) > 0){
                            foreach($vendorShippings as $vendorShipping){
                                $vendorShipping->update(['is_del' => 1]);
                            }
                        }
                    }else{
                        $vendorShipping = VendorShippingItemDB::where([['shipping_no',$item->vendor_shipping_no],['purchase_no',$item->purchase_no],['poi_id',$item->id],['is_del',0]])->first();
                        !empty($vendorShipping) ? $vendorShipping->update(['is_del' => 1]) : '';
                    }
                    $item->update(['vendor_shipping_no' => null]);
                }
            }
            if(count($vendorShippingNos) > 0){
                $vendorShippingNos = array_unique($vendorShippingNos);
                //檢查商家出貨單是否全部被取消, 若是則取消整張出貨單
                for($i=0;$i<count($vendorShippingNos);$i++){
                    $vendorShipping = VendorShippingDB::with('items')->where('shipping_no',$vendorShippingNos[$i])->first();
                    $chkVendorShipping = 0;
                    foreach($vendorShipping->items as $vendorItem){
                        $vendorItem->is_del == 1 ? $chkVendorShipping++ : '';
                    }
                    $chkVendorShipping == count($vendorShipping->items) ? $vendorShipping->update(['status' => -1, 'memo' => '已被iCarry系統取消。']) : '';
                }
            }
            //檢查是否有同步過頂新
            if($beforStatus == 1){
                //加入request
                request()->request->add([
                    'id' => [$order->id],
                    'purchaseNo' => $order->purchase_no,
                    'adminId' => $adminId,
                    'admin_id' => $adminId
                ]);
                $chkNotice = 0;
                $syncedLog = PurchaseSyncedLogDB::where('purchase_order_id',$order->id)
                ->groupBy('purchase_order_id')->having(DB::raw('count(notice_time)'), '>', 0)->first();
                //通知廠商, 先檢查是否曾經通知過廠商, 不曾通知過則不做通知, 由採購人員手動通知
                if(!empty($syncedLog)){
                    PurchaseOrderNoticeVendorModify::dispatchNow(request()->all());
                }
            }
        }
    }
}
