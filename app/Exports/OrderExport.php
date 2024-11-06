<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

use App\Exports\Sheets\EmptySheet;
use App\Exports\Sheets\OrderDetailSheet;
use App\Exports\Sheets\OrderDetailItemSheet;
use App\Exports\Sheets\OrderDetailNoPriceSheet;
use App\Exports\Sheets\OrderChineseInvoiceSheet;
use App\Exports\Sheets\OrderEnglishInvoiceSheet;
use App\Exports\Sheets\OrderLowPriceInvoiceSheet;
use App\Exports\Sheets\OrderPurchaseItemSheet;
use App\Exports\Sheets\OrderPurchaseBlackCatSheet;
use App\Exports\Sheets\OrderReturnDetailSheet;
use App\Exports\Sheets\OrderShippingSFTaiwanSheet;
use App\Exports\Sheets\OrderShippingEcanSheet;
use App\Exports\Sheets\OrderShippingBlackcatSheet;
use App\Exports\Sheets\OrderShippingSFSpeedTypeSheet;
use App\Exports\Sheets\OrderShippingLinexSheet;
use App\Exports\Sheets\OrderShippingGoodMajiSheet;
use App\Exports\Sheets\OrderDigiWinSheet;
use App\Exports\Sheets\OrderShippingDHLSheet;
use App\Exports\Sheets\OrderShippingDHLNEWSheet;
use App\Exports\Sheets\OrderShippingUbonexSheet;
use App\Exports\Sheets\OrderShippingSFOldSheet;
use App\Exports\Sheets\OrderShippingSFHandwriteSheet;
use App\Exports\Sheets\OrderShippingSFWarehousingSheet;
use App\Exports\Sheets\OrderShippingWarehousingSheet;
use App\Exports\Sheets\OrderShippingWarehousingShipmentSheet;
use App\Exports\Sheets\OrderShippingWarehousingPreDeliverySheet;
use App\Exports\Sheets\OrderExcelAsiamilesSheet;
use App\Exports\Sheets\OrderExcelShopcomSheet;
use App\Exports\Sheets\OrderDigiWinExcelSheet;
use App\Exports\Sheets\OrderPickupSheet;
use App\Exports\Sheets\OrderShippingSFXinZhuangSheet;
use App\Exports\Sheets\OrderShippingBlackcatShippingSheet;
use App\Exports\Sheets\OrderShippingEcanShippingSheet;
use App\Exports\Sheets\OrderShippingScooterShippingSheet;
use App\Exports\Sheets\OrderShippingLuggageShippingSheet;
use App\Exports\Sheets\OrderShippingShopeeShippingSheet;
use App\Exports\Sheets\OrderShippingSF2ShippingSheet;
use App\Exports\Sheets\OrderShippingPSEShippingSheet;
use App\Exports\Sheets\OrderGreetingCardSheet;
use App\Exports\Sheets\OrderInvoiceSheet;
use App\Exports\Sheets\OrderShippingExportSheet;
use App\Exports\Sheets\OrderShippingExpressSheet;
use App\Traits\OrderFunctionTrait;

class OrderExport implements WithMultipleSheets, WithProperties
{
    use OrderFunctionTrait;
    protected $param;

