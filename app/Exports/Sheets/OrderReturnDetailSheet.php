<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderReturnDetailSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths,WithHeadings
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
        $orders = $this->getOrderData($this->param,'orderReturnDetail');
        if(!empty($orders)){
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    $data[] = [
                        'R'.$order->order_number,
                        $order->order_number,
                        '',
                        $order->receiver_name,
                        $order->receiver_tel,
                        $order->receiver_address,
                        $item->model->gtin13,
                        $item->product_name,
                        $item->quantity,
                        '',
                        '',
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
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('J1:L1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:L1')->getFont()->setSize(12)->setBold(true);
        $sheet->getStyle('A1:L1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        for($i=0;$i<=($this->count + 2);$i++){
            $sheet->getStyle('A'.$i)->getNumberFormat()->setFormatCode('#');
            $sheet->getStyle('A'.$i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B'.$i)->getNumberFormat()->setFormatCode('#');
            $sheet->getStyle('B'.$i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('E'.$i)->getNumberFormat()->setFormatCode('#');
            $sheet->getStyle('E'.$i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('F'.$i)->getAlignment()->setWrapText(true);
            $sheet->getStyle('G'.$i)->getNumberFormat()->setFormatCode('#');
            $sheet->getStyle('G'.$i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('H'.$i)->getAlignment()->setWrapText(true);
            $sheet->getStyle('I'.$i)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A'.$i.':L'.$i)->getBorders()->getAllBorders() //框線
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '退貨明細';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 15,
            'D' => 25,
            'E' => 20,
            'F' => 50,
            'G' => 20,
            'H' => 50,
            'I' => 10,
            'J' => 15,
            'K' => 15,
            'L' => 15,
        ];
    }

    public function headings(): array
    {
        $headings = [
            ['ICARRY','','','','','','','','','順豐','',''],
            ['退貨ERP單號','原ERP單號','原運單號','收貨人','電話','地址','貨品編號','商品名稱','數量','客戶通知日期','異常原因','退貨入倉日期'],
        ];
        return $headings;
    }
}
