<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

use App\Exports\Sheets\EmptySheet;
use App\Exports\Sheets\GroupbuyOrderShipListSheet;

class GroupbuyOrderExport implements WithMultipleSheets, WithProperties
{
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
            if ($param['cate'] == 'export') {
                $param['type'] == 'ShipList' ? $sheets = [ new GroupbuyOrderShipListSheet($this->param) ] : '';

                // // 單一sheet
                // $param['type'] == 'Shopee' ? $sheets = [ new OrderShippingShopeeShippingSheet($this->param) ] : '';

                // 雙sheets
                if($param['type'] == 'OrderDetail'){
                    $sheets = [
                        // new GroupbuyOrderDetailSheet($this->param),
                        // new GroupbuyOrderDetailItemSheet($this->param),
                    ];
                }

                // //多sheets方式, 須先判斷參數並得到訂單資料, 再將訂單參數帶入
                // if($param['type'] == 'SF2'){
                //     $orders = $this->getOrderData($this->param);
                //     foreach($orders as $order){
                //         $this->param['title'] = $order->order_number;
                //         $this->param['order_id'] = $order->id;
                //         $sheets[] = new OrderShippingSF2ShippingSheet($this->param);
                //     }
                // }

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
            'title'          => 'iCarry後台管理-團購訂單資料匯出',
            'description'    => 'iCarry後台管理-團購訂單資料匯出',
            'subject'        => 'iCarry後台管理-團購訂單資料匯出',
            'keywords'       => '',
            'category'       => '',
            'manager'        => 'iCarry系統管理員',
            'company'        => 'iCarry.me 直流電通股份有限公司',
        ];
    }
}
