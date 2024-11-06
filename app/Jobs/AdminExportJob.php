<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Exports\OrderExport;
use App\Exports\GroupbuyOrderExport;
use App\Exports\OrderShippingExport;
use App\Exports\TicketExport;
use App\Exports\ReferFriendExport;
use App\Exports\PurchaseOrderExport;
use Maatwebsite\Excel\Facades\Excel;
use Session;
use File;
use PDF;
use App\Models\ExportCenter as ExportCenterDB;
use App\Traits\OrderFunctionTrait;

class AdminExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,OrderFunctionTrait;

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
        // dd(file_exists($destPath));
        !file_exists($destPath) ? File::makeDirectory($destPath, 0755, true) : '';
        //檢驗是否為pdf類型
        if($param['cate'] == 'pdf'){
            $title = $param['name'];
            if($param['model'] == 'orders'){
                $orders = $this->getOrderData($this->param);
                if($param['type'] == 'Pickup'){
                    $viewFile = 'gate.orders.pdf_view_pickup';
                }
                if($param['type'] == 'Screenshot'){
                    $viewFile = 'gate.orders.pdf_view_order_export';
                    //計算及資料變更
                    foreach ($orders as $order) {
                        if($order->shipping_memo){
                            if( json_decode( $order->shipping_memo , true ) ){
                                $shippingMemo = collect(json_decode($order->shipping_memo));
                                foreach($shippingMemo as $sm){
                                    $order->shipping_memo_vendor = $sm->express_way;
                                }
                            }else{
                                $order->shipping_memo_vendor = $order->shipping_memo;
                            }
                        }
                        $totalQty = 0;
                        $totalPrice = 0;
                        $totalWeight = 0;
                        foreach ($order->items as $item) {
                            $totalQty = $totalQty + $item->quantity;
                            $totalPrice = $totalPrice + $item->price * $item->quantity;
                            $totalWeight = $totalWeight + $item->gross_weight * $item->quantity;
                        }
                        $order->totalQty = $totalQty;
                        $order->totalPrice = $totalPrice;
                        $order->totalWeight = $totalWeight;
                        //金流支付 (付款金額 = 商品費+跨境稅+運費-使用購物金-折扣)
                        $order->total_pay = $order->amount + $order->shipping_fee + $order->parcel_tax - $order->spend_point - $order->discount;
                    }
                }
                $pdf = PDF::loadView($viewFile, compact('orders', 'title'));
                $pdf = $pdf->setPaper('A4', 'landscape')->setOptions(['defaultFont' => 'TaipeiSansTCBeta-Regular']);
                if(!empty($param['store']) && $param['store'] == true){
                    $pdf->save($destPath.$param['filename']);
                }else{
                    return $pdf->download($param['filename']);
                }
            }
        }else{
            if($param['model'] == 'orders'){
                if (!empty($param['store']) && $param['store'] == true) {
                    Excel::store(new OrderExport($param), $param['filename'], 'export');
                }else{
                    return Excel::download(new OrderExport($param), $param['filename']);
                }
            }elseif($param['model'] == 'purchase'){
                if (!empty($param['store']) && $param['store'] == true) {
                    Excel::store(new PurchaseOrderExport($param), $param['filename'], 'export');
                }else{
                    return Excel::download(new PurchaseOrderExport($param), $param['filename']);
                }
            }elseif($param['model'] == 'tickets'){
                if (!empty($param['store']) && $param['store'] == true) {
                    Excel::store(new TicketExport($param), $param['filename'], 'export');
                }else{
                    return Excel::download(new TicketExport($param), $param['filename']);
                }
            }elseif($param['model'] == 'referFriend'){
                if (!empty($param['store']) && $param['store'] == true) {
                    Excel::store(new ReferFriendExport($param), $param['filename'], 'export');
                }else{
                    return Excel::download(new ReferFriendExport($param), $param['filename']);
                }
            }elseif($param['model'] == 'groupbuyOrders'){
                if (!empty($param['store']) && $param['store'] == true) {
                    Excel::store(new GroupbuyOrderExport($param), $param['filename'], 'export');
                }else{
                    return Excel::download(new GroupbuyOrderExport($param), $param['filename']);
                }
            }else{
                return null;
            }
        }
        //儲存紀錄到匯出中心資料表
        $param['end_time'] = date('Y-m-d H:i:s');
        $param['condition'] = json_encode($param,true);
        $param['cate'] = $param['model'];
        $log = ExportCenterDB::create($param);
        return 'success';
    }
}
