<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\NidinOrder as NidinOrderDB;
use App\Models\NidinTicket as NidinTicketDB;
use App\Models\SellImport as SellImportDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\iCarryOrderItem as OrderItemDB;

use App\Jobs\SellImportJob;
use App\Jobs\iCarryPurchaseOrderToACpayErpJob;
use App\Jobs\PurchaseOrderSynchronizeToDigiwinJob;

class NidinTicketWriteOffJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $importNo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($importNo)
    {
        $this->importNo = $importNo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $param['selected'] = $sellImport = [];
        $importNo = time();
        $nidinTickets = NidinTicketDB::where([['type','writeOff'],['import_no',$this->importNo],['is_chk',0]])->get();
        $tmp = $nidinTickets->groupBy('merchant_no')->all();
        foreach($tmp as $merchantNo => $tmp2){
            $tmp3 = $tmp2->groupBy('transaction_id')->all();
            foreach($tmp3 as $transactionId => $tickItems){
                $nidinOrder = NidinOrderDB::with('order','order.items')->where([['merchant_no',$merchantNo],['transaction_id',$transactionId],['is_sync',1]])->first();
                if(!empty($nidinOrder)){
                    $order = $nidinOrder->order;
                    $items = $order->items;
                    foreach($tickItems as $ticketItem){
                        $writeOffDate = explode(' ',$ticketItem->writeoff_time)[0];
                        $orderItem = $syncedOrderItemIds = $productModelId = $gtin13 = $orderItemId = null;
                        $productModel = ProductModelDB::where('vendor_product_model_id',$ticketItem->product_num)->first();
                        if(!empty($productModel)){
                            $orderItem = OrderItemDB::with('syncedOrderItem')->where('ticket_no',$ticketItem->ticket_no)->first();
                            if(!empty($orderItem)){
                                $orderItemId = $orderItem->id;
                                $gtin13 = $productModel->sku;
                                $productModelId = $productModel->id;
                                $syncedOrderItemIds = $orderItem->syncedOrderItem->id;
                                $orderItemIds = $orderItem->id;
                                $param['selected'][] = $productModelId.'_@_'.$orderItemIds.'_@_'.$syncedOrderItemIds.'_@_1';
                                $sellImport[] = [
                                    'import_no' => $importNo,
                                    'type' => 'warehouse',
                                    'order_number' => $order->order_number,
                                    'shipping_number' => $order->partner_order_number,
                                    'gtin13' => $gtin13,
                                    'purchase_no' => null,
                                    'digiwin_no' => null,
                                    'product_name' => $ticketItem->description,
                                    'quantity' => 1,
                                    'sell_date' => $writeOffDate,
                                    'stockin_time' => $writeOffDate,
                                    'status' => 0,
                                    'order_item_id' => $orderItemId,
                                    'created_at' => date('Y-m-d H:i:s'),
                                ];
                                $ticketItem->update(['is_chk' => 1]);
                            }
                        }
                    }
                }
            }
        }
        if(count($sellImport) > 0){
            if(count($sellImport) >= 100){
                $data = array_chunk($sellImport,100);
                for($i=0;$i<count($data);$i++){
                    SellImportDB::insert($data[$i]);
                }
            }else{
                SellImportDB::insert($sellImport);
            }
            $sellParam['import_no'] = $importNo;
            $sellParam['type'] = 'warehouse';
            $result = SellImportJob::dispatchNow($sellParam);

            if(count($param['selected']) > 0){
                //建立採購單並同步至鼎新
                $param['type'] = 'nidinOrder';
                $purchaseOrderIds = iCarryOrderToPurchaseOrderJob::dispatchNow($param);
                $param['id'] = $purchaseOrderIds;
                //iCarry採購單 to ACpay訂單
                iCarryPurchaseOrderToACpayErpJob::dispatchNow($param);
            }
        }
    }
}
