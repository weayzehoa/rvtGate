<?php

namespace App\Exports\Sheets;

use App\Models\iCarryOrder as OrderDB;
use App\Models\iCarryOrderItem as OrderItemDB;
use App\Models\SellReturn as SellReturnDB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;


use DB;
use App\Traits\OrderFunctionTrait;

class OrderDetailSheet extends DefaultValueBinder implements FromCollection,WithStrictNullComparison, WithHeadings,WithStyles,WithTitle,WithCustomValueBinder
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

        $orders = $this->getOrderData($this->param,'exportOrderDetail');
        if(!empty($orders)){
            foreach($orders as $order){
                $order->is_call || $order->is_call != 0 ? $order->is_call = substr($order->is_call,0,4)."/".substr($order->is_call,4,2)."/".substr($order->is_call,6,2) : $order->is_call = '';
                $order->is_print || $order->is_print != 0 ? $order->is_print = substr($order->is_print,0,4)."/".substr($order->is_print,4,2)."/".substr($order->is_print,6,2) : $order->is_print = '';
                $totalAmount = $order->amount + $order->shipping_fee + $order->parcel_tax;
                if($order->shippingMethod['name'] == "寄送海外"){
                    $tax = 0;
                    $beforeTaxAmount = $order->amount;
                }else{
                    $beforeTaxAmount = round($order->amount / 1.05);
                    $tax = $order->amount - round($order->amount / 1.05);
                }
                $SellReturnAmount = 0;
                $SellReturns = SellReturnDB::where([['order_id',$order->id],['is_del',0]])->get();
                if(count($SellReturns) > 0){
                    foreach($SellReturns as $sellRetrun){
                        $SellReturnAmount += $sellRetrun->price + $sellRetrun->tax;
                    }
                }
                if (!empty($this->param['vendor_id'])) { //商家後台
                    $items = $order->items;
                    $totalGrossWeight = 0;
                    $quantity = 0;
                    foreach ($items as $item) {
                        if($item->vendor_id == $this->param['vendor_id']){
                            $totalGrossWeight += $item->gross_weight * $item->quantity;
                            $quantity += $item->quantity;
                        }
                    }
                    $data[] = [
                        $order->order_number,
                        $this->statusText($order->status),
                        $order->user_name,
                        $totalGrossWeight,
                        $order->pay_time,
                        $quantity,
                        $order->shipping_time,
                        $order->shippingMethod['name'],
                        $order->vendor_memo,
                    ];
                }else{
                    $data[] = [
                        $order->order_number,
                        $order->partner_order_number,
                        $this->statusText($order->status),
                        $order->receiver_address,
                        $order->receiver_name,
                        $order->receiver_tel,
                        $order->receiver_email,
                        $order->pay_time,
                        $order->book_shipping_date,
                        $order->pay_method,
                        $order->amount,
                        $order->spend_point,
                        $order->get_point,
                        $order->discount,
                        $order->shipping_fee,
                        $order->parcel_tax,
                        $totalAmount,
                        $order->amount + $order->shipping_fee + $order->parcel_tax - $order->spend_point - $order->discount,
                        $order->shippingMethod['name'],
                        $tax,
                        $beforeTaxAmount,
                        $order->receiver_key_time,
                        $order->user_memo,
                        !empty($order->is_print) ? substr($order->is_print,0,2).'-'.substr($order->is_print,2,2).'-'.substr($order->is_print,4,2) : null,
                        $order->user_nation,
                        $SellReturnAmount,
                        $order->user_id,
                    ];
                }
            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        // $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        // $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        // $sheet->getStyle('F')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('F')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        // $sheet->getStyle('H')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
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
        $sheet->getColumnDimension('P')->setAutoSize(true);
        $sheet->getColumnDimension('Q')->setAutoSize(true);
        $sheet->getColumnDimension('T')->setAutoSize(true);
        $sheet->getColumnDimension('U')->setAutoSize(true);
        $sheet->getColumnDimension('V')->setAutoSize(true);
        $sheet->getColumnDimension('W')->setAutoSize(true);
        $sheet->getColumnDimension('X')->setAutoSize(true);
        $sheet->getColumnDimension('Y')->setAutoSize(true);
        $sheet->getColumnDimension('Z')->setAutoSize(true);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '訂單資料';
    }

    public function headings(): array
    {
        if(!empty($this->param['vendor_id'])){ //商家後台
            $headings = [
                '訂單編號',
                '狀態',
                '訂購者',
                '重量(kg)',
                '付款時間',
                '數量',
                '出貨日',
                '物流方式',
                '供應商備註',
            ];
        }else{
            $headings = [
                '訂單編號',
                '渠道訂單號',
                '狀態',
                '寄送地址',
                '收件人',
                '收件人電話',
                '收件人email',
                '付款時間',
                '出貨日',
                '訂單使用之支付工具',
                '訂單金額',
                '使用購物金',
                '新產生購物金',
                '折扣',
                '運費',
                '跨境稅',
                '訂單總金額',
                '消費者付款金額',
                '物流方式',
                '營業稅',
                '稅前金額',
                '提貨時間',
                '訂單備註',
                '已列印',
                '訂購人國碼',
                '銷退折讓',
                '購買者ID',
            ];
        }
        return $headings;
    }

    //數字改文字
    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        // else return default behavior
        return parent::bindValue($cell, $value);
    }
}

