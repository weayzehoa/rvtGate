<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use App\Models\iCarryOrder as OrderDB;
use App\Models\OrderShipping as OrderShippingDB;
use App\Models\ErpINVLA as INVLADB;

use DB;

class OrderShippingExportSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths,WithColumnFormatting
{
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
        $shippings = $this->param['data'];
        if(count($shippings) > 0){
            foreach($shippings as $shipping){
                $expressWay = $shipping[0];
                $expressNo = $shipping[1];
                $orderNumber = $shipping[2];
                $array1 = [
                    $expressWay,
                    $expressNo,
                    $orderNumber,
                ];
                if(strtoupper($shipping[0]) == 'DHL'){
                    $orders = OrderDB::with('sells')->where('order_number',$shipping[2])->get();
                }if(strtoupper($shipping[0]) == '順豐'){
                    $orders = OrderDB::with('sells')->whereIn('id',OrderShippingDB::where('express_no',$shipping[1])->select('order_id'))->get();
                }
                if(count($orders) > 0){
                    foreach($orders as $order){
                        $orderNumber = $order->order_number;
                        $array1 = [
                            $expressWay,
                            $expressNo,
                            $orderNumber,
                        ];
                        $customer = $order->erpCustomer;
                        if(count($order->sells) > 0){
                            foreach($order->sells as $sell){
                                $shippingFee = $percalTax = $discount = $points = null;
                                $erpCosts = INVLADB::where([['LA006',$sell->erpSell->TG001],['LA007',$sell->erpSell->TG002]])
                                ->select([
                                    DB::raw("SUM(LA013) as cost"),
                                ])->groupBy('LA006','LA007')->first();
                                !empty($erpCosts) ? $cost = intval(round($erpCosts->cost,0)) : $cost = 0;
                                foreach($sell->erpSell->items as $item){
                                    if($item->TH004 == '901002'){
                                        $percalTax = $item->TH035+$item->TH036;
                                    }elseif($item->TH004 == '901001'){
                                        $shippingFee = $item->TH035+$item->TH036;
                                    }elseif($item->TH004 == '999001'){
                                        $points = $item->TH035+$item->TH036;
                                    }elseif($item->TH004 == '999000'){
                                        $discount = $item->TH035+$item->TH036;
                                    }
                                }
                                $array2 = [
                                    $order->erpOrder->TC001.'-'.$order->erpOrder->TC002,
                                    $sell->erpSell->TG001.'-'.$sell->erpSell->TG002,
                                    round($sell->erpSell->TG045,0),
                                    round($sell->erpSell->TG046,0),
                                    !empty($shippingFee) ? round($shippingFee,0) : $shippingFee,
                                    !empty($percalTax) ? round($percalTax,0) : $percalTax,
                                    !empty($discount) ? round($discount,0) : $discount,
                                    !empty($points) ? round($points,0) : $points,
                                    null,
                                    $cost,
                                    $customer->MA002,
                                ];
                                $data[] = array_merge($array1,$array2);
                            }
                        }else{
                            $array2 = [
                                $order->erpOrder->TC001.'-'.$order->erpOrder->TC002,
                                null,
                                null,
                                null,
                                null,
                                null,
                                null,
                                null,
                                null,
                                null,
                                $customer->MA002,
                            ];
                            $data[] = array_merge($array1,$array2);
                        }
                    }
                }else{
                    $array2 = [
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                    ];
                    $data[] = array_merge($array1,$array2);
                }

            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('C')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    }

    public function title(): string
    {
        return '訂單在途存貨';
    }
    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 20,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 10,
            'H' => 10,
            'I' => 10,
            'J' => 10,
            'K' => 10,
            'L' => 10,
            'M' => 10,
            'N' => 10,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT, //字串
            'C' => NumberFormat::FORMAT_TEXT, //字串
        ];
    }

    public function headings(): array
    {
        return [
            '貨運公司',
            '提單號',
            'iCarry訂單編號',
            '鼎新訂單單號',
            '鼎新銷貨單號',
            '銷售金額(未稅)',
            '稅額',
            '運費',
            '跨境稅',
            '折扣',
            '購物金',
            '總計',
            '成本',
            '客戶',
        ];
    }
}
