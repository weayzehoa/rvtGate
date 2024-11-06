<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryPay2go as Pay2goDB;
use App\Models\SystemSetting as SystemSettingDB;
use App\Models\iCarryGroupBuyingOrder as GroupBuyOrderDB;
use App\Models\iCarryDigiwinPayment as DigiwinPaymentDB;

use App\Traits\ACPayInvoiceFunctionTrait;
use App\Traits\EzPayInvoiceFunctionTrait;
use App\Traits\OrderFunctionTrait;
use App\Traits\GroupBuyingOrderFunctionTrait;

class AdminInvoiceJob implements ShouldQueue
{
    use ACPayInvoiceFunctionTrait, EzPayInvoiceFunctionTrait, Dispatchable, InteractsWithQueue, Queueable, SerializesModels,OrderFunctionTrait,GroupBuyingOrderFunctionTrait;

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
        $systemSetting = SystemSettingDB::first();
        $orders = $ids = [];
        //發票開立供應商參數不存在，使用系統預設
        isset($param['supplier']) ? $supplier = strtolower($param['supplier']) : $supplier = strtolower($systemSetting->invoice_supplier);
        isset($param['id']) ? is_array($param['id']) ? $this->ids = $ids = $param['id'] : $this->ids = $ids = [$param['id']] : '';
        isset($param['reason']) ? $reason = $param['reason'] : $reason = null;
        if(isset($param['model'])){
            if($param['model'] == 'ticketOrderOpenInvoice') {
                $orders = $this->getOrderData($param['model'],$ids);
            }elseif($param['model'] == 'OrderOpenInvoice' || $param['model'] == 'checkAcOrderInvoice'){
                $orders = $this->getOrderData($param['model']);
            }elseif($param['model'] == 'acOrderOpenInvoice'){
                $orders = $this->getOrderData($param,$param['model'],'acOrder');
            }elseif($param['model'] == 'nidinOrderOpenInvoice'){
                $orders = $this->getOrderData($param,$param['model'],'nidinOrder');
            }elseif($param['model'] == 'groupbuyOrders'){
                $orders = $this->getGroupBuyingOrderData($this->param,'openInvoice');
            }else{
                $orders = $this->getOrderData($param);
            }
        }else{
            if($param['type'] == 'allowance'){
                $orders = $this->getOrderData($param,'allowance');
            }else{
                $orders = $this->getOrderData($param);
            }
        }
        if(count($orders) > 0 && isset($param['type'])){
            if($supplier == 'ezpay'){
                if($param['type'] == 'create'){
                    $allowDigiwinPaymentIds = DigiwinPaymentDB::where('is_invoice',1)->get()->pluck('customer_no')->all();
                    $add = []; //填入特定代號可以暫時性開啟功能
                    $allowDigiwinPaymentIds = array_merge($allowDigiwinPaymentIds,$add);
                    foreach($orders as $order){
                        if($param['model'] == 'groupbuyOrders'){
                            $supplier == 'ezpay' ? $result = $this->ezpayCreate($order, $param) : $result = $this->acpayCreate($order, $param);
                            $result == 'success' ? $order->update(['status' => 4]) : '';
                        }else{
                            if(in_array($order->digiwin_payment_id,$allowDigiwinPaymentIds)){
                                if(!empty($order->acOrder)){
                                    if($order->acOrder->is_invoice == 0 && strstr($order->acOrder->message,'訂單建立成功，發票開立失敗。')){
                                        $supplier == 'ezpay' ? $result = $this->ezpayCreate($order, $param) : $result = $this->acpayCreate($order, $param);
                                    }else{
                                        $supplier == 'ezpay' ? $result = $this->ezpayCreate($order, $param) : $result = $this->acpayCreate($order, $param);
                                        return $result;
                                    }
                                }else{
                                    $supplier == 'ezpay' ? $result = $this->ezpayCreate($order, $param) : $result = $this->acpayCreate($order, $param);
                                    return $result;
                                }
                            }elseif($order->create_type == 'groupbuy') {
                                $groupbuyOrders = GroupBuyOrderDB::where([['status',3],['partner_order_number',$order->order_number],['is_invoice',0]])->whereNull('is_invoice_no')->whereNull('invoice_time')->get();
                                if(count($groupbuyOrders) > 0){
                                    foreach($groupbuyOrders as $groupbuyOrder){
                                        $supplier == 'ezpay' ? $result = $this->ezpayCreate($groupbuyOrder, $param) : $result = $this->acpayCreate($groupbuyOrder, $param);
                                        $result == 'success' ? $groupbuyOrder->update(['status' => 4]) : '';
                                    }
                                }
                            }
                        }
                    }
                }elseif($param['type'] == 'reopen'){
                    foreach($orders as $order){
                        if(strstr($order->is_invoice_no,'廢')){
                            $invoiceNo = str_replace('(廢)','',$order->is_invoice_no);
                            $pay2go = Pay2GoDB::where([['type','cancel'],['invoice_no',$invoiceNo]])->whereNull('allowance_no')->orderBy('create_time','desc')->first();
                            if(strstr($pay2go->canceled_order_number,'_')){
                                $oldNumber = explode('_',$pay2go->canceled_order_number);
                                $newOrderNumber = $oldNumber[0].'_'.$oldNumber[1]+1;
                            }else{
                                $newOrderNumber = $pay2go->canceled_order_number.'_1';
                            }
                            $supplier == 'ezpay' ? $result = $this->ezpayCreate($order, $param, $newOrderNumber) : $result = $this->acpayCreate($order, $param, $newOrderNumber);
                        }
                    }
                }elseif($param['type'] == 'cancel'){
                    $supplier == 'ezpay' ? $result = $this->ezpayCancel($orders,'cancel',$reason) : $result = $this->acpayCancel($orders,'cancel',$reason);
                }elseif($param['type'] == 'allowance'){
                    foreach($orders as $order){
                        $supplier == 'ezpay' ? $result = $this->ezpayAllowance($order,$param) : $result = $this->acpayAllowance($order,$param);
                        return $result;
                    }
                }
            }
        }
        return null;
    }
}
