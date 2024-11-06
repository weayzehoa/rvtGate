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

use DB;
use App\Traits\TicketFunctionTrait;

class TicketExportSheet implements FromCollection,WithStrictNullComparison,WithStyles,WithTitle,WithHeadings,ShouldAutoSize,WithColumnWidths,WithColumnFormatting
{
    use TicketFunctionTrait;
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
        $tickets = $this->getTicketData($this->param,'export');
        if(count($tickets) > 0){
            foreach($tickets as $ticket){
                $ticket->status == -1 ? $status = '已作廢' : '';
                $ticket->status == 0 ? $status = '未售出' : '';
                $ticket->status == 1 ? $status = '已售出' : '';
                $ticket->status == 2 ? $status = '已結算' : '';
                $ticket->status == 9 ? $status = '已核銷' : '';
                $data[] = [
                    $ticket->created_at,
                    $ticket->ticket_no,
                    $ticket->vendor_name,
                    $ticket->digiwin_no,
                    $ticket->product_name,
                    $ticket->create_type,
                    $ticket->partner_order_number,
                    $ticket->order_number,
                    $ticket->used_time,
                    $status,
                ];
            }
        }
        return collect($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('E')->getAlignment()->setWrapText(true); //自動換行
        $sheet->getStyle('B')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H')->getNumberFormat()->setFormatCode('#');
        $sheet->getStyle('H')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    }

    public function title(): string
    {
        return '票券資料';
    }
    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 20,
            'D' => 20,
            'E' => 60,
            'F' => 20,
            'G' => 25,
            'H' => 25,
            'I' => 20,
            'J' => 10,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_TEXT, //字串
            'H' => NumberFormat::FORMAT_TEXT, //字串
        ];
    }

    public function headings(): array
    {
        return [
            '開票日期',
            '票券號碼',
            '商家',
            '鼎新貨號',
            '票券商品名稱',
            '使用渠道',
            '外渠訂單號碼',
            'iCarry訂單號碼',
            '核銷日期',
            '狀態',
        ];
    }
}