    public function __construct(array $param)
    {
        $this->param = $param;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $param = $this->param;
        $sheets = [];
        if(!empty($param['cate'])){
            if ($param['cate'] == 'shipping') {
                $param['type'] == 'SFTaiwan' ? $sheets = [ new OrderShippingSFTaiwanSheet($this->param) ] : ''; //順豐物流(台灣)
                $param['type'] == 'Ecan'? $sheets = [ new OrderShippingEcanSheet($this->param) ] : ''; //宅配通物流(機場)
                $param['type'] == 'Blackcat' ? $sheets = [ new OrderShippingBlackcatSheet($this->param) ] : ''; //黑貓物流(台灣、機場)
                $param['type'] == 'SFSpeedType' ? $sheets = [ new OrderShippingSFSpeedTypeSheet($this->param) ] : ''; //順豐速打單
                $param['type'] == 'Linex' ? $sheets = [ new OrderShippingLinexSheet($this->param) ] : ''; //順豐速打單
                $param['type'] == 'GoodMaji' ? $sheets = [ new OrderShippingGoodMajiSheet($this->param) ] : ''; //好馬吉
                $param['type'] == 'DHL' ? $sheets = [ new OrderShippingDHLSheet($this->param) ] : ''; //DHL
                $param['type'] == 'DHLnew' ? $sheets = [ new OrderShippingDHLNEWSheet($this->param) ] : ''; //DHL新版
                $param['type'] == 'SFXinZhuang' ? $sheets = [ new OrderShippingSFXinZhuangSheet($this->param) ] : ''; //順豐新莊
                $param['type'] == 'Ubonex' ? $sheets = [ new OrderShippingUbonexSheet($this->param) ] : ''; //優邦(中國)物流
                $param['type'] == 'SFOld' ? $sheets = [ new OrderShippingSFOldSheet($this->param) ] : ''; //順豐出貨單(OLD)
                $param['type'] == 'Express' ? $sheets = [ new OrderShippingExpressSheet($this->param) ] : ''; //訂單物流
                $param['type'] == 'SFHandwrite' ? $sheets = [ new OrderShippingSFHandwriteSheet($this->param) ] : ''; //順豐出貨單
                $param['type'] == 'Warehousing' ? $sheets = [ new OrderShippingWarehousingSheet($this->param) ] : ''; //入庫單匯入
                $param['type'] == 'WarehousingShipment' ? $sheets = [ new OrderShippingWarehousingShipmentSheet($this->param) ] : ''; //入庫單匯入
                $param['type'] == 'WarehousingPreDelivery' ? $sheets = [ new OrderShippingWarehousingPreDeliverySheet($this->param) ] : ''; //入庫單匯出(依預交日)
                $param['type'] == 'BlackcatShipping' ? $sheets = [ new OrderShippingBlackcatShippingSheet($this->param) ] : ''; //黑貓宅急便
                $param['type'] == 'EcanShipping' ? $sheets = [ new OrderShippingEcanShippingSheet($this->param) ] : ''; //台灣宅配通
                $param['type'] == 'Scooter' ? $sheets = [ new OrderShippingScooterShippingSheet($this->param) ] : ''; //巨邦機車快遞
                $param['type'] == 'Luggage' ? $sheets = [ new OrderShippingLuggageShippingSheet($this->param) ] : ''; //行李特工
                $param['type'] == 'Shopee' ? $sheets = [ new OrderShippingShopeeShippingSheet($this->param) ] : ''; //蝦皮便利商店

                if($param['type'] == 'SF2'){//順豐速運V2, 多sheets方式, 須先判斷參數並得到訂單資料, 再將訂單參數帶入
                    $orders = $this->getOrderData($this->param);
                    foreach($orders as $order){
                        $this->param['title'] = $order->order_number;
                        $this->param['order_id'] = $order->id;
                        $sheets[] = new OrderShippingSF2ShippingSheet($this->param);
                    }
                }

                if($param['type'] == 'PSE'){//沛羽國際, 多sheets方式, 須先判斷參數並得到訂單資料, 再將訂單參數帶入
                    $orders = $this->getOrderData($this->param);
                    foreach($orders as $order){
                        $this->param['title'] = $order->order_number;
                        $this->param['order_id'] = $order->id;
                        $sheets[] = new OrderShippingPSEShippingSheet($this->param);
                    }
                }
            }elseif ($param['cate'] == 'excel'){
                if($param['type'] == 'OrderDetail'){
                    $sheets = [
                        new OrderDetailSheet($this->param),
                        new OrderDetailItemSheet($this->param),
                    ];
                }
                if($param['type'] == 'OrderNoprice'){
                    $sheets = [
                        new OrderDetailNoPriceSheet($this->param),
                        new OrderDetailItemSheet($this->param),
                    ];
                }
                $param['type'] == 'OrderInvoice' ? $sheets = [ new OrderInvoiceSheet($this->param) ] : '';
                $param['type'] == 'InvoiceCN' ? $sheets = [ new OrderChineseInvoiceSheet($this->param) ] : '';
                $param['type'] == 'InvoiceEN' ? $sheets = [ new OrderEnglishInvoiceSheet($this->param) ] : '';
                $param['type'] == 'InvoiceLowprice' ? $sheets = [ new OrderLowPriceInvoiceSheet($this->param) ] : '';
                $param['type'] == 'Return' ? $sheets = [ new OrderReturnDetailSheet($this->param) ] : '';

                $param['type'] == 'Pickup' ? $sheets = [ new OrderPickupSheet($this->param) ] : ''; //列印撿貨單

                $param['type'] == 'Asiamiles' ? $sheets = [ new OrderExcelAsiamilesSheet($this->param) ] : ''; //Asiamiles匯出
                $param['type'] == 'Shopcom' ? $sheets = [ new OrderExcelShopcomSheet($this->param) ] : ''; //Asiamiles匯出
                $param['type'] == 'Digiwin' ? $sheets = [ new OrderDigiWinSheet($this->param) ] : ''; //鼎新匯出
                $param['type'] == 'GreetingCard' ? $sheets = [ new OrderGreetingCardSheet($this->param) ] : ''; //賀卡留言匯出

                $param['type'] == 'orderShipping' ? $sheets = [ new OrderShippingExportSheet($this->param) ] : ''; //訂單在途

                if($param['type'] == 'PurchaseCall'){ //列印採購叫貨單
                    $sheets = [
                        new OrderPurchaseItemSheet($this->param),
                        new OrderPurchaseBlackCatSheet($this->param),
                    ];
                }
            }else{
                $sheets = [new EmptySheet()];
            }

        }else{
            $sheets = [new EmptySheet()];
        }

        return $sheets;
    }

    public function properties(): array
    {
        return [
            'creator'        => 'iCarry系統管理員',
            'lastModifiedBy' => 'iCarry系統管理員',
            'title'          => 'iCarry後台管理-訂單資料匯出',
            'description'    => 'iCarry後台管理-訂單資料匯出',
            'subject'        => 'iCarry後台管理-訂單資料匯出',
            'keywords'       => '',
            'category'       => '',
            'manager'        => 'iCarry系統管理員',
            'company'        => 'iCarry.me 直流電通股份有限公司',
        ];
    }
}
