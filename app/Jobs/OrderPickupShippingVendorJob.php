<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\iCarryOrderLog as OrderLogDB;
use App\Models\iCarryProduct as ProductDB;
use App\Models\iCarryTicket as TicketDB;
use App\Models\iCarryProductModel as ProductModelDB;
use App\Models\SyncedOrderItem as SyncedOrderItemDB;

use App\Traits\OrderFunctionTrait;

class OrderPickupShippingVendorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, OrderFunctionTrait;

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
        $type = $param['type'];
        !empty(auth('gate')->user()->id) ? $editor = auth('gate')->user()->id : $editor = 0;
        $productTable = env('DB_ICARRY').'.'.(new ProductDB)->getTable();
        $productModelTable = env('DB_ICARRY').'.'.(new ProductModelDB)->getTable();
        $orderItemTable = env('DB_ICARRY').'.'.(new OrderItemDB)->getTable();
        //找出訂單資料, 包含items資料
        if(!empty($param['order_item_id'])){
            $items = OrderItemDB::with('order','package')
                ->join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
                ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                ->where($orderItemTable.'.id',$param['order_item_id'])
                ->select([
                    $orderItemTable.'.*',
                    $productModelTable.'.sku',
                    $productTable.'.direct_shipment as directShip',
                    $productTable.'.trans_start_date',
                    $productTable.'.trans_end_date',
                    $productTable.'.category_id',
                ])->get();
        }else{
            $orderIds = $this->getOrderData($this->param,'pickupShipping');
            $items = OrderItemDB::with('order','package')
            ->join($productModelTable,$productModelTable.'.id',$orderItemTable.'.product_model_id')
            ->join($productTable,$productTable.'.id',$productModelTable.'.product_id')
            ->whereIn($orderItemTable.'.order_id',$orderIds)
            ->select([
                $orderItemTable.'.*',
                $productModelTable.'.sku',
                $productTable.'.direct_shipment as directShip',
                $productTable.'.category_id as category_id',
                $productTable.'.trans_start_date',
                $productTable.'.trans_end_date',
                $productTable.'.category_id',
            ])->get();
        }
        foreach($items as $item){
            $order = $item->order;
            if($order->status <= 2){
                $chkTrans = 0;
                if($item->is_del == 0){
                    if(strtolower($order->create_type == 'momo')){
                        foreach($order->itemData as $it){
                            if($it->is_del == 0){
                                if(strstr($it->sku,'BOM')){
                                    foreach($it->package as $package){
                                        !empty($package->trans_start_date) ? (strtotime($package->trans_start_date) <= strtotime($order->book_shipping_date) && strtotime($order->book_shipping_date) <= strtotime($package->trans_end_date) ? $chkTrans = 1 : $chkTrans = 0) : $chkTrans = 0;
                                        if($chkTrans == 0){ //有非轉倉商品則跳出
                                            break 2;
                                        }
                                    }
                                }else{
                                    !empty($it->trans_start_date) ? (strtotime($it->trans_start_date) <= strtotime($order->book_shipping_date) && strtotime($order->book_shipping_date) <= strtotime($it->trans_end_date) ? $chkTrans = 1 : $chkTrans = 0) : $chkTrans = 0;
                                    if($chkTrans == 0){ //有非轉倉商品則跳出
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                $oldShipping = $item->shipping_memo;
                if($type == '移除物流'){
                    $shippingVendor = null;
                }elseif($type == '廠商發貨' || $type == '電子郵件'){
                    $shippingVendor = $type;
                }elseif($type == '自行挑選'){
                    if(!empty($param['shippingMemo'])){
                        $shippingVendor = $param['shippingMemo'];
                    }
                }elseif($type == '依系統設定'){
                    $row['receiver_address'] = $order->receiver_address;
                    $row['shipping_method'] = $order->shipping_method;
                    $row['ship_to'] = $order->ship_to;
                    $row['create_type'] = $order->create_type;
                    $row['user_memo'] = $order->user_memo;
                    //檢查item是否為票券
                    $chkTicket = ProductModelDB::join($productTable,$productTable.'.id',$productModelTable.'.product_id')
                    ->where($productModelTable.'.digiwin_no',$item->digiwin_no)
                    ->where($productTable.'.category_id',17)->first();
                    //優先判斷是否已經有設定 => 商品是否直寄, 是的話優先設定為廠商發貨
                    if(!empty($oldShipping)){
                        $shippingVendor = $oldShipping;
                    }elseif(!empty($chkTicket) || $item->category_id == 18){
                        $shippingVendor = '電子郵件';
                    }elseif($item->directShip == 1 || $item->direct_shipment == 1){
                        $shippingVendor = '廠商發貨';
                    }elseif($chkTrans == 1){
                        $shippingVendor = 'momo-新莊';
                    }else{
                        $shippingVendor = $this->return_shipping_vendor($row);
                    }
                }
                if($oldShipping != $shippingVendor){
                    if($shippingVendor == '廠商發貨'){
                        $item->update(['direct_shipment' => 1]);
                        $syncedOrderItem = SyncedOrderItemDB::where([['order_id',$order->id],['order_item_id',$item->id]])->first();
                        !empty($syncedOrderItem) ? $syncedOrderItem->update(['not_purchase' => $item->not_purchase,'direct_shipment' => 1]) : '';
                    }else{
                        $item->update(['direct_shipment' => 0]);
                        $syncedOrderItem = SyncedOrderItemDB::where([['order_id',$order->id],['order_item_id',$item->id]])->first();
                        !empty($syncedOrderItem) ? $syncedOrderItem->update(['not_purchase' => $item->not_purchase, 'direct_shipment' => 0]) : '';
                    }
                    $item->update(['shipping_memo' => $shippingVendor]);
                    $shippingVendor == null ? $shippingVendor == '移除物流' : '';
                    $shippingVendor == null ? $log = $shippingVendor : ($oldShipping == null ? $log = $shippingVendor : $log = $oldShipping.' => '.$shippingVendor);
                    OrderLogDB::create([
                        'column_name' => 'shipping_memo',
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                        'log' => $log,
                        'editor' => $editor,
                        'create_time' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }
}
