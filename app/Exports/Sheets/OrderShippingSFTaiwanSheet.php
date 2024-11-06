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

class OrderShippingSFTaiwanSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths,WithHeadings
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
        if(!empty($orders)){
            $company = CompanySettingDB::first();
            foreach ($orders as $order) {
                $order->shipping_method == 2 ? $receiverAddress = $order->receiver_address.'/'.$order->receiver_keyword : $receiverAddress = $order->receiver_address;
                //寄件者備註新增提貨時間及訂單號碼
                if($order->book_shipping_date && $order->shipping_method != 4){
                    if($order->user_memo){
                        $order->user_memo = '提貨時間：'.$order->book_shipping_date.'，'.$order->user_memo;
                    }else{
                        $order->user_memo = '提貨時間：'.$order->book_shipping_date;
                    }
                }
                $order->user_memo ? $order->user_memo = $order->order_number.'/'.$order->user_memo : $order->user_memo = $order->order_number;
                $data[] = [
                    $order->order_number,
                    $company->name,
                    'iCarry-我來寄',
                    $company->service_tel,
                    $company->address,
                    '',
                    $order->receiver_name,
                    $this->phoneChange($order->receiver_tel),
                    '',
                    $receiverAddress,
                    '糕餅零食',
                    '1',
                    '',
                    $order->user_memo,
                    '寄付月結',
                    '標準快遞',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    date('Y-m-d', strtotime('+1 day', time())), //派送日期加1天
                    '09:00-12:00',
                    '',
                ];
            }
            $this->count = $count = count($data);
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('I')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('N')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('J')->getAlignment()->setWrapText(true);
        $sheet->getStyle('N')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('N')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }

    public function title(): string
    {
        return '順豐物流(台灣)';
    }

    public function headings(): array
    {
        return [
            '用戶訂單號',
            '寄件公司',
            '寄件人',
            '寄件電話',
            '寄件詳細地址',
            '收件公司',
            '收件人',
            '收件電話',
            '收件手機',
            '收件詳細地址',
            '托寄物內容',
            '托寄物數量',
            '包裹重量',
            '寄方備註',
            '運費付款方式',
            '業務類型',
            '件數',
            '代收金額',
            '保價金額',
            '標準化包裝',
            '個性化包裝',
            '簽回單',
            '自取件',
            '易碎件',
            '大閘蟹專遞',
            '電子驗收',
            '超長超重服務費',
            '是否定時派送',
            '派送日期',
            '派送時段',
            '擴展字段',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 25,
            'C' => 15,
            'D' => 16,
            'E' => 45,
            'F' => 10,
            'G' => 15,
            'H' => 20,
            'I' => 15,
            'J' => 50,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 50,
            'O' => 10,
            'P' => 10,
            'Q' => 10,
            'R' => 10,
            'S' => 10,
            'T' => 10,
            'U' => 10,
            'V' => 10,
            'W' => 10,
            'X' => 10,
            'Y' => 10,
            'Z' => 10,
            'AA' => 10,
            'AB' => 10,
            'AC' => 15,
            'AD' => 15,
            'AE' => 10,
        ];
    }
}
