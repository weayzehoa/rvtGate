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
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderShippingShopeeShippingSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths
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
        $orders = $this->getOrderData($this->param);

        if (!empty($orders)) {
            $i = 0;
            $c = 0;
            $printTime = date('Y-m-d H:i:s');
            $tmp1 = $tmp2 = $tmp3 = [];
            foreach ($orders as $order) {
                if($i%2 == 0){ //左邊欄位
                    $tmp1 = ['列印時間：'.$printTime,'','','','','','',''];
                    $tmp2 = ['蝦皮訂編：'.$order->partner_order_number,'','','','','','',''];
                    $tmp3 = ['iCarry訂編：'.$order->order_number,'','','','','','',''];
                    $c = 27;
                }else{ //右邊欄位
                    $data[$c] = array_merge($tmp1,['列印時間：'.$printTime,'','','','']);
                    $c++;
                    $data[$c] = array_merge($tmp2,['蝦皮訂編：'.$order->partner_order_number,'','','','']);
                    $c++;
                    $data[$c] = array_merge($tmp3,['iCarry訂編：'.$order->order_number,'','','','']);
                    $c++;
                    for($x=0;$x<25;$x++){
                        $data[$c] = ['','','','','','','','','','','','',''];
                        $c++;
                    }
                }
                $c = $c * $i;
                $i++;
            }
            if(count($orders)%2 != 0){ //奇數筆剩餘左邊欄位資料
                $data[$c] = $tmp1;
                $c++;
                $data[$c] = $tmp2;
                $c++;
                $data[$c] = $tmp3;
                $c++;
                for($x=0;$x<25;$x++){
                    $data[$c] = ['','','','','','','','','','','','',''];
                    $c++;
                }
            }
            $this->orderCount = count($orders);
            $this->totalCount = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $orderCount = $this->orderCount;
        $totalCount = $this->totalCount;
        for($i=1;$i<=$totalCount;$i++){ //全部字型14
            $sheet->getStyle($i)->getFont()->setSize(14);
        }

        //框線
        $x=4;
        $y=28;
        $z=5;
        for($i=0;$i<ceil($orderCount/2);$i++){
            //上方框線
            $sheet->getStyle('A'.$x.':E'.$x)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            //左右框線
            for($j=0;$j<25;$j++){
                $xx = $x + $j;
                $sheet->getStyle('A'.$xx)->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
                $sheet->getStyle('E'.$xx)->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            }
            //下方框線
            $sheet->getStyle('A'.$y.':E'.$y)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);

            if($orderCount%2 != 0 && $i == (ceil($orderCount/2) - 1)){

            }else{
                $sheet->getStyle('I'.$x.':M'.$x)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
                for($j=0;$j<25;$j++){
                    $xx = $x + $j;
                    $sheet->getStyle('I'.$xx)->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
                    $sheet->getStyle('M'.$xx)->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
                }
                $sheet->getStyle('I'.$y.':M'.$y)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            }
            $x = $x+28;
            $y = $y+28;
        }
    }

    public function title(): string
    {
        return '蝦皮訂單';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 14,
            'C' => 14,
            'D' => 14,
            'E' => 14,
            'F' => 1,
            'G' => 1,
            'H' => 1,
            'I' => 14,
            'J' => 14,
            'K' => 14,
            'L' => 14,
            'M' => 14,
        ];
    }
}
