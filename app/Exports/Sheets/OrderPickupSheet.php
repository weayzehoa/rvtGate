<?php

namespace App\Exports\Sheets;

use DB;
use App\Models\iCarryOrder;
use App\Models\iCarryOrderItems;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Traits\OrderFunctionTrait;

class OrderPickupSheet implements FromCollection,ShouldAutoSize,WithStrictNullComparison,WithColumnWidths,WithStyles,WithTitle
{
    use OrderFunctionTrait;
    protected $param;
    protected $orderStart;
    protected $orderEnd;
    protected $itemStart;
    protected $itemEnd;
    protected $orderCount;

    public function __construct(array $param)
    {
        $this->param = $param;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $orders = $this->getOrderData($this->param);
        if(!empty($orders)){
            $x=0;
            $c=0;
            foreach ($orders as $order) {
                $oc = 0;
                $orderStart[$c] = $x + 1;
                $data[$x+0] = ['訂單編號:'.$order->order_number,'','','','','','iCarry 我來寄'];
                $data[$x+1] = ['訂購人:'.$order->user->name,'','','','','',''];
                $data[$x+2] = ['收件人:'.$order->receiver_name,'','','','','',''];
                $data[$x+3] = ['訂單日期:'.substr($order->created_time,0,10),'','','','','','物流:'.$order->shippingMethod->name];
                $data[$x+4] = ['貨號','商家','品名','單位','數量','撿貨','確認'];
                $i = 5; //第六筆開始 items 資料
                $totalQuantity = 0;
                $itemStart[$c] = $x + $i;
                foreach($order->items as $item){
                    $data[$x+$i] = [$item->sku,$item->vendor_name,$item->product_name,$item->unit_name,$item->quantity,'',''];
                    $i++;
                    $totalQuantity = $totalQuantity + $item->quantity;
                }
                $itemEnd[$c] = $x + $i;
                $data[$x+$i] = ['','','','總計',$totalQuantity,'',''];
                $data[$x+$i+1] = ['','','','','','','']; //空行
                $data[$x+$i+2] = ['撿貨人員:','','','包裝人員:','','',''];
                $data[$x+$i+3] = ['','','','','','',''];
                $data[$x+$i+4] = ['訂單備註:'.$order->user_memo,'','','','','',''];
                $data[$x+$i+5] = ['','','','','','',''];
                $data[$x+$i+6] = ['','','','','','',''];
                $data[$x+$i+7] = ['','','','','','',''];
                $orderEnd[$c] = count($data);
                $orderCount[$c] = $oc + $i + 7 + 1;
                $x = $orderEnd[$c];
                $c++;
            }
            $this->orderStart = $orderStart;
            $this->orderEnd = $orderEnd;
            $this->itemStart = $itemStart;
            $this->itemEnd = $itemEnd;
            $this->orderCount = $orderCount;
        }
        return collect($data);
    }

    public function title(): string
    {
        return 'iCarry 我來寄 撿貨單';
    }

    public function styles(Worksheet $sheet)
    {
        $orderStart = $this->orderStart;
        $orderEnd = $this->orderEnd;
        $itemStart = $this->itemStart;
        $itemEnd = $this->itemEnd;
        $orderCount = $this->orderCount;
        // dd($orderCount);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles

        for($i=0; $i<count($orderStart); $i++){
            $start = $orderStart[$i];
            $end = $orderEnd[$i];
            $istart = $itemStart[$i];
            $iend = $itemEnd[$i];

            $sheet->getStyle('A'.($start + 4).':G'.($start + 4))->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('00EEEEEE');

            $sheet->getRowDimension($start)->setRowHeight(40); //第一行高度40
            $sheet->getRowDimension($start + 1)->setRowHeight(30); //第二行高度40
            $sheet->getRowDimension($start + 2)->setRowHeight(30); //第三行高度40
            $sheet->getRowDimension($start + 3)->setRowHeight(30); //第四行高度40

            $sheet->getStyle('A'.$start)->getFont()->setSize(20)->setBold(true);
            $sheet->getStyle('A'.($start+1))->getFont()->setSize(16);
            $sheet->getStyle('A'.($start+2))->getFont()->setSize(16);
            $sheet->getStyle('A'.($start+3))->getFont()->setSize(16);
            $sheet->getStyle('G'.$start)->getFont()->setSize(16);
            $sheet->getStyle('G'.$start)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G'.($start+3))->getFont()->setSize(16);
            $sheet->getStyle('G'.($start+3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            //第四行開始 (跑回圈的資料)
            for($j=$istart;$j<=$iend;$j++){
                $sheet->getStyle($j)->getAlignment()->setWrapText(true); //自動換行
                $sheet->getStyle($j)->getFont()->setSize(16); //字型大小

                $sheet->getRowDimension($istart)->setRowHeight(30); //標題行高度

                $sheet->getStyle('A'.$j.':G'.$j)->getBorders()->getAllBorders() //框線
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);

                if($j >= $istart+1){
                    $sheet->getRowDimension($j)->setRowHeight(1)->setRowHeight(-1); //自動高度
                }

                // $sheet->getRowDimension('B'.$j)->setRowHeight(-1); //自動高度
                // $sheet->getStyle('B'.$j)->getAlignment()->setWrapText(true); //自動換行
                // $sheet->getRowDimension('C'.$j)->setRowHeight(-1); //自動高度
                // $sheet->getStyle('C'.$j)->getAlignment()->setWrapText(true); //自動換行

                //置中對齊
                $sheet->getStyle("D".$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E".$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F".$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G".$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }

            //迴圈跑完後下一行的資料
            for($j=($iend+1);$j<=($iend+2);$j++){
                $sheet->getRowDimension($j)->setRowHeight(30); //高度
                $sheet->getStyle('D'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A'.($j).':G'.($j))->getFont()->setSize(20); //字型大小
            }
            //剩下的資料
            for($j=($iend+2);$j<=$end;$j++){
                $sheet->getRowDimension($j)->setRowHeight(30); //高度
                $sheet->getStyle('A'.($j).':G'.($j))->getFont()->setSize(16); //字型大小
            }
        }
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 45,
            'C' => 80,
            'D' => 10,
            'E' => 10,
            'F' => 20,
            'G' => 20,
        ];
    }
}
