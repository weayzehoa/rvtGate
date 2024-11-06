<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use App\Models\AcOrder as AcOrderDB;
use App\Models\iCarryOrder as OrderDB;
use DB;
use Carbon\Carbon;

class AcOrderInvoiceExport implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithColumnWidths,WithHeadings
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
        $key = env('APP_AESENCRYPT_KEY');
        $param = $this->param;
        $data = [];
        $snos = $param['snos'];
        $acOrderTable = env('DB_DATABASE').'.'.(new AcOrderDB)->getTable();
        $orderTable = env('DB_ICARRY').'.'.(new OrderDB)->getTable();
        $acorders = AcOrderDB::join($orderTable,$orderTable.'.id',$acOrderTable.'.order_id')
            ->whereIn('serial_no',$snos)
            ->select([
                $acOrderTable.'.serial_no',
                $orderTable.'.invoice_time',
                $orderTable.'.is_invoice_no',
                $orderTable.'.invoice_rand',
                $orderTable.'.receiver_name',
                $orderTable.'.receiver_email',
                $orderTable.'.love_code',
                $orderTable.'.carrier_num',
                $orderTable.'.invoice_number',
                DB::raw("IF($orderTable.receiver_tel IS NULL,'',AES_DECRYPT($orderTable.receiver_tel,'$key')) as receiver_tel"),
            ])->get();
        foreach($acorders as $order){
            $data[] = [
                $order->serial_no,
                $order->invoice_time,
                $order->is_invoice_no,
                $order->invoice_rand,
                $order->receiver_name,
                $order->receiver_email,
                $order->receiver_tel,
                $order->carrier_num,
                $order->love_code,
                $order->invoice_number,
                '已開立',
                '已上傳',
            ];
        }

        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode('#'); //數字改字串
        $sheet->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('#'); //數字改字串
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('#'); //數字改字串
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('I')->getNumberFormat()->setFormatCode('#'); //數字改字串
        $sheet->getStyle('I')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        //參數參考連結
        //https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#styles
    }
    public function title(): string
    {
        return '發票資訊';
    }
    public function headings(): array
    {
        return [
            'ACpay金流序號',
            '發票日期',
            '發票號碼',
            '隨機碼',
            '姓名',
            'EMAIL',
            '電話',
            '載具號碼',
            '捐贈碼',
            '統編',
            '發票狀態',
            '上傳狀態',
        ];
    }
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 25,
            'C' => 20,
            'D' => 10,
            'E' => 15,
            'F' => 25,
            'G' => 20,
            'H' => 20,
            'I' => 20,
            'J' => 20,
            'K' => 20,
        ];
    }
    public function properties(): array
    {
        return [
            'creator'        => 'iCarry系統管理員',
            'lastModifiedBy' => 'iCarry系統管理員',
            'title'          => 'iCarry後台管理-資料匯出',
            'description'    => 'iCarry後台管理-資料匯出',
            'subject'        => 'iCarry後台管理-資料匯出',
            'keywords'       => '',
            'category'       => '',
            'manager'        => 'iCarry系統管理員',
            'company'        => 'iCarry.me 直流電通股份有限公司',
        ];
    }
}
