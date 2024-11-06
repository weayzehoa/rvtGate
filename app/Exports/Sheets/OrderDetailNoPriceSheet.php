<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;
use App\Traits\OrderFunctionTrait;

class OrderDetailNoPriceSheet implements FromCollection,WithStrictNullComparison, WithHeadings,WithStyles,WithTitle
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
            foreach ($orders as $order) {
                $order->is_call || $order->is_call != 0 ? $order->is_call = substr($order->is_call, 0, 4)."/".substr($order->is_call, 4, 2)."/".substr($order->is_call, 6, 2) : $order->is_call = '';
                $order->is_print || $order->is_print != 0 ? $order->is_print = substr($order->is_print, 0, 4)."/".substr($order->is_print, 4, 2)."/".substr($order->is_print, 6, 2) : $order->is_print = '';
                $totalAmount = $order->amount + $order->shipping_fee + $order->parcel_tax;
                if ($order->shippingMethod['name'] == "寄送海外") {
                    $tax = 0;
                    $beforeTaxAmount = $order->amount;
                } else {
                    $beforeTaxAmount = round($order->amount / 1.05);
                    $tax = $order->amount - round($order->amount / 1.05);
                }
                $items = $order->items->groupBy('vendor_id');
                foreach ($items as $tmp) {
                    $totalGrossWeight = 0;
                    $quantity = 0;
                    $totalPrice = 0;
                    $vendorServiceFeePercent = 0;
                    $shippingVerdorPercent = 0;
                    foreach ($tmp as $item) {
                        $vendorName = $item->vendor_name;
                        $totalGrossWeight += $item->gross_weight * $item->quantity;
                        $quantity += $item->quantity;
                        $totalPrice += $item->price * $item->quantity;
                        $vendorServiceFeePercent = $item->vendor_service_fee_percent + $item->product_service_fee_percent;
                        $shippingVerdorPercent = $item->shipping_verdor_percent;
                    }
                    $icarryServiceFee = $totalAmount * $vendorServiceFeePercent * 0.01;
                    $vendorSupportShippingFee = $order->shipping_fee * 0.01 * $shippingVerdorPercent;
                    $data[] = [
                        $order->order_number,
                        $this->statusText($order->status),
                        $vendorName,
                        strstr($order->receiver_name, "蝦皮台灣特選店") ? '蝦皮購物' : $order->receiver_name,
                        $order->receiver_address,
                        $order->receiver_name,
                        $order->receiver_tel,
                        $order->receiver_email,
                        $totalGrossWeight,
                        $order->pay_time,
                        $quantity,
                        $order->shipping_time,
                        $order->receiver_key_time,
                        $order->user_memo,
                        $order->is_call,
                        $order->is_print,
                    ];
                }
            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('#');
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setAutoSize(true);
        $sheet->getColumnDimension('O')->setAutoSize(true);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '訂單資料-無金額';
    }

    public function headings(): array
    {
        return [
            '訂單編號',
            '狀態',
            '廠商名稱',
            '訂購者',
            '寄送地址',
            '收件人',
            '收件人電話',
            '收件人email',
            '重量(kg)',
            '付款時間',
            '數量',
            '出貨日',
            '提貨時間',
            '訂單備註',
            '已叫貨',
            '已列印',
        ];
    }
}
