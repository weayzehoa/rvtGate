<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\AcOrder as AcOrderDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;
use App\Models\PurchaseOrder as PurchaseOrderDB;

use App\Jobs\SellReturnJob;
use App\Jobs\ReturnDiscountJob;

class AcOrderReturnProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $acOrderId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($acOrderId)
    {
        $this->acOrderId = $acOrderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $param['admin_id'] = 1;
        $acOrder = AcOrderDB::find($this->acOrderId);
        if(!empty($acOrder)){
            $order = OrderDB::with('itemData','sells','returns')->select(['id','order_number','digiwin_payment_id'])->find($acOrder->order_id);
            $purchaseOrder = PurchaseOrderDB::with('exportItems','returns')->find($acOrder->purchase_id);
            $digiWinPayment = DigiwinPaymentDB::where('customer_no',$order->digiwin_payment_id)->first();
            !empty($digiWinPayment) ? $customer = $digiWinPayment->customer_name : $customer = null;
            if(!empty($order)){
                $myId['id'] = $order->id;
                $param['returnQty'] = 1;
                $param['returnDate'] = $acOrder->return_date;
                $param['shippingFeeModify'] = 0;
                $param['zeroFeeModify'] = 0;
                $param['returnMemo'] = "$customer 客人退貨";
                $i=0;
                foreach($order->itemData as $item){
                    $param['items'][$i]['id'] = $item->id;
                    $param['items'][$i]['qty'] = 0;
                    $i++;
                }
                SellReturnJob::dispatch($myId,$param);
                // env('APP_ENV') == 'local' ? $result = SellReturnJob::dispatchNow($myId,$param) : SellReturnJob::dispatch($myId,$param);
                // return $result;
            }
            if(!empty($purchaseOrder) && count($purchaseOrder->returns) == 0){
                unset($param['items']);
                $param['memo'] = "$acOrder->serial_no 客人退貨";
                $param['return_date'] = $acOrder->return_date;
                $param['purchaseOrderId'] = $purchaseOrder->id;
                $param['type'] = 'return';
                $i=0;
                foreach($purchaseOrder->exportItems as $item){
                    $param['items'][$i]['id'] = $item->id;
                    $param['items'][$i]['qty'] = $item->quantity;
                    $param['items'][$i]['close'] = 0;
                    $i++;
                }
                ReturnDiscountJob::dispatch($param);
                // env('APP_ENV') == 'local' ? $result = ReturnDiscountJob::dispatchNow($param) : ReturnDiscountJob::dispatch($param);
                // return $result;
            }
        }
    }
}
