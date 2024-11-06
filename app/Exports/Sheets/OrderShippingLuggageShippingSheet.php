<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\CompanySetting as CompanySettingDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingLuggageShippingSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths
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
        $orders = $this->getOrderData($this->param,'orderShipping');

        if (!empty($orders)) {
            $i = 0;
            foreach ($orders as $order) {
                $data[$i] = ['　　　　行李特工'];
                $setSize36[] = $i;
                $setMerge[] = $i;
                $i++;
                $data[$i] = [date('Y-m-d H:i:s')];
                $i++;
                $data[$i] = ['機場提貨：'.$this->getDayWithWeek($order->receiver_key_time)];
                $setSize14[] = $i;
                $i++;
                $data[$i] = ['訂單編號：'.$order->order_number];
                $i++;
                $data[$i] = ['','','']; //空一行
                $i++;
                $data[$i] = ['寄送地址','收件人','收件人電話'];
                $i++;
                $setBorder[] = $i;
                $data[$i] = [$order->receiver_address,$order->receiver_name,'+'.ltrim(str_replace(array("o","-"," ","++"),array("+","","","+"),$order->receiver_tel),'+')];
                $i++;
                $setBorder[] = $i;
                $data[$i] = ['航班：'.$order->receiver_keyword.'　　報到：'.$order->receiver_address];
                $setMerge[] = $i;
                $i++;
                $data[$i] = ['提貨時間：'.substr($order->receiver_key_time,0,16)];
                $setMerge[] = $i;
                $i++;
            }
            $this->setSize36 = $setSize36;
            $this->setSize14 = $setSize14;
            $this->setBorder = $setBorder;
            $this->setMerge = $setMerge;
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {

        $count = $this->count;
        $setSize36 = $this->setSize36;
        $setSize14 = $this->setSize14;
        $setBorder = $this->setBorder;
        $setMerge = $this->setMerge;
        $border = [ //全部框,粗線
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];
        $sheet->getStyle('A')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('B')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('C')->getAlignment()->setWrapText(true); //自動換行
        for($i=1;$i<=$count;$i++){ //全部字型12
            $sheet->getStyle('A'.$i)->getFont()->setSize(12);
            $sheet->getStyle('B'.$i)->getFont()->setSize(12);
            $sheet->getStyle('C'.$i)->getFont()->setSize(12);
        }
        for($i=0;$i<count($setSize36);$i++){
            $sheet->getStyle('A'.($setSize36[$i]+1))->getFont()->setSize(36);
            $sheet->getRowDimension($setSize36[$i]+1)->setRowHeight(46); //高度
        }
        for($i=0;$i<count($setSize14);$i++){
            $sheet->getStyle('A'.($setSize14[$i]+1))->getFont()->setSize(14);
        }
        for($i=0;$i<count($setMerge);$i++){
            $sheet->mergeCells('A'.($setMerge[$i]+1).':C'.($setMerge[$i]+1)); //合併A-C
        }
        for($i=0;$i<count($setBorder);$i++){
            $sheet->getStyle('A'.($setBorder[$i]))->applyFromArray($border);
            $sheet->getStyle('B'.($setBorder[$i]))->applyFromArray($border);
            $sheet->getStyle('C'.($setBorder[$i]))->applyFromArray($border);
        }
        $sheet->getStyle('C')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    }

    public function title(): string
    {
        return '行李特工';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 72,
            'B' => 20,
            'C' => 20,
        ];
    }
}
