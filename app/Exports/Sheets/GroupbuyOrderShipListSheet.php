<?php

namespace App\Exports\Sheets;

use DB;
use App\Models\Product as ProductDB;
use App\Models\ProductModel as ProductModelDB;
use App\Models\ProductPackageList as ProductPackageListDB;
use App\Models\Country as CountryDB;
use App\Models\Vendor as VendorDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use App\Traits\GroupBuyingOrderFunctionTrait;

class GroupbuyOrderShipListSheet implements FromCollection,ShouldAutoSize,WithStrictNullComparison, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    use GroupBuyingOrderFunctionTrait;
    protected $param;

    public function __construct(array $param)
    {
        $this->param = $param;
    }

    public function collection()
    {
        $itemCount = $i = $c = 0;
        $setLine = $setColor = $data = null;
        $orders = $this->getGroupBuyingOrderData($this->param,'export');
        foreach($orders as $order){
            if($order->status >= 2){
                $items = $order->itemData;
                foreach($items as $item){
                    if($c == $itemCount){
                        $data[$c] = [
                            $order->order_number,
                            $order->receiver_name,
                            $order->receiver_tel,
                            $order->receiver_email,
                            $item->digiwin_no,
                            $item->product_name,
                            $item->unit_name,
                            $item->quantity,
                            null,
                        ];
                    }else{
                        $data[$c] = [
                            null,
                            null,
                            null,
                            null,
                            $item->digiwin_no,
                            $item->product_name,
                            $item->unit_name,
                            $item->quantity,
                            null,
                        ];
                    }
                    $i % 2 == 0 ? $setColor[] = $c : '';
                    $c++;
                }
                $setLine[$i] = $itemCount+1;
                $itemCount += count($order->itemData);
                $i++;
            }
        }
        $this->setColor = $setColor;
        $this->setLine = $setLine;
        return collect($data);
    }


    public function styles(Worksheet $sheet)
    {
        $setColor = $this->setColor;
        $setLine = $this->setLine;
        $border1 = [ //全部框,粗線
            'borders' => [
                'bottom' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];
        $backgroundColor = [
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => 'EEEEEE'],
        ];
        if(!empty($setColor)){
            for ($i=0; $i < count($setColor) ; $i++) {
                $sheet->getStyle('A'.($setColor[$i]+2).':I'.$setColor[$i]+2)->getFill()->applyFromArray($backgroundColor);
            }
        }
        if(!empty($setLine)){
            for ($i=0; $i < count($setLine) ; $i++) {
                $sheet->getStyle('A'.($setLine[$i]).':I'.$setLine[$i])->applyFromArray($border1);
            }
        }
        $sheet->getStyle('F')->getAlignment()->setWrapText(true);
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    }

    public function title(): string
    {
        return '團主發貨清單';
    }

    public function headings(): array
    {
        return [
            '團購訂單號碼',
            '收件人姓名',
            '收件人電話',
            '收件人email',
            '商品貨號',
            '商品名稱',
            '商品單位',
            '商品數量',
            '備註',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 18,
            'D' => 25,
            'E' => 18,
            'F' => 60,
            'G' => 10,
            'H' => 10,
            'I' => 10,
        ];
    }
}
