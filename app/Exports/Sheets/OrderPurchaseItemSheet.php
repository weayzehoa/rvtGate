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

class OrderPurchaseItemSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths
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
            $orderNumber = '';
            foreach ($orders as $order) {
                $orderNumber .= $order->order_number . "\n";
                $orderIds[] = $order->id;
            }
            if(!empty($orderIds)){
                $data[0] = ['【採購叫貨單】'];
                $tmp = OrderDB::whereIn('id', $orderIds)->where('status', '>', 0)->select([
                            DB::raw('MAX(book_shipping_date) as max'),
                            DB::raw('MIN(book_shipping_date) as min'),
                        ])->first();
                $maxBookingDate = $tmp->max;
                $minBookingDate = $tmp->min;
                $minBookingDate ?  $data[1] = ['預計出貨日：'.$minBookingDate.' － '.$maxBookingDate] : $data[1] = ['預計出貨日：'];

                $items = OrderItemDB::with('order', 'model', 'model.product')->whereIn('order_id', $orderIds)
                        ->select([
                            'product_model_id',
                            'vendor_name',
                            'product_name',
                            'unit_name',
                            DB::raw('SUM(quantity) as quantity'),
                            'is_call',
                        ])->orderBy('is_call', 'desc')->groupBy('product_model_id')->get();
                $data[2] = ['商家名稱','商品名稱※','自訂規格','規格','單位','數量','單價','叫貨日期','取貨日期','入庫日期','點交與備註','Product Model ID'];
                $i = 3;
                $start = $i;
                foreach ($items as $item) {
                    if ($item->model->product->model_type == 1) {
                        $spec = '單一規格';
                    } else {
                        $spec = $item->model->name;
                    }
                    $data[$i] = [
                        $item->vendor_name,
                        $item->product_name,
                        $spec,
                        $item->model->product->serving_size,
                        $item->unit_name,
                        $item->quantity,
                        $item->model->product->price,
                        $item->is_call,
                        '',
                        '',
                        '',
                        $item->product_model_id,
                    ];
                    $i++;
                }
                $end = $i;
                $data[$i] = ['訂單編號'];
                $this->start = $start;
                $this->end = $end;
                $this->orderNumber = $orderNumber;
            }
        }else{
            $data[0] = ['【採購叫貨單】'];
            $data[1] = ['預計出貨日：'];
            $data[2] = ['商家名稱','商品名稱※','自訂規格','規格','單位','數量','單價','叫貨日期','取貨日期','入庫日期','點交與備註','Product Model ID'];
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        isset($this->start) ? $start = $this->start : $start = 0;
        isset($this->end) ? $end = $this->end : $end = 0;
        isset($this->orderNumber) ? $orderNumber = $this->orderNumber : $orderNumber = '';

        $sheet->getRowDimension(1)->setRowHeight(30); //第一行高度60
        $sheet->getRowDimension(2)->setRowHeight(30); //第一行高度60
        $sheet->getStyle(1)->getFont()->setSize(20)->setBold(true); //第一行字型大小
        $sheet->getStyle(2)->getFont()->setSize(20)->setBold(true); //第二行字型大小
        $sheet->getStyle(3)->getFont()->setSize(12)->setBold(true); //第三行字型大小

        for($i = $start+1; $i <= $end ; $i++){
            $sheet->getStyle('B'.$i)->getAlignment()->setWrapText(true);
            $sheet->getStyle('C'.$i)->getAlignment()->setWrapText(true);
            $sheet->getStyle('D'.$i)->getAlignment()->setWrapText(true);
        }
        $sheet->getCell('B'.($end+1))
        ->setValueExplicit(
            $orderNumber,
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );
        $sheet->getStyle('B'.($end+1))->getAlignment()->setWrapText(true);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '商品資料';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 60,
            'C' => 25,
            'D' => 25,
            'E' => 10,
            'F' => 10,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 20,
            'L' => 20,
        ];
    }
}
