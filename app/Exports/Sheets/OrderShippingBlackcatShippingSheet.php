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

class OrderShippingBlackcatShippingSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths
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
                $data[$i] = [$order->created_time];
                $i++;
                $data[$i] = ['　　　**　　　　　　　　'.$order->receiver_tel];
                $i++;
                $data[$i] = ['　　　'.mb_substr($order->receiver_address,0,16,'utf-8')];
                $setAutoWrap[] = $i;
                $i++;
                $data[$i] = ['　　　'.mb_substr($order->receiver_address,16,null,'utf-8')];
                $setAutoWrap[] = $i;
                $i++;
                $data[$i] = ['　　　','','']; //空一行
                $i++;
                empty($order->user_name) ? $order->user_name = $order->user_id : '';
                $data[$i] = ['　　　'.$order->receiver_name,'訂購人: '.$order->user_name."\r\n".$order->order_number];
                $i++;
                $data[$i] = ['　　　','','']; //空一行
                $i++;
                $data[$i] = ['　　　','','']; //空一行
                $i++;
                $data[$i] = ['　　　','','']; //空一行
                $i++;
                $data[$i] = ['iCarry-我來寄','',''];
                $setCenter[] = $i;
                $i++;
                $data[$i] = ['　　　','','']; //空一行 size18
                $setSize18[] = $i;
                $i++;
                $data[$i] = ['　　　','','']; //空一行 size14
                $setSize14[] = $i;
                $i++;
                $data[$i] = ['　　糕餅零食','',''];
                $i++;
            }
            $this->setCenter = $setCenter;
            $this->setAutoWrap = $setAutoWrap;
            $this->setSize14 = $setSize14;
            $this->setSize18 = $setSize18;
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('B')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('C')->getAlignment()->setWrapText(true); //自動換行

        $count = $this->count;
        $setCenter = $this->setCenter;
        $setAutoWrap = $this->setAutoWrap;
        $setSize14 = $this->setSize14;
        $setSize18 = $this->setSize18;
        for($i=1;$i<=$count;$i++){
            $sheet->getStyle('A'.$i)->getFont()->setSize(12); //字型大小
            $sheet->getStyle('B'.$i)->getFont()->setSize(12); //字型大小
            $sheet->getStyle('C'.$i)->getFont()->setSize(12); //字型大小
        }
        for($i=0;$i<count($setAutoWrap);$i++){
            $sheet->getStyle('A'.($setAutoWrap[$i]+1))->getAlignment()->setWrapText(true); //自動換行
        }
        for($i=0;$i<count($setSize14);$i++){
            $sheet->getStyle('A'.($setSize14[$i]+1))->getFont()->setSize(14); //字型大小
        }
        for($i=0;$i<count($setSize18);$i++){
            $sheet->getStyle('A'.($setSize18[$i]+1))->getFont()->setSize(18); //字型大小
        }
        for($i=0;$i<count($setCenter);$i++){
            $sheet->getStyle('A'.($setCenter[$i]+1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); //置中
        }
    }

    public function title(): string
    {
        return '黑貓宅急便';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 38,
            'B' => 38,
            'C' => 15,
        ];
    }
}
