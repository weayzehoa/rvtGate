<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\CompanySetting as CompanySettingDB;
use App\Models\Country as CountryDB;
use App\Models\UserAddress as UserAddressDB;
use App\Models\ProductLang as ProductLangDB;
use App\Models\SystemSetting as SystemSettingDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingUbonexSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,ShouldAutoSize,WithHeadings
{
    use OrderFunctionTrait;
    protected $param;

    public function __construct(array $param)
    {
        $this->param = $param;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = [];
        $company = CompanySettingDB::first();
        $orders = $this->getOrderData($this->param,'orderShipping');
        if(!empty($orders)){
            foreach($orders as $order){
                $items = $order->items;
                $totalGrossWeight = 0;
                foreach ($items as $item) {
                    $totalGrossWeight += $item->gross_weight * $item->quantity;
                    $rmbRate = SystemSettingDB::first()->exchange_rate_RMB;
                    $rmbPrice = round(($item->price/$rmbRate),2);//人民幣單價(商品單價/4.7*0.8)==單價/5.875
                    $data[] = [
                        $order->order_number,
                        $order->receiver_name,
                        $order->receiver_province,
                        $order->receiver_city,
                        $order->receiver_area,
                        $order->receiver_address,
                        $order->receiver_tel,
                        $order->receiver_id_card,
                        round(($totalGrossWeight/1000),2),
                        'KG',
                        $item->product_name,
                        $item->sku,
                        $rmbPrice,
                        $item->quantity,
                        '*自送',
                        '',
                        'N',
                        'N',
                        'iCarry',
                        $company->service_tel,
                        $company->address,
                        104,
                        '',
                    ];
                }
            }
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('J')->getNumberFormat()->setFormatCode('#');
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '優邦(中國)物流';
    }

    public function headings(): array
    {
        return [
            '*客户订单号*',
            '*收货人',
            '*省',
            '*市',
            '*区',
            '*收货人详细地址',
            '*收货人手机号码',
            '收货人身份证号码',
            '*包裹重量',
            '*重量单位',
            '*物品名称',
            '物品条形码',
            '*人民币单价',
            '*物品数量',
            '*取货方式',
            '保险金额',
            '包装服务',
            '加固服务',
            '发货人姓名',
            '发货人电话',
            '发货人详细地址',
            '邮编',
            '备注',
        ];
    }
}
