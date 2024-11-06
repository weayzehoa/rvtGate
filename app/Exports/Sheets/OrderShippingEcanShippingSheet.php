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

class OrderShippingEcanShippingSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths
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
                $order->receiver_address .= '取貨點　';
                $order->receiver_address = str_replace('門口','',$order->receiver_address);
                $address1 = mb_substr($order->receiver_address,0,14,'utf-8');
                $address2 = mb_substr($order->receiver_address,14,null,'utf-8');
                $airplane = str_replace('-','/',substr($order->receiver_key_time,5,5)).' '.$order->receiver_keyword;
                $getTime = substr($order->receiver_key_time,11,5);
                $memo = '班機:'.$airplane.', 預計 '.$getTime.' 取貨';
                $memo1 = mb_substr($memo,0,16,'utf-8');
                $memo2 = mb_substr($memo,16,NULL,'utf-8');

                for($j=1;$j<=4;$j++){ //空四行
                    $data[$i] = ['','',''];
                    $i++;
                }
                $data[$i] = [$address1,'',$order->order_number];
                $i++;
                $data[$i] = [$address2];
                $i++;
                $data[$i] = ['','','']; //空一行
                $i++;
                $data[$i] = [$order->receiver_name];
                $i++;
                $data[$i] = [$order->receiver_tel];
                $setSize11[] = $i;
                $i++;
                $data[$i] = ['','','']; //空一行
                $setSize6[] = $i;
                $i++;
                $data[$i] = ['　　　　　　　　新北',$memo1."\r\n".$memo2,'']; //空八個字
                $setBcolSize9[] = $i;
                $setCenter[] = $i;
                $i++;
                $data[$i] = ['土城　　　　忠承路95號10樓','','']; //空三個字
                $i++;
                $data[$i] = ['iCarry-我來寄','糕餅零食',''];
                $setAcolSize14[] = $i;
                $i++;
                $data[$i] = ['　洪’S　　0906053588','',''];
                $i++;
                $data[$i] = ['','','']; //空一行 size14
                $setSize20[] = $i;
                $i++;
                $data[$i] = ['　4645270101','',''];
                $setSize11[] = $i;
                $i++;
                $data[$i] = [$order->created_time,'',''];
                $i++;
            }
            $this->setCenter = $setCenter;
            $this->setSize6 = $setSize6;
            $this->setBcolSize9 = $setBcolSize9;
            $this->setAcolSize14 = $setAcolSize14;
            $this->setSize11 = $setSize11;
            $this->setSize20 = $setSize20;
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {

        $count = $this->count;
        $setCenter = $this->setCenter;
        $setBcolSize9 = $this->setBcolSize9;
        $setAcolSize14 = $this->setAcolSize14;
        $setSize6 = $this->setSize6;
        $setSize11 = $this->setSize11;
        $setSize20 = $this->setSize20;

        $sheet->getStyle('A')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('B')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('C')->getAlignment()->setWrapText(true); //自動換行

        for($i=1;$i<=$count;$i++){ //全部字型12
            $sheet->getStyle('A'.$i)->getFont()->setSize(12);
            $sheet->getStyle('B'.$i)->getFont()->setSize(12);
            $sheet->getStyle('C'.$i)->getFont()->setSize(12);
        }
        for($i=0;$i<count($setSize6);$i++){
            $sheet->getStyle('A'.($setSize6[$i]+1))->getFont()->setSize(11);
            $sheet->getStyle('B'.($setSize6[$i]+1))->getFont()->setSize(11);
            $sheet->getStyle('C'.($setSize6[$i]+1))->getFont()->setSize(11);
        }
        for($i=0;$i<count($setBcolSize9);$i++){
            $sheet->getStyle('B'.($setBcolSize9[$i]+1))->getFont()->setSize(9);
            $sheet->getStyle('B'.($setBcolSize9[$i]+1))->getAlignment()->setWrapText(true); //自動換行
        }
        for($i=0;$i<count($setSize11);$i++){
            $sheet->getStyle('A'.($setSize11[$i]+1))->getFont()->setSize(11);
        }
        for($i=0;$i<count($setAcolSize14);$i++){
            $sheet->getStyle('A'.($setAcolSize14[$i]+1))->getFont()->setSize(14);
        }
        for($i=0;$i<count($setSize20);$i++){
            $sheet->getStyle('A'.($setSize20[$i]+1))->getFont()->setSize(20);
            $sheet->getStyle('B'.($setSize20[$i]+1))->getFont()->setSize(20);
            $sheet->getStyle('C'.($setSize20[$i]+1))->getFont()->setSize(20);
        }
        for($i=0;$i<count($setCenter);$i++){
            $sheet->getStyle('A'.($setCenter[$i]+1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); //置中
        }
    }

    public function title(): string
    {
        return '台灣宅配通';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 36,
            'B' => 20,
            'C' => 20,
        ];
    }
}
