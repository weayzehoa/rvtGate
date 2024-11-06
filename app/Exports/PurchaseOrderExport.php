<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

use App\Exports\Sheets\EmptySheet;
use App\Exports\Sheets\PurchaseOrderStockinExportSheet;
use App\Exports\Sheets\PurchaseOrderWithSingleExportSheet;
use App\Exports\Sheets\PurchaseOrderDetailExportSheet;
use App\Exports\Sheets\PurchaseOrderChangeExportSheet;


class PurchaseOrderExport implements WithMultipleSheets, WithProperties
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
        if($param['model'] == 'purchase'){
            if($param['type'] == 'Stockin'){
                $sheets = [new PurchaseOrderStockinExportSheet($param)];
            }elseif($param['type'] == 'WithSingle'){
                $sheets = [new PurchaseOrderWithSingleExportSheet($param)];
            }elseif($param['type'] == 'OrderDetail'){
                $sheets = [new PurchaseOrderDetailExportSheet($param)];
            }elseif($param['type'] == 'OrderChange'){
                $sheets = [new PurchaseOrderChangeExportSheet($param)];
            }else{
                $sheets = [new EmptySheet()];
            }
        }

        return $sheets;
    }

    public function properties(): array
    {
        return [
            'creator'        => 'iCarry系統管理員',
            'lastModifiedBy' => 'iCarry系統管理員',
            'title'          => 'iCarry後台管理-採購單資料匯出',
            'description'    => 'iCarry後台管理-採購單資料匯出',
            'subject'        => 'iCarry後台管理-採購單資料匯出',
            'keywords'       => '',
            'category'       => '',
            'manager'        => 'iCarry系統管理員',
            'company'        => 'iCarry.me 直流電通股份有限公司',
        ];
    }
}
