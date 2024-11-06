<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\SystemSetting as SystemSettingDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use App\Traits\OrderFunctionTrait;

class OrderChineseInvoiceSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths
{
    use OrderFunctionTrait;
    protected $param;
    protected $orderStart;
    protected $orderEnd;
    protected $itemStart;
    protected $itemEnd;

    public function __construct(array $param)
    {
        $this->param = $param;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $USD = SystemSettingDB::select('exchange_rate_USD')->first()->exchange_rate_USD;
        $data = [];
        $orders = $this->getOrderData($this->param);
        if(!empty($orders)){
            $x=0;
            $c=0;
            foreach ($orders as $order) {
                $oc = 0;
                $orderStart[$c] = $x + 1;
                $data[$x+0] = ['INVOICE'];
                $data[$x+1] = ['']; //空行
                $data[$x+2] = ['NO.','','','Date: '.date('Y-m-d')];
                $data[$x+3] = ['INVOICE of',$order->order_number,'','','','',''];
                $data[$x+4] = ['For account and risk of Messre.','','','',''];
                $data[$x+5] = ['']; //空行
                $data[$x+6] = ['Shipped by.','Direct Current Co.,Ltd','','per',''];
                $data[$x+7] = ['sailing on or about','FROM　　　　Taiwan R.O.C　　　'.$this->checkNation($order->invoice_address)];
                $data[$x+8] = ['L/C No','','Contract No.',$order->order_number];
                $data[$x+9] = ['']; //空行
                $data[$x+10] = ['Marks & Nos.','Description of Goods','Quantity','Unit Price(USD)','Amount(USD)'];

                $i = 11; //第十二行開始 items 資料
                $totalQuantity = 0;
                $totalAmount = 0;
                $itemStart[$c] = $x + $i + 1;
                foreach($order->items as $item){
                    $data[$x+$i] = [$item->sku,$item->product_name,$item->quantity, round($item->price / $USD , 2), round($item->quantity * $item->price / $USD, 2)];
                    $i++;
                    $totalQuantity += $item->quantity;
                    $totalAmount += round($item->quantity * $item->price / $USD, 2);
                }

                if($order->shipping_fee > 0){
                    $data[$x+$i] = ['Z','運費','1', round($order->shipping_fee / $USD, 2), round($order->shipping_fee / $USD, 2)];
                    $totalAmount += round($order->shipping_fee / $USD, 2);
                    $totalQuantity += 1;
                    $i++;
                }
                if($order->spend_point > 0){
                    $data[$x+$i] = ['','使用購物金折抵','1',-round($order->spend_point / $USD, 2),-round($order->spend_point / $USD, 2)];
                    $totalAmount += -round($order->spend_point / $USD, 2);
                    $totalQuantity += 1;
                    $i++;
                }

                if($order->discount != 0){
                    if($order->discount > 0){
                        $data[$x+$i] = ['','折扣','1',-round($order->discount / $USD , 2),-round($order->discount / $USD , 2)];
                        $totalAmount += -round($order->discount / $USD , 2);
                    }else{
                        $data[$x+$i] = ['','折扣','1',round($order->discount / $USD , 2),round($order->discount / $USD , 2)];
                        $totalAmount += round($item->quantity * $item->price / $USD, 2);
                    }
                    $totalQuantity += 1;
                    $i++;
                }

                $itemEnd[$c] = $x + $i;
                $data[$x+$i+0] = ['','',$totalQuantity,'',$totalAmount];
                $data[$x+$i+1] = ['','','','',''];
                $data[$x+$i+2] = ['','','','',''];
                $data[$x+$i+3] = ['','','','直流電通股份有限公司'];
                $data[$x+$i+4] = ['','','','Direct Current Co.,Ltd'];
                $data[$x+$i+5] = ['','','','',''];
                $data[$x+$i+6] = ['','','','',''];
                $data[$x+$i+7] = ['','','','',''];
                $data[$x+$i+8] = ['','','','AUTHORIZED SIGNATURE'];
                $data[$x+$i+9] = ['','','','',''];
                $data[$x+$i+10] = ['','','','',''];
                $data[$x+$i+11] = ['','','','',''];
                $orderEnd[$c] = count($data);
                $x = $orderEnd[$c];
                $c++;
            }
        }

        $this->orderStart = $orderStart;
        $this->orderEnd = $orderEnd;
        $this->itemStart = $itemStart;
        $this->itemEnd = $itemEnd;

        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles

        $orderStart = $this->orderStart;
        $orderEnd = $this->orderEnd;
        $itemStart = $this->itemStart;
        $itemEnd = $this->itemEnd;

        $border1 = [ //全部框,粗線
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];

        $border2 = [ //全部框,細線
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];

        $border3 = [ //底部, Dash線條
            'borders' => [
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DASHED,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];
        for($i=0; $i<count($orderStart); $i++){
            $start = $orderStart[$i]; //$start = 1
            $end = $orderEnd[$i];
            $istart = $itemStart[$i];
            $iend = $itemEnd[$i];

            $sheet->getRowDimension($start)->setRowHeight(60); //第一行高度60
            $sheet->mergeCells('A'.($start).':E'.($start)); //合併第一行A-E
            $sheet->getStyle($start)->getFont()->setSize(48)->setBold(true); //第一行字型大小

            $sheet->getStyle('B'.($start + 3))->getNumberFormat()->setFormatCode('#'); //第四行B格式數字改字串
            $sheet->getStyle('D'.($start + 8))->getNumberFormat()->setFormatCode('#'); //第九行D格式數字改字串

            $sheet->mergeCells('A'.($start + 1).':E'.($start + 1)); //合併第二行A-E
            $sheet->mergeCells('D'.($start + 2).':E'.($start + 2)); //合併第三行D-E
            $sheet->mergeCells('A'.($start + 5).':E'.($start + 5)); //合併第六行A-E
            $sheet->mergeCells('B'.($start + 7).':E'.($start + 7)); //合併第八行B-E
            $sheet->mergeCells('D'.($start + 8).':E'.($start + 8)); //合併第九行D-E
            $sheet->mergeCells('A'.($start + 9).':E'.($start + 9)); //合併第十行A-E


            for($cc = 1; $cc <= $end; $cc++){
                $sheet->getRowDimension($start + $cc)->setRowHeight(35); //第二行開始高度35到此訂單結束
                $sheet->getStyle($start + $cc)->getFont()->setSize(20)->setBold(true); //第二行開始字型大小
            }

            //欄位對齊
            $sheet->getStyle('A'.$start)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B'.($start+3))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B'.($start+7))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D'.($start+2))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('D'.($start+8))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('G'.($start+8))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            //欄位底線
            $sheet->getStyle('B'.($start+2))->applyFromArray($border3);
            $sheet->getStyle('E'.($start+2))->applyFromArray($border3);
            $sheet->getStyle('B'.($start+3))->applyFromArray($border3);
            $sheet->getStyle('B'.($start+6))->applyFromArray($border3);
            $sheet->getStyle('D'.($start+6).':E'.($start+6))->applyFromArray($border3);
            $sheet->getStyle('B'.($start+7).':E'.($start+7))->applyFromArray($border3);
            $sheet->getStyle('B'.($start+8))->applyFromArray($border3);
            $sheet->getStyle('D'.($start+8).':E'.($start+8))->applyFromArray($border3);
            $sheet->getStyle('B'.($start+9))->applyFromArray($border3);
            $sheet->getStyle('D'.($start+9))->applyFromArray($border3);

            $sheet->getStyle('A'.($start+10).':E'.($start+10))->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('00EEEEEE');
            $sheet->getStyle('A'.($start+10).':E'.($start+10))->getBorders()->getAllBorders() //框線
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getStyle("C".($start+10).':E'.($start+10))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // //第十二行開始 (跑items的資料)
            for($j=$istart;$j<=$iend;$j++){
                $sheet->getStyle($j)->getAlignment()->setWrapText(true); //自動換行
                $sheet->getRowDimension($j)->setRowHeight(1)->setRowHeight(-1); //自動高度
                $sheet->getStyle('A'.$j.':E'.$j)->getBorders()->getAllBorders() //框線
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                //置中對齊
                $sheet->getStyle("C".$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D".$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E".$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }

            // 迴圈跑完後下一行的總計資料
            $sheet->getStyle('C'.($iend+1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E'.($iend+1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // 簽名檔
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setWorksheet($sheet);
            $drawing->setName('SIGNATURE');
            $drawing->setDescription('AUTHORIZED SIGNATURE');
            $drawing->setPath(public_path('/img/max_sign.jpg'));
            $drawing->setHeight(120);
            $drawing->setOffsetX(50);
            $drawing->setCoordinates('D'.($end-6));

            //剩下的資料
            for($j=($iend+2);$j<=$end;$j++){
                $sheet->mergeCells('D'.$j.':E'.$j); //合併D-E
                $sheet->getStyle('D'.$j)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
        }
    }

    public function title(): string
    {
        return '訂單資料-中文發票';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 50,
            'B' => 100,
            'C' => 30,
            'D' => 40,
            'E' => 30,
        ];
    }
}
