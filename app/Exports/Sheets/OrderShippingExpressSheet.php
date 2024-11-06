<?php

namespace App\Exports\Sheets;

use App\Models\iCarryShippingMethod as ShippingMethodDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingExpressSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths
{
    use OrderFunctionTrait;
    protected $param;

    public function __construct(array $param)
    {
        $this->param = $param;
        $this->setBGColor = $this->setBGColor2 = [];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = [];
        $orders = $this->getOrderData($this->param);
        if(count($orders) > 0){
            foreach ($orders as $order) {
                $shippingName = ShippingMethodDB::find($order->shipping_method)->name;
                $expressWay = $expressNo = [];
                if(count($order->shippings) > 0){
                    foreach($order->shippings as $shipping){
                        $expressWay[] = $shipping->express_way;
                        $expressNo[] = $shipping->express_no;
                    }
                }
                $sellDate = [];
                foreach($order->items as $item){
                    if($item->is_del == 0){
                        if(strstr($item->sku,'BOM')){
                            foreach($item->package as $package){
                                foreach($package->sells as $sell){
                                    $sellDate[] = $sell->sell_date;
                                }
                            }
                        }else{
                            foreach($item->sells as $sell){
                                $sellDate[] = $sell->sell_date;
                            }
                        }
                    }
                }
                $sellDate = array_unique($sellDate);
                sort($sellDate);
                $data[] = [
                    $order->order_number,
                    $order->partner_order_number,
                    $order->receiver_name,
                    $order->receiver_address,
                    $order->book_shipping_date,
                    $shippingName,
                    $order->customer->customer_name,
                    count($expressWay) > 0 ? join(',',$expressWay) : null,
                    count($expressNo) > 0 ? join(',',$expressNo) : null,
                    count($sellDate) > 0 ? join(',',$sellDate) : null,
                    $order->user_memo,
                ];
            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D')->getAlignment()->setWrapText(true);
        $sheet->getStyle('G')->getAlignment()->setWrapText(true);
        $sheet->getStyle('H')->getAlignment()->setWrapText(true);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '訂單物流資料';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 25,
            'C' => 20,
            'D' => 60,
            'E' => 20,
            'F' => 20,
            'G' => 40,
            'H' => 40,
            'I' => 40,
            'J' => 40,
            'K' => 40,
        ];
    }

    public function headings(): array
    {
        return [
            [
                '訂單號碼',
                '渠道訂單號',
                '收件人',
                '收件人地址',
                '預定出貨日',
                '物流方式',
                '渠道',
                '物流商',
                '物流單號',
                '銷貨日期',
                '訂單備註'
            ],
        ];
    }
}

