<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\iCarryOrder as OrderDB;
use App\Models\ErpCOPTI as ErpCOPTIDB;
use App\Models\ErpCOPTJ as ErpCOPTJDB;
use App\Models\ErpOrderItem as ErpOrderItemDB;
use App\Models\SellReturn as SellReturnDB;
use App\Models\SellReturnItem as SellReturnItemDB;
use App\Traits\OrderFunctionTrait;

class SellAllowanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,OrderFunctionTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order,$data)
    {
        $this->order = $order;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->order;
        $data = $this->data;
        $order = $this->oneOrderItemDataTransfer($order); //轉換貨號資料
        $erpOrder = $order->erpOrder;
        $inputPrice = 0;
        $message = null;
        // //檢查輸入金額是否超過訂單金額
        // for($i=0;$i<count($data['items']);$i++){
        //     $inputPrice += $data['items'][$i]['price'];
        // }
        // if($inputPrice > ($order->amount - $order->discount - $order->point ) ){
        //     $message .= '輸入商品折讓總金額大於訂單商品總金額。';
        // }
        // if($data['shippingFee'] > $order->shipping_fee){
        //     $message .= '輸入運費折讓總金額大於訂單運費金額。';
        // }
        // if($data['parcelTax'] > $order->parcel_tax){
        //     $message .= '輸入跨境費折讓總金額大於訂單跨境費金額。';
        // }
        // if(!empty($message)){
        //     return $message;
        // }
        !empty($data['allowanceDate']) ? $allowanceDate = $data['allowanceDate'] : $allowanceDate = date('Y-m-d');
        !empty($data['allowanceMemo']) ? $memo = $data['allowanceMemo'] : $memo = '';
        $allowanceDateSix = substr(str_replace('-','',$allowanceDate),2,6);
        //已出貨才可做折讓, 且必須有填原因
        if(!empty($erpOrder) && !empty($data['allowanceMemo']) && $data['allowance'] == 1 && !empty($order) && $order->status >= 3 && count($data['items']) > 0){
            $c = 1; $totalPriceWithTax = $totalPriceWithoutTax = $totalTax = 0;
            $createDate = date('Ymd');
            $createTime = date('H:i:s');
            !empty(auth('gate')->user()->account) ? $creator = strtoupper(auth('gate')->user()->account) : $creator = 'DS';
            //找出鼎新折讓單的最後一筆單號
            $tmp = ErpCOPTIDB::where('TI002','like',"$allowanceDateSix%")->select('TI002')->orderBy('TI002','desc')->first();
            !empty($tmp) ? $TI002 = $tmp->TI002 + 1 : $TI002 = $allowanceDateSix.str_pad(1,5,0,STR_PAD_LEFT);
            //找中繼銷退折讓單的最後一筆單號
            $tmp = SellReturnDB::where('return_no','like',"$createDate%")->select('return_no')->orderBy('return_no','desc')->first();
            !empty($tmp) ? $returnNo = $tmp->return_no + 1 : $returnNo = $createDate.str_pad(1,5,0,STR_PAD_LEFT);
            //以下客戶代號 TJ001 = A242 其餘 A241
            $v = ['001','002','003','004','005','006','007','008','009','037','063','73'];
            in_array($order->digiwin_payment_id,$v) ? $TJ001 = 'A242' : $TJ001 = 'A241';
            $array1 = [
                'COMPANY' => 'iCarry',
                'CREATOR' => $creator,
                'USR_GROUP' => 'DSC',
                'CREATE_DATE' => $createDate,
                'MODIFIER' => '',
                'MODI_DATE' => '',
                'FLAG' => 1,
                'CREATE_TIME' => $createTime,
                'CREATE_AP' => 'iCarry',
                'CREATE_PRID' => 'COPI08',
                'MODI_TIME' => '',
                'MODI_AP' => '',
                'MODI_PRID' => '',
                'EF_ERPMA001' => '',
                'EF_ERPMA002' => '',
                'TJ001' => $TJ001,  //單別
                'TJ002' => $TI002,  //單號
                'TJ007' => 0,  //數量
                'TJ009' => 0,  //庫存數量
                'TJ010' => '',  //小單位
                'TJ014' => '',  //批號
                'TJ015' => '',  //銷貨單別
                'TJ016' => '',  //銷貨單號
                'TJ017' => '',  //銷貨序號
                'TJ021' => 'Y',  //確認碼
                'TJ022' => 'N',  //更新碼
                'TJ023' => '',  //備註
                'TJ024' => 'N',  //結帳碼
                'TJ025' => '',  //結帳單別
                'TJ026' => '',  //結帳單號
                'TJ027' => '',  //結帳序號
                'TJ028' => '',  //專案代號
                'TJ029' => '',  //客戶品號
                'TJ030' => 2,  //類型
                'TJ035' => 0,  //包裝數量
                'TJ036' => '',  //包裝單位
                'TJ041' => 1,  //數量類型
                'TJ042' => 0,  //贈/備品量
                'TJ043' => 0,  //贈/備品包裝量
                'TJ044' => '',  //發票號碼
                'TJ047' => 0,  //產品序號數量
                'TJ052' => '',  //銷退原因代號
                'TJ099' => 1,  //品號稅別
            ];
            $items = $data['items'];
            for($i=0;$i<count($items);$i++){
                if(!empty($items[$i]['price']) && $items[$i]['price'] > 0){ //金額大於0才做折讓
                    foreach($order->itemData as $it){
                        if($it->id == $items[$i]['id']){
                            if(strstr($it->sku,'BOM')){
                                $allowancePrice = $items[$i]['price'];
                                $itemTotalPrice = $it->quantity * $it->price; //總金額
                                $allowanceRate = $allowancePrice / $itemTotalPrice;
                                if(count($it->package) > 0){
                                    foreach($it->package as $package){
                                        $erpItems = ErpOrderItemDB::where([
                                            ['TD001',$erpOrder->TC001],
                                            ['TD002',$erpOrder->TC002],
                                            ['TD004',$package->digiwin_no],
                                            ['TD007',$it->direct_shipment == 1 ? 'W02' : 'W01'],
                                        ])->get();
                                        foreach($erpItems as $erpItem){
                                            $price = $erpItem->TD011 * $allowanceRate; //不做4捨5入, 由下面計算程式去做
                                            $calculate = $this->priceCalculate($price,$erpOrder->TC016);
                                            $totalPriceWithTax += $calculate['priceWithTax'];
                                            $totalPriceWithoutTax += $calculate['priceWithoutTax'];
                                            $totalTax += $calculate['tax'];
                                            $array2 = [
                                                'TJ003' => str_pad($c,4,'0',STR_PAD_LEFT), //序號
                                                'TJ004' => $erpItem->TD004,  //品號
                                                'TJ005' => $erpItem->TD005,  //品名
                                                'TJ006' => $erpItem->TD006,  //規格
                                                'TJ008' => $erpItem->TD010,  //單位
                                                'TJ011' => round($erpItem->TD011,0),  //單價
                                                'TJ012' => round($calculate['priceWithTax'],0),  //金額
                                                'TJ013' => $it->direct_shipment == 1 ? 'W02' : 'W01',  //退貨庫別
                                                'TJ018' => $erpItem->TD001,  //訂單單別
                                                'TJ019' => $erpItem->TD002,  //訂單單號
                                                'TJ020' => $erpItem->TD003,  //訂單序號
                                                'TJ031' => round($calculate['priceWithoutTax'],0),  //原幣未稅金額
                                                'TJ032' => round($calculate['tax'],0),  //原幣稅額
                                                'TJ033' => round($calculate['priceWithoutTax'],0),  //本幣未稅金額
                                                'TJ034' => round($calculate['tax'],0),  //本幣稅額
                                                'TJ045' => $erpOrder->TC061,  //網購訂單編號
                                            ];
                                            $erpCOPTJ[] = array_merge($array1,$array2);
                                            $sellReturnItems[] = [
                                                'order_id' => $order->id,
                                                'order_item_id' => $it->id,
                                                'order_number' => $order->order_number,
                                                'return_no' => $returnNo,
                                                'erp_return_type' => $TJ001,
                                                'erp_return_no' => $TI002,
                                                'erp_return_sno' => str_pad($c,4,'0',STR_PAD_LEFT),
                                                'order_digiwin_no' => !empty($package->order_digiwin_no) ? $package->order_digiwin_no : $package->digiwin_no,
                                                'origin_digiwin_no' => $package->digiwin_no,
                                                'quantity' => 0,
                                                'price' => $calculate['priceWithTax'],
                                                'created_at' => date('Y-m-d H:i:s'),
                                            ];
                                            $c++;
                                        }
                                    }
                                }
                            }else{
                                $price = $items[$i]['price'];
                                $erpItem = ErpOrderItemDB::where([
                                    ['TD001',$erpOrder->TC001],
                                    ['TD002',$erpOrder->TC002],
                                    ['TD004',$it->digiwin_no],
                                    ['TD007',$it->direct_shipment == 1 ? 'W02' : 'W01'],
                                ])->first();
                                $calculate = $this->priceCalculate($price,$erpOrder->TC016);
                                $totalPriceWithTax += $calculate['priceWithTax'];
                                $totalPriceWithoutTax += $calculate['priceWithoutTax'];
                                $totalTax += $calculate['tax'];
                                $array2 = [
                                    'TJ003' => str_pad($c,4,'0',STR_PAD_LEFT), //序號
                                    'TJ004' => $it->digiwin_no,  //品號
                                    'TJ005' => $it->product_name,  //品名
                                    'TJ006' => $it->serving_size,  //規格
                                    'TJ008' => $it->unit_name,  //單位
                                    'TJ011' => round($it->price,0),  //單價
                                    'TJ012' => round($calculate['priceWithTax'],0),  //金額
                                    'TJ013' => $it->direct_shipment == 1 ? 'W02' : 'W01',  //退貨庫別
                                    'TJ018' => $erpItem->TD001,  //訂單單別
                                    'TJ019' => $erpItem->TD002,  //訂單單號
                                    'TJ020' => $erpItem->TD003,  //訂單序號
                                    'TJ031' => round($calculate['priceWithoutTax'],0),  //原幣未稅金額
                                    'TJ032' => round($calculate['tax'],0),  //原幣稅額
                                    'TJ033' => round($calculate['priceWithoutTax'],0),  //本幣未稅金額
                                    'TJ034' => round($calculate['tax'],0),  //本幣稅額
                                    'TJ045' => $erpOrder->TC061,  //網購訂單編號
                                ];
                                $erpCOPTJ[] = array_merge($array1,$array2);
                                $sellReturnItems[] = [
                                    'order_id' => $order->id,
                                    'order_item_id' => $it->id,
                                    'order_number' => $order->order_number,
                                    'return_no' => $returnNo,
                                    'erp_return_type' => $TJ001,
                                    'erp_return_no' => $TI002,
                                    'erp_return_sno' => str_pad($c,4,'0',STR_PAD_LEFT),
                                    'order_digiwin_no' => !empty($it->order_digiwin_no) ? $it->order_digiwin_no : $it->digiwin_no,
                                    'origin_digiwin_no' => $it->digiwin_no,
                                    'quantity' => 0,
                                    'price' => $calculate['priceWithTax'],
                                    'created_at' => date('Y-m-d H:i:s'),
                                ];
                                $c++;
                            }
                            break;
                        }
                    }
                }
            }
            isset($data['allowance']) && $data['allowance'] == 1 && !empty($data['shippingFee']) ? $shippingFee = $data['shippingFee'] : $shippingFee = 0;
            if($shippingFee > 0){
                $erpItem = ErpOrderItemDB::where([
                    ['TD001',$erpOrder->TC001],
                    ['TD002',$erpOrder->TC002],
                    ['TD004','901001'],
                    ['TD007','W07'],
                ])->first();
                if(!empty($erpItem)){
                    $calculate = $this->priceCalculate($shippingFee,$erpOrder->TC016);
                    $totalPriceWithTax += $calculate['priceWithTax'];
                    $totalPriceWithoutTax += $calculate['priceWithoutTax'];
                    $totalTax += $calculate['tax'];
                    $array2 = [
                        'TJ003' => str_pad($c,4,'0',STR_PAD_LEFT), //序號
                        'TJ004' => $erpItem->TD004,  //品號
                        'TJ005' => $erpItem->TD005,  //品名
                        'TJ006' => '',  //規格
                        'TJ008' => $erpItem->TD010,  //單位
                        'TJ011' => round($erpItem->TD011,0),  //單價
                        'TJ012' => round($calculate['priceWithTax'],0),  //金額
                        'TJ013' => $erpItem->TD007,  //退貨庫別
                        'TJ018' => $erpItem->TD001,  //訂單單別
                        'TJ019' => $erpItem->TD002,  //訂單單號
                        'TJ020' => $erpItem->TD003,  //訂單序號
                        'TJ031' => round($calculate['priceWithoutTax'],0),  //原幣未稅金額
                        'TJ032' => round($calculate['tax'],0),  //原幣稅額
                        'TJ033' => round($calculate['priceWithoutTax'],0),  //本幣未稅金額
                        'TJ034' => round($calculate['tax'],0),  //本幣稅額
                        'TJ045' => $erpOrder->TC061,  //網購訂單編號
                    ];
                    $erpCOPTJ[] = array_merge($array1,$array2);
                    $sellReturnItems[] = [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_item_id' => null,
                        'return_no' => $returnNo,
                        'erp_return_type' => $TJ001,
                        'erp_return_no' => $TI002,
                        'erp_return_sno' => str_pad($c,4,'0',STR_PAD_LEFT),
                        'order_digiwin_no' => 901001,
                        'origin_digiwin_no' => 901001,
                        'quantity' => 0,
                        'price' => $calculate['priceWithTax'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $c++;
                }
            }
            isset($data['allowance']) && $data['allowance'] == 1 && !empty($data['parcelTax']) ? $parcelTax = $data['parcelTax'] : $parcelTax = 0;
            if($parcelTax > 0){
                $erpItem = ErpOrderItemDB::where([
                    ['TD001',$erpOrder->TC001],
                    ['TD002',$erpOrder->TC002],
                    ['TD004','901002'],
                    ['TD007','W07'],
                ])->first();
                if(!empty($erpItem)){
                    $calculate = $this->priceCalculate($parcelTax,$erpOrder->TC016);
                    $totalPriceWithTax += $calculate['priceWithTax'];
                    $totalPriceWithoutTax += $calculate['priceWithoutTax'];
                    $totalTax += $calculate['tax'];
                    $array2 = [
                        'TJ003' => str_pad($c,4,'0',STR_PAD_LEFT), //序號
                        'TJ004' => $erpItem->TD004,  //品號
                        'TJ005' => $erpItem->TD005,  //品名
                        'TJ006' => '',  //規格
                        'TJ008' => $erpItem->TD010,  //單位
                        'TJ011' => round($erpItem->TD011,0),  //單價
                        'TJ012' => round($calculate['priceWithTax'],0),  //金額
                        'TJ013' => $erpItem->TD007,  //退貨庫別
                        'TJ018' => $erpItem->TD001,  //訂單單別
                        'TJ019' => $erpItem->TD002,  //訂單單號
                        'TJ020' => $erpItem->TD003,  //訂單序號
                        'TJ031' => round($calculate['priceWithoutTax'],0),  //原幣未稅金額
                        'TJ032' => round($calculate['tax'],0),  //原幣稅額
                        'TJ033' => round($calculate['priceWithoutTax'],0),  //本幣未稅金額
                        'TJ034' => round($calculate['tax'],0),  //本幣稅額
                        'TJ045' => $erpOrder->TC061,  //網購訂單編號
                    ];
                    $erpCOPTJ[] = array_merge($array1,$array2);
                    $sellReturnItems[] = [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_item_id' => null,
                        'return_no' => $returnNo,
                        'erp_return_type' => $TJ001,
                        'erp_return_no' => $TI002,
                        'erp_return_sno' => str_pad($c,4,'0',STR_PAD_LEFT),
                        'order_digiwin_no' => 901002,
                        'origin_digiwin_no' => 901002,
                        'quantity' => 0,
                        'price' => $calculate['priceWithTax'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
            $erpCOPTI = [
                'COMPANY' => 'iCarry',
                'CREATOR' => $creator,
                'USR_GROUP' => 'DSC',
                'CREATE_DATE' => $createDate,
                'MODIFIER' => '',
                'MODI_DATE' => '',
                'FLAG' => 1,
                'CREATE_TIME' => $createTime,
                'CREATE_AP' => 'iCarry',
                'CREATE_PRID' => 'COPI08',
                'MODI_TIME' => '',
                'MODI_AP' => '',
                'MODI_PRID' => '',
                'EF_ERPMA001' => '',
                'EF_ERPMA002' => '',
                'TI001' => $TJ001,  //單別
                'TI002' => $TI002,  //單號
                'TI003' => str_replace(['-','/'],['',''],$allowanceDate), //銷退日
                'TI004' => $order->customer['customer_no'], //客戶
                'TI005' => '', //部門
                'TI006' => '', //業務員
                'TI007' => '001', //廠別
                'TI008' => 'NTD', //幣別
                'TI009' => 1, //匯率
                'TI010' => $totalPriceWithoutTax, //原幣銷退金額
                'TI011' => $totalTax, //原幣銷退稅額
                'TI012' => $order->customer['MA037'], //發票聯數
                'TI013' => $erpOrder->TC016, //課稅別
                'TI014' => '', //發票號碼
                'TI015' => $erpOrder->TC001 == 'A221' ? $order->customer['MA010'] : $order->invoice_number, //統一編號
                'TI016' => 1, //列印次數
                'TI017' => str_replace(['-','/'],['',''],$allowanceDate), //發票日期
                'TI018' => 'N', //更新碼
                'TI019' => 'N', //確認碼
                'TI020' => $memo, //備註
                'TI021' => $order->erpCustomer['customer_name'], //客戶全名
                'TI022' => '', //收款業務員
                'TI023' => '', //備註一
                'TI024' => '', //備註二
                'TI025' => 0, //折讓列印次數
                'TI026' => 1, //扣抵區分
                'TI027' => 1, //通關方式
                'TI028' => 0, //件數
                'TI029' => 0, //總數量
                'TI030' => '', //員工代號
                'TI031' => 'N', //產生分錄碼(收入)
                'TI032' => 'N', //產生分錄碼(成本)
                'TI033' => substr(str_replace(['-','/'],['',''],$allowanceDate),0,6), //申報年月
                'TI034' => str_replace(['-','/'],['',''],$allowanceDate), //單據日期
                'TI035' => '', //確認者
                'TI036' => 0.05, //營業稅率
                'TI037' => $totalPriceWithoutTax, //本幣銷退金額
                'TI038' => $totalTax, //本幣銷退稅額
                'TI039' => 'N', //簽核狀態碼
                'TI040' => 1, //交易條件
                'TI041' => 0, //總包裝數量
                'TI042' => 0, //傳送次數
                'TI043' => $order->customer['MA083'], //付款條件代號
                'TI044' => '', //客戶描述
                'TI045' => '', //作廢日期
                'TI046' => '', //作廢時間
                'TI047' => '', //作廢原因
                'TI081' => 0, //原幣應稅銷售額
                'TI089' => '', //賣方開立折讓單
                'TI090' => '', //折讓單成立日
                'TI091' => '', //連絡人EMAIL
                'TI092' => 0, //原幣免稅銷售額
                'TI093' => 0, //本幣應稅銷售額
                'TI094' => 0, //本幣免稅銷售額
            ];
            $sellReturn = [
                'type' => '折讓',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'return_no' => $returnNo,
                'erp_return_type' => $TJ001,
                'erp_return_no' => $TI002,
                'price' => $totalPriceWithoutTax,
                'tax' => $totalTax,
                'memo' => $memo,
                'return_date' => $allowanceDate,
                'return_admin_id' => auth('gate')->user()->id,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            if(count($erpCOPTJ) > 0){
                ErpCOPTJDB::insert($erpCOPTJ);
                ErpCOPTIDB::insert($erpCOPTI);
                SellReturnItemDB::insert($sellReturnItems);
                $sellReturn = SellReturnDB::create($sellReturn);
                return $sellReturn;
            }
        }
    }
}
