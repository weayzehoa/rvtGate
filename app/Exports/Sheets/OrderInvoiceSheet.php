<?php

namespace App\Exports\Sheets;

use App\Models\UserAddress as UserAddressDB;
use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\CompanySetting as CompanySettingDB;
use App\Models\Country as CountryDB;
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

class OrderInvoiceSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths
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
        $orders = $this->getOrderData($this->param,'OrderInvoiceExport');
        $i=1;
        foreach($orders as $order){
            $shippingData = null;
            if(count($order->shippings) > 0){
                foreach($order->shippings as $shipping){
                    $shippingData .= $shipping->express_way.'_'.$shipping->express_no;
                }
            }
            //invoice_title與invoice_number相反時(抬頭與統編)
            if (is_numeric($order->invoice_title) && !is_numeric($order->invoice_number)) {
                $tmp = $order->invoice_title;
                $order->invoice_title=$order->invoice_number;
                $order->invoice_number=$tmp;
            }
            $total = $order->amount + $order->shipping_fee + $order->parcel_tax - $order->discount - $order->spend_point;
            $data[] = [
                $i,
                $order->order_number,
                $shippingData,
                '零食',
                explode(' ',$order->invoice_time)[0],
                $order->is_invoice_no,
                $order->invoice_number,
                $order->invoice_type == 2 ? $order->buyer_name : $order->invoice_title,
                $total,
                $order->book_shipping_date,
            ];
            $i++;
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('I')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '發票物流明細';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 20,
            'C' => 40,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 30,
            'I' => 20,
            'J' => 20,
        ];
    }

    public function headings(): array
    {
        return [
            'No.',
            '網路訂單編號',
            '物流商_貨運單號',
            '貨物名稱',
            '開立發票時間',
            '發票號碼',
            '買受人統編',
            '買受人名稱',
            '發票金額',
            '預交日',
        ];
    }
}

